<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\Columns\Dimension;
use Piwik\Common;
use Piwik\DataAccess\LogAggregator;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;
use Piwik\Plugins\DataExplorer\Catalog\ScopeCompatibility;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;

/**
 * Compiles a visual definition into one aggregate query per metric scope and row level.
 *
 * Every table, column, join and metric expression comes from the catalogue, looked up by id.
 * Filter values only ever reach the database as bound parameters.
 */
class VisualQueryBuilder
{
    public const OPERATORS = ['equals', 'not_equals', 'contains', 'starts_with', 'regex', 'gt', 'lt'];

    public const TABLE_BY_SCOPE = [
        'visit' => 'log_visit',
        'action' => 'log_link_visit_action',
        'conversion' => 'log_conversion',
    ];

    private const DATETIME_COLUMN = [
        'log_visit' => 'visit_last_action_time',
        'log_link_visit_action' => 'server_time',
        'log_conversion' => 'server_time',
    ];

    private const IDENTIFIER = '/^[a-zA-Z0-9_]+$/';

    /** @var DimensionCatalog */
    private $dimensionCatalog;
    /** @var MetricCatalog */
    private $metricCatalog;
    /** @var ScopeCompatibility */
    private $compatibility;

    public function __construct(
        ?DimensionCatalog $dimensionCatalog = null,
        ?MetricCatalog $metricCatalog = null,
        ?ScopeCompatibility $compatibility = null
    ) {
        $this->dimensionCatalog = $dimensionCatalog ?: new DimensionCatalog();
        $this->metricCatalog = $metricCatalog ?: new MetricCatalog();
        $this->compatibility = $compatibility ?: new ScopeCompatibility();
    }

    /**
     * Resolves every id of the definition against the catalogue and checks every dimension (rows,
     * pivot column, filters) against every metric.
     *
     * @return array{rows: DimensionEntry[], column: ?DimensionEntry, metrics: MetricEntry[], filters: array, scopes: string[]}
     * @throws \InvalidArgumentException with a translated message
     */
    public function resolve(ReportDefinition $definition, int $idSite): array
    {
        $dimensions = $this->dimensionCatalog->all($idSite);
        $catalogMetrics = $this->metricCatalog->all($idSite);

        $rows = array_map(fn (string $id) => $this->dimension($dimensions, $id), $definition->getRows());
        $column = $definition->getColumn() === null ? null : $this->dimension($dimensions, $definition->getColumn());

        $metrics = [];
        foreach ($definition->getMetrics() as $id) {
            if (!isset($catalogMetrics[$id])) {
                throw $this->error('DataExplorer_ErrorUnknownMetric', $id);
            }
            $metrics[] = $catalogMetrics[$id];
        }

        $filters = [];
        foreach ($definition->getFilters() as $filter) {
            $filters[] = $this->filter($this->dimension($dimensions, $filter['dimension']), $filter['operator'], $filter['value']);
        }

        $chosen = array_merge($rows, $column ? [$column] : [], array_column($filters, 'dimension'));
        foreach ($chosen as $dimension) {
            foreach ($metrics as $metric) {
                if (!$this->compatibility->isAllowed($dimension->scope, $metric)) {
                    throw $this->error('DataExplorer_ErrorIncompatibleCombination', $metric->name, $dimension->name);
                }
            }
        }

        $scopes = array_values(array_unique(array_map(fn (MetricEntry $m) => $m->scope, $metrics)));

        return ['rows' => $rows, 'column' => $column, 'metrics' => $metrics, 'filters' => $filters, 'scopes' => $scopes];
    }

    /**
     * Query of one row level (0 for the first row dimension) for the metrics of one scope.
     *
     * @param string|null $scope metric scope, defaults to the scope of the first metric
     * @return array{sql: string, bind: array}
     * @throws \InvalidArgumentException with a translated message
     */
    public function build(ReportDefinition $definition, LogAggregator $aggregator, int $level = 0, ?string $scope = null, bool $withPivot = true): array
    {
        return $this->buildQuery($definition, $aggregator, [
            'scope' => $scope,
            'labels' => $definition->getRows() ? max(0, $level) + 1 : 0,
            'pivot' => $withPivot,
        ]);
    }

    /**
     * Selected aliases: label_0 to label_<labels - 1>, pivot when grouped by the pivot column, metric_<index> where
     * index is the position of the metric in the definition. Rows come ordered by the first metric of the scope.
     *
     * @param array{scope?: ?string, labels?: int, pivot?: bool, pivotIn?: ?array, include?: ?array, exclude?: ?array, limit?: int, rankPerParent?: ?int} $spec
     *        - labels: number of row dimensions grouped, from the first one
     *        - pivotIn: pivot column values the query is restricted to
     *        - include / exclude: [int $depth, array $tuples] keeps / drops rows whose first $depth row values
     *          match one of the tuples (NULL matches NULL)
     *        - limit: maximum number of rows returned, 0 for none
     *        - rankPerParent: keep at most this many rows per parent (the first labels - 1 values), ranked by the
     *          first metric, plus one more row per parent that still has rows, so the caller knows it overflows
     * @return array{sql: string, bind: array}
     * @throws \InvalidArgumentException with a translated message
     */
    public function buildQuery(ReportDefinition $definition, LogAggregator $aggregator, array $spec): array
    {
        $sites = $aggregator->getSites();
        $resolved = $this->resolve($definition, (int) reset($sites));
        $scope = $spec['scope'] ?? $resolved['scopes'][0];

        $metrics = array_filter($resolved['metrics'], fn (MetricEntry $m) => $m->scope === $scope);
        if (!$metrics) {
            throw $this->error('DataExplorer_ErrorUnknownMetric', $scope);
        }

        $labels = min((int) ($spec['labels'] ?? 0), count($resolved['rows']));
        $include = $spec['include'] ?? null;
        $exclude = $spec['exclude'] ?? null;
        $depth = max($labels, $include ? (int) $include[0] : 0, $exclude ? (int) $exclude[0] : 0);
        $rows = array_slice($resolved['rows'], 0, $depth);
        $pivotIn = $spec['pivotIn'] ?? null;
        $column = !empty($spec['pivot']) || $pivotIn !== null ? $resolved['column'] : null;
        $filterDimensions = array_column($resolved['filters'], 'dimension');
        $dimensions = array_merge($rows, $column ? [$column] : [], $filterDimensions);

        $baseTable = $this->baseTable($scope, $dimensions);
        $from = array_values(array_unique([$baseTable, self::TABLE_BY_SCOPE[$scope]]));
        foreach ($dimensions as $dimension) {
            if (!in_array($dimension->table, $from, true)) {
                $from[] = $dimension->table;
            }
        }
        if (in_array('log_link_visit_action', $from, true) && in_array('log_conversion', $from, true)) {
            throw $this->error('DataExplorer_ErrorScopesCannotBeCombined');
        }

        $joins = [];
        $conditions = [];
        foreach ($dimensions as $dimension) {
            $this->addJoin($dimension, $joins, $conditions);
        }
        foreach ($joins as $join) {
            $from[] = $join['from'];
        }

        $rowExpressions = array_map(fn (DimensionEntry $d) => $this->groupExpression($d, $joins), $rows);

        $select = [];
        $groupBy = [];
        for ($i = 0; $i < $labels; $i++) {
            $select[] = $rowExpressions[$i] . ' AS label_' . $i;
            $groupBy[] = $rowExpressions[$i];
        }
        $pivotExpression = $column ? $this->groupExpression($column, $joins) : null;
        if ($pivotExpression !== null && !empty($spec['pivot'])) {
            $select[] = $pivotExpression . ' AS pivot';
            $groupBy[] = $pivotExpression;
        }
        foreach ($metrics as $index => $metric) {
            $select[] = $metric->sql . ' AS metric_' . $index;
        }

        $conditions = array_values(array_unique($conditions));
        $bind = [];
        foreach ($resolved['filters'] as $filter) {
            [$conditions[], $bind[]] = $this->filterCondition($this->expression($filter['dimension'], $joins), $filter);
        }
        if ($include) {
            $conditions[] = $this->tuplePredicate(array_slice($rowExpressions, 0, (int) $include[0]), $include[1], $bind);
        }
        if ($exclude) {
            $conditions[] = 'NOT ' . $this->tuplePredicate(array_slice($rowExpressions, 0, (int) $exclude[0]), $exclude[1], $bind);
        }
        if ($pivotIn !== null) {
            $conditions[] = $this->tuplePredicate([$pivotExpression], array_map(fn ($v) => [$v], $pivotIn), $bind);
        }

        $where = $aggregator->getWhereStatement($baseTable, self::DATETIME_COLUMN[$baseTable]);
        foreach ($conditions as $condition) {
            $where .= ' AND ' . $condition;
        }

        $metricOrder = 'metric_' . array_key_first($metrics) . ' DESC';
        $perParent = isset($spec['rankPerParent']) ? max(1, (int) $spec['rankPerParent']) : 0;
        $parentDepth = $labels - 1;
        $orderBy = $metricOrder;
        if ($perParent && $parentDepth > 0) {
            $parentOrder = array_map(fn (int $i) => 'label_' . $i, range(0, $parentDepth - 1));
            $orderBy = implode(', ', $parentOrder) . ', ' . $metricOrder;
        }

        $query = $aggregator->generateQuery(
            implode(', ', $select),
            $from,
            $where,
            implode(', ', $groupBy),
            $groupBy ? $orderBy : ''
        );

        if ($perParent && $parentDepth > 0) {
            $query['sql'] = $this->rankPerParent($query['sql'], $parentDepth, $perParent + 1, $metricOrder);
        }

        // the LIMIT is appended to the outer query: given to generateQuery(), a segment that wraps the query in a
        // subselect would also LIMIT the inner, ungrouped log rows
        $limit = max(0, (int) ($spec['limit'] ?? 0));
        if ($limit > 0) {
            $query['sql'] .= ' LIMIT ' . $limit;
        }

        return ['sql' => $query['sql'], 'bind' => $this->insertFilterBind($aggregator, $query['sql'], $query['bind'], $bind)];
    }

    /**
     * Numbers the rows of each parent in the order of the inner query (parents together, best first) and keeps the
     * first $keep of each. Session variables instead of ROW_NUMBER() so it runs on every MySQL and MariaDB version
     * Matomo supports, the way core RankingQuery does. The rank and the parent are updated in one expression so
     * the evaluation order of the select list does not matter, and the LIMIT on the inner query keeps its ORDER BY
     * (MariaDB drops the ORDER BY of an unlimited subquery).
     */
    private function rankPerParent(string $innerSql, int $parentDepth, int $keep, string $metricOrder): string
    {
        $same = [];
        $assign = [];
        $init = ['@dataexplorer_rank := 0'];
        for ($i = 0; $i < $parentDepth; $i++) {
            $same[] = '@dataexplorer_parent_' . $i . ' <=> label_' . $i;
            $assign[] = '0 * ((@dataexplorer_parent_' . $i . ' := label_' . $i . ') IS NULL)';
            $init[] = '@dataexplorer_parent_' . $i . ' := NULL';
        }
        $rank = 'IF(' . implode(' AND ', $same) . ', @dataexplorer_rank := @dataexplorer_rank + 1, '
            . '@dataexplorer_rank := 1 + ' . implode(' + ', $assign) . ')';

        return 'SELECT dataexplorer_ranked.* FROM ('
            . ' SELECT dataexplorer_sorted.*, ' . $rank . ' AS dataexplorer_rank'
            . ' FROM (' . $innerSql . ' LIMIT 18446744073709551615) AS dataexplorer_sorted,'
            . ' (SELECT ' . implode(', ', $init) . ') AS dataexplorer_init'
            . ' ) AS dataexplorer_ranked'
            . ' WHERE dataexplorer_rank <= ' . $keep
            . ' ORDER BY ' . $metricOrder;
    }

    /**
     * Never NULL, so it can be negated: tuples without NULL go through one IN list, the others through
     * null-safe equality.
     *
     * @param string[] $expressions
     * @param array[] $tuples
     */
    private function tuplePredicate(array $expressions, array $tuples, array &$bind): string
    {
        $plain = [];
        $withNull = [];
        foreach ($tuples as $tuple) {
            $tuple = array_slice(array_values($tuple), 0, count($expressions));
            if (in_array(null, $tuple, true)) {
                $withNull[] = $tuple;
            } else {
                $plain[] = $tuple;
            }
        }

        $parts = [];
        if ($plain) {
            $single = count($expressions) === 1;
            $left = $single ? $expressions[0] : '(' . implode(', ', $expressions) . ')';
            $placeholder = $single ? '?' : '(' . implode(', ', array_fill(0, count($expressions), '?')) . ')';
            $parts[] = 'COALESCE(' . $left . ' IN (' . implode(', ', array_fill(0, count($plain), $placeholder)) . '), 0)';
            foreach ($plain as $tuple) {
                array_push($bind, ...$tuple);
            }
        }
        foreach ($withNull as $tuple) {
            $parts[] = '(' . implode(' AND ', array_map(fn (string $e) => $e . ' <=> ?', $expressions)) . ')';
            array_push($bind, ...$tuple);
        }

        return $parts ? '(' . implode(' OR ', $parts) . ')' : '(1 = 0)';
    }

    /**
     * Grouped value of a dimension, with NULL and '' folded into one "not defined" group. NULLIF(expr, '') is not
     * used because it also turns the number 0 into NULL ('' compares equal to 0).
     */
    private function groupExpression(DimensionEntry $dimension, array $joins): string
    {
        $expression = $this->expression($dimension, $joins);

        return 'IF(LENGTH(' . $expression . ') = 0, NULL, ' . $expression . ')';
    }

    /**
     * generateQuery() binds the date range and sites first and the segment last, the filter placeholders sit
     * between them in the WHERE clause. During segment archiving with the segment cache, the date range, sites and
     * segment move into a temporary table and generateQuery() binds nothing.
     */
    private function insertFilterBind(LogAggregator $aggregator, string $sql, array $generated, array $filterBind): array
    {
        if (!$generated) {
            if (substr_count($sql, '?') !== count($filterBind)) {
                throw new \RuntimeException('Unexpected bind count in the generated query.');
            }

            return $filterBind;
        }

        $general = $aggregator->getGeneralQueryBindParams();
        if (array_slice($generated, 0, count($general)) != $general) {
            throw new \RuntimeException('Unexpected bind order in the generated query.');
        }

        return array_merge($general, $filterBind, array_slice($generated, count($general)));
    }

    /**
     * Visit counts next to action dimensions are read from the action rows, so the period applies to the action time.
     *
     * @param DimensionEntry[] $dimensions
     */
    private function baseTable(string $scope, array $dimensions): string
    {
        if ($scope === 'visit') {
            foreach ($dimensions as $dimension) {
                if ($dimension->scope === 'action') {
                    return self::TABLE_BY_SCOPE['action'];
                }
            }
        }

        return self::TABLE_BY_SCOPE[$scope];
    }

    private function addJoin(DimensionEntry $dimension, array &$joins, array &$conditions): void
    {
        $this->assertIdentifiers($dimension->table, $dimension->column);

        $join = $dimension->join;
        $alias = null;
        if ($join instanceof \Piwik\Columns\Join) {
            // getAdditionalKeyColumns() only exists since Matomo 5.13
            $keyColumns = method_exists($join, 'getAdditionalKeyColumns') ? $join->getAdditionalKeyColumns() : [];
            $this->assertIdentifiers($join->getTable(), $join->getColumn(), $join->getTargetColumn(), ...$keyColumns);

            $key = $dimension->table . '.' . $dimension->column . '>' . $join->getTable() . '.' . $join->getColumn();
            if (!isset($joins[$key])) {
                $alias = 'dataexplorer_join_' . count($joins);
                $on = [$dimension->table . '.' . $dimension->column . ' = ' . $alias . '.' . $join->getColumn()];
                foreach ($keyColumns as $keyColumn) {
                    $on[] = $dimension->table . '.' . $keyColumn . ' = ' . $alias . '.' . $keyColumn;
                }
                $joins[$key] = [
                    'alias' => $alias,
                    'target' => $join->getTargetColumn(),
                    'table' => $join->getTable(),
                    'from' => ['table' => $join->getTable(), 'tableAlias' => $alias, 'joinOn' => implode(' AND ', $on)],
                ];
            }
            $alias = $joins[$key]['alias'];
        }

        $discriminator = $dimension->discriminator;
        if ($discriminator instanceof \Piwik\Columns\Discriminator && $discriminator->isValid()) {
            $this->assertIdentifiers($discriminator->getColumn());
            if ($join instanceof \Piwik\Columns\Join && $discriminator->getTable() === $join->getTable()) {
                $table = $alias;
            } elseif ($discriminator->getTable() === $dimension->table) {
                $table = $dimension->table;
            } else {
                throw $this->error('DataExplorer_ErrorUnknownDimension', $dimension->id);
            }
            $conditions[] = $table . '.' . $discriminator->getColumn() . ' = ' . (int) $discriminator->getValue();
        }
    }

    private function expression(DimensionEntry $dimension, array $joins): string
    {
        $join = $dimension->join;
        if ($join instanceof \Piwik\Columns\Join) {
            $key = $dimension->table . '.' . $dimension->column . '>' . $join->getTable() . '.' . $join->getColumn();

            return $joins[$key]['alias'] . '.' . $joins[$key]['target'];
        }

        return $dimension->table . '.' . $dimension->column;
    }

    /**
     * @return array{0: string, 1: string|int|float}
     */
    private function filterCondition(string $expression, array $filter): array
    {
        $value = $filter['value'];

        switch ($filter['operator']) {
            case 'equals':
                return [$expression . ' = ?', $value];
            case 'not_equals':
                return ['(' . $expression . ' IS NULL OR ' . $expression . ' <> ?)', $value];
            case 'contains':
                return [$expression . ' LIKE ?', '%' . $this->escapeLike($value) . '%'];
            case 'starts_with':
                return [$expression . ' LIKE ?', $this->escapeLike($value) . '%'];
            case 'regex':
                return [$expression . ' REGEXP ?', $value];
            case 'gt':
                return [$expression . ' > ?', $value];
            case 'lt':
                return [$expression . ' < ?', $value];
        }

        throw $this->error('DataExplorer_ErrorFilterOperatorInvalid', $filter['operator']);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    /**
     * @return array{dimension: DimensionEntry, operator: string, value: string|float}
     */
    private function filter(DimensionEntry $dimension, string $operator, $value): array
    {
        if (!in_array($operator, self::OPERATORS, true)) {
            throw $this->error('DataExplorer_ErrorFilterOperatorInvalid', $operator);
        }
        if (!is_scalar($value)) {
            throw $this->error('DataExplorer_ErrorFiltersInvalid');
        }

        if ($operator === 'gt' || $operator === 'lt') {
            if (!is_numeric($value)) {
                throw $this->error('DataExplorer_ErrorFilterValueNotNumeric', $dimension->name);
            }

            return ['dimension' => $dimension, 'operator' => $operator, 'value' => $value + 0];
        }

        if ($operator === 'regex') {
            $this->assertValidRegex((string) $value);
        }

        // the tracker stores text sanitized (quotes as &#039;) but URLs unsanitized, like core segments compare
        $value = Common::unsanitizeInputValue((string) $value);
        if ($dimension->type !== Dimension::TYPE_URL) {
            $value = Common::sanitizeInputValue($value);
        }

        return ['dimension' => $dimension, 'operator' => $operator, 'value' => $value];
    }

    private function assertValidRegex(string $pattern): void
    {
        if ($pattern === '' || @preg_match('/' . str_replace('/', '\/', $pattern) . '/u', '') === false) {
            throw $this->error('DataExplorer_ErrorFilterRegexInvalid', $pattern);
        }
    }

    /**
     * @param DimensionEntry[] $dimensions
     */
    private function dimension(array $dimensions, string $id): DimensionEntry
    {
        if (!isset($dimensions[$id])) {
            throw $this->error('DataExplorer_ErrorUnknownDimension', $id);
        }

        return $dimensions[$id];
    }

    private function assertIdentifiers(string ...$identifiers): void
    {
        foreach ($identifiers as $identifier) {
            if (!preg_match(self::IDENTIFIER, $identifier)) {
                throw new \RuntimeException('Invalid SQL identifier in the dimension catalogue.');
            }
        }
    }

    private function error(string $key, ...$params): \InvalidArgumentException
    {
        return new \InvalidArgumentException(Piwik::translate($key, $params));
    }
}
