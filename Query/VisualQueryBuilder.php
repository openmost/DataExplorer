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
use Piwik\Plugins\DataExplorer\Catalog\LogTables;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;

/**
 * Compiles a visual definition into one aggregate query per metric scope and row level.
 *
 * Every metric can be read next to every dimension. A dimension of another table than the metric's repeats its
 * rows: a visit has several actions, an action several conversions of its visit. Such a query is first grouped
 * per row and per visit, action or conversion of the metric, so each one counts once per row, then aggregated:
 * "Total events" by event name is the events of the visits having that event name, "Conversions" by page URL the
 * conversions of the visits that viewed that page.
 *
 * Every table, column, join and metric expression comes from the catalogue, looked up by id.
 * Filter values only ever reach the database as bound parameters.
 */
class VisualQueryBuilder
{
    public const OPERATORS = [
        'equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with',
        'regex', 'gt', 'gte', 'lt', 'lte',
    ];

    // a negative operator keeps the rows without a value, like core segments do
    public const NEGATIONS = [
        'not_equals' => 'equals',
        'not_contains' => 'contains',
        'not_starts_with' => 'starts_with',
        'not_ends_with' => 'ends_with',
    ];
    public const OPERATOR_IN = 'in';
    public const OPERATOR_NOT_IN = 'not_in';

    /** the historical scopes, any other log table is the scope of its own name (see LogTables) */
    public const TABLE_BY_SCOPE = [
        'visit' => 'log_visit',
        'action' => 'log_link_visit_action',
        'conversion' => 'log_conversion',
    ];

    private const IDENTIFIER = '/^[a-zA-Z0-9_]+$/';

    /** dimensions of a funnel step that match page views only */
    public const PAGE_VIEW_DIMENSIONS = ['Actions.PageUrl', 'Actions.PageTitle'];

    // alias.column of a log_action row joined for a metric (MetricCatalog::actionAlias())
    private const ACTION_REFERENCE = '/\b(' . MetricCatalog::ACTION_ALIAS_PREFIX . '[a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)/';

    /** @var DimensionCatalog */
    private $dimensionCatalog;
    /** @var MetricCatalog */
    private $metricCatalog;
    /** @var LogTables|null */
    private $logTables;

    public function __construct(?DimensionCatalog $dimensionCatalog = null, ?MetricCatalog $metricCatalog = null, ?LogTables $logTables = null)
    {
        $this->dimensionCatalog = $dimensionCatalog ?: new DimensionCatalog();
        $this->metricCatalog = $metricCatalog ?: new MetricCatalog();
        $this->logTables = $logTables;
    }

    private function logTables(): LogTables
    {
        if ($this->logTables === null) {
            $this->logTables = LogTables::get();
        }

        return $this->logTables;
    }

    private function tableOf(string $scope): string
    {
        $table = self::TABLE_BY_SCOPE[$scope] ?? $this->logTables()->tableOf($scope);
        if ($table === null) {
            throw $this->error('DataExplorer_ErrorUnknownMetric', $scope);
        }

        return $table;
    }

    /**
     * Resolves every id of the definition against the catalogue, and checks that a metric read under a dimension
     * that repeats its rows can be counted once per row.
     *
     * A processed metric (rate, average) is queried as the metrics it is computed from: "metrics" lists what the queries
     * read, the chosen ones first then the extra ones, "processed" what is computed from them afterwards (in an order
     * where a metric comes after those it reads), "hidden" the extra ones nobody chose, and "chosen" the ids of the
     * definition in their order.
     *
     * @return array{rows: DimensionEntry[], column: ?DimensionEntry, metrics: MetricEntry[], processed: MetricEntry[], hidden: string[], chosen: string[], filters: array, scopes: string[]}
     * @throws \InvalidArgumentException with a translated message
     */
    public function resolve(ReportDefinition $definition, int $idSite): array
    {
        $dimensions = $this->dimensionCatalog->all($idSite);
        $catalogMetrics = $this->metricCatalog->all($idSite);

        $rows = array_map(fn (string $id) => $this->dimension($dimensions, $id), $definition->getRows());
        $column = $definition->getColumn() === null ? null : $this->dimension($dimensions, $definition->getColumn());

        $metrics = [];
        $processed = [];
        foreach ($definition->getMetrics() as $id) {
            if (!isset($catalogMetrics[$id])) {
                throw $this->error('DataExplorer_ErrorUnknownMetric', $id);
            }
            if (!$catalogMetrics[$id]->isProcessed()) {
                $metrics[$id] = $catalogMetrics[$id];
            }
        }
        $chosenIds = $definition->getMetrics();
        foreach ($chosenIds as $id) {
            if ($catalogMetrics[$id]->isProcessed()) {
                $this->expandProcessed($id, $catalogMetrics, $metrics, $processed, []);
            }
        }
        $hidden = array_values(array_diff(array_merge(array_keys($metrics), array_keys($processed)), $chosenIds));
        $metrics = array_values($metrics);

        $filters = [];
        foreach ($definition->getFilters() as $filter) {
            $filters[] = $this->filter($this->dimension($dimensions, $filter['dimension']), $filter['operator'], $filter['value'])
                + ['group' => $filter['group']];
        }

        $chosen = array_merge($rows, $column ? [$column] : [], array_column($filters, 'dimension'));
        foreach ($chosen as $dimension) {
            foreach ($metrics as $metric) {
                if ($this->repeatsRows($this->tableOf($metric->scope), [$dimension->table])) {
                    $this->metricColumns($metric, $dimension->name);
                }
            }
        }

        $scopes = array_values(array_unique(array_map(fn (MetricEntry $m) => $m->scope, $metrics)));

        return [
            'rows' => $rows,
            'column' => $column,
            'metrics' => $metrics,
            'processed' => array_values($processed),
            'hidden' => $hidden,
            'chosen' => $chosenIds,
            'filters' => $filters,
            'scopes' => $scopes,
        ];
    }

    /**
     * Adds the metrics a processed metric reads, then the processed metric itself.
     *
     * @param array<string, MetricEntry> $catalogMetrics
     * @param array<string, MetricEntry> $metrics queried metrics, by id
     * @param array<string, MetricEntry> $processed computed metrics, by id, in computing order
     * @param array<string, true> $seen
     */
    private function expandProcessed(string $id, array $catalogMetrics, array &$metrics, array &$processed, array $seen): void
    {
        if (isset($processed[$id]) || isset($seen[$id])) {
            return;
        }
        $seen[$id] = true;
        foreach ($catalogMetrics[$id]->dependents as $dependent) {
            if (!isset($catalogMetrics[$dependent])) {
                throw $this->error('DataExplorer_ErrorUnknownMetric', $dependent);
            }
            if ($catalogMetrics[$dependent]->isProcessed()) {
                $this->expandProcessed($dependent, $catalogMetrics, $metrics, $processed, $seen);
            } elseif (!isset($metrics[$dependent])) {
                $metrics[$dependent] = $catalogMetrics[$dependent];
            }
        }
        $processed[$id] = $catalogMetrics[$id];
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
     * @param array|null $resolved resolve() of the definition for the site of $aggregator, when the caller has it
     * @return array{sql: string, bind: array}
     * @throws \InvalidArgumentException with a translated message
     */
    public function buildQuery(ReportDefinition $definition, LogAggregator $aggregator, array $spec, ?array $resolved = null): array
    {
        if ($resolved === null) {
            $sites = $aggregator->getSites();
            $resolved = $this->resolve($definition, (int) reset($sites));
        }
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
        $datedTable = $this->logTables()->datedTable($baseTable);
        $from = array_values(array_unique([$baseTable, $this->tableOf($scope), $datedTable]));
        foreach ($dimensions as $dimension) {
            if (!in_array($dimension->table, $from, true)) {
                $from[] = $dimension->table;
            }
        }
        $metricTable = $this->tableOf($scope);
        foreach ($metrics as $metric) {
            foreach ($this->metricParentTables($metric) as $table) {
                if (!in_array($table, $from, true)) {
                    $from[] = $table;
                }
            }
        }
        $from = $this->logTables()->joinOrder($from);
        $perGrain = $this->repeatsRows($metricTable, $from) && $this->countsRepeatedRows($metrics);

        $joins = [];
        $conditions = [];
        foreach ($dimensions as $dimension) {
            $this->addJoin($dimension, $joins, $conditions);
        }
        // a row of a plugin table (a media view, a form, a crash) groups the rows of the metric table of its visit
        // only: actions or conversions of visits without one are not counted, like visits are not
        $parents = $this->logTables()->parentsOf($metricTable);
        foreach ($dimensions as $dimension) {
            if (isset(LogTables::SCOPE_BY_TABLE[$dimension->table]) || $dimension->table === $metricTable || in_array($dimension->table, $parents, true)) {
                continue;
            }
            $grain = $this->logTables()->grain($dimension->table);
            if ($grain) {
                $this->assertIdentifiers($dimension->table, $grain[0]);
                $conditions[] = $dimension->table . '.' . $grain[0] . ' IS NOT NULL';
            }
        }
        foreach ($joins as $join) {
            $from[] = $join['from'];
        }
        foreach ($this->actionJoins($metrics, $metricTable) as $actionJoin) {
            $from[] = $actionJoin;
        }

        $rowExpressions = array_map(fn (DimensionEntry $d) => $this->groupExpression($d, $joins), $rows);

        $select = [];
        $groupBy = [];
        $aliases = [];
        for ($i = 0; $i < $labels; $i++) {
            $select[] = $rowExpressions[$i] . ' AS label_' . $i;
            $groupBy[] = $rowExpressions[$i];
            $aliases[] = 'label_' . $i;
        }
        $pivotExpression = $column ? $this->groupExpression($column, $joins) : null;
        if ($pivotExpression !== null && !empty($spec['pivot'])) {
            $select[] = $pivotExpression . ' AS pivot';
            $groupBy[] = $pivotExpression;
            $aliases[] = 'pivot';
        }
        $metricSelect = [];
        foreach ($metrics as $index => $metric) {
            $metricSelect[] = $metric->sql . ' AS metric_' . $index;
        }

        $conditions = array_values(array_unique($conditions));
        $bind = [];
        $groups = [];
        foreach ($resolved['filters'] as $filter) {
            [$condition, $values] = $this->filterCondition($this->expression($filter['dimension'], $joins), $filter);
            $groups[$filter['group'] ?? 0][] = $condition;
            array_push($bind, ...$values);
        }
        foreach ($groups as $groupConditions) {
            $conditions[] = '(' . implode(' OR ', $groupConditions) . ')';
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

        $where = $aggregator->getWhereStatement($datedTable, $this->logTables()->datetimeColumn($datedTable));
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

        if ($perGrain) {
            $query = $this->perGrainQuery($aggregator, $metricTable, $metrics, $select, $groupBy, $aliases, $metricSelect, $from, $where, $orderBy);
        } else {
            $query = $aggregator->generateQuery(
                implode(', ', array_merge($select, $metricSelect)),
                $from,
                $where,
                implode(', ', $groupBy),
                $groupBy ? $orderBy : ''
            );
        }

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
     * The table of the log a funnel step is tested on: the actions, or the conversions when one of its conditions
     * reads a conversion dimension. A visit dimension matches every action of its visit.
     *
     * @param array[] $conditions resolved filters of the step
     * @throws \InvalidArgumentException with a translated message for a dimension of another table, or a step
     *         mixing action and conversion dimensions
     */
    public function funnelStepTable(array $conditions, string $stepName): string
    {
        $tables = [];
        foreach ($conditions as $condition) {
            $tables[$this->funnelTableOf($condition['dimension'])] = true;
        }
        unset($tables['log_visit']);
        if (count($tables) > 1) {
            throw $this->error('DataExplorer_ErrorFunnelStepMixesTables', $stepName);
        }

        return $tables ? (string) array_key_first($tables) : 'log_link_visit_action';
    }

    /**
     * @throws \InvalidArgumentException with a translated message for a dimension that is not a visit, action or
     *         conversion dimension
     */
    public function funnelTableOf(DimensionEntry $dimension): string
    {
        if (!in_array($dimension->table, ['log_visit', 'log_link_visit_action', 'log_conversion'], true)) {
            throw $this->error('DataExplorer_ErrorFunnelDimension', $dimension->name);
        }

        return $dimension->table;
    }

    /**
     * The actions (or conversions) of the period matching at least one step, in visit order, one flag column per
     * step (step_<index>, 1 when the row matches it). Columns: idvisit, pos (the action, the one a conversion was
     * recorded on), prev (the action before pos in the visit, when $withPrevious), ts (seconds), bkey (breakdown
     * value). Steps tested on another table are left out of $steps.
     *
     * The rows are read first, with the period, the segment and the conditions of every step in the WHERE clause,
     * then flagged by an outer query over their values: the flags come first in the SQL, so do their bound values.
     *
     * @param array<int, array[]> $steps resolved filters (dimension, operator, value, group) by step index
     * @return array{sql: string, bind: array}
     */
    public function funnelQuery(LogAggregator $aggregator, string $table, array $steps, ?DimensionEntry $breakdown, bool $withPrevious): array
    {
        $dimensions = [];
        foreach ($steps as $conditions) {
            foreach ($conditions as $condition) {
                $dimensions[$condition['dimension']->id] = $condition['dimension'];
            }
        }
        if ($breakdown !== null && in_array($breakdown->table, ['log_visit', $table], true)) {
            $dimensions[$breakdown->id] = $breakdown;
        } else {
            $breakdown = null;
        }

        $from = [$table];
        foreach ($dimensions as $dimension) {
            if (!in_array($dimension->table, $from, true)) {
                $from[] = $dimension->table;
            }
        }
        $from = $this->logTables()->joinOrder($from);

        $joins = [];
        $columns = [];
        foreach (array_values($dimensions) as $index => $dimension) {
            // a discriminator restricts the value, not the rows: other steps may read the same rows
            $discriminators = [];
            $this->addJoin($dimension, $joins, $discriminators);
            $expression = $this->expression($dimension, $joins);
            if ($table === 'log_link_visit_action' && in_array($dimension->id, self::PAGE_VIEW_DIMENSIONS, true)) {
                // an event or a content interaction carries the URL and title of its page: a step on a page means a
                // page view of it
                $discriminators[] = $table . '.idaction_event_category IS NULL';
                $discriminators[] = $table . '.idaction_content_interaction IS NULL';
            }
            if ($discriminators) {
                $expression = 'IF(' . implode(' AND ', $discriminators) . ', ' . $expression . ', NULL)';
            }
            $columns[$dimension->id] = ['expression' => $expression, 'alias' => 'c_' . $index];
        }
        foreach ($joins as $join) {
            $from[] = $join['from'];
        }

        $select = [
            $table . '.idvisit AS idvisit',
            $table . '.idlink_va AS pos',
            "TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', " . $table . '.server_time) AS ts',
        ];
        if ($withPrevious) {
            $select[] = '(SELECT MAX(dataexplorer_previous.idlink_va) FROM ' . \Piwik\Common::prefixTable('log_link_visit_action')
                . ' AS dataexplorer_previous WHERE dataexplorer_previous.idvisit = ' . $table . '.idvisit'
                . ' AND dataexplorer_previous.idlink_va < ' . $table . '.idlink_va) AS prev';
        }
        foreach ($columns as $column) {
            $select[] = $column['expression'] . ' AS ' . $column['alias'];
        }
        $select[] = ($breakdown !== null ? $columns[$breakdown->id]['expression'] : 'NULL') . ' AS bkey';

        $whereBind = [];
        $any = [];
        foreach ($steps as $conditions) {
            [$condition, $values] = $this->funnelStepCondition($conditions, fn (DimensionEntry $d) => $columns[$d->id]['expression']);
            $any[] = $condition;
            array_push($whereBind, ...$values);
        }
        $where = $aggregator->getWhereStatement($table, 'server_time');
        $where .= ' AND (' . ($any ? implode(' OR ', $any) : '1 = 0') . ')';

        $inner = $aggregator->generateQuery(implode(', ', $select), $from, $where, '', '');
        $bind = $this->insertFilterBind($aggregator, $inner['sql'], $inner['bind'], $whereBind);

        $outerBind = [];
        $outer = ['funnel_rows.idvisit', 'funnel_rows.pos', 'funnel_rows.ts', 'funnel_rows.bkey'];
        if ($withPrevious) {
            $outer[] = 'funnel_rows.prev';
        }
        foreach ($steps as $index => $conditions) {
            [$condition, $values] = $this->funnelStepCondition($conditions, fn (DimensionEntry $d) => 'funnel_rows.' . $columns[$d->id]['alias']);
            $outer[] = 'IF(' . $condition . ', 1, 0) AS step_' . (int) $index;
            array_push($outerBind, ...$values);
        }

        $sql = 'SELECT ' . implode(', ', $outer) . ' FROM (' . $inner['sql'] . ') AS funnel_rows'
            . ' ORDER BY funnel_rows.idvisit, funnel_rows.ts, funnel_rows.pos';

        return ['sql' => $sql, 'bind' => array_merge($outerBind, $bind)];
    }

    /**
     * Distinct stored values of a dimension over the period, at most $limit, for the label conditions of a funnel.
     *
     * @return array{sql: string, bind: array}
     */
    public function distinctValuesQuery(LogAggregator $aggregator, DimensionEntry $dimension, int $limit): array
    {
        $table = $dimension->table === 'log_visit' ? 'log_visit' : $this->funnelTableOf($dimension);
        $joins = [];
        $discriminators = [];
        $this->addJoin($dimension, $joins, $discriminators);
        $from = [$table];
        foreach ($joins as $join) {
            $from[] = $join['from'];
        }
        $expression = $this->expression($dimension, $joins);
        $datetime = $table === 'log_visit' ? 'visit_last_action_time' : 'server_time';
        $where = $aggregator->getWhereStatement($table, $datetime);
        foreach ($discriminators as $discriminator) {
            $where .= ' AND ' . $discriminator;
        }

        $query = $aggregator->generateQuery('DISTINCT ' . $expression . ' AS label_0', $from, $where, '', '');
        $query['sql'] .= ' LIMIT ' . max(1, $limit);

        return $query;
    }

    /**
     * The conditions of a step: OR inside a group, AND between groups.
     *
     * @param callable(DimensionEntry): string $expressionOf
     * @return array{0: string, 1: array}
     */
    private function funnelStepCondition(array $conditions, callable $expressionOf): array
    {
        $groups = [];
        $bind = [];
        foreach ($conditions as $condition) {
            [$sql, $values] = $this->filterCondition($expressionOf($condition['dimension']), $condition);
            $groups[$condition['group'] ?? 0][] = $sql;
            array_push($bind, ...$values);
        }
        if (!$groups) {
            return ['1 = 0', []];
        }

        return ['(' . implode(' AND ', array_map(fn (array $group) => '(' . implode(' OR ', $group) . ')', $groups)) . ')', $bind];
    }

    /**
     * Resolves the conditions of a funnel step like filters: the dimension looked up, the value checked.
     *
     * @return array[] dimension (DimensionEntry), operator, value, group
     */
    public function resolveConditions(array $conditions, int $idSite): array
    {
        $dimensions = $this->dimensionCatalog->all($idSite);
        $resolved = [];
        foreach ($conditions as $condition) {
            $resolved[] = $this->filter($this->dimension($dimensions, $condition['dimension']), $condition['operator'], $condition['value'])
                + ['group' => $condition['group'] ?? 0];
        }

        return $resolved;
    }

    public function resolveDimension(string $id, int $idSite): DimensionEntry
    {
        return $this->dimension($this->dimensionCatalog->all($idSite), $id);
    }

    /**
     * The query grouped per row and per visit, action or conversion of the metric table first, keeping the columns
     * the metrics read, then aggregated per row by the metric expressions themselves: the derived table takes the
     * name of the metric table, so they apply unchanged.
     *
     * @param MetricEntry[] $metrics
     * @return array{sql: string, bind: array}
     */
    private function perGrainQuery(LogAggregator $aggregator, string $metricTable, array $metrics, array $select, array $groupBy, array $aliases, array $metricSelect, array $from, string $where, string $orderBy): array
    {
        $columns = [];
        foreach ($metrics as $metric) {
            foreach ($this->metricColumns($metric, '') as $column) {
                $columns[$column] = true;
            }
        }
        foreach ($this->logTables()->grain($metricTable) as $column) {
            $select[] = $metricTable . '.' . $column . ' AS ' . $column;
            $groupBy[] = $metricTable . '.' . $column;
            unset($columns[$column]);
        }
        foreach (array_keys($columns) as $column) {
            $select[] = 'MAX(' . $metricTable . '.' . $column . ') AS ' . $column;
        }
        // the type of the action a row points to, a column of its visit: one value per row, the derived table
        // carries it as a column of its own
        $actionColumns = [];
        $parents = $this->logTables()->parentsOf($metricTable);
        foreach ($metricSelect as $expression) {
            preg_match_all(self::ACTION_REFERENCE, $expression, $matches, PREG_SET_ORDER);
            foreach ($matches as [$reference, $alias, $column]) {
                $actionColumns[$reference] = $alias . '__' . $column;
            }
            preg_match_all('/\b(log_[a-z_]+)\.`?([a-zA-Z0-9_]+)`?/', $expression, $matches, PREG_SET_ORDER);
            foreach ($matches as [$reference, $table, $column]) {
                if (in_array($table, $parents, true)) {
                    $actionColumns[$reference] = $table . '__' . $column;
                }
            }
        }
        foreach ($actionColumns as $reference => $name) {
            $select[] = 'MAX(' . $reference . ') AS ' . $name;
        }
        $metricSelect = array_map(
            fn (string $expression) => strtr($expression, array_map(fn (string $name) => $metricTable . '.' . $name, $actionColumns)),
            $metricSelect
        );

        $inner = $aggregator->generateQuery(implode(', ', $select), $from, $where, implode(', ', $groupBy), '');

        $sql = 'SELECT ' . implode(', ', array_merge($aliases, $metricSelect)) . ' FROM (' . $inner['sql'] . ') AS ' . $metricTable;
        if ($aliases) {
            $sql .= ' GROUP BY ' . implode(', ', $aliases) . ' ORDER BY ' . $orderBy;
        }

        return ['sql' => $sql, 'bind' => $inner['bind']];
    }

    /**
     * The log_action rows the metrics of $metricTable read the type of (pageviews, downloads...), each joined once
     * on the action column it is named after: one action per row, so the join repeats nothing.
     *
     * @param MetricEntry[] $metrics
     * @return array[] FROM entries of LogAggregator::generateQuery()
     */
    private function actionJoins(array $metrics, string $metricTable): array
    {
        $joins = [];
        foreach ($metrics as $metric) {
            preg_match_all(self::ACTION_REFERENCE, $metric->sql, $matches, PREG_SET_ORDER);
            foreach ($matches as [, $alias]) {
                $column = substr($alias, strlen(MetricCatalog::ACTION_ALIAS_PREFIX));
                $this->assertIdentifiers($metricTable, $column);
                $joins[$alias] = [
                    'table' => 'log_action',
                    'tableAlias' => $alias,
                    'joinOn' => $metricTable . '.' . $column . ' = ' . $alias . '.idaction',
                ];
            }
        }

        return array_values($joins);
    }

    /**
     * Whether a dimension of one of $tables gives a row of $metricTable several rows: a visit has several actions,
     * conversions, products or media views, an action shares its visit with the conversions of that visit. A table
     * having one row per row of the metric table (its visit, the crash of a crash event) never repeats it.
     *
     * @param array<int, string|array> $tables
     */
    private function repeatsRows(string $metricTable, array $tables): bool
    {
        foreach (array_filter($tables, 'is_string') as $table) {
            if ($this->logTables()->repeats($metricTable, $table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Distinct counts are the same over repeated rows, so a query of distinct counts only is run as it is.
     *
     * @param MetricEntry[] $metrics
     */
    private function countsRepeatedRows(array $metrics): bool
    {
        foreach ($metrics as $metric) {
            if ($metric->aggregation !== 'uniq') {
                return true;
            }
        }

        return false;
    }

    /**
     * Columns of its own table a metric expression reads, so it can run over the rows grouped per visit, action or
     * conversion.
     *
     * @return string[]
     * @throws \InvalidArgumentException when the expression reads another table
     */
    private function metricColumns(MetricEntry $metric, string $dimensionName): array
    {
        $table = $this->tableOf($metric->scope);
        preg_match_all('/\b(log_[a-z_]+)\.`?([a-zA-Z0-9_]+)`?/', $metric->sql, $matches, PREG_SET_ORDER);

        $parents = $this->logTables()->parentsOf($table);
        $columns = [];
        foreach ($matches as [, $matchedTable, $column]) {
            if (in_array($matchedTable, $parents, true)) {
                continue;
            }
            if ($matchedTable !== $table) {
                throw $this->error('DataExplorer_ErrorIncompatibleCombination', $metric->name, $dimensionName);
            }
            $columns[$column] = $column;
        }

        return array_values($columns);
    }

    /**
     * Tables a metric reads besides its own, each with one row per row of it (the visit of a conversion).
     *
     * @return string[]
     */
    private function metricParentTables(MetricEntry $metric): array
    {
        $table = $this->tableOf($metric->scope);
        preg_match_all('/\b(log_[a-z_]+)\.`?[a-zA-Z0-9_]+`?/', $metric->sql, $matches);

        return array_values(array_intersect(array_unique($matches[1]), $this->logTables()->parentsOf((string) $table)));
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
     * The first table of the query. Visit counts next to action dimensions are read from the action rows, so the
     * period applies to the action time, and the same for the rows of a plugin table (products, media views, forms,
     * crashes): with the visits first, core would group them per visit in a subquery and count each visit under one
     * value only. The period is then read from that table or, when it has no datetime column, from its visit or the
     * related table that has one (see LogTables::datedTable()).
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
            $undated = null;
            foreach ($dimensions as $dimension) {
                if (isset(LogTables::SCOPE_BY_TABLE[$dimension->table]) || !$this->logTables()->repeats('log_visit', $dimension->table)) {
                    continue;
                }
                if ($this->logTables()->datetimeColumn($dimension->table) !== '') {
                    return $dimension->table;
                }
                $undated = $undated ?? $dimension->table;
            }
            if ($undated !== null) {
                return $undated;
            }
        }

        return $this->tableOf($scope);
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

        if ($dimension->sql !== '') {
            return $dimension->sql;
        }

        return $dimension->table . '.' . $dimension->column;
    }

    /**
     * The condition and its bound values. "in" and "not_in" come from VisualQueryRunner, which turns a filter on
     * a formatted dimension (country code, device type id...) into the raw values whose label matches.
     *
     * @return array{0: string, 1: array}
     */
    private function filterCondition(string $expression, array $filter): array
    {
        $value = $filter['value'];

        switch ($filter['operator']) {
            case 'equals':
                return [$expression . ' = ?', [$value]];
            case 'not_equals':
                return ['(' . $expression . ' IS NULL OR ' . $expression . ' <> ?)', [$value]];
            case 'contains':
                return [$expression . ' LIKE ?', ['%' . $this->escapeLike($value) . '%']];
            case 'starts_with':
                return [$expression . ' LIKE ?', [$this->escapeLike($value) . '%']];
            case 'ends_with':
                return [$expression . ' LIKE ?', ['%' . $this->escapeLike($value)]];
            case 'not_contains':
            case 'not_starts_with':
            case 'not_ends_with':
                [$positive, $values] = $this->filterCondition($expression, ['operator' => self::NEGATIONS[$filter['operator']]] + $filter);

                return ['(' . $expression . ' IS NULL OR ' . str_replace(' LIKE ', ' NOT LIKE ', $positive) . ')', $values];
            case 'regex':
                return [$expression . ' REGEXP ?', [$value]];
            case 'gt':
                return [$expression . ' > ?', [$value]];
            case 'gte':
                return [$expression . ' >= ?', [$value]];
            case 'lt':
                return [$expression . ' < ?', [$value]];
            case 'lte':
                return [$expression . ' <= ?', [$value]];
            case self::OPERATOR_IN:
                if (!$value) {
                    return ['1 = 0', []];
                }

                return [$expression . ' IN (' . implode(', ', array_fill(0, count($value), '?')) . ')', array_values($value)];
            case self::OPERATOR_NOT_IN:
                if (!$value) {
                    return ['1 = 1', []];
                }

                return ['(' . $expression . ' IS NULL OR ' . $expression . ' NOT IN (' . implode(', ', array_fill(0, count($value), '?')) . '))', array_values($value)];
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

        if (in_array($operator, ['gt', 'gte', 'lt', 'lte'], true)) {
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
        } elseif ($operator !== 'regex') {
            // log_action stores URLs without the protocol and "www.", which go to url_prefix, as the reports show them
            $value = (string) preg_replace('~^(https?://)?(www\.)?~i', '', $value);
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
