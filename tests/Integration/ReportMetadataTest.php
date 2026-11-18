<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\API\Request;
use Piwik\Cache;
use Piwik\Category\CategoryList;
use Piwik\Piwik;
use Piwik\Plugins\API\API as CoreAPI;
use Piwik\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\Plugins\CoreVisualizations\Visualizations\Sparklines;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Saved reports registered as Matomo reports, subcategories and widgets of the sites they are placed on.
 *
 * @group DataExplorer
 * @group DataExplorerReportMetadataTest
 * @group Plugins
 */
class ReportMetadataTest extends IntegrationTestCase
{
    /** @var array */
    private $backupGet;
    /** @var array<string, int> */
    private $ids = [];

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
        ReportsDao::clearCache();
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site 1', 'https://one.example');
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site 2', 'https://two.example');

        $this->backupGet = $_GET;
        $this->ids['site1'] = $this->save('On site 1', 'table', ['sites' => [1]], 'General_Visitors', 'General_Overview', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);
        $this->ids['all'] = $this->save('Everywhere', 'evolution', ['all' => true], 'General_Actions', 'Campaign pages', ['metrics' => ['nb_actions']]);
        $this->ids['site2'] = $this->save('On site 2', 'table', ['sites' => [2]], 'General_Visitors', 'General_Overview', ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);
        $this->ids['kpi'] = $this->save('Visits card', 'kpicard', ['sites' => [1]], 'General_Visitors', 'General_Overview', ['metrics' => ['nb_visits']]);
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

    public function test_reportMetadata_listsTheReportsOfTheSite_andOfAllWebsites(): void
    {
        $reports = $this->dataExplorerReports(1);

        $this->assertEqualsCanonicalizing(['On site 1', 'Visits card', 'Everywhere'], array_column($reports, 'name'));

        $bySite = array_column($reports, null, 'name');
        $this->assertSame(Piwik::translate('General_Visitors'), $bySite['On site 1']['category']);
        $this->assertSame(Piwik::translate('General_Overview'), $bySite['On site 1']['subcategory']);
        $this->assertSame(['idExploration' => $this->ids['site1']], $bySite['On site 1']['parameters']);
        $this->assertSame(CategoryList::get()->getCategory('General_Actions')->getDisplayName(), $bySite['Everywhere']['category']);
        $this->assertSame('Campaign pages', $bySite['Everywhere']['subcategory']);
        $this->assertArrayHasKey('nb_visits', $bySite['On site 1']['metrics']);
    }

    public function test_reportMetadata_ofSite2_doesNotListTheReportsOfSite1(): void
    {
        $this->assertEqualsCanonicalizing(['On site 2', 'Everywhere'], array_column($this->dataExplorerReports(2), 'name'));
    }

    public function test_missingSubcategory_isCreatedInTheChosenCategory_andTheExplorePageExists(): void
    {
        $_GET['idSite'] = '1';

        $list = CategoryList::get();

        $this->assertTrue($list->getCategory('General_Actions')->hasSubcategory('Campaign pages'));
        $this->assertTrue($list->getCategory('General_Visitors')->hasSubcategory('General_Overview'));
        $this->assertTrue($list->getCategory(ExploreSubcategory::CATEGORY)->hasSubcategory(ExploreSubcategory::ID));
    }

    public function test_reportPages_holdTheWidgetsOfTheSavedReports_withTheirDefaultViews(): void
    {
        $_GET['idSite'] = '1';

        $widgets = [];
        foreach (CoreAPI::getInstance()->getReportPagesMetadata(1) as $page) {
            foreach ($page['widgets'] as $widget) {
                if (($widget['module'] ?? '') === 'DataExplorer') {
                    $widgets[$page['category']['id'] . '/' . $page['subcategory']['id'] . '/' . $widget['name']] = $widget;
                }
            }
        }

        $this->assertArrayHasKey('General_Actions/Campaign pages/Everywhere', $widgets);
        $this->assertArrayHasKey(ExploreSubcategory::CATEGORY . '/' . ExploreSubcategory::ID . '/' . Piwik::translate('DataExplorer_ExploreWidget'), $widgets);

        $table = $widgets['General_Visitors/General_Overview/On site 1'];
        $this->assertSame(HtmlTable::ID, $table['viewDataTable']);
        $this->assertEquals($this->ids['site1'], $table['parameters']['idExploration']);
        $this->assertSame(Evolution::ID, $widgets['General_Actions/Campaign pages/Everywhere']['viewDataTable']);
        $this->assertSame(Sparklines::ID, $widgets['General_Visitors/General_Overview/Visits card']['viewDataTable']);
        $this->assertArrayNotHasKey('General_Visitors/General_Overview/On site 2', $widgets);
    }

    /**
     * The reporting menu opens a page from the subcategory id in the URL hash: an id with characters HTML and URLs
     * treat specially must reach the browser unchanged in the JSON the menu reads, and in the save dialog list.
     */
    public function test_subcategoryWithSpecialCharacters_reachesTheMenuAndTheSaveDialogUnchanged(): void
    {
        $subcategory = 'Q&A <beta> "x": 100% #1';
        $this->save('Special page report', 'table', ['sites' => [1]], 'General_Actions', $subcategory, ['rows' => ['UserCountry.Country'], 'metrics' => ['nb_visits']]);
        $_GET['idSite'] = '1';
        Cache::getTransientCache()->flushAll();

        $json = Request::processRequest('API.getReportPagesMetadata', ['idSite' => 1, 'format' => 'json', 'filter_limit' => -1]);
        $page = null;
        foreach (json_decode($json, true) as $candidate) {
            if ($candidate['subcategory']['id'] === $subcategory) {
                $page = $candidate;
            }
        }
        $this->assertNotNull($page, $json);
        $this->assertSame('General_Actions', $page['category']['id']);
        $this->assertSame($subcategory, $page['subcategory']['name']);
        $this->assertContains('Special page report', array_column($page['widgets'], 'name'));

        $json = Request::processRequest('DataExplorer.getCategories', ['idSite' => 1, 'format' => 'json']);
        $actions = array_column(json_decode($json, true), null, 'id')['General_Actions'];
        $this->assertContains(['id' => $subcategory, 'name' => $subcategory], $actions['subcategories']);
    }

    private function dataExplorerReports(int $idSite): array
    {
        $_GET['idSites'] = (string) $idSite;
        Cache::getTransientCache()->flushAll();

        $reports = CoreAPI::getInstance()->getReportMetadata((string) $idSite);

        return array_values(array_filter($reports, fn (array $report) => $report['module'] === 'DataExplorer'));
    }

    private function save(string $name, string $reportType, array $placement, string $category, string $subcategory, array $definition): int
    {
        static $order = 0;

        return (new ReportsDao())->insert([
            'name' => $name,
            'type' => 'visual',
            'report_type' => $reportType,
            'definition' => $definition,
            'category' => $category,
            'subcategory' => $subcategory,
            'order' => ++$order,
        ], Placement::fromArray($placement));
    }
}
