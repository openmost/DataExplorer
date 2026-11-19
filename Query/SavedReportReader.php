<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\Archive;
use Piwik\Archive\DataTableFactory;
use Piwik\DataTable;
use Piwik\DataTable\DataTableInterface;
use Piwik\Period;
use Piwik\Piwik;
use Piwik\Period\Range;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Archiving\BrowserArchiver;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\RecordBuilders\SavedReport;
use Piwik\Plugins\DataExplorer\Sql\RawSqlRunner;
use Piwik\Site;

/**
 * Data of a saved report at display time: visual reports from their archives (current revision only), with the
 * raw archived values turned into display labels; raw SQL reports run live through the cache.
 */
class SavedReportReader
{
    /** table metadata naming the raw SQL column shown as the label */
    public const LABEL_COLUMN_METADATA = 'dataexplorer_label_column';

    /** table metadata giving the row dimension level of a table: 0 the report, 1 a subtable, ... */
    public const DEPTH_METADATA = 'dataexplorer_depth';

    /** @var RawSqlRunner */
    private $sqlRunner;
    /** @var LabelFormatter */
    private $labels;
    /** @var callable(): float */
    private $clock;
    /** @var BrowserArchiver */
    private $browserArchiver;

    /**
     * @param callable(): float|null $clock seconds as a float, microtime(true) by default
     */
    public function __construct(?RawSqlRunner $sqlRunner = null, ?callable $clock = null, ?BrowserArchiver $browserArchiver = null)
    {
        $this->sqlRunner = $sqlRunner ?: new RawSqlRunner();
        $this->labels = new LabelFormatter();
        $this->clock = $clock ?: fn () => microtime(true);
        $this->browserArchiver = $browserArchiver ?: new BrowserArchiver();
    }

    /**
     * @param array $report a hydrated row of ReportsDao
     * @throws \Piwik\NoAccessException when the user may not view the report on this site
     */
    public function read(array $report, int $idSite, string $period, string $date, string $segment = '', bool $expanded = false, int $idSubtable = 0): DataTableInterface
    {
        (new ReportPermissions())->checkCanView($report, $idSite);

        if ($report['type'] === ReportDefinition::TYPE_SQL) {
            return $this->readSql($report, $idSite, $period, $date);
        }

        $definition = ReportDefinition::fromReport($report);
        if ($definition->getReportType() === ReportDefinition::REPORT_TYPE_TABLE) {
            return $this->readTable($report, $definition, $idSite, $period, $date, $segment, $expanded, $idSubtable);
        }

        $names = [];
        foreach ($definition->getMetrics() as $metric) {
            $names[SavedReport::numericRecordName((int) $report['idreport'], (int) $report['revision'], $metric)] = $metric;
        }
        $this->browserArchiver->archiveMissingRecords($report, array_keys($names), $idSite, $period, $date, $segment);

        $table = Archive::build($idSite, $period, $date, $segment)->getDataTableFromNumeric(array_keys($names));
        $table->filter('ReplaceColumnNames', [$names]);

        return $table;
    }

    private function readTable(array $report, ReportDefinition $definition, int $idSite, string $period, string $date, string $segment, bool $expanded, int $idSubtable): DataTableInterface
    {
        $resolved = (new VisualQueryBuilder())->resolve($definition, $idSite);
        $recordName = SavedReport::recordName((int) $report['idreport'], (int) $report['revision']);
        if (!$idSubtable) {
            $this->browserArchiver->archiveMissingRecords($report, [$recordName], $idSite, $period, $date, $segment);
        }

        $table = Archive::createDataTableFromArchive($recordName, $idSite, $period, $date, $segment, $expanded, false, $idSubtable ?: null);

        $depth = $idSubtable ? $this->subtableDepth($recordName, count($resolved['rows']), $idSite, $period, $date, $segment, $idSubtable) : 0;
        $rows = array_slice($resolved['rows'], $depth);
        $table->filter(function (DataTable $table) use ($rows, $resolved, $idSite, $depth) {
            $this->labels->formatArchivedTable($table, $rows, $resolved['column'], $idSite);
            $table->setMetadata(self::DEPTH_METADATA, $depth);
        });

        return $table;
    }

    /**
     * Row dimension level of a subtable: subtable ids say nothing about their depth, so with 3 or more row
     * dimensions the expanded table is searched for the row that owns it.
     */
    private function subtableDepth(string $recordName, int $dimensionCount, int $idSite, string $period, string $date, string $segment, int $idSubtable): int
    {
        if ($dimensionCount <= 2) {
            return 1;
        }

        $expanded = Archive::build($idSite, $period, $date, $segment)->getDataTableExpanded($recordName);
        if ($expanded instanceof DataTable\Map) {
            $tables = $expanded->getDataTables();
            $expanded = $tables ? reset($tables) : null;
        }

        $depth = $expanded instanceof DataTable ? $this->findDepth($expanded, $idSubtable, 1) : null;

        return $depth ?? 1;
    }

    private function findDepth(DataTable $table, int $idSubtable, int $depth): ?int
    {
        foreach ($table->getRows() as $row) {
            // an expanded table renumbers its subtables in memory, the archive id stays in the metadata
            $inDb = $row->getMetadata('idsubdatatable_in_db');
            if ((int) ($inDb !== false ? $inDb : $row->getIdSubDataTable()) === $idSubtable) {
                return $depth;
            }
            $subtable = $row->getSubtable();
            if ($subtable instanceof DataTable) {
                $found = $this->findDepth($subtable, $idSubtable, $depth + 1);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * A table runs once for the requested period. Evolution and KPI card keep the first row and its numeric
     * columns. Several periods (lastN, a date range) run once per sub-period, keeping the most recent ones only,
     * all of them within one max_execution_time_seconds budget: each run gets the time left, a run that times
     * out or no time left stops the whole report with the timeout error.
     */
    private function readSql(array $report, int $idSite, string $period, string $date): DataTableInterface
    {
        $numeric = $report['report_type'] !== ReportDefinition::REPORT_TYPE_TABLE;

        if (!Period::isMultiplePeriod($date, $period)) {
            $table = $this->sqlRunner->runSaved($report, $idSite, $period, $date);

            return $numeric ? $this->firstNumericRow($table) : $this->withLabel($table);
        }

        $site = new Site($idSite);
        $subPeriods = (new Range($period, $date, $site->getTimezone()))->getSubperiods();
        $subPeriods = array_slice($subPeriods, -Configuration::getRawSqlMaxPoints());

        $budget = Configuration::getMaxExecutionTimeSeconds();
        $deadline = ($this->clock)() + $budget;

        $map = new DataTable\Map();
        $map->setKeyName('date');
        foreach ($subPeriods as $subPeriod) {
            $left = (int) ceil($deadline - ($this->clock)());
            if ($left <= 0) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorRawSqlTimeout', [$budget]));
            }
            $table = $this->sqlRunner->runSaved($report, $idSite, $subPeriod->getLabel(), $subPeriod->getDateStart()->toString(), $left);
            $table = $numeric ? $this->firstNumericRow($table) : $this->withLabel($table);
            $table->setMetadata(DataTableFactory::TABLE_METADATA_PERIOD_INDEX, $subPeriod);
            $table->setMetadata(DataTableFactory::TABLE_METADATA_SITE_INDEX, $site);

            $label = $subPeriod->getLabel();
            $map->addTable($table, $label === 'week' ? $subPeriod->getRangeString() : $subPeriod->getPrettyString());
        }

        return $map;
    }

    /**
     * Matomo tables are keyed by their label column: without a "label" column of its own, the first column of
     * the result becomes the label, its name kept in the table metadata for the column header.
     */
    private function withLabel(DataTable $table): DataTable
    {
        $first = $table->getFirstRow();
        if ($first === false || $first->hasColumn('label')) {
            return $table;
        }

        $columns = $first->getColumns();
        $labelColumn = (string) array_key_first($columns);
        foreach ($table->getRows() as $row) {
            $columns = $row->getColumns();
            $label = $columns[$labelColumn] ?? '';
            unset($columns[$labelColumn]);
            $row->setColumns(['label' => $label] + $columns);
        }
        $table->setMetadata(self::LABEL_COLUMN_METADATA, $labelColumn);

        return $table;
    }

    private function firstNumericRow(DataTable $table): DataTable
    {
        $columns = [];
        $first = $table->getFirstRow();
        if ($first !== false) {
            foreach ($first->getColumns() as $name => $value) {
                if (is_numeric($value)) {
                    $columns[$name] = $value + 0;
                }
            }
        }

        $result = new DataTable\Simple();
        $result->addRowsFromArray($columns);

        return $result;
    }
}
