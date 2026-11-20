<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\DataTable;
use Piwik\DataTable\DataTableInterface;
use Piwik\DataTable\Row;
use Piwik\Columns\MetricsList;
use Piwik\Plugin\Metric;
use Piwik\Plugin\ProcessedMetric;
use Piwik\Plugins\DataExplorer\Catalog\SiteContext;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;

/**
 * Rates and averages computed by core from the metrics they read, once those are aggregated for the row and the
 * period: archives and live runs only hold the metrics they read. Each pivot column ("metric|value") gets its own.
 */
final class ProcessedMetrics
{
    /** @var array<string, ProcessedMetric> */
    private $metrics = [];

    /** @var array<string, Metric> core metric of each chosen metric, to format its columns */
    private $formats = [];

    /** @var string[] metrics only read to compute others, dropped from the output */
    private $hidden = [];

    /**
     * @param MetricEntry[] $processed resolve()['processed'], in computing order
     * @param string[] $chosen resolve()['chosen']
     * @param string[] $hidden resolve()['hidden']
     */
    public function __construct(array $processed, int $idSite, ?MetricCatalog $catalog = null, array $chosen = [], array $hidden = [])
    {
        $catalog = $catalog ?: new MetricCatalog();
        foreach ($processed as $entry) {
            $metric = $catalog->processedMetric($entry->id, $idSite);
            if ($metric !== null) {
                $this->metrics[$entry->id] = $metric;
            }
        }
        if ($chosen) {
            $list = SiteContext::run($idSite, fn () => MetricsList::get());
            foreach ($chosen as $id) {
                $metric = $this->metrics[$id] ?? $list->getMetric($id);
                if ($metric instanceof Metric) {
                    $this->formats[$id] = $metric;
                }
            }
        }
        $this->hidden = $hidden;
    }

    /**
     * @param array $resolved VisualQueryBuilder::resolve()
     */
    public static function forResolved(array $resolved, int $idSite): ProcessedMetrics
    {
        return new self($resolved['processed'], $idSite, null, $resolved['chosen'] ?? [], $resolved['hidden'] ?? []);
    }

    /**
     * After the rows are final: core formats every column of a chosen metric (pivot columns included) like the
     * metric, and drops the metrics only read to compute others once it has computed the totals and the processed
     * metrics from them (queued filters run after both).
     */
    public function decorate(DataTableInterface $table): void
    {
        if (!$this->formats && !$this->hidden) {
            return;
        }
        $table->filter(function (DataTable $table) {
            $this->addFormats($table);
            if ($this->hidden) {
                $table->queueFilter(function (DataTable $table) {
                    $this->dropHidden($table);
                });
            }
        });
    }

    private function addFormats(DataTable $table): void
    {
        $formats = [];
        foreach ($table->getRows() as $row) {
            foreach (array_keys($row->getColumns()) as $column) {
                $column = (string) $column;
                $metric = $this->formats[self::metricOf($column)] ?? null;
                if ($metric !== null) {
                    $formats[$column] = new ColumnFormat($column, $metric);
                }
            }
            $subtable = $row->getSubtable();
            if ($subtable instanceof DataTable) {
                $this->addFormats($subtable);
            }
        }
        if ($formats) {
            $existing = $table->getMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME) ?: [];
            $table->setMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME, array_merge($existing, array_values($formats)));
        }
    }

    private function dropHidden(DataTable $table): void
    {
        foreach ($table->getRows() as $row) {
            foreach (array_keys($row->getColumns()) as $column) {
                if (in_array(self::metricOf((string) $column), $this->hidden, true)) {
                    $row->deleteColumn($column);
                }
            }
            $subtable = $row->getSubtable();
            if ($subtable instanceof DataTable) {
                $this->dropHidden($subtable);
            }
        }
        $totals = $table->getMetadata('totals');
        if (is_array($totals)) {
            foreach (array_keys($totals) as $column) {
                if (in_array(self::metricOf((string) $column), $this->hidden, true)) {
                    unset($totals[$column]);
                }
            }
            $table->setMetadata('totals', $totals);
        }
    }

    private static function metricOf(string $column): string
    {
        $position = strpos($column, LabelFormatter::PIVOT_SEPARATOR);

        return $position === false ? $column : substr($column, 0, $position);
    }

    /**
     * @param array<string, ProcessedMetric> $metrics by id, in computing order
     */
    public static function of(array $metrics): ProcessedMetrics
    {
        $instance = new self([], 0);
        $instance->metrics = $metrics;

        return $instance;
    }

    /**
     * @return array<string, ProcessedMetric>
     */
    public function metrics(): array
    {
        return $this->metrics;
    }

    public function apply(DataTableInterface $table): void
    {
        if (!$this->metrics) {
            return;
        }
        $table->filter(function (DataTable $table) {
            $this->applyTable($table);
        });
    }

    private function applyTable(DataTable $table): void
    {
        $metrics = [];
        foreach ($this->metrics as $id => $metric) {
            try {
                if ($metric->beforeCompute(null, $table) !== false) {
                    $metrics[$id] = $metric;
                }
            } catch (\Throwable $e) {
                // a metric needing a report of its own plugin to compute is left out rather than failing the table
            }
        }

        foreach ($table->getRows() as $row) {
            $this->applyRow($row, $metrics);
            $subtable = $row->getSubtable();
            if ($subtable instanceof DataTable) {
                $this->applyTable($subtable);
            }
        }
    }

    /**
     * @param array<string, ProcessedMetric> $metrics
     */
    private function applyRow(Row $row, array $metrics): void
    {
        $bySuffix = ['' => []];
        foreach ($row->getColumns() as $name => $value) {
            $name = (string) $name;
            $position = strpos($name, LabelFormatter::PIVOT_SEPARATOR);
            if ($position === false) {
                $bySuffix[''][$name] = $value;
            } else {
                $bySuffix[substr($name, $position)][substr($name, 0, $position)] = $value;
            }
        }

        foreach ($bySuffix as $suffix => $columns) {
            $view = new Row([Row::COLUMNS => $columns]);
            foreach ($metrics as $id => $metric) {
                if ($row->hasColumn($id . $suffix)) {
                    $view->setColumn($id, $row->getColumn($id . $suffix));
                    continue;
                }
                // a pivot table has no plain metric columns, only the "metric|value" ones
                foreach ((array) $metric->getDependentMetrics() as $dependent) {
                    if (!$view->hasColumn((string) $dependent)) {
                        continue 2;
                    }
                }
                try {
                    $value = $metric->compute($view);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($value !== false) {
                    $view->setColumn($id, $value);
                    $row->setColumn($id . $suffix, $value);
                }
            }
        }
    }
}
