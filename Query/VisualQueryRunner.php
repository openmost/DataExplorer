<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\ArchiveProcessor\Parameters;
use Piwik\Columns\Dimension;
use Piwik\Columns\DimensionsProvider;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Db;
use Piwik\Metrics\Formatter;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;
use Piwik\Plugins\DataExplorer\Catalog\SiteContext;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Sql\SessionLimits;
use Piwik\Segment;
use Piwik\Site;

/**
 * Runs a visual definition live over the log tables and shapes the result as a DataTable:
 * nested subtables for 2 or 3 row dimensions, pivot cells as "<metric> | <column value>" columns.
 */
class VisualQueryRunner
{
    public const MAX_PIVOT_VALUES = 10;

    private const AGGREGATION_OPS = ['sum' => 'sum', 'uniq' => 'sum', 'max' => 'max', 'min' => 'min'];

    /** @var VisualQueryBuilder */
    private $builder;
    /** @var array<string, Dimension|null> */
    private $dimensions = [];

    public function __construct(?VisualQueryBuilder $builder = null)
    {
        $this->builder = $builder ?: new VisualQueryBuilder();
    }

    /**
     * @throws \InvalidArgumentException with a translated message when the definition cannot be run
     */
    public function run(ReportDefinition $definition, int $idSite, string $period, string $date, string $segment): DataTable
    {
        $resolved = $this->builder->resolve($definition, $idSite);
        $aggregator = $this->aggregator($idSite, $period, $date, $this->combineSegments($definition->getSegment(), $segment));

        $levels = max(1, count($resolved['rows']));
        $hasPivot = $resolved['column'] !== null;

        $db = Db::getReader();
        $seconds = Configuration::getMaxExecutionTimeSeconds();
        $db->query(SessionLimits::forCurrentServer($db, $seconds));
        try {
            $cells = [];
            for ($level = 0; $level < $levels; $level++) {
                foreach ($resolved['scopes'] as $scope) {
                    $this->merge($cells, $level, $this->fetch($db, $definition, $aggregator, $level, $scope, false), false);
                    if ($hasPivot) {
                        $this->merge($cells, $level, $this->fetch($db, $definition, $aggregator, $level, $scope, true), true);
                    }
                }
            }
        } catch (\Exception $e) {
            if (SessionLimits::isTimeout($db, $e)) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorQueryTimeout', [$seconds]), 0, $e);
            }
            throw $e;
        } finally {
            $db->query(SessionLimits::forCurrentServer($db, 0));
        }

        $metricIds = array_map(fn (MetricEntry $m) => $m->id, $resolved['metrics']);

        if (!$resolved['rows']) {
            $table = new DataTable\Simple();
            $table->addRowsFromArray($this->metricColumns($cells[0][$this->key([])]['metrics'] ?? [], $metricIds));

            return $table;
        }

        $formatter = new Formatter();
        $pivotValues = $hasPivot ? $this->topPivotValues($cells[0] ?? []) : [];
        $pivotLabels = [];
        foreach ($pivotValues as $value) {
            $pivotLabels[$this->key([$value])] = $this->label($resolved['column'], $value, $idSite, $formatter);
        }

        $children = [];
        for ($level = 1; $level < $levels; $level++) {
            foreach ($cells[$level] ?? [] as $key => $cell) {
                $children[$level][$this->key(array_slice($cell['values'], 0, $level))][] = $key;
            }
        }

        $context = [
            'cells' => $cells,
            'children' => $children,
            'rows' => $resolved['rows'],
            'metricIds' => $metricIds,
            'pivotValues' => $pivotValues,
            'pivotLabels' => $pivotLabels,
            'aggregationOps' => $this->aggregationOps($resolved['metrics'], $pivotLabels),
            'idSite' => $idSite,
            'formatter' => $formatter,
        ];

        $table = $this->buildTable($context, 0, array_keys($cells[0] ?? []));
        $this->truncate($table, Configuration::getPreviewRowLimit(), $metricIds[0]);

        return $table;
    }

    private function aggregator(int $idSite, string $period, string $date, string $segment): LogAggregator
    {
        $site = new Site($idSite);
        $periodObject = PeriodFactory::makePeriodFromQueryParams($site->getTimezone(), $period, $date);
        $segmentObject = new Segment($segment, [$idSite], $periodObject->getDateTimeStart(), $periodObject->getDateTimeEnd());

        $aggregator = new LogAggregator(new Parameters($site, $periodObject, $segmentObject));
        $aggregator->setQueryOriginHint('DataExplorer');

        return $aggregator;
    }

    private function combineSegments(string $stored, string $requested): string
    {
        return implode(';', array_filter([trim($stored), trim($requested)], 'strlen'));
    }

    private function fetch($db, ReportDefinition $definition, LogAggregator $aggregator, int $level, string $scope, bool $withPivot): array
    {
        $query = $this->builder->build($definition, $aggregator, $level, $scope, $withPivot);

        return $db->fetchAll($query['sql'], $query['bind']);
    }

    /**
     * Rows of every scope query are merged by their dimension values, never summed across scopes.
     */
    private function merge(array &$cells, int $level, array $rows, bool $pivot): void
    {
        foreach ($rows as $row) {
            $values = [];
            $metrics = [];
            foreach ($row as $alias => $value) {
                if (strpos($alias, 'label_') === 0) {
                    $values[(int) substr($alias, 6)] = $this->normalizeValue($value);
                } elseif (strpos($alias, 'metric_') === 0) {
                    $metrics[(int) substr($alias, 7)] = $this->number($value);
                }
            }
            ksort($values);
            $values = array_values($values);

            $key = $this->key($values);
            if (!isset($cells[$level][$key])) {
                $cells[$level][$key] = ['values' => $values, 'metrics' => [], 'pivot' => []];
            }

            if ($pivot) {
                $pivotKey = $this->key([$this->normalizeValue($row['pivot'] ?? null)]);
                $cells[$level][$key]['pivot'][$pivotKey] = ($cells[$level][$key]['pivot'][$pivotKey] ?? []) + $metrics;
                $cells[$level][$key]['pivotValues'][$pivotKey] = $this->normalizeValue($row['pivot'] ?? null);
            } else {
                $cells[$level][$key]['metrics'] += $metrics;
            }
        }
    }

    /**
     * Column values ranked by the first metric summed over the first row level, top MAX_PIVOT_VALUES kept.
     */
    private function topPivotValues(array $cells): array
    {
        $totals = [];
        $values = [];
        foreach ($cells as $cell) {
            foreach ($cell['pivot'] as $pivotKey => $metrics) {
                $totals[$pivotKey] = ($totals[$pivotKey] ?? 0) + ($metrics[0] ?? 0);
                $values[$pivotKey] = $cell['pivotValues'][$pivotKey];
            }
        }
        arsort($totals);

        $top = [];
        foreach (array_slice(array_keys($totals), 0, self::MAX_PIVOT_VALUES) as $pivotKey) {
            $top[$pivotKey] = $values[$pivotKey];
        }

        return $top;
    }

    private function buildTable(array $context, int $level, array $keys): DataTable
    {
        $table = new DataTable();
        $table->setMetadata(DataTable::COLUMN_AGGREGATION_OPS_METADATA_NAME, $context['aggregationOps']);

        $dimension = $context['rows'][$level];
        foreach ($keys as $key) {
            $cell = $context['cells'][$level][$key];
            $value = $cell['values'][$level];

            $columns = ['label' => $this->label($dimension, $value, $context['idSite'], $context['formatter'])];
            $columns += $this->metricColumns($cell['metrics'], $context['metricIds']);
            foreach ($context['pivotValues'] as $pivotKey => $pivotValue) {
                foreach ($context['metricIds'] as $index => $metricId) {
                    $columns[$metricId . ' | ' . $context['pivotLabels'][$pivotKey]] = $cell['pivot'][$pivotKey][$index] ?? 0;
                }
            }

            $row = new Row([Row::COLUMNS => $columns, Row::METADATA => ['value' => $value]]);
            if (isset($context['children'][$level + 1][$key])) {
                $row->setSubtable($this->buildTable($context, $level + 1, $context['children'][$level + 1][$key]));
            }
            $table->addRow($row);
        }

        return $table;
    }

    private function metricColumns(array $metrics, array $metricIds): array
    {
        $columns = [];
        foreach ($metricIds as $index => $metricId) {
            $columns[$metricId] = $metrics[$index] ?? 0;
        }

        return $columns;
    }

    private function aggregationOps(array $metrics, array $pivotLabels): array
    {
        $ops = [];
        foreach ($metrics as $metric) {
            $op = self::AGGREGATION_OPS[$metric->aggregation] ?? 'sum';
            $ops[$metric->id] = $op;
            foreach ($pivotLabels as $label) {
                $ops[$metric->id . ' | ' . $label] = $op;
            }
        }

        return $ops;
    }

    /**
     * Keeps the top rows by the first metric and sums the rest into one "Others" summary row, at every level.
     * Unlike the core Truncate filter, a single extra row is summed too so the row count never exceeds the limit.
     */
    private function truncate(DataTable $table, int $limit, string $sortColumn): void
    {
        $table->filter('Sort', [$sortColumn, 'desc', $naturalSort = true, $recursiveSort = false]);

        $rows = array_values($table->getRows());
        if (count($rows) > $limit) {
            $summary = new Row([Row::COLUMNS => ['label' => DataTable::LABEL_SUMMARY_ROW]]);
            $ops = $table->getMetadata(DataTable::COLUMN_AGGREGATION_OPS_METADATA_NAME);
            foreach (array_slice($rows, $limit) as $row) {
                $summary->sumRow($row, $copyMetadata = false, $ops);
            }
            $table->filter('Limit', [0, $limit]);
            $table->addSummaryRow($summary);
            $table->filter('ReplaceSummaryRowLabel');
        }

        foreach ($table->getRowsWithoutSummaryRow() as $row) {
            $subtable = $row->getSubtable();
            if ($subtable) {
                $this->truncate($subtable, $limit, $sortColumn);
            }
        }
    }

    private function label(DimensionEntry $entry, $value, int $idSite, Formatter $formatter): string
    {
        if ($value === null) {
            return Piwik::translate('General_NotDefined', $entry->name);
        }

        $dimension = $this->dimensionObject($entry->id, $idSite);
        if (!$dimension) {
            return (string) $value;
        }

        $formatted = $dimension->formatValue($value, $idSite, $formatter);

        return $formatted === null || $formatted === '' ? (string) $value : (string) $formatted;
    }

    private function dimensionObject(string $id, int $idSite): ?Dimension
    {
        $cacheKey = $idSite . '|' . $id;
        if (!array_key_exists($cacheKey, $this->dimensions)) {
            $this->dimensions[$cacheKey] = SiteContext::run($idSite, fn () => (new DimensionsProvider())->factory($id));
        }

        return $this->dimensions[$cacheKey];
    }

    /**
     * NULL and empty string both mean "not defined", like core reports.
     */
    private function normalizeValue($value)
    {
        return $value === null || $value === '' ? null : $value;
    }

    private function number($value)
    {
        if ($value === null) {
            return 0;
        }
        if (is_numeric($value) && (string) (int) $value === (string) $value) {
            return (int) $value;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    private function key(array $values): string
    {
        return json_encode($values);
    }
}
