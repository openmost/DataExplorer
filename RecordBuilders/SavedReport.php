<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\RecordBuilders;

use Piwik\Archive;
use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Parameters;
use Piwik\ArchiveProcessor\Record;
use Piwik\ArchiveProcessor\RecordBuilder;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\LogAggregator;
use Piwik\Log\LoggerInterface;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\FunnelQueryRunner;
use Piwik\Plugins\DataExplorer\Query\FunnelResult;
use Piwik\Plugins\DataExplorer\Query\VisualQueryBuilder;
use Piwik\Plugins\DataExplorer\Query\VisualQueryRunner;
use Piwik\Segment;
use Piwik\SettingsPiwik;

/**
 * Archives one saved visual report, with the same SQL as the live explorer (VisualQueryRunner).
 *
 * - Table: one blob record, a subtable per extra row dimension, pivot cells as "<metric> | <column value>"
 *   columns of the same row. The top 10 pivot values are picked per archived day, a week or month sums the
 *   columns of its days, so it can show more than 10 of them.
 * - Evolution and KPI card: one numeric record per metric.
 *
 * Periods other than day aggregate the day archives the core way: blobs are summed, numerics follow the metric
 * aggregation. Distinct counts that do not add up across days (unique visitors, not visits) are recomputed from
 * the logs for the periods where core computes unique visitors (enable_processing_unique_visitors_*), like core
 * VisitsSummary; for the other periods their numeric record keeps the highest daily value and their table column
 * is renamed sum_daily_<metric>, like core reports. A table with a pivot
 * column and a max, min or such distinct metric is always rebuilt from the logs for periods other than day: its
 * pivot columns are created per archive, so no aggregation can be declared for them.
 *
 * "Others" of a period aggregated from days is the sum of the daily "Others" rows plus the rows cut when the
 * period is truncated, so its distinct counts are summed per day (an approximation, like core reports).
 */
class SavedReport extends RecordBuilder
{
    private const RECORD_PREFIX = 'DataExplorer_report_';

    /** @var array{0: ArchiveProcessor, 1: ArchiveProcessor}|null the main processor and its sub-period processor */
    private static $subPeriodProcessor = null;

    /** @var array */
    private $report;
    /** @var VisualQueryBuilder */
    private $builder;
    /** @var VisualQueryRunner */
    private $runner;
    /** @var array<int, array|null> resolved definition per site, null when it no longer validates */
    private $resolved = [];
    /** @var ReportDefinition|null|false false until parsed */
    private $definition = false;

    /**
     * @param array $report a hydrated row of ReportsDao
     */
    public function __construct(array $report, ?VisualQueryBuilder $builder = null, ?VisualQueryRunner $runner = null)
    {
        parent::__construct();

        $this->report = $report;
        $this->builder = $builder ?: new VisualQueryBuilder();
        $this->runner = $runner ?: new VisualQueryRunner($this->builder);
    }

    public static function recordName(int $idReport, int $revision): string
    {
        return self::RECORD_PREFIX . $idReport . '_' . $revision;
    }

    public static function numericRecordName(int $idReport, int $revision, string $metric): string
    {
        return self::recordName($idReport, $revision) . '_' . $metric;
    }

    public function getIdReport(): int
    {
        return (int) $this->report['idreport'];
    }

    public function isEnabled(ArchiveProcessor $archiveProcessor): bool
    {
        if (!in_array($this->report['type'] ?? '', [ReportDefinition::TYPE_VISUAL, ReportDefinition::TYPE_FUNNEL], true)) {
            return false;
        }

        $placement = $this->report['placement'] ?? null;
        $idSite = $this->idSite($archiveProcessor);
        if (!$placement instanceof Placement || !$placement->includes($idSite)) {
            return false;
        }

        $definition = $this->definition();
        if ($definition !== null && !$definition->processesSegments()
            && !$archiveProcessor->getParams()->getSegment()->isEmpty()) {
            return false;
        }

        return $this->resolve($idSite) !== null;
    }

    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        $definition = $this->definition();
        if ($definition !== null && $definition->isFunnel()) {
            // the steps, each with its breakdown values: every column adds up across days
            return [
                Record::make(Record::TYPE_BLOB, $this->name())
                    ->setMaxRowsInTable(ReportDefinition::MAX_FUNNEL_STEPS)
                    ->setMaxRowsInSubtable(ReportDefinition::MAX_BREAKDOWN_ROWS + 1)
                    ->setColumnToSortByBeforeTruncation(FunnelResult::STARTED),
            ];
        }
        if ($definition === null || $definition->getType() !== ReportDefinition::TYPE_VISUAL) {
            return [];
        }

        $metrics = $this->resolve($this->idSite($archiveProcessor))['metrics'] ?? [];
        $ops = [];
        $renamed = [];
        foreach ($metrics as $metric) {
            $ops[$metric->id] = $metric->aggregationOperation();
            if ($this->isNotSummableAcrossDays($metric)) {
                $renamed[$metric->id] = 'sum_daily_' . $metric->id;
            }
        }

        $name = $this->name();
        if ($definition->getReportType() === ReportDefinition::REPORT_TYPE_TABLE) {
            $depth = count($definition->getRows());

            // the live preview keeps the cap rows plus "Others", core counts "Others" in the cap
            $record = Record::make(Record::TYPE_BLOB, $name)
                ->setMaxRowsInTable(Configuration::getMaxRowsInMainTable($depth) + 1)
                ->setMaxRowsInSubtable(Configuration::getMaxRowsInSubTable($depth) + 1)
                ->setColumnToSortByBeforeTruncation($this->sortColumn(array_map(fn (MetricEntry $m) => $m->id, $metrics), $renamed))
                ->setBlobColumnAggregationOps($ops)
                ->setColumnToRenameAfterAggregation($renamed);

            return [$record];
        }

        $records = [];
        $numericOps = [];
        foreach ($metrics as $metric) {
            $recordName = $name . '_' . $metric->id;
            $records[] = Record::make(Record::TYPE_NUMERIC, $recordName);
            $numericOps[$recordName] = $ops[$metric->id] ?? 'sum';
        }
        foreach ($metrics as $metric) {
            if ($this->isNotSummableAcrossDays($metric)) {
                $numericOps[$name . '_' . $metric->id] = 'max';
            }
        }
        $this->columnAggregationOps = $numericOps;

        return $records;
    }

    public function buildForNonDayPeriod(ArchiveProcessor $archiveProcessor): void
    {
        if ($this->isEnabled($archiveProcessor) && $this->mustRebuildFromLogs($archiveProcessor)) {
            $this->buildFromLogs($archiveProcessor);

            return;
        }

        parent::buildForNonDayPeriod(self::subPeriodProcessor($archiveProcessor));
    }

    /**
     * Saving a report rebuilds its history in archives of this plugin alone, the archives of all plugins of those
     * sub-periods predate it and lack its records. Core's processor shares one sub-period archive ID cache between
     * all plugins, filled with the archives of all plugins by whichever plugin aggregates first, so a period
     * archived later for all plugins would sum nothing. An Archive of our own selects the archives of this plugin
     * newer than those, and sharing it between the saved reports keeps it to one lookup per sub-period.
     *
     * It must not launch archiving: the main processor already has, and a relaunch could replace a sub-period
     * archive the main processor resolved, so plugins aggregating after us would lose that sub-period. Archived per
     * plugin, the main processor's cache already holds the archives of this plugin.
     */
    private static function subPeriodProcessor(ArchiveProcessor $archiveProcessor): ArchiveProcessor
    {
        $params = $archiveProcessor->getParams();
        if (!Rules::shouldProcessReportsAllPlugins($params->getIdSites(), $params->getSegment(), $params->getPeriod()->getLabel())) {
            return $archiveProcessor;
        }

        if (self::$subPeriodProcessor === null || self::$subPeriodProcessor[0] !== $archiveProcessor) {
            $processor = new ArchiveProcessor($params, $archiveProcessor->getArchiveWriter(), $archiveProcessor->getLogAggregator());
            $processor->setNumberOfVisits($archiveProcessor->getNumberOfVisits(), $archiveProcessor->getNumberOfVisitsConverted());
            $archive = Archive::factory($params->getSegment(), $params->getSubPeriods(), $params->getIdSites());
            $archive->forceFetchingWithoutLaunchingArchiving();
            // stands in for the Archive of ArchiveProcessor::getArchive(), which posts this event for its own
            Piwik::postEvent('ArchiveProcessor.getArchive', [$archive]);
            $processor->archive = $archive;
            self::$subPeriodProcessor = [$archiveProcessor, $processor];
        }

        return self::$subPeriodProcessor[1];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        $definition = $this->definition();
        if ($definition === null) {
            return [];
        }

        if ($definition->isFunnel()) {
            try {
                $table = (new FunnelQueryRunner($this->builder))->runForArchive(
                    $definition,
                    $this->aggregator($archiveProcessor, $definition),
                    $this->idSite($archiveProcessor)
                );
            } catch (\InvalidArgumentException $e) {
                $this->warn('archiving failed', $e);

                return [];
            }

            return [$this->name() => $table];
        }

        try {
            $aggregator = $this->aggregator($archiveProcessor, $definition);
            $depth = count($definition->getRows());
            $table = $this->runner->runForArchive(
                $definition,
                $aggregator,
                Configuration::getMaxRowsInMainTable($depth),
                Configuration::getMaxRowsInSubTable($depth)
            );
        } catch (\InvalidArgumentException $e) {
            // invalid for this site or segment: skipped, while database errors propagate so core retries
            $this->warn('archiving failed', $e);

            return [];
        }

        if ($definition->getReportType() === ReportDefinition::REPORT_TYPE_TABLE) {
            return [$this->name() => $table];
        }

        $totals = $table->getFirstRow();
        $records = [];
        foreach ($this->resolve($this->idSite($archiveProcessor))['metrics'] ?? [] as $metric) {
            $value = $totals ? $totals->getColumn($metric->id) : 0;
            $records[$this->name() . '_' . $metric->id] = $value === false ? 0 : $value;
        }

        return $records;
    }

    /**
     * The archive's own LogAggregator, or one over the same site and period with the stored segment ANDed to the
     * archive segment.
     *
     * @throws \InvalidArgumentException when the stored segment is not valid
     */
    private function aggregator(ArchiveProcessor $archiveProcessor, ReportDefinition $definition): LogAggregator
    {
        $stored = trim($definition->getSegment());
        if ($stored === '') {
            return $archiveProcessor->getLogAggregator();
        }

        $params = $archiveProcessor->getParams();
        $period = $params->getPeriod();
        $idSite = $this->idSite($archiveProcessor);
        $combined = VisualQueryRunner::combineSegments($stored, (string) $params->getSegment()->getString());
        try {
            $segment = new Segment($combined, [$idSite], $period->getDateTimeStart(), $period->getDateTimeEnd());
        } catch (\Exception $e) {
            // a stored segment that no longer parses (plugin deactivated, ...) is a definition problem
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $aggregator = new LogAggregator(new Parameters($params->getSite(), $period, $segment));
        $aggregator->setQueryOriginHint($this->getQueryOriginHint());

        return $aggregator;
    }

    private function mustRebuildFromLogs(ArchiveProcessor $archiveProcessor): bool
    {
        $uniquesEnabled = SettingsPiwik::isUniqueVisitorsEnabled($archiveProcessor->getParams()->getPeriod()->getLabel());
        $hasPivot = $this->definition()->getColumn() !== null;

        foreach ($this->resolve($this->idSite($archiveProcessor))['metrics'] as $metric) {
            $notSummable = $this->isNotSummableAcrossDays($metric);
            if ($notSummable && $uniquesEnabled) {
                return true;
            }
            if ($hasPivot && ($notSummable || in_array($metric->aggregation, ['max', 'min'], true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The sort column must exist in tables summed over several days, where the distinct counts that do not add up
     * are renamed: the first other metric, else the renamed column. Day tables and tables rebuilt from the logs
     * are already within the caps, so they are never sorted for truncation.
     *
     * @param string[] $metricIds
     * @param array<string, string> $renamed
     */
    private function sortColumn(array $metricIds, array $renamed): string
    {
        foreach ($metricIds as $metricId) {
            if (!isset($renamed[$metricId])) {
                return $metricId;
            }
        }

        return $renamed[reset($metricIds)] ?? (string) reset($metricIds);
    }

    /**
     * Visits and actions never span two archived days, so their distinct counts add up; other distinct counts
     * (visitors, values, ...) do not.
     */
    public static function isNotSummableAcrossDays(MetricEntry $metric): bool
    {
        return $metric->aggregation === 'uniq' && !in_array($metric->distinctColumn(), ['idvisit', 'idlink_va'], true);
    }

    private function resolve(int $idSite): ?array
    {
        if (!array_key_exists($idSite, $this->resolved)) {
            $definition = $this->definition();
            $this->resolved[$idSite] = null;
            if ($definition !== null && $definition->isFunnel()) {
                try {
                    foreach ($definition->getSteps() as $step) {
                        $this->builder->funnelStepTable($this->builder->resolveConditions($step['conditions'], $idSite), $step['name']);
                    }
                    $this->resolved[$idSite] = ['metrics' => []];
                } catch (\Throwable $e) {
                    $this->warn('funnel no longer matches the catalogue of site ' . $idSite, $e);
                }
            } elseif ($definition !== null && $definition->getType() === ReportDefinition::TYPE_VISUAL) {
                try {
                    $this->resolved[$idSite] = $this->builder->resolve($definition, $idSite);
                } catch (\Throwable $e) {
                    $this->warn('definition no longer matches the catalogue of site ' . $idSite, $e);
                }
            }
        }

        return $this->resolved[$idSite];
    }

    private function definition(): ?ReportDefinition
    {
        if ($this->definition === false) {
            $this->definition = null;
            try {
                $this->definition = ReportDefinition::fromReport($this->report);
            } catch (\Throwable $e) {
                $this->warn('invalid definition', $e);
            }
        }

        return $this->definition;
    }

    private function name(): string
    {
        return self::recordName($this->getIdReport(), (int) $this->report['revision']);
    }

    private function idSite(ArchiveProcessor $archiveProcessor): int
    {
        return (int) $archiveProcessor->getParams()->getSite()->getId();
    }

    private function warn(string $what, \Throwable $e): void
    {
        StaticContainer::get(LoggerInterface::class)->warning('DataExplorer: saved report {idreport} skipped, {what}: {message}', [
            'idreport' => $this->getIdReport(),
            'what' => $what,
            'message' => $e->getMessage(),
        ]);
    }
}
