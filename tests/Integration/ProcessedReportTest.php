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
use Piwik\Piwik;
use Piwik\Plugins\API\API as CoreAPI;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Plugins\ScheduledReports\API as ScheduledReportsAPI;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Saved reports through API.getProcessedReport, the path of scheduled email reports and exports: the declared
 * columns must be the columns of the data.
 *
 * @group DataExplorer
 * @group DataExplorerProcessedReportTest
 * @group Plugins
 */
class ProcessedReportTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

    private const WEEK = '2024-02-26';
    private const READ_ONLY_USER = 'dataexplorer_pr';
    private const HOSTS = ['localhost', '127.0.0.1', '::1'];
    private const CORE_PROCESSED = ['nb_actions_per_visit', 'avg_time_on_site', 'bounce_rate', 'conversion_rate'];

    /** @var mixed */
    private $originalSection;
    /** @var array */
    private $backupGet;

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
        $this->backupGet = $_GET;
        $_GET['idSite'] = '1';

        $this->originalSection = Config::getInstance()->DataExplorer;
        $password = 'p' . bin2hex(random_bytes(12));
        $this->dropUser();
        foreach (self::HOSTS as $host) {
            Db::exec(sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", self::READ_ONLY_USER, $host, $password));
            foreach ($this->allowedTables() as $table) {
                Db::exec(sprintf("GRANT SELECT ON `%s`.`%s` TO '%s'@'%s'", Config::getInstance()->database['dbname'], $table, self::READ_ONLY_USER, $host));
            }
        }
        $section = is_array($this->originalSection) ? $this->originalSection : [];
        Config::getInstance()->DataExplorer = array_merge($section, ['readonly_username' => self::READ_ONLY_USER, 'readonly_password' => $password]);
        ReadOnlyConnection::reset();
    }

    public function tearDown(): void
    {
        ReadOnlyConnection::reset();
        Config::getInstance()->DataExplorer = $this->originalSection;
        $this->dropUser();
        $_GET = $this->backupGet;

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_visualTableWithPivot_declaresThePivotColumns_andNoCoreProcessedMetric(): void
    {
        $id = $this->save('visual', 'table', ['rows' => ['UserCountry.Country'], 'column' => 'DevicesDetection.DeviceType', 'metrics' => ['nb_visits']]);

        $report = $this->processed($id, 'week', self::WEEK);

        $this->assertArrayHasKey('nb_visits | Desktop', $report['columns']);
        $this->assertArrayHasKey('nb_visits | Smartphone', $report['columns']);
        $this->assertNoCoreProcessedMetric($report['columns']);
        $rows = $this->rowsByLabel($report['reportData']);
        $this->assertEquals(3, $rows['France']['nb_visits']);
        $this->assertEquals(2, $rows['France']['nb_visits | Desktop']);
        $this->assertEquals(1, $rows['France']['nb_visits | Smartphone']);
        $this->assertEquals(1, $rows['Germany']['nb_visits | Desktop']);
    }

    public function test_visualEvolution_returnsItsMetricPerDay(): void
    {
        $id = $this->save('visual', 'evolution', ['metrics' => ['nb_visits']]);

        $report = $this->processed($id, 'day', '2024-03-01,2024-03-03');

        $this->assertSame(['nb_visits'], array_keys($report['columns']));
        $this->assertInstanceOf(DataTable\Map::class, $report['reportData']);
        $visits = [];
        foreach ($report['reportData']->getDataTables() as $day) {
            $visits[] = (int) $day->getFirstRow()->getColumn('nb_visits');
        }
        $this->assertSame([2, 2, 2], $visits);
    }

    public function test_visualKpiCard_returnsItsMetric(): void
    {
        $id = $this->save('visual', 'kpicard', ['metrics' => ['nb_visits']]);

        $report = $this->processed($id, 'week', self::WEEK);

        $this->assertSame(['nb_visits'], array_keys($report['columns']));
        $this->assertEquals(6, $report['reportData']->getFirstRow()->getColumn('nb_visits'));
    }

    public function test_sqlTable_declaresTheColumnsOfItsSelectList(): void
    {
        $id = $this->save('sql', 'table', ['sql' => 'SELECT location_country AS country, COUNT(*) AS visits FROM ' . Common::prefixTable('log_visit')
            . ' WHERE idsite = {idsite} AND visit_last_action_time >= {date_start} AND visit_last_action_time <= {date_end}'
            . ' GROUP BY location_country ORDER BY visits DESC, country']);

        $report = $this->processed($id, 'week', self::WEEK);

        $this->assertSame(['label', 'visits'], array_keys($report['columns']));
        $this->assertSame('Country', $report['columns']['label']);
        $rows = $this->rowsByLabel($report['reportData']);
        $this->assertEquals(3, $rows['fr']['visits']);
        $this->assertEquals(2, $rows['us']['visits']);
    }

    public function test_sqlReportWithUnnamedColumns_isNotOfferedInScheduledReports(): void
    {
        $named = $this->save('sql', 'table', ['sql' => 'SELECT name AS site_name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}']);
        $star = $this->save('sql', 'table', ['sql' => 'SELECT * FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}']);
        $expression = $this->save('sql', 'table', ['sql' => 'SELECT UPPER(name) FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}']);

        $offered = [];
        foreach (ScheduledReportsAPI::getReportMetadata(1, 'email') as $metadata) {
            if (($metadata['module'] ?? '') === 'DataExplorer') {
                $offered[] = (int) $metadata['parameters']['idReport'];
            }
        }

        $this->assertContains($named, $offered);
        $this->assertNotContains($star, $offered);
        $this->assertNotContains($expression, $offered);
    }

    private function processed(int $idReport, string $period, string $date): array
    {
        Cache::getTransientCache()->flushAll();

        return CoreAPI::getInstance()->getProcessedReport(1, $period, $date, 'DataExplorer', 'getReport', false, ['idReport' => $idReport], false, false, false, false, false, true);
    }

    private function assertNoCoreProcessedMetric(array $columns): void
    {
        foreach (self::CORE_PROCESSED as $metric) {
            $this->assertArrayNotHasKey($metric, $columns);
        }
    }

    private function rowsByLabel(DataTable $table): array
    {
        $rows = [];
        foreach ($table->getRows() as $row) {
            $rows[(string) $row->getColumn('label')] = $row->getColumns();
        }

        return $rows;
    }

    private function save(string $type, string $reportType, array $definition): int
    {
        ReportsDao::clearCache();

        return (new ReportsDao())->insert([
            'name' => 'Report ' . uniqid(),
            'type' => $type,
            'report_type' => $reportType,
            'definition' => $definition,
            'category' => 'General_Visitors',
            'subcategory' => 'General_Overview',
        ], Placement::fromArray(['sites' => [1]]));
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

ProcessedReportTest::$fixture = new ExplorerFixture();
