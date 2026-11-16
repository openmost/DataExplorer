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
     * Selected aliases: label_0 to label_<level>, pivot when the pivot column is grouped, metric_<index> where index
     * is the position of the metric in the definition.
     *
     * @param string|null $scope metric scope, defaults to the scope of the first metric
     * @return array{sql: string, bind: array}
     * @throws \InvalidArgumentException with a translated message
     */
    public function build(ReportDefinition $definition, LogAggregator $aggregator, int $level = 0, ?string $scope = null, bool $withPivot = true): array
    {
        $sites = $aggregator->getSites();
        $resolved = $this->resolve($definition, (int) reset($sites));
        $scope = $scope ?? $resolved['scopes'][0];

        $metrics = array_filter($resolved['metrics'], fn (MetricEntry $m) => $m->scope === $scope);
        if (!$metrics) {
            throw $this->error('DataExplorer_ErrorUnknownMetric', $scope);
        }

        $rows = $resolved['rows'] ? array_slice($resolved['rows'], 0, max(0, $level) + 1) : [];
        $column = $withPivot ? $resolved['column'] : null;
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

        $select = [];
        $groupBy = [];
        foreach ($rows as $i => $dimension) {
            $expression = $this->expression($dimension, $joins);
            $select[] = $expression . ' AS label_' . $i;
            $groupBy[] = $expression;
        }
        if ($column) {
            $expression = $this->expression($column, $joins);
            $select[] = $expression . ' AS pivot';
            $groupBy[] = $expression;
        }
        foreach ($metrics as $index => $metric) {
            $select[] = $metric->sql . ' AS metric_' . $index;
        }

        $conditions = array_values(array_unique($conditions));
        $bind = [];
        foreach ($resolved['filters'] as $filter) {
            [$conditions[], $bind[]] = $this->filterCondition($this->expression($filter['dimension'], $joins), $filter);
        }

        $where = $aggregator->getWhereStatement($baseTable, self::DATETIME_COLUMN[$baseTable]);
        foreach ($conditions as $condition) {
            $where .= ' AND ' . $condition;
        }

        $query = $aggregator->generateQuery(
            implode(', ', $select),
            $from,
            $where,
            implode(', ', $groupBy),
            'metric_' . array_key_first($metrics) . ' DESC'
        );

        return ['sql' => $query['sql'], 'bind' => $this->insertFilterBind($aggregator, $query['bind'], $bind)];
    }

    /**
     * generateQuery() binds the date range and sites first and the segment last, the filter placeholders sit
     * between them in the WHERE clause.
     */
    private function insertFilterBind(LogAggregator $aggregator, array $generated, array $filterBind): array
    {
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
