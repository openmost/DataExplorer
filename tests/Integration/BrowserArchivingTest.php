<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\Archive;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Cache;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Db;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Query\SavedReportReader;
use Piwik\Plugins\DataExplorer\RecordBuilders\SavedReport;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * A report saved after its periods were archived for every plugin is archived when it is displayed, with browser
 * archiving on, instead of staying empty until core:archive processes its invalidations.
 *
 * @group DataExplorer
 * @group DataExplorerBrowserArchivingTest
 * @group Plugins
 */
class BrowserArchivingTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

    private const COUNTRY = 'UserCountry.Country';
    private const WEEK = '2024-02-26';
    private const DAYS = '2024-02-26,2024-03-03';

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
        Rules::setBrowserTriggerArchiving(true);

        // archives of every plugin made before the report exists, so they hold none of its records
        Date::$now = strtotime('2024-03-10 11:00:00');
        Archive::build(1, 'week', self::WEEK)->getNumeric('nb_visits');
        Archive::build(1, 'day', self::DAYS)->getNumeric('nb_visits');
        Date::$now = strtotime('2024-03-10 12:00:00');
    }

    public function tearDown(): void
    {
        Date::$now = null;
        Rules::setBrowserTriggerArchiving(true);

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_newTable_overAWeekArchivedBeforeIt_isArchivedOnDisplay(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        $this->assertSame(0, $this->rawTable($report, 'week', self::WEEK)->getRowsCount());

        $this->later();
        $table = $this->reader()->read($report, 1, 'week', self::WEEK);

        $this->assertEquals(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_newEvolution_overDaysArchivedBeforeIt_isArchivedOnDisplay(): void
    {
        $report = $this->save(['report_type' => 'evolution', 'metrics' => ['nb_visits']]);

        $this->later();
        $map = $this->reader()->read($report, 1, 'day', self::DAYS);

        $this->assertInstanceOf(DataTable\Map::class, $map);
        $total = 0;
        foreach ($map->getDataTables() as $day) {
            $row = $day->getFirstRow();
            $total += $row ? (int) $row->getColumn('nb_visits') : 0;
        }
        $this->assertSame(6, $total);
    }

    public function test_aViewUser_getsTheReportArchived(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        FakeAccess::clearAccess(false, [], [1], 'viewer');

        $this->later();
        $table = $this->reader()->read($report, 1, 'week', self::WEEK);

        $this->assertSame(3, $table->getRowsCount());
    }

    public function test_aSecondDisplay_archivesNothing_evenWhenTheReportTotalsZero(): void
    {
        // a metric totalling 0 stores no numeric record, the archive time tells it is up to date
        $report = $this->save(['report_type' => 'evolution', 'metrics' => ['nb_visits'], 'segment' => 'countryCode==jp']);

        $this->later();
        $this->reader()->read($report, 1, 'week', self::WEEK);
        $archives = $this->pluginArchiveCount();
        $this->assertGreaterThan(0, $archives);

        $this->reader()->read($report, 1, 'week', self::WEEK);
        $this->assertSame($archives, $this->pluginArchiveCount());
    }

    public function test_anUnchangedReportArchivedForEveryPlugin_isNotArchivedAgain(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        $this->later();
        // an archive of every plugin newer than the report holds its record
        StaticContainer::get(ArchiveInvalidator::class)->markArchivesAsInvalidated([1], [Date::factory('2024-03-01')], 'day');
        Archive::build(1, 'day', '2024-03-01')->getNumeric('nb_visits');
        $this->assertSame(1, $this->rawTable($report, 'day', '2024-03-01')->getRowsCount());
        $before = $this->pluginArchiveCount();

        $this->reader()->read($report, 1, 'day', '2024-03-01');

        $this->assertSame($before, $this->pluginArchiveCount());
    }

    public function test_withBrowserArchivingOff_theReportWaitsForCoreArchive(): void
    {
        $report = $this->save(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        Rules::setBrowserTriggerArchiving(false);
        FakeAccess::clearAccess(false, [], [1], 'viewer');

        $this->later();
        $table = $this->reader()->read($report, 1, 'week', self::WEEK);

        $this->assertSame(0, $table->getRowsCount());
        $this->assertSame(0, $this->pluginArchiveCount());
    }

    private function later(): void
    {
        Date::$now = strtotime('2024-03-10 12:05:00');
        Cache::getTransientCache()->flushAll();
    }

    private function save(array $definition): array
    {
        $reportType = $definition['report_type'] ?? 'table';
        unset($definition['report_type']);

        $dao = new ReportsDao();
        $id = $dao->insert([
            'name' => 'Saved ' . uniqid(),
            'type' => 'visual',
            'report_type' => $reportType,
            'definition' => $definition,
        ], Placement::fromArray(['sites' => [1]]));
        Cache::getTransientCache()->flushAll();

        return $dao->get($id);
    }

    private function reader(): SavedReportReader
    {
        return new SavedReportReader();
    }

    private function rawTable(array $report, string $period, string $date): DataTable
    {
        return Archive::build(1, $period, $date)->getDataTable(SavedReport::recordName($report['idreport'], $report['revision']));
    }

    private function pluginArchiveCount(): int
    {
        $count = 0;
        foreach (['2024_02', '2024_03'] as $month) {
            $count += (int) Db::fetchOne('SELECT COUNT(*) FROM ' . Common::prefixTable('archive_numeric_' . $month) . " WHERE name = 'done.DataExplorer'");
        }

        return $count;
    }

    private function column(DataTable $table, string $column): array
    {
        $result = [];
        foreach ($table->getRows() as $row) {
            $result[(string) $row->getColumn('label')] = $row->getColumn($column);
        }

        return $result;
    }
}

BrowserArchivingTest::$fixture = new ExplorerFixture();
