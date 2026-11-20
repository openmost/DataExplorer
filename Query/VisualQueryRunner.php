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
use Piwik\Archive\DataTableFactory;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Db;
use Piwik\Period;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Sql\SessionLimits;
use Piwik\Segment;
use Piwik\Segment\SegmentExpression;
use Piwik\Site;

/**
 * Runs a visual definition live over the log tables and shapes the result as a DataTable:
 * nested subtables for 2 or more row dimensions, pivot cells as "<metric> | <column value>" columns.
 *
 * Every statement transfers a bounded number of rows: each level keeps the configured number of rows per table
 * (ranked in SQL by the first metric), child levels and the other metric scopes are restricted to the labels
 * already kept, and "Others" is computed in SQL over the rows that were not kept, so it is exact for every
 * aggregation, distinct counts included.
 *
 * The same collection runs live (explorer preview, with a time budget) and inside archiving (SavedReport), where
 * labels stay raw dimension values formatted at display time and "Others" carries the core summary row label.
 */
class VisualQueryRunner
{
    public const MAX_PIVOT_VALUES = 10;

    /** label of the NULL / empty value in archived tables, formatted at display time */
    public const ARCHIVED_NOT_DEFINED = '__dataexplorer_not_defined__';

    // above this many distinct values a filtered dimension is free text (URL, title), compared as stored
    public const MAX_FILTER_VALUES = 2000;

    /** @var VisualQueryBuilder */
    private $builder;
    /** @var callable(): float */
    private $clock;
    /** @var LabelFormatter */
    private $labels;

    /** @var ReportDefinition */
    private $definition;
    /** @var LogAggregator */
    private $aggregator;
    /** @var array resolution of the definition by the builder, done once per run */
    private $resolved;
    /** @var \Piwik\Db\AdapterInterface */
    private $db;
    /** @var float|null null when the run has no time budget */
    private $deadline;
    /** @var int */
    private $seconds;
    /** @var bool */
    private $archiving = false;
    /** @var int */
    private $mainCap;
    /** @var int */
    private $subCap;

    /**
     * @param callable(): float|null $clock seconds as a float, microtime(true) by default
     */
    public function __construct(?VisualQueryBuilder $builder = null, ?callable $clock = null)
    {
        $this->builder = $builder ?: new VisualQueryBuilder();
        $this->clock = $clock ?: fn () => microtime(true);
        $this->labels = new LabelFormatter();
    }

    /**
     * @throws \InvalidArgumentException with a translated message when the definition cannot be run
     */
    public function run(ReportDefinition $definition, int $idSite, string $period, string $date, string $segment): DataTable
    {
        $resolved = $this->builder->resolve($definition, $idSite);
        $aggregator = $this->aggregator($idSite, $period, $date, self::combineSegments($definition->getSegment(), $segment));
        $depth = count($definition->getRows());

        $this->archiving = false;
        $this->seconds = Configuration::getMaxExecutionTimeSeconds();
        $this->deadline = ($this->clock)() + $this->seconds;

        $table = $this->execute($definition, $resolved, $aggregator, $idSite, Configuration::getMaxRowsInMainTable($depth), Configuration::getMaxRowsInSubTable($depth));
        (new ProcessedMetrics($resolved['processed'], $idSite))->apply($table);

        return $table;
    }

    /**
     * The filters of a definition with the label filters of coded dimensions turned into the stored values they
     * match on the site and period, as run() applies them ("in" / "not_in" lists).
     *
     * @return array[] resolved filters: dimension (DimensionEntry), operator, value
     */
    public function storedFilters(ReportDefinition $definition, int $idSite, string $period, string $date): array
    {
        $resolved = $this->builder->resolve($definition, $idSite);
        $this->definition = $definition;
        $this->resolved = $resolved;
        $this->aggregator = $this->aggregator($idSite, $period, $date, $definition->getSegment());
        $this->db = Db::getReader();
        $this->deadline = null;

        return $this->resolveLabelFilters($resolved, $idSite)['filters'];
    }

    /**
     * Runs a definition without row dimensions once per period, all of them within one time budget: the days of a
     * range or the requested periods, or the periods ending at a single period, at most raw_sql_max_points runs
     * (the most recent), like raw SQL evolutions.
     *
     * @throws \InvalidArgumentException with a translated message when the definition cannot be run
     */
    public function runEvolution(ReportDefinition $definition, int $idSite, string $period, string $date, string $segment): DataTable\Map
    {
        $resolved = $this->builder->resolve($definition, $idSite);
        if ($resolved['rows']) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorRowsOnlyForTable'));
        }

        $site = new Site($idSite);
        $points = Configuration::getRawSqlMaxPoints();
        if ($period === 'range') {
            $period = 'day';
        } elseif (!Period::isMultiplePeriod($date, $period)) {
            $date = Range::getRelativeToEndDate($period, 'last' . $points, $date, $site);
        }
        $subPeriods = (new Range($period, $date, $site->getTimezone()))->getSubperiods();
        $subPeriods = array_slice($subPeriods, -$points);

        $this->archiving = false;
        $this->seconds = Configuration::getMaxExecutionTimeSeconds();
        $this->deadline = ($this->clock)() + $this->seconds;

        $map = new DataTable\Map();
        $map->setKeyName('date');
        foreach ($subPeriods as $subPeriod) {
            $aggregator = $this->aggregator($idSite, $subPeriod->getLabel(), $subPeriod->getDateStart()->toString(), self::combineSegments($definition->getSegment(), $segment));
            $table = $this->execute($definition, $resolved, $aggregator, $idSite, 1, 1);
            $table->setMetadata(DataTableFactory::TABLE_METADATA_PERIOD_INDEX, $subPeriod);
            $table->setMetadata(DataTableFactory::TABLE_METADATA_SITE_INDEX, $site);
            $map->addTable($table, $subPeriod->getLabel() === 'week' ? $subPeriod->getRangeString() : $subPeriod->getPrettyString());
        }
        $processed = ProcessedMetrics::forResolved($resolved, $idSite);
        $processed->apply($map);
        $processed->decorate($map);

        return $map;
    }

    /**
     * Runs a definition for an archive: the LogAggregator carries the site, period and segment of the archive
     * (the stored segment already combined), core manages the archiving time so there is no time budget, the
     * statement row cap still applies.
     *
     * @throws \InvalidArgumentException with a translated message when the definition cannot be run
     */
    public function runForArchive(ReportDefinition $definition, LogAggregator $aggregator, int $mainCap, int $subCap): DataTable
    {
        $sites = $aggregator->getSites();
        $idSite = (int) reset($sites);
        $resolved = $this->builder->resolve($definition, $idSite);

        $this->archiving = true;
        $this->deadline = null;

        return $this->execute($definition, $resolved, $aggregator, $idSite, $mainCap, $subCap);
    }

    /**
     * Stored segment AND requested segment. Plain concatenation: core Segment::combine() drops the condition when
     * it finds it as a substring of the other segment, even inside an OR group; a repeated condition is harmless.
     */
    public static function combineSegments(string $stored, string $requested): string
    {
        return implode(SegmentExpression::AND_DELIMITER, array_filter([trim($stored), trim($requested)], 'strlen'));
    }

    private function execute(ReportDefinition $definition, array $resolved, LogAggregator $aggregator, int $idSite, int $mainCap, int $subCap): DataTable
    {
        $this->definition = $definition;
        $this->resolved = $resolved;
        $this->aggregator = $aggregator;
        $this->mainCap = $mainCap;
        $this->subCap = $subCap;
        $this->db = Db::getReader();

        $metricIds = array_map(fn (MetricEntry $m) => $m->id, $resolved['metrics']);
        $restore = $this->deadline === null ? null : SessionLimits::restoreStatement($this->db);
        $failure = null;

        try {
            $resolved = $this->resolveLabelFilters($resolved, $idSite);
            $this->resolved = $resolved;

            if (!$resolved['rows']) {
                $totals = [];
                foreach ($resolved['scopes'] as $scope) {
                    foreach ($this->fetch(['scope' => $scope, 'labels' => 0, 'limit' => 1]) as $row) {
                        $totals += $this->parse($row)['metrics'];
                    }
                }
                $table = new DataTable\Simple();
                $table->addRowsFromArray($this->metricColumns($totals, $metricIds));

                return $table;
            }

            $data = $this->collect($resolved);
        } catch (\Exception $e) {
            $failure = $e;
            if ($restore !== null && SessionLimits::isTimeout($this->db, $e)) {
                throw $this->timeout($e);
            }
            throw $e;
        } finally {
            try {
                if ($restore !== null) {
                    $this->db->query($restore);
                }
            } catch (\Exception $restoreFailure) {
                if ($failure === null) {
                    throw $restoreFailure;
                }
            }
        }

        $pivotLabels = [];
        foreach ($data['pivotValues'] as $pivotKey => $value) {
            $pivotLabels[$pivotKey] = $this->archiving ? $this->archivedLabel($value) : $this->label($resolved['column'], $value, $idSite);
        }

        $context = $data + [
            'rows' => $resolved['rows'],
            'metricIds' => $metricIds,
            'pivotLabels' => $pivotLabels,
            'aggregationOps' => $this->aggregationOps($resolved['metrics'], $pivotLabels),
            'idSite' => $idSite,
        ];

        return $this->buildTable($context, 0, $this->key([]));
    }

    /**
     * @return array{nodes: array, children: array, others: array, pivotValues: array}
     */
    private function collect(array $resolved): array
    {
        $depth = count($resolved['rows']);
        $mainCap = $this->mainCap;
        $subCap = $this->subCap;
        $primary = $resolved['scopes'][0];
        $hasPivot = $resolved['column'] !== null;

        $pivotValues = [];
        if ($hasPivot) {
            foreach ($this->fetch(['scope' => $primary, 'labels' => 0, 'pivot' => true, 'limit' => self::MAX_PIVOT_VALUES]) as $row) {
                $value = $this->normalizeValue($row['pivot'] ?? null);
                $pivotValues[$this->key([$value])] = $value;
            }
        }
        $pivotIn = array_values($pivotValues);

        $nodes = [];
        $children = [];
        $others = [];
        $parents = [[]];

        for ($level = 0; $level < $depth && $parents; $level++) {
            $cap = $level === 0 ? $mainCap : $subCap;
            if ($level === 0) {
                $limit = min($cap + 1, Configuration::getSqlRowLimit());
                $rows = $this->fetch(['scope' => $primary, 'labels' => 1, 'include' => null, 'limit' => $limit]);
                $overflow = count($rows) >= $limit;
            } else {
                // ranked per parent, so batches of parents return the rows of one statement
                $rows = [];
                $overflow = false;
                foreach ($this->chunks($parents) as $chunk) {
                    $limit = min(count($chunk) * ($cap + 1), Configuration::getSqlRowLimit());
                    $fetched = $this->fetch(['scope' => $primary, 'labels' => $level + 1, 'include' => [$level, $chunk],
                        'limit' => $limit, 'rankPerParent' => $cap]);
                    $overflow = $overflow || count($fetched) >= $limit;
                    array_push($rows, ...$fetched);
                }
            }

            $kept = [];
            foreach ($rows as $row) {
                $parsed = $this->parse($row);
                $parentKey = $this->key(array_slice($parsed['values'], 0, $level));
                if (count($children[$level][$parentKey] ?? []) >= $cap) {
                    $overflow = true;
                    continue;
                }
                $key = $this->key($parsed['values']);
                $children[$level][$parentKey][] = $key;
                $nodes[$level][$key] = ['values' => $parsed['values'], 'metrics' => $parsed['metrics'], 'pivot' => []];
                $kept[] = $parsed['values'];
            }
            if (!$kept) {
                break;
            }

            foreach (array_slice($resolved['scopes'], 1) as $scope) {
                $specOf = fn (array $chunk) => ['scope' => $scope, 'labels' => $level + 1, 'include' => [$level + 1, $chunk],
                    'limit' => count($chunk) + 1];
                foreach ($this->fetchChunked($kept, $specOf) as $row) {
                    $parsed = $this->parse($row);
                    $key = $this->key($parsed['values']);
                    if (isset($nodes[$level][$key])) {
                        $nodes[$level][$key]['metrics'] += $parsed['metrics'];
                    }
                }
            }

            if ($pivotIn) {
                foreach ($resolved['scopes'] as $scope) {
                    $specOf = fn (array $chunk) => ['scope' => $scope, 'labels' => $level + 1, 'pivot' => true,
                        'pivotIn' => $pivotIn, 'include' => [$level + 1, $chunk], 'limit' => count($chunk) * count($pivotIn) + 1];
                    foreach ($this->fetchChunked($kept, $specOf) as $row) {
                        $parsed = $this->parse($row);
                        $key = $this->key($parsed['values']);
                        if (isset($nodes[$level][$key])) {
                            $pivotKey = $this->key([$this->normalizeValue($row['pivot'] ?? null)]);
                            $nodes[$level][$key]['pivot'][$pivotKey] = $parsed['metrics'] + ($nodes[$level][$key]['pivot'][$pivotKey] ?? []);
                        }
                    }
                }
            }

            // the other scopes always get an "Others" query: their labels missing from the first scope land there
            $othersScopes = $overflow ? $resolved['scopes'] : array_slice($resolved['scopes'], 1);
            if ($othersScopes) {
                $this->collectOthers($othersScopes, $level, $parents, $kept, $pivotIn, $others);
            }

            $parents = $kept;
        }

        return ['nodes' => $nodes, 'children' => $children, 'others' => $others, 'pivotValues' => $pivotValues];
    }

    /**
     * "Others" of every table of a level: the rows of each parent that were not kept, aggregated in SQL.
     */
    private function collectOthers(array $scopes, int $level, array $parents, array $kept, array $pivotIn, array &$others): void
    {
        foreach ($this->othersBatches($level, $parents, $kept) as [$batchParents, $batchKept]) {
            $base = ['labels' => $level, 'exclude' => [$level + 1, $batchKept], 'limit' => count($batchParents) + 1];
            if ($level > 0) {
                $base['include'] = [$level, $batchParents];
            }
            $this->collectOthersOf($scopes, $base, count($batchParents), $pivotIn, $level, $others);
        }
    }

    /**
     * Parents grouped with their own kept rows, at most the chunk size of tuples per batch: the Others of a parent
     * only exclude its own kept rows. A parent holding more kept rows than the chunk size forms a batch alone,
     * bounded by the row cap.
     *
     * @return array[] [[parents, kept rows of these parents], ...]
     */
    private function othersBatches(int $level, array $parents, array $kept): array
    {
        if ($level === 0) {
            return [[$parents, $kept]];
        }

        $keptByParent = [];
        foreach ($kept as $values) {
            $keptByParent[$this->key(array_slice($values, 0, $level))][] = $values;
        }

        $size = Configuration::getTupleChunkSize();
        $batches = [];
        $batchParents = [];
        $batchKept = [];
        foreach ($parents as $parent) {
            $own = $keptByParent[$this->key($parent)] ?? [];
            if ($batchParents && count($batchParents) + count($batchKept) + 1 + count($own) > $size) {
                $batches[] = [$batchParents, $batchKept];
                $batchParents = [];
                $batchKept = [];
            }
            $batchParents[] = $parent;
            array_push($batchKept, ...$own);
        }
        if ($batchParents) {
            $batches[] = [$batchParents, $batchKept];
        }

        return $batches;
    }

    private function collectOthersOf(array $scopes, array $base, int $parentCount, array $pivotIn, int $level, array &$others): void
    {
        foreach ($scopes as $scope) {
            foreach ($this->fetch(['scope' => $scope] + $base) as $row) {
                $parsed = $this->parse($row);
                if (!array_filter($parsed['metrics'])) {
                    continue;
                }
                $parentKey = $this->key($parsed['values']);
                $others[$level][$parentKey]['metrics'] = $parsed['metrics'] + ($others[$level][$parentKey]['metrics'] ?? []);
            }

            if ($pivotIn) {
                $spec = ['scope' => $scope, 'pivot' => true, 'pivotIn' => $pivotIn, 'limit' => ($parentCount * count($pivotIn)) + 1] + $base;
                foreach ($this->fetch($spec) as $row) {
                    $parsed = $this->parse($row);
                    $parentKey = $this->key($parsed['values']);
                    $pivotKey = $this->key([$this->normalizeValue($row['pivot'] ?? null)]);
                    $others[$level][$parentKey]['pivot'][$pivotKey] = $parsed['metrics'] + ($others[$level][$parentKey]['pivot'][$pivotKey] ?? []);
                }
            }
        }
    }

    /**
     * @return array[][] $tuples in batches of the chunk size
     */
    private function chunks(array $tuples): array
    {
        return array_chunk($tuples, Configuration::getTupleChunkSize());
    }

    /**
     * One statement per batch of $tuples, rows merged.
     *
     * @param callable(array): array $specOf spec of the statement filtering on one batch
     */
    private function fetchChunked(array $tuples, callable $specOf): array
    {
        $rows = [];
        foreach ($this->chunks($tuples) as $chunk) {
            array_push($rows, ...$this->fetch($specOf($chunk)));
        }

        return $rows;
    }

    /**
     * Runs one statement inside the time left of the run, capped at the SQL row limit.
     */
    private function fetch(array $spec, ?array $resolved = null): array
    {
        $spec['limit'] = min(max(1, (int) $spec['limit']), Configuration::getSqlRowLimit());
        $query = $this->builder->buildQuery($this->definition, $this->aggregator, $spec, $resolved ?? $this->resolved);

        if ($this->deadline !== null) {
            $remaining = (int) floor(($this->deadline - ($this->clock)()) * 1000);
            if ($remaining < 1) {
                throw $this->timeout();
            }
            $this->db->query(SessionLimits::forCurrentServerMilliseconds($this->db, $remaining));
        }

        return $this->db->fetchAll($query['sql'], $query['bind']);
    }

    private function timeout(?\Throwable $previous = null): \InvalidArgumentException
    {
        return new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorQueryTimeout', [$this->seconds]), 0, $previous);
    }

    /**
     * @return array{values: array, metrics: array}
     */
    private function parse(array $row): array
    {
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

        return ['values' => array_values($values), 'metrics' => $metrics];
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

    private function buildTable(array $context, int $level, string $parentKey): DataTable
    {
        $table = new DataTable();
        $table->setMetadata(DataTable::COLUMN_AGGREGATION_OPS_METADATA_NAME, $context['aggregationOps']);

        $dimension = $context['rows'][$level];
        foreach ($context['children'][$level][$parentKey] ?? [] as $key) {
            $node = $context['nodes'][$level][$key];
            $value = $node['values'][$level];

            $label = $this->archiving ? $this->archivedLabel($value) : $this->label($dimension, $value, $context['idSite']);
            $columns = ['label' => $label];
            $columns += $this->cellColumns($node, $context);

            $row = new Row([Row::COLUMNS => $columns, Row::METADATA => ['value' => $value]]);
            if ($level + 1 < count($context['rows']) && (isset($context['children'][$level + 1][$key]) || isset($context['others'][$level + 1][$key]))) {
                $row->setSubtable($this->buildTable($context, $level + 1, $key));
            }
            $table->addRow($row);
        }

        if (isset($context['others'][$level][$parentKey])) {
            $others = $context['others'][$level][$parentKey] + ['metrics' => [], 'pivot' => []];
            $label = $this->archiving ? DataTable::LABEL_SUMMARY_ROW : Piwik::translate('General_Others');
            $columns = ['label' => $label] + $this->cellColumns($others, $context);
            $table->addSummaryRow(new Row([Row::COLUMNS => $columns]));
        }

        return $table;
    }

    private function cellColumns(array $cell, array $context): array
    {
        $columns = $this->metricColumns($cell['metrics'], $context['metricIds']);
        foreach ($context['pivotLabels'] as $pivotKey => $pivotLabel) {
            foreach ($context['metricIds'] as $index => $metricId) {
                $columns[$metricId . ' | ' . $pivotLabel] = $cell['pivot'][$pivotKey][$index] ?? 0;
            }
        }

        return $columns;
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
            $op = $metric->aggregationOperation();
            $ops[$metric->id] = $op;
            foreach ($pivotLabels as $label) {
                $ops[$metric->id . ' | ' . $label] = $op;
            }
        }

        return $ops;
    }

    /**
     * A filter compares what the user reads: "France", "Smartphone", "Chrome". The logs store a code or an id
     * for those dimensions, so the filter becomes the list of stored values whose label, or the value itself,
     * matches. Compared as stored, "Smartphone" would even match device type 0 (Desktop): MySQL casts the text to 0.
     */
    private function resolveLabelFilters(array $resolved, int $idSite): array
    {
        foreach ($resolved['filters'] as $index => $filter) {
            $negated = VisualQueryBuilder::NEGATIONS[$filter['operator']] ?? null;
            $operator = $negated ?? $filter['operator'];
            if (!in_array($operator, ['equals', 'contains', 'starts_with', 'ends_with', 'regex'], true)) {
                continue;
            }

            $rows = $this->fetch([
                'scope' => $resolved['scopes'][0],
                'labels' => 1,
                'limit' => self::MAX_FILTER_VALUES + 1,
            ], ['rows' => [$filter['dimension']], 'column' => null, 'filters' => []] + $resolved);
            if (count($rows) > self::MAX_FILTER_VALUES) {
                continue;
            }

            $matching = [];
            $formatted = false;
            foreach ($rows as $row) {
                $raw = $row['label_0'] ?? null;
                if ($raw === null) {
                    continue;
                }
                $label = $this->label($filter['dimension'], $raw, $idSite);
                $formatted = $formatted || $label !== (string) $raw;
                if (LabelFilter::matches($operator, (string) $filter['value'], $label, (string) $raw)) {
                    $matching[] = $raw;
                }
            }
            if (!$formatted) {
                continue;
            }

            $resolved['filters'][$index] = [
                'dimension' => $filter['dimension'],
                'operator' => $negated !== null ? VisualQueryBuilder::OPERATOR_NOT_IN : VisualQueryBuilder::OPERATOR_IN,
                'value' => $matching,
                'group' => $filter['group'],
            ];
        }

        return $resolved;
    }

    private function label(DimensionEntry $entry, $value, int $idSite): string
    {
        return $this->labels->format($entry, $value, $idSite);
    }

    private function archivedLabel($value)
    {
        return $value === null ? self::ARCHIVED_NOT_DEFINED : $value;
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
