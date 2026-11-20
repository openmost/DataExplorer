<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\Cache;
use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Query\SavedReportReader;
use Piwik\Plugins\DataExplorer\Reports\GetSavedReport;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * DataExplorer.getReport: archived visual reports read back with display labels, saved raw SQL reports run live
 * for the viewers of the placed sites.
 *
 * @group DataExplorer
 * @group DataExplorerGetReportTest
 * @group Plugins
 */
class GetReportTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

    private const COUNTRY = 'UserCountry.Country';
    private const DEVICE = 'DevicesDetection.DeviceType';
    private const WEEK = '2024-02-26';
    private const READ_ONLY_USER = 'dataexplorer_gr';
    private const HOSTS = ['localhost', '127.0.0.1', '::1'];

    /** @var string */
    private $password;
    /** @var mixed */
    private $originalSection;

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
        Cache::getLazyCache()->flushAll();
        Cache::getTransientCache()->flushAll();
        ReportsDao::clearCache();

        $this->originalSection = Config::getInstance()->DataExplorer;
        $this->password = 'p' . bin2hex(random_bytes(12));
        $this->dropUser();
        foreach (self::HOSTS as $host) {
            Db::exec(sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", self::READ_ONLY_USER, $host, $this->password));
            foreach ($this->allowedTables() as $table) {
                Db::exec(sprintf("GRANT SELECT ON `%s`.`%s` TO '%s'@'%s'", Config::getInstance()->database['dbname'], $table, self::READ_ONLY_USER, $host));
            }
        }
        $section = is_array($this->originalSection) ? $this->originalSection : [];
        Config::getInstance()->DataExplorer = array_merge($section, ['readonly_username' => self::READ_ONLY_USER, 'readonly_password' => $this->password]);
        ReadOnlyConnection::reset();
    }

    public function tearDown(): void
    {
        ReadOnlyConnection::reset();
        Config::getInstance()->DataExplorer = $this->originalSection;
        $this->dropUser();

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_visualTable_showsFormattedLabels(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report);

        $this->assertEquals(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));
        $this->assertSame('fr', $table->getRowFromLabel('France')->getMetadata('value'));
    }

    public function test_visualTable_renamed_keepsItsArchivedData(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
        $this->assertSame(3, $this->api()->getReport(1, 'week', self::WEEK, $report)->getRowsCount());

        $_GET['idSite'] = '1';
        $this->api()->updateReport($report, 'Renamed', 'visual', 'table', '{"rows":["UserCountry.Country"],"metrics":["nb_visits"]}', '{"sites":[1]}', 'General_Visitors', 'Renamed page', 5);
        unset($_GET['idSite']);
        Cache::getTransientCache()->flushAll();
        $general = Config::getInstance()->General;
        Config::getInstance()->General = array_merge($general, ['enable_browser_archiving_triggering' => 0, 'browser_archiving_disabled_enforce' => 1]);

        try {
            $table = $this->api()->getReport(1, 'week', self::WEEK, $report);
        } finally {
            Config::getInstance()->General = $general;
        }

        $this->assertEquals(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_visualTable_expanded_returnsFormattedSubtables(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report, '', true);

        $france = $table->getRowFromLabel('France');
        $this->assertNotFalse($france);
        $this->assertEquals(['Desktop' => 2, 'Smartphone' => 1], $this->column($france->getSubtable(), 'nb_visits'));
    }

    public function test_visualTable_subtableRequest_isFormattedWithTheSecondDimension(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits']]);
        $france = $this->api()->getReport(1, 'week', self::WEEK, $report)->getRowFromLabel('France');

        $subtable = $this->api()->getReport(1, 'week', self::WEEK, $report, '', false, (int) $france->getIdSubDataTable());

        $this->assertEquals(['Desktop' => 2, 'Smartphone' => 1], $this->column($subtable, 'nb_visits'));
    }

    public function test_visualTable_thirdLevelSubtable_isFormattedWithTheThirdDimension(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY, self::DEVICE, self::COUNTRY], 'metrics' => ['nb_visits']]);
        $expanded = $this->api()->getReport(1, 'week', self::WEEK, $report, '', true);
        $desktop = $expanded->getRowFromLabel('France')->getSubtable()->getRowFromLabel('Desktop');

        $subtable = $this->api()->getReport(1, 'week', self::WEEK, $report, '', false, (int) $desktop->getMetadata('idsubdatatable_in_db'));

        $this->assertEquals(['France' => 2], $this->column($subtable, 'nb_visits'));
    }

    public function test_visualTable_pivotColumns_carryTheFormattedValue(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => ['nb_visits']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report);

        $this->assertEquals(['France' => 2, 'United States' => 1, 'Germany' => 1], $this->column($table, 'nb_visits | Desktop'));
        $this->assertEquals(['France' => 1, 'United States' => 1, 'Germany' => 0], $this->column($table, 'nb_visits | Smartphone'));
    }

    public function test_visualTable_rate_isComputedFromTheArchivedMetrics_perPivotColumnToo(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => ['bounce_rate']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report);

        // France: desktop visits 1 (3 pages) and 6 (1 page), smartphone visit 2 (1 page)
        $this->assertEquals(0.5, $table->getRowFromLabel('France')->getColumn('bounce_rate | Desktop'));
        $this->assertEquals(1, $table->getRowFromLabel('France')->getColumn('bounce_rate | Smartphone'));
    }

    public function test_visualEvolution_rate_isComputedPerDay(): void
    {
        $report = $this->save('visual', 'evolution', ['metrics' => ['visits_conversion_rate']]);

        $map = $this->api()->getReport(1, 'day', '2024-03-01,2024-03-03', $report);

        $rates = [];
        foreach ($map->getDataTables() as $day) {
            $rates[] = $day->getFirstRow()->getColumn('visits_conversion_rate');
        }
        $this->assertEquals([0.5, 0.5, 0], $rates);
    }

    public function test_visualKpiCard_rate_overAWeek_isNotASumOfDays(): void
    {
        $report = $this->save('visual', 'kpicard', ['metrics' => ['bounce_rate']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report);

        $this->assertEquals(0.5, $table->getFirstRow()->getColumn('bounce_rate'));
    }

    public function test_visualTable_notDefinedValue_showsTheCoreNotDefinedLabel(): void
    {
        $report = $this->save('visual', 'table', ['rows' => ['Referrers.Keyword'], 'metrics' => ['nb_visits']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report);

        $keyword = (new DimensionCatalog())->all(1)['Referrers.Keyword'];
        $this->assertSame([Piwik::translate('General_NotDefined', $keyword->name)], array_keys($this->column($table, 'nb_visits')));
    }

    public function test_visualEvolution_overSeveralDays_returnsOneTablePerDay(): void
    {
        $report = $this->save('visual', 'evolution', ['metrics' => ['nb_visits', 'nb_actions']]);

        $map = $this->api()->getReport(1, 'day', '2024-03-01,2024-03-03', $report);

        $this->assertInstanceOf(DataTable\Map::class, $map);
        $visits = [];
        foreach ($map->getDataTables() as $key => $day) {
            $visits[$key] = $day->getFirstRow()->getColumn('nb_visits');
        }
        $this->assertEquals([2, 2, 2], array_values($visits));
    }

    public function test_visualKpiCard_returnsTheMetricOfThePeriod(): void
    {
        $report = $this->save('visual', 'kpicard', ['metrics' => ['nb_actions']]);

        $table = $this->api()->getReport(1, 'week', self::WEEK, $report);

        $this->assertEquals(10, $table->getFirstRow()->getColumn('nb_actions'));
    }

    public function test_reportPlacedOnSite2_isRefusedOnSite1(): void
    {
        $report = $this->save('visual', 'table', ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], ['sites' => [2]]);

        $this->expectException(NoAccessException::class);
        $this->api()->getReport(1, 'week', self::WEEK, $report);
    }

    public function test_savedSqlReport_isViewableByAViewUserOfThePlacedSite(): void
    {
        $report = $this->save('sql', 'table', ['sql' => 'SELECT name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}']);
        FakeAccess::clearAccess(false, [], [1], 'viewer1');

        $table = $this->api()->getReport(1, 'day', '2024-03-01', $report);

        // without a label column of its own, the first column of the result is the label
        $this->assertSame(['Site 1'], $table->getColumn('label'));
        $this->assertSame('name', $table->getMetadata('dataexplorer_label_column'));
    }

    public function test_savedSqlReport_isRefusedToAViewUserOfAnotherSite(): void
    {
        $report = $this->save('sql', 'table', ['sql' => 'SELECT name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}']);
        FakeAccess::clearAccess(false, [], [2], 'viewer2');

        $this->expectException(NoAccessException::class);
        $this->api()->getReport(1, 'day', '2024-03-01', $report);
    }

    public function test_savedSqlReport_onAllWebsites_bindsTheDisplayedSite_andCachesPerSite(): void
    {
        $report = $this->save('sql', 'table', ['sql' => 'SELECT name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}'], ['all' => true]);
        FakeAccess::clearAccess(false, [], [1, 2], 'viewer12');

        $this->assertSame(['Site 1'], $this->api()->getReport(1, 'day', '2024-03-01', $report)->getColumn('label'));
        $this->assertSame(['Site 2'], $this->api()->getReport(2, 'day', '2024-03-01', $report)->getColumn('label'));
    }

    public function test_storedSqlNoLongerProvenSiteScoped_isRefusedAtDisplay(): void
    {
        $report = $this->save('sql', 'table', ['sql' => 'SELECT COUNT(*) AS visits FROM ' . Common::prefixTable('log_visit')]);

        $this->expectException(RejectedQueryException::class);
        $this->api()->getReport(1, 'day', '2024-03-01', $report);
    }

    public function test_storedSqlRefusedByTheGuard_isRefusedAtDisplay(): void
    {
        $report = $this->save('sql', 'table', ['sql' => 'SELECT login FROM ' . Common::prefixTable('user')]);

        $this->expectException(RejectedQueryException::class);
        $this->api()->getReport(1, 'day', '2024-03-01', $report);
    }

    public function test_cachedSqlResult_isNotServedWhenTheStoredQueryNoLongerPassesTheGuard(): void
    {
        $report = $this->save('sql', 'table', ['sql' => 'SELECT name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}']);
        $this->assertSame(['Site 1'], $this->api()->getReport(1, 'day', '2024-03-01', $report)->getColumn('label'));

        // same query text, so the same cache entry, but the guard now refuses it
        Config::getInstance()->DataExplorer = array_merge(Config::getInstance()->DataExplorer, ['dataexplorer_sql_max_length' => 10]);

        $this->expectException(RejectedQueryException::class);
        $this->api()->getReport(1, 'day', '2024-03-01', $report);
    }

    public function test_sqlEvolution_stopsWithTheTimeoutError_whenTheTotalTimeBudgetIsSpent(): void
    {
        $id = $this->save('sql', 'evolution', ['sql' => 'SELECT COUNT(*) AS visits FROM ' . Common::prefixTable('log_visit')
            . ' WHERE idsite = {idsite} AND visit_last_action_time >= {date_start} AND visit_last_action_time <= {date_end}']);
        $now = 1000.0;
        $runs = 0;
        // every clock reading after the first run is past the budget
        $clock = function () use (&$now, &$runs) {
            return $now + ($runs++ > 1 ? 31 : 0);
        };

        try {
            (new SavedReportReader(null, $clock))->read((new ReportsDao())->get($id), 1, 'day', '2024-03-01,2024-03-05');
            $this->fail('expected the timeout error');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorRawSqlTimeout', [30]), $e->getMessage());
        }
    }

    public function test_sqlEvolution_overLast60Days_keepsTheLast30Points(): void
    {
        $report = $this->save('sql', 'evolution', ['sql' => 'SELECT COUNT(*) AS visits FROM ' . Common::prefixTable('log_visit')
            . ' WHERE idsite = {idsite} AND visit_last_action_time >= {date_start} AND visit_last_action_time <= {date_end}']);

        $map = $this->api()->getReport(1, 'day', 'last60', $report);

        $this->assertInstanceOf(DataTable\Map::class, $map);
        $this->assertSame(30, $map->getRowsCount());
        $keys = array_keys($map->getDataTables());
        $this->assertSame(\Piwik\Date::factory('today')->toString(), end($keys));
        $tables = $map->getDataTables();
        $this->assertSame(0, (int) end($tables)->getFirstRow()->getColumn('visits'));
    }

    public function test_sqlEvolution_overFixedDays_countsTheVisitsOfEachDay(): void
    {
        $report = $this->save('sql', 'kpicard', ['sql' => 'SELECT COUNT(*) AS visits FROM ' . Common::prefixTable('log_visit')
            . ' WHERE idsite = {idsite} AND visit_last_action_time >= {date_start} AND visit_last_action_time <= {date_end}']);

        $map = $this->api()->getReport(1, 'day', '2024-03-01,2024-03-03', $report);

        $visits = [];
        foreach ($map->getDataTables() as $day) {
            $visits[] = (int) $day->getFirstRow()->getColumn('visits');
        }
        $this->assertSame([2, 2, 2], $visits);
    }

    public function test_metricNames_includeTheDailySumOfDistinctCounts(): void
    {
        $id = $this->save('visual', 'table', ['rows' => [self::COUNTRY], 'metrics' => ['nb_uniq_visitors', 'nb_visits']]);
        $report = new GetSavedReport();
        $report->initFromRow((new ReportsDao())->get($id), 1);

        $metrics = $report->getMetrics();

        $this->assertSame(Piwik::translate('General_ColumnNbVisits'), $metrics['nb_visits']);
        $this->assertSame(Piwik::translate('General_ColumnNbUniqVisitors') . ' (' . Piwik::translate('General_DailySum') . ')', $metrics['sum_daily_nb_uniq_visitors']);
    }

    private function api(): API
    {
        return API::getInstance();
    }

    private function save(string $type, string $reportType, array $definition, array $placement = ['sites' => [1]]): int
    {
        return (new ReportsDao())->insert([
            'name' => 'Report ' . uniqid(),
            'type' => $type,
            'report_type' => $reportType,
            'definition' => $definition,
            'category' => 'General_Visitors',
            'subcategory' => 'General_Overview',
        ], Placement::fromArray($placement));
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

    private function allowedTables(): array
    {
        $prefix = Common::prefixTable('');
        $tables = [];
        foreach (Db::fetchAll('SHOW TABLES') as $row) {
            $table = (string) reset($row);
            if (strpos($table, $prefix) === 0 && SqlGuard::isAllowedTable(substr($table, strlen($prefix)))) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    private function dropUser(): void
    {
        foreach (self::HOSTS as $host) {
            Db::exec(sprintf("DROP USER IF EXISTS '%s'@'%s'", self::READ_ONLY_USER, $host));
        }
    }
}

GetReportTest::$fixture = new ExplorerFixture();
