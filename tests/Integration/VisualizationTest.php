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
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Reports\GetSavedReport;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Saved reports rendered as report widgets with the native Matomo visualizations: table, bar, pie, evolution
 * graph and sparkline. The footer of a table offers the table, bar and pie only.
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

    public function test_tableReport_rendersTheHtmlTable_withTheNativeBarAndPieInTheFooter(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);

        $html = $this->render($id, 'week', '2024-02-26');

        $this->assertStringContainsString('dataTableVizHtmlTable', $html);
        $this->assertStringContainsString('France', $html);
        $icons = $this->footerIcons($html);
        // the treemap of the Treemap Visualization plugin when it is active
        $treemap = Configuration::isTreemapAvailable() ? [GetSavedReport::TREEMAP_VIEW] : [];
        $this->assertSame(array_merge(['table', 'graphVerticalBar', 'graphPie'], $treemap), $icons);
    }

    public function test_pivotColumns_areShownWithTheMetricNameAndTheFormattedValue(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'column' => 'DevicesDetection.DeviceType', 'metrics' => ['nb_visits']]);

        $html = $this->render($id, 'week', '2024-02-26');

        $this->assertStringContainsString('Visits | Desktop', $html);
        $this->assertStringContainsString('Visits | Smartphone', $html);
    }

    public function test_twoRowDimensions_flatView_loadsTheSubtablesThroughTheReportMetadata(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country', 'DevicesDetection.DeviceType'], 'metrics' => ['nb_visits'], 'table_view' => 'flat']);

        $html = $this->render($id, 'week', '2024-02-26');

        $this->assertStringNotContainsString('could not be found', $html);
        $this->assertStringContainsString('France', $html);
        $this->assertStringContainsString('Desktop', $html);
    }

    public function test_aScheduledReportIdInTheRequest_isNotReadAsASavedReport(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);
        $_GET = ['idSite' => '1', 'idReport' => (string) $id];

        $report = new \Piwik\Plugins\DataExplorer\Reports\GetSavedReport();

        $this->assertFalse($report->isEnabled());
    }

    public function test_evolutionReport_footerOffersNoOtherVisualization(): void
    {
        $id = $this->save('evolution', ['metrics' => ['nb_visits']]);

        $icons = $this->footerIcons($this->render($id, 'day', '2024-03-03'));

        $this->assertSame([], $icons, 'one visualization, nothing to switch to');
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

    public function test_coreReports_offerTheirOwnFooterIcons(): void
    {
        $html = $this->dispatch('UserCountry', 'getCountry', ['period' => 'week', 'date' => '2024-02-26']);

        $icons = $this->footerIcons($html);
        $this->assertContains('graphVerticalBar', $icons);
        $this->assertContains('tableAllColumns', $icons);
    }

    public function test_barView_isTheNativeBarGraph_withOneSeriesPerMetric(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits', 'nb_actions']]);

        $data = $this->graphData($this->render($id, 'week', '2024-02-26', 'graphVerticalBar'));

        $this->assertSame(['France', 'United States', 'Germany'], $data['params']['axes']['xaxis']['labels']);
        $this->assertCount(2, $data['data'], 'one bar series per metric');
        $this->assertEquals([3, 2, 1], $data['data'][0]);
    }

    public function test_barView_othersSlice_onlyForMetricsThatAddUpAcrossRows(): void
    {
        $original = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = ['datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 1];
        try {
            $visits = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);
            $visitors = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_uniq_visitors']]);

            $visitsData = $this->graphData($this->render($visits, 'week', '2024-02-26', 'graphVerticalBar'));
            $visitorsData = $this->graphData($this->render($visitors, 'week', '2024-02-26', 'graphVerticalBar'));
        } finally {
            Config::getInstance()->DataExplorer = $original;
        }

        $others = Piwik::translate('General_Others');
        $this->assertSame(['France', $others], $visitsData['params']['axes']['xaxis']['labels'], 'distinct visits add up across countries');
        $this->assertNotContains($others, $visitorsData['params']['axes']['xaxis']['labels'], 'distinct visitors are shared by countries');
    }

    public function test_pieView_isTheNativePieGraph(): void
    {
        $id = $this->save('table', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);

        $data = $this->graphData($this->render($id, 'week', '2024-02-26', 'graphPie'));

        $this->assertEquals([3, 2, 1], $data['data'][0]);
    }

    public function test_evolutionReport_isTheNativeEvolutionGraph_overTheLastPeriods(): void
    {
        $id = $this->save('evolution', ['metrics' => ['nb_visits']]);

        $data = $this->graphData($this->render($id, 'day', '2024-03-03'));

        $this->assertCount(30, $data['params']['axes']['xaxis']['labels']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals([2, 2, 2], array_slice($data['data'][0], -3));
    }

    public function test_kpiCard_isTheNativeSparkline_withTheValueAndTheEvolution(): void
    {
        $id = $this->save('kpicard', ['metrics' => ['nb_visits']]);

        // no visit on 2024-03-04, two the day before
        $html = $this->render($id, 'day', '2024-03-04');

        $sparkline = $this->firstSparkline($html);
        $this->assertSame('-100%', $sparkline['evolution']['percent']);
        $this->assertLessThan(0, $sparkline['evolution']['trend']);
        $metrics = reset($sparkline['metrics']);
        $this->assertSame('0', (string) $metrics[0]['value']);
    }

    public function test_preview_rendersTheUnsavedExploration_inTheNativeVisualizationOfItsChart(): void
    {
        $definition = json_encode(['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits'], 'chart' => 'pie']);

        $html = $this->dispatch('DataExplorer', 'preview', ['period' => 'week', 'date' => '2024-02-26', 'definition' => $definition]);

        $this->assertEquals([3, 2, 1], $this->graphData($html)['data'][0]);
        $this->assertStringContainsString('DataExplorer.getReport', $html);
    }

    public function test_preview_table_keepsTheDefinitionForTheReloadsOfTheDataTable(): void
    {
        $definition = json_encode(['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);

        $html = $this->dispatch('DataExplorer', 'preview', ['period' => 'week', 'date' => '2024-02-26', 'definition' => $definition]);

        $this->assertStringContainsString('dataTableVizHtmlTable', $html);
        $this->assertStringContainsString('France', $html);
        $this->assertStringContainsString('&quot;definition&quot;', $html);
    }

    public function test_preview_flatTable_ofTwoRowDimensions_showsOneColumnPerDimension(): void
    {
        $definition = json_encode(['rows' => ['UserCountry.Country', 'DevicesDetection.DeviceType'], 'metrics' => ['nb_visits'], 'table_view' => 'flat']);

        $html = $this->dispatch('DataExplorer', 'preview', ['period' => 'week', 'date' => '2024-02-26', 'definition' => $definition, 'flat' => '1', 'show_dimensions' => '1']);

        $this->assertStringNotContainsString('could not be found', $html);
        $this->assertStringContainsString('Desktop', $html);
        $this->assertSame(1, substr_count($html, '>Device type<'), 'the second dimension is one column, not repeated as the label');
    }

    public function test_preview_kpiCard_drawsItsSparklineOverSeveralPeriods(): void
    {
        $definition = json_encode(['report_type' => 'kpicard', 'metrics' => ['nb_visits']]);

        $table = \Piwik\API\Request::processRequest('DataExplorer.getReport', [
            'idSite' => 1, 'period' => 'day', 'date' => '2024-02-26,2024-03-03', 'idExploration' => 0, 'definition' => $definition,
        ]);

        $this->assertInstanceOf(\Piwik\DataTable\Map::class, $table);
        $this->assertCount(7, $table->getDataTables());
    }

    public function test_kpiCard_rendersInADashboardWidget(): void
    {
        $id = $this->save('kpicard', ['metrics' => ['nb_visits']]);

        $html = $this->dispatch('Widgetize', 'iframe', [
            'moduleToWidgetize' => 'DataExplorer',
            'actionToWidgetize' => 'getReport',
            'idExploration' => (string) $id,
            'period' => 'day',
            'date' => '2024-03-05',
            'disableLink' => '1',
            'widget' => '1',
        ]);

        $this->assertStringContainsString('CoreVisualizations.SparklinesGrid', $html);
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
        $params = ['idExploration' => (string) $idReport, 'period' => $periodOverride ?: $period, 'date' => $date, 'widget' => '1'];
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
        // Matomo 6 hands the icons to the report actions menu of the header, as a JSON config
        $this->assertSame(1, preg_match('/\sdata-report-actions="([^"]*)"/', $html, $match));
        $config = json_decode(html_entity_decode($match[1], ENT_QUOTES), true);

        $ids = [];
        foreach ($config['footerIcons'] ?? [] as $group) {
            foreach ($group['buttons'] as $button) {
                $ids[] = $button['id'];
            }
        }

        return $ids;
    }

    /**
     * @return array the data-data JSON of a native jqPlot graph
     */
    private function graphData(string $html): array
    {
        $this->assertSame(1, preg_match('/class="piwik-graph" data-data="([^"]*)"/', $html, $match), $html);

        return json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
    }

    /**
     * @return array the first sparkline of the native sparklines grid
     */
    private function firstSparkline(string $html): array
    {
        $this->assertSame(1, preg_match('/vue-entry="CoreVisualizations.SparklinesGrid"\s+sparklines="([^"]*)"/', $html, $match), $html);
        $sparklines = json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
        $group = reset($sparklines);

        return isset($group['metrics']) ? $group : reset($group);
    }
}

VisualizationTest::$fixture = new ExplorerFixture();
