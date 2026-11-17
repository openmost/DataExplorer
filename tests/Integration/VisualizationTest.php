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
use Piwik\Db;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Saved reports rendered as report widgets: the HTML holds the chart mount point of the ECharts component and
 * the serialized data, the footer offers the DataExplorer bar and pie on DataExplorer tables only.
 *
 * @group DataExplorer
 * @group DataExplorerVisualizationTest
 * @group Plugins
 */
class VisualizationTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

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
        FakeAccess::clearAccess(false, [], [1], 'viewer');
        Cache::getLazyCache()->flushAll();
        Cache::getTransientCache()->flushAll();
        ReportsDao::clearCache();
        $this->backupGet = $_GET;
    }

    public function tearDown(): void
    {
        $_GET = $this->backupGet;

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_tableReport_rendersTheHtmlTable_withTheDataExplorerBarAndPieInTheFooter(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);

        $html = $this->render($id, 'week', '2024-02-26');

        $this->assertStringContainsString('dataTableVizHtmlTable', $html);
        $this->assertStringContainsString('France', $html);
        $icons = $this->footerIcons($html);
        $this->assertContains('DataExplorerBar', $icons);
        $this->assertContains('DataExplorerPie', $icons);
        $this->assertNotContains('graphVerticalBar', $icons);
        $this->assertNotContains('DataExplorerLine', $icons);
    }

    public function test_pivotColumns_areShownWithTheMetricNameAndTheFormattedValue(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'column' => 'DevicesDetection.DeviceType', 'metrics' => ['nb_visits']]);

        $html = $this->render($id, 'week', '2024-02-26');

        $this->assertStringContainsString('Visits | Desktop', $html);
        $this->assertStringContainsString('Visits | Smartphone', $html);
    }

    public function test_evolutionReport_footerOffersNoOtherVisualization(): void
    {
        $id = $this->save('evolution', ['metrics' => ['nb_visits']]);

        $icons = $this->footerIcons($this->render($id, 'day', '2024-03-03'));

        $this->assertSame(['DataExplorerLine'], $icons);
    }

    public function test_sqlTable_keepsTheOrderOfTheQuery(): void
    {
        $user = 'dataexplorer_vt';
        $password = 'p' . bin2hex(random_bytes(12));
        $original = Config::getInstance()->DataExplorer;
        $hosts = ['localhost', '127.0.0.1', '::1'];
        $table = Common::prefixTable('log_visit');
        foreach ($hosts as $host) {
            Db::exec(sprintf("DROP USER IF EXISTS '%s'@'%s'", $user, $host));
            Db::exec(sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", $user, $host, $password));
            Db::exec(sprintf("GRANT SELECT ON `%s`.`%s` TO '%s'@'%s'", Config::getInstance()->database['dbname'], $table, $user, $host));
        }
        Config::getInstance()->DataExplorer = array_merge(is_array($original) ? $original : [], ['readonly_username' => $user, 'readonly_password' => $password]);
        ReadOnlyConnection::reset();

        try {
            // ascending, while the default table sort is descending on the first metric
            $sql = 'SELECT idvisit, visit_total_actions FROM ' . $table . ' WHERE idsite = {idsite} ORDER BY visit_total_actions ASC, idvisit ASC';
            $id = (new ReportsDao())->insert([
                'name' => 'Visits by actions',
                'type' => 'sql',
                'report_type' => 'table',
                'definition' => ['sql' => $sql],
                'category' => 'General_Visitors',
                'subcategory' => 'General_Overview',
            ], Placement::fromArray(['sites' => [1]]));
            $expected = array_map('strval', array_column(Db::fetchAll(str_replace('{idsite}', '1', $sql)), 'idvisit'));

            $html = $this->render($id, 'day', '2024-03-01,2024-03-06', '', 'range');

            preg_match_all('/<span class=\'label\'\s*><span class="value">(\d+)<\/span>/', $html, $labels);
            $this->assertSame($expected, $labels[1]);
            $this->assertCount(8, $expected);
        } finally {
            ReadOnlyConnection::reset();
            Config::getInstance()->DataExplorer = $original;
            foreach ($hosts as $host) {
                Db::exec(sprintf("DROP USER IF EXISTS '%s'@'%s'", $user, $host));
            }
        }
    }

    public function test_coreReports_doNotOfferTheDataExplorerCharts(): void
    {
        $html = $this->dispatch('UserCountry', 'getCountry', ['period' => 'week', 'date' => '2024-02-26']);

        $icons = $this->footerIcons($html);
        $this->assertContains('graphVerticalBar', $icons);
        $this->assertNotContains('DataExplorerBar', $icons);
        $this->assertNotContains('DataExplorerPie', $icons);
    }

    public function test_barView_serializesTheTopRowsOfTheFirstMetric(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits', 'nb_actions']]);

        $props = $this->props($this->render($id, 'week', '2024-02-26', 'DataExplorerBar'), 'DataExplorer.EChartsBar');

        $this->assertSame(['France', 'United States', 'Germany'], $props['labels']);
        $this->assertEquals([3, 2, 1], $props['values']);
        $this->assertNotSame('', $props['metric-name']);
        $this->assertCount(2, $props['series'], 'one bar series per metric');
        $this->assertEquals([3, 2, 1], $props['series'][0]['data']);
        $this->assertSame($props['metric-name'], $props['series'][0]['name']);
        $this->assertCount(3, $props['series'][1]['data']);
    }

    public function test_barView_othersSlice_onlyForMetricsThatAddUpAcrossRows(): void
    {
        $original = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = ['datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 1];
        try {
            $visits = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);
            $visitors = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_uniq_visitors']]);

            $visitsProps = $this->props($this->render($visits, 'week', '2024-02-26', 'DataExplorerBar'), 'DataExplorer.EChartsBar');
            $visitorsProps = $this->props($this->render($visitors, 'week', '2024-02-26', 'DataExplorerBar'), 'DataExplorer.EChartsBar');
        } finally {
            Config::getInstance()->DataExplorer = $original;
        }

        $others = Piwik::translate('General_Others');
        $this->assertSame(['France', $others], $visitsProps['labels'], 'distinct visits add up across countries');
        $this->assertEquals([3, 3], $visitsProps['values']);
        $this->assertSame(['France'], $visitorsProps['labels'], 'distinct visitors are shared by countries');
    }

    public function test_pieView_rendersTheEChartsPie(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);

        $props = $this->props($this->render($id, 'week', '2024-02-26', 'DataExplorerPie'), 'DataExplorer.EChartsPie');

        $this->assertEquals([3, 2, 1], $props['values']);
    }

    public function test_evolutionReport_rendersTheEChartsLine_overTheLastPeriods(): void
    {
        $id = $this->save('evolution', ['metrics' => ['nb_visits']]);

        $props = $this->props($this->render($id, 'day', '2024-03-03'), 'DataExplorer.EChartsLine');

        $this->assertCount(30, $props['labels']);
        $this->assertCount(1, $props['series']);
        $data = $props['series'][0]['data'];
        $this->assertEquals([2, 2, 2], array_slice($data, -3));
    }

    public function test_kpiCard_rendersTheValue_theEvolution_andTheSparkline(): void
    {
        $id = $this->save('kpicard', ['metrics' => ['nb_visits']]);

        // no visit on 2024-03-04, two the day before
        $props = $this->props($this->render($id, 'day', '2024-03-04'), 'DataExplorer.KpiCard');

        $this->assertSame('0', $props['value']);
        $this->assertSame(-1, $props['evolution']['trend']);
        $this->assertSame('-100%', $props['evolution']['percent']);
        $this->assertCount(30, $props['sparkline']);
    }

    public function test_kpiCard_rendersInADashboardWidget(): void
    {
        $id = $this->save('kpicard', ['metrics' => ['nb_visits']]);

        $html = $this->dispatch('Widgetize', 'iframe', [
            'moduleToWidgetize' => 'DataExplorer',
            'actionToWidgetize' => 'getReport',
            'idReport' => (string) $id,
            'period' => 'day',
            'date' => '2024-03-05',
            'disableLink' => '1',
            'widget' => '1',
        ]);

        $this->assertStringContainsString('vue-entry="DataExplorer.KpiCard"', $html);
    }

    private function save(string $reportType, array $definition): int
    {
        return (new ReportsDao())->insert([
            'name' => 'Report ' . uniqid(),
            'type' => 'visual',
            'report_type' => $reportType,
            'definition' => $definition,
            'category' => 'General_Visitors',
            'subcategory' => 'General_Overview',
        ], Placement::fromArray(['sites' => [1]]));
    }

    private function render(int $idReport, string $period, string $date, string $viewDataTable = '', string $periodOverride = ''): string
    {
        $params = ['idReport' => (string) $idReport, 'period' => $periodOverride ?: $period, 'date' => $date, 'widget' => '1'];
        if ($viewDataTable !== '') {
            $params['viewDataTable'] = $viewDataTable;
        }

        return $this->dispatch('DataExplorer', 'getReport', $params);
    }

    private function dispatch(string $module, string $action, array $params): string
    {
        $_GET = array_merge(['module' => $module, 'action' => $action, 'idSite' => '1'], $params);

        return (string) FrontController::getInstance()->fetchDispatch($module, $action);
    }

    /**
     * @return string[] ids of the footer icons of the rendered data table
     */
    private function footerIcons(string $html): array
    {
        $this->assertSame(1, preg_match('/\sfooter-icons="([^"]*)"/', $html, $match));

        $ids = [];
        foreach (json_decode(html_entity_decode($match[1], ENT_QUOTES), true) as $group) {
            foreach ($group['buttons'] as $button) {
                $ids[] = $button['id'];
            }
        }

        return $ids;
    }

    /**
     * @return array attribute => decoded JSON value of the vue-entry element
     */
    private function props(string $html, string $entry): array
    {
        $this->assertSame(1, preg_match('/<div class="dataExplorerChart" vue-entry="' . preg_quote($entry, '/') . '"([^>]*)>/', $html, $match), $html);
        preg_match_all('/ ([a-z-]+)="([^"]*)"/', $match[1], $attributes, PREG_SET_ORDER);

        $props = [];
        foreach ($attributes as [, $name, $value]) {
            $props[$name] = json_decode(html_entity_decode($value, ENT_QUOTES), true);
        }

        return $props;
    }
}

VisualizationTest::$fixture = new ExplorerFixture();
