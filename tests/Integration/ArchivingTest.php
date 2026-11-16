<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\Archive;
use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Parameters;
use Piwik\Cache;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\Log\NullLogger;
use Piwik\Config;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Db;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Plugins\CoreAdminHome\API as CoreAdminHomeAPI;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\DataExplorer;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\VisualQueryBuilder;
use Piwik\Plugins\DataExplorer\Query\VisualQueryRunner;
use Piwik\Plugins\DataExplorer\RecordBuilders\SavedReport;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Segment;
use Piwik\Site;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Saved visual reports archived by core through the SavedReport RecordBuilder, read back with Archive::build.
 *
 * Archived labels are the raw dimension values (country codes, device type ids), formatted at display time like
 * core reports. Device type ids: 0 desktop, 1 smartphone.
 *
 * @group DataExplorer
 * @group DataExplorerArchivingTest
 * @group Plugins
 */
class ArchivingTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

    private const COUNTRY = 'UserCountry.Country';
    private const DEVICE = 'DevicesDetection.DeviceType';
    private const DESKTOP = '0';
    private const SMARTPHONE = '1';
    private const WEEK = '2024-02-26';

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        FakeAccess::clearAccess(true);
    }

    public function tearDown(): void
    {
        Date::$now = null;

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_countryVisits_areArchivedForDayAndWeek(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $day = $this->table($report, 'day', '2024-03-01');
        $this->assertSame(['fr' => 2], $this->column($day, 'nb_visits'));

        $week = $this->table($report, 'week', self::WEEK);
        $this->assertEquals(['fr' => 3, 'us' => 2, 'de' => 1], $this->column($week, 'nb_visits'));
    }

    public function test_secondRowDimension_isArchivedAsSubtables(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits']]);

        $archive = Archive::build(1, 'week', self::WEEK);
        $week = $archive->getDataTableExpanded(SavedReport::recordName($report['idreport'], $report['revision']), null, null, true);

        $france = $week->getRowFromLabel('fr');
        $this->assertNotFalse($france);
        $this->assertSame([self::DESKTOP => 2, self::SMARTPHONE => 1], $this->column($france->getSubtable(), 'nb_visits'));
    }

    public function test_pivotColumns_areArchived_andSummedOverTheWeek(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => ['nb_visits']]);

        $day = $this->table($report, 'day', '2024-03-01');
        $this->assertSame(['fr' => 1], $this->column($day, 'nb_visits | ' . self::DESKTOP));
        $this->assertSame(['fr' => 1], $this->column($day, 'nb_visits | ' . self::SMARTPHONE));

        $week = $this->table($report, 'week', self::WEEK);
        $this->assertEquals(['fr' => 2, 'us' => 1, 'de' => 1], $this->column($week, 'nb_visits | ' . self::DESKTOP));
        $this->assertEquals(['fr' => 1, 'us' => 1, 'de' => 0], $this->column($week, 'nb_visits | ' . self::SMARTPHONE));
        $this->assertEquals(['fr' => 3, 'us' => 2, 'de' => 1], $this->column($week, 'nb_visits'));
    }

    public function test_evolutionNumericRecords_equalTheVisualRunnerTotals(): void
    {
        $report = $this->save(['report_type' => 'evolution', 'metrics' => ['nb_visits', 'nb_actions', 'nb_conversions']]);
        $definition = ReportDefinition::fromArray($report['definition'] + ['name' => 'preview', 'report_type' => 'evolution']);

        foreach ([['day', '2024-03-02'], ['week', self::WEEK]] as [$period, $date]) {
            $live = (new VisualQueryRunner())->run($definition, 1, $period, $date, '')->getFirstRow();
            $archive = Archive::build(1, $period, $date);

            foreach (['nb_visits', 'nb_actions', 'nb_conversions'] as $metric) {
                $name = SavedReport::numericRecordName($report['idreport'], $report['revision'], $metric);
                $this->assertEquals($live->getColumn($metric), $this->numeric($archive, $name), "$period $metric");
            }
        }

        $week = Archive::build(1, 'week', self::WEEK);
        $this->assertEquals(6, $this->numeric($week, SavedReport::numericRecordName($report['idreport'], $report['revision'], 'nb_visits')));
    }

    public function test_kpiCard_isArchivedAsOneNumericRecord(): void
    {
        $report = $this->save(['report_type' => 'kpicard', 'metrics' => ['nb_actions']]);

        $archive = Archive::build(1, 'week', self::WEEK);

        $this->assertEquals(10, $this->numeric($archive, SavedReport::numericRecordName($report['idreport'], $report['revision'], 'nb_actions')));
    }

    public function test_uniqueVisitors_areRecomputedFromLogsForTheWeek_likeCore(): void
    {
        $kpi = $this->save(['report_type' => 'kpicard', 'metrics' => ['nb_uniq_visitors']]);
        $table = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits', 'nb_uniq_visitors']]);

        // one visitor, one visit on 2024-03-05 and one on 2024-03-06
        $archive = Archive::build(1, 'week', '2024-03-04');
        $this->assertEquals(1, $this->numeric($archive, SavedReport::numericRecordName($kpi['idreport'], $kpi['revision'], 'nb_uniq_visitors')));

        $week = $this->table($table, 'week', '2024-03-04');
        $this->assertEquals(['fr' => 2], $this->column($week, 'nb_visits'));
        $this->assertEquals(['fr' => 1], $this->column($week, 'nb_uniq_visitors'));
    }

    public function test_uniqueVisitors_keepTheHighestDailyValue_whenCoreDoesNotComputeThemForThePeriod(): void
    {
        $general = Config::getInstance()->General;
        $general['enable_processing_unique_visitors_week'] = 0;
        Config::getInstance()->General = $general;
        $kpi = $this->save(['report_type' => 'evolution', 'metrics' => ['nb_uniq_visitors', 'nb_visits']]);

        $archive = Archive::build(1, 'week', '2024-03-04');
        $this->assertEquals(1, $this->numeric($archive, SavedReport::numericRecordName($kpi['idreport'], $kpi['revision'], 'nb_uniq_visitors')));
        $this->assertEquals(2, $this->numeric($archive, SavedReport::numericRecordName($kpi['idreport'], $kpi['revision'], 'nb_visits')));
    }

    public function test_uniqueVisitorsOfATable_areRenamedSumDaily_whenCoreDoesNotComputeThemForThePeriod(): void
    {
        $this->disableUniques(['week', 'month', 'year']);
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_uniq_visitors', 'nb_visits']]);

        $france = $this->table($report, 'year', '2024-01-01')->getRowFromLabel('fr');

        // fr visitors 1, 2 and 6 on one day each, visitor 7 on two days: 4 distinct, 5 summed per day
        $this->assertNotFalse($france);
        $this->assertEquals(5, $france->getColumn('sum_daily_nb_uniq_visitors'));
        $this->assertFalse($france->getColumn('nb_uniq_visitors'));
        $this->assertEquals(5, $france->getColumn('nb_visits'));
    }

    public function test_uniqueVisitorsOfATable_keepTheirNameAndTrueValue_whenCoreComputesThemForThePeriod(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_uniq_visitors']]);

        $france = $this->table($report, 'week', '2024-03-04')->getRowFromLabel('fr');

        $this->assertEquals(1, $france->getColumn('nb_uniq_visitors'));
        $this->assertFalse($france->getColumn('sum_daily_nb_uniq_visitors'));
    }

    public function test_reportPlacedOnSite1_producesNoRecordOnSite2(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], ['sites' => [1]]);

        $this->assertSame(0, $this->table($report, 'day', '2024-03-02', '', 2)->getRowsCount());
        $this->assertNotEmpty($this->column($this->table($report, 'day', '2024-03-02', '', 1), 'nb_visits'));
    }

    public function test_reportOnAllWebsites_isArchivedForEverySite(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], ['all' => true]);

        $this->assertSame(['de' => 1], $this->column($this->table($report, 'day', '2024-03-03', '', 2), 'nb_visits'));
    }

    public function test_savedSegment_isAndedWithTheArchiveSegment(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits'], 'segment' => 'countryCode==fr']);

        $week = $this->table($report, 'week', self::WEEK, 'deviceType==desktop');
        $this->assertSame(['fr' => 2], $this->column($week, 'nb_visits'));

        $noSegment = $this->table($report, 'week', self::WEEK);
        $this->assertSame(['fr' => 3], $this->column($noSegment, 'nb_visits'));
    }

    public function test_conversionsUnderAnActionSegment_countEveryConversion(): void
    {
        $definition = ['rows' => [self::COUNTRY], 'metrics' => ['nb_conversions']];
        $report = $this->save($definition);

        $archived = $this->table($report, 'day', '2024-03-05', 'pageUrl=@pricing');
        $this->assertSame(['fr' => ExplorerFixture::MULTIPLE_CONVERSIONS], $this->column($archived, 'nb_conversions'));

        $live = (new VisualQueryRunner())->run(ReportDefinition::fromArray($definition + ['name' => 'preview']), 1, 'day', '2024-03-05', 'pageUrl=@pricing');
        $this->assertSame(['France' => ExplorerFixture::MULTIPLE_CONVERSIONS], $this->column($live, 'nb_conversions'));
    }

    public function test_rowCaps_applyToArchives_withCoreSummaryRowLabel(): void
    {
        Config::getInstance()->DataExplorer = ['datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 1];
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        // like the live preview: the cap counts the kept rows, "Others" comes on top
        $day = $this->table($report, 'day', '2024-03-02');
        $this->assertSame(2, $day->getRowsCount());
        $others = $day->getRowFromId(DataTable::ID_SUMMARY_ROW);
        $this->assertNotFalse($others);
        $this->assertEquals(DataTable::LABEL_SUMMARY_ROW, $others->getColumn('label'));
        $this->assertSame(1, $others->getColumn('nb_visits'));

        $week = $this->table($report, 'week', self::WEEK);
        $this->assertSame(2, $week->getRowsCount());
        $this->assertNotFalse($week->getRowFromId(DataTable::ID_SUMMARY_ROW));
        $this->assertEquals(6, array_sum($this->column($week, 'nb_visits')));
    }

    public function test_recordBuilders_skipSqlAndInvalidReports_andNeverThrow(): void
    {
        $visual = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        $this->save(['type' => 'sql', 'sql' => 'SELECT 1'], ['sites' => [1]], 'sql');
        $broken = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        Db::query('UPDATE ' . Common::prefixTable('dataexplorer_report') . ' SET definition = ? WHERE idreport = ?', [
            json_encode(['rows' => ['Nope.Nope'], 'metrics' => ['nb_visits']]), $broken['idreport'],
        ]);

        $builders = [];
        (new DataExplorer())->addRecordBuilders($builders);
        $processor = $this->processor(1, 'day', '2024-03-01');

        $enabled = [];
        foreach ($builders as $builder) {
            $this->assertInstanceOf(SavedReport::class, $builder);
            if ($builder->isEnabled($processor)) {
                $enabled[] = $builder->getIdReport();
            }
        }
        $this->assertSame([$visual['idreport']], $enabled);

        // the broken report archives nothing and does not stop the others
        $this->assertSame(['fr' => 2], $this->column($this->table($visual, 'day', '2024-03-01'), 'nb_visits'));
        $this->assertSame(0, $this->table($broken, 'day', '2024-03-01')->getRowsCount());
    }

    public function test_requestedSite_doesNotHideTheReportsOfAnotherArchivedSite(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], ['sites' => [2]]);
        $backup = $_GET;
        $_GET['idSite'] = '1';

        try {
            $this->assertSame(['de' => 1], $this->column($this->table($report, 'day', '2024-03-03', '', 2), 'nb_visits'));
        } finally {
            $_GET = $backup;
        }
    }

    public function test_databaseErrors_propagateSoCoreRetries_whileInvalidRunsAreSkipped(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $skipped = new SavedReport($report, null, $this->failingRunner(new \InvalidArgumentException('invalid here')));
        $skipped->buildFromLogs($this->processor(1, 'day', '2024-03-01'));

        $failing = new SavedReport($report, null, $this->failingRunner(new \RuntimeException('server has gone away')));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('server has gone away');
        $failing->buildFromLogs($this->processor(1, 'day', '2024-03-01'));
    }

    public function test_malformedDefinition_isSkippedWithAWarning_evenOnATypeError(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        $logger = new class extends NullLogger {
            public $warnings = [];

            public function log($level, $message, array $context = array())
            {
                $this->warnings[] = [$level, $message, $context];
            }
        };
        StaticContainer::getContainer()->set(LoggerInterface::class, $logger);
        $builder = new class extends VisualQueryBuilder {
            public function resolve(ReportDefinition $definition, int $idSite): array
            {
                throw new \TypeError('malformed');
            }
        };

        $savedReport = new SavedReport($report, $builder);

        $this->assertFalse($savedReport->isEnabled($this->processor(1, 'day', '2024-03-01')));
        $this->assertCount(1, $logger->warnings);
        $this->assertSame('warning', $logger->warnings[0][0]);
        $this->assertSame('malformed', $logger->warnings[0][2]['message']);
    }

    public function test_savedSegment_isKeptWhenTheRequestedSegmentContainsItInAnOrGroup(): void
    {
        $saved = 'countryCode==fr';
        $requested = 'deviceType==desktop;countryCode==fr,countryCode==de';
        $this->assertSame($saved . ';' . $requested, VisualQueryRunner::combineSegments($saved, $requested));

        $definition = ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits'], 'segment' => $saved];
        $report = $this->save($definition);
        $this->assertSame(['fr' => 2], $this->column($this->table($report, 'week', self::WEEK, $requested), 'nb_visits'));

        $live = (new VisualQueryRunner())->run(ReportDefinition::fromArray($definition + ['name' => 'preview']), 1, 'week', self::WEEK, $requested);
        $this->assertSame(['France' => 2], $this->column($live, 'nb_visits'));
    }

    public function test_pivotOfAMaxMetric_isRebuiltFromLogsForTheWeek(): void
    {
        $metric = 'max_corehome_visittotaltime';
        $definition = ['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => [$metric]];
        $report = $this->save($definition);
        $column = $metric . ' | ' . self::DESKTOP;

        $days = $this->column($this->table($report, 'day', '2024-03-05'), $column)['fr']
            + $this->column($this->table($report, 'day', '2024-03-06'), $column)['fr'];
        $week = $this->column($this->table($report, 'week', '2024-03-04'), $column)['fr'];
        $live = (new VisualQueryRunner())->run(ReportDefinition::fromArray($definition + ['name' => 'preview']), 1, 'week', '2024-03-04', '');

        $this->assertEquals($this->column($live, $metric . ' | Desktop')['France'], $week);
        $this->assertGreaterThan(0, $week);
        $this->assertNotEquals($days, $week);
    }

    public function test_pivotOfUniqueVisitors_isRebuiltFromLogs_whenCoreDoesNotComputeUniquesForThePeriod(): void
    {
        $this->disableUniques(['week']);
        $report = $this->save(['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => ['nb_uniq_visitors']]);

        $week = $this->table($report, 'week', '2024-03-04');

        $this->assertEquals(['fr' => 1], $this->column($week, 'nb_uniq_visitors | ' . self::DESKTOP));
        $this->assertEquals(['fr' => 1], $this->column($week, 'nb_uniq_visitors'));
    }

    public function test_truncationOfSummedPeriods_sortsByUniqueVisitors(): void
    {
        $this->disableUniques(['week', 'month', 'year']);
        Config::getInstance()->DataExplorer = ['datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 1];
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_uniq_visitors']]);

        $year = $this->table($report, 'year', '2024-01-01');

        $this->assertSame(2, $year->getRowsCount());
        $this->assertSame('fr', (string) $year->getFirstRow()->getColumn('label'));
        $this->assertNotFalse($year->getRowFromId(DataTable::ID_SUMMARY_ROW));
    }

    public function test_changingTheDefinition_bumpsTheRevision_andArchivesTheNewRecord(): void
    {
        Date::$now = strtotime('2024-03-10 12:00:00');
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        $this->assertSame(['fr' => 2], $this->column($this->table($report, 'day', '2024-03-01'), 'nb_visits'));

        $dao = new ReportsDao();
        $dao->update($report['idreport'], [
            'name' => $report['name'],
            'type' => 'visual',
            'report_type' => 'table',
            'definition' => ['rows' => [self::DEVICE], 'metrics' => ['nb_visits']],
        ], $report['placement']);
        $updated = $dao->get($report['idreport']);
        $this->assertSame($report['revision'] + 1, $updated['revision']);

        (new DataExplorer())->invalidateHistory($updated);
        // the record builders of the request were cached with the previous revision
        Cache::getTransientCache()->flushAll();

        $builders = [];
        (new DataExplorer())->addRecordBuilders($builders);
        $names = [];
        foreach ($builders as $builder) {
            foreach ($builder->getRecordMetadata($this->processor(1, 'day', '2024-03-01')) as $record) {
                $names[] = $record->getName();
            }
        }
        $this->assertSame([SavedReport::recordName($updated['idreport'], $updated['revision'])], $names);

        $invalidations = Db::fetchAll('SELECT name FROM ' . Common::prefixTable('archive_invalidations') . " WHERE idsite = 1 AND period = 1 AND date1 = '2024-03-01' AND name LIKE '%.DataExplorer'");
        $this->assertNotEmpty($invalidations);
        $this->processInvalidation(1, 'day', '2024-03-01');
        $this->assertSame([self::DESKTOP => 1, self::SMARTPHONE => 1], $this->column($this->table($updated, 'day', '2024-03-01'), 'nb_visits'));
    }

    public function test_invalidateHistory_invalidatesOnlyDataExplorerArchives_ofThePlacedSites(): void
    {
        Date::$now = strtotime('2024-03-10 12:00:00');
        Config::getInstance()->DataExplorer = ['invalidation_months' => 1];
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], ['sites' => [2]]);
        $table = Common::prefixTable('archive_invalidations');
        Db::query("DELETE FROM $table");

        (new DataExplorer())->invalidateHistory($report);

        $rows = Db::fetchAll("SELECT idsite, name, date1, period FROM $table");
        $this->assertNotEmpty($rows);
        $days = [];
        foreach ($rows as $row) {
            $this->assertSame('2', (string) $row['idsite']);
            $this->assertStringEndsWith('.DataExplorer', $row['name']);
            if ((int) $row['period'] === 1) {
                $days[] = $row['date1'];
            }
        }
        $this->assertContains('2024-02-10', $days);
        $this->assertContains('2024-03-10', $days);
        $this->assertNotContains('2024-02-09', $days);
    }

    public function test_invalidateHistory_onAllWebsites_coversEverySite(): void
    {
        Date::$now = strtotime('2024-03-10 12:00:00');
        Config::getInstance()->DataExplorer = ['invalidation_months' => 1];
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], ['all' => true]);
        $table = Common::prefixTable('archive_invalidations');
        Db::query("DELETE FROM $table");

        (new DataExplorer())->invalidateHistory($report);

        $sites = array_column(Db::fetchAll("SELECT DISTINCT idsite FROM $table ORDER BY idsite"), 'idsite');
        $this->assertEquals([1, 2], $sites);
    }

    private function disableUniques(array $periods): void
    {
        $general = Config::getInstance()->General;
        foreach ($periods as $period) {
            $general['enable_processing_unique_visitors_' . $period] = 0;
        }
        $general['enable_processing_unique_visitors_year_and_range'] = 0;
        Config::getInstance()->General = $general;
    }

    private function failingRunner(\Exception $exception): VisualQueryRunner
    {
        return new class ($exception) extends VisualQueryRunner {
            private $exception;

            public function __construct(\Exception $exception)
            {
                parent::__construct();
                $this->exception = $exception;
            }

            public function runForArchive(ReportDefinition $definition, LogAggregator $aggregator, int $mainCap, int $subCap): DataTable
            {
                throw $this->exception;
            }
        };
    }

    private function save(array $definition, array $placement = ['sites' => [1]], string $type = 'visual'): array
    {
        $reportType = $definition['report_type'] ?? 'table';
        unset($definition['report_type'], $definition['type']);

        $dao = new ReportsDao();
        $id = $dao->insert([
            'name' => 'Saved ' . uniqid(),
            'type' => $type,
            'report_type' => $reportType,
            'definition' => $definition,
        ], Placement::fromArray($placement));

        return $dao->get($id);
    }

    private function table(array $report, string $period, string $date, string $segment = '', int $idSite = 1): DataTable
    {
        $archive = Archive::build($idSite, $period, $date, $segment);

        return $archive->getDataTable(SavedReport::recordName($report['idreport'], $report['revision']));
    }

    private function processor(int $idSite, string $period, string $date): ArchiveProcessor
    {
        $site = new Site($idSite);
        $params = new Parameters($site, PeriodFactory::build($period, $date), new Segment('', [$idSite]));

        return new ArchiveProcessor($params, new ArchiveWriter($params), new LogAggregator($params));
    }

    /**
     * What core:archive does for a "done.DataExplorer" invalidation: archives the plugin alone, even though an
     * archive of every plugin exists for the date (browser archiving never re-runs a valid archive).
     */
    private function processInvalidation(int $idSite, string $period, string $date): void
    {
        $backup = $_GET;
        $_GET['trigger'] = 'archivephp';
        $_GET['pluginOnly'] = '1';

        try {
            CoreAdminHomeAPI::getInstance()->archiveReports($idSite, $period, $date, false, 'DataExplorer');
        } finally {
            $_GET = $backup;
        }
    }

    private function numeric(Archive $archive, string $name)
    {
        $row = $archive->getDataTableFromNumeric($name)->getFirstRow();

        return $row ? $row->getColumn($name) : false;
    }

    private function column(DataTable $table, string $column): array
    {
        $result = [];
        foreach ($table->getRows() as $row) {
            $value = $row->getColumn($column);
            $result[(string) $row->getColumn('label')] = $value === false ? 0 : $value;
        }

        return $result;
    }
}

ArchivingTest::$fixture = new ExplorerFixture();
