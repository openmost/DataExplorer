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
use Piwik\CacheId;
use Piwik\Category\CategoryList;
use Piwik\Common;
use Piwik\Config;
use Piwik\Date;
use Piwik\Db;
use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\API\Request;
use Piwik\Plugins\API\API as CoreAPI;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Reports\GetSavedReport;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DataExplorer
 * @group DataExplorerManagementApiTest
 * @group Plugins
 */
class ManagementApiTest extends IntegrationTestCase
{
    private const VISUAL = '{"rows":["UserCountry.Country"],"metrics":["nb_visits"]}';

    /** @var API */
    private $api;
    /** @var array */
    private $backupGet;
    /** @var array */
    private $events = [];

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        Fixture::createSuperUser();
        for ($i = 1; $i <= 3; $i++) {
            Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site ' . $i, 'https://site' . $i . '.example');
        }
        ReportsDao::clearCache();
        Date::$now = strtotime('2024-03-10 12:00:00');
        Config::getInstance()->DataExplorer = ['invalidation_months' => 1];
        $this->backupGet = $_GET;
        $_GET['idSite'] = '1';
        $this->api = API::getInstance();
        $this->events = [];
        foreach (['DataExplorer.reportSaved', 'DataExplorer.reportDeleted'] as $event) {
            Piwik::addAction($event, function (...$args) use ($event) {
                $this->events[] = [$event, $args];
            });
        }
    }

    public function tearDown(): void
    {
        $_GET = $this->backupGet;
        Date::$now = null;

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    private function as(array $view = [], array $write = [], array $admin = [], string $login = 'alice', bool $super = false): void
    {
        FakeAccess::clearAccess($super, $admin, $view, $login, $write);
    }

    private function asSuperUser(): void
    {
        $this->as([], [], [], 'root', true);
    }

    private function add(string $placement = '{"sites":[1]}', string $type = 'visual', string $definition = self::VISUAL, string $name = 'Countries', string $category = 'General_Visitors', string $subcategory = 'Explorations', int $order = 10): int
    {
        return $this->api->addReport($name, $type, 'table', $definition, $placement, $category, $subcategory, $order);
    }

    private function update(int $idReport, string $placement = '{"sites":[1]}', string $type = 'visual', string $definition = self::VISUAL, string $name = 'Countries'): void
    {
        $this->api->updateReport($idReport, $name, $type, 'table', $definition, $placement, 'General_Visitors', 'Explorations', 10);
    }

    private function sql(string $sql): string
    {
        return (string) json_encode(['sql' => $sql]);
    }

    private function assertDenied(callable $call): void
    {
        try {
            $call();
        } catch (NoAccessException $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('NoAccessException expected');
    }

    private function invalidatedSites(): array
    {
        $rows = Db::fetchAll('SELECT DISTINCT idsite FROM ' . Common::prefixTable('archive_invalidations') . " WHERE name LIKE '%.DataExplorer' ORDER BY idsite");

        return array_map('intval', array_column($rows, 'idsite'));
    }

    private function clearInvalidations(): void
    {
        Db::query('DELETE FROM ' . Common::prefixTable('archive_invalidations'));
    }

    public function test_writeUser_savesOnItsSite(): void
    {
        $this->as([1], [1]);

        $id = $this->add();

        $report = (new ReportsDao())->get($id);
        $this->assertSame('Countries', $report['name']);
        $this->assertSame('alice', $report['created_by']);
        $this->assertSame([1], $report['placement']->getSiteIds());
        $this->assertSame(['UserCountry.Country'], $report['definition']['rows']);
        $this->assertSame('General_Visitors', $report['category']);
        $this->assertSame('Explorations', $report['subcategory']);
    }

    public function test_writeOnOneSite_cannotSaveOnTwoSites(): void
    {
        $this->as([1, 2], [1]);
        $this->assertDenied(function () { $this->add('{"sites":[1,2]}'); });
    }

    public function test_nonSuperUser_cannotSaveOnAllWebsites(): void
    {
        $this->as([], [], [1, 2, 3]);
        $this->assertDenied(function () { $this->add('{"all":true}'); });
    }

    public function test_adminOfTheSite_cannotCreateSqlReport_superUserCan(): void
    {
        $sql = $this->sql('SELECT COUNT(*) AS visits FROM ' . Common::prefixTable('log_visit') . ' WHERE idsite = {idsite}');
        $this->as([], [], [1]);
        $this->assertDenied(function () use ($sql) { $this->add('{"sites":[1]}', 'sql', $sql); });

        $this->asSuperUser();
        $id = $this->add('{"sites":[1]}', 'sql', $sql);
        $this->assertSame('sql', (new ReportsDao())->get($id)['type']);
    }

    public function test_sqlWithoutSiteFilterOnALogTable_isRefusedNamingTheTable(): void
    {
        $this->asSuperUser();
        try {
            $this->add('{"sites":[1]}', 'sql', $this->sql('SELECT COUNT(*) FROM ' . Common::prefixTable('log_visit')));
        } catch (RejectedQueryException $e) {
            $this->assertStringContainsString('log_visit', $e->getMessage());
            $this->assertSame([], (new ReportsDao())->getAll());
            return;
        }
        $this->fail('RejectedQueryException expected');
    }

    public function test_sqlRefusedByTheGuard_isNotSaved(): void
    {
        $this->asSuperUser();
        $this->expectException(RejectedQueryException::class);
        $this->add('{"sites":[1]}', 'sql', $this->sql('SELECT login FROM ' . Common::prefixTable('user')));
    }

    public function test_sqlTouchingNoPerSiteTable_isAccepted(): void
    {
        $this->asSuperUser();
        $id = $this->add('{"sites":[1]}', 'sql', $this->sql('SELECT name FROM ' . Common::prefixTable('log_action') . ' LIMIT 5'));
        $this->assertGreaterThan(0, $id);
    }

    public function test_adminOfTheSite_deletesAnotherUsersReport(): void
    {
        $this->as([1], [1], [], 'alice');
        $id = $this->add();

        $this->as([1], [], [1], 'bob');
        $this->api->deleteReport($id);

        $this->assertSame(1, (new ReportsDao())->get($id)['deleted']);
        $this->assertSame('DataExplorer.reportDeleted', end($this->events)[0]);
    }

    public function test_writeUser_cannotDeleteSomeoneElsesReport(): void
    {
        $this->as([1], [1], [], 'alice');
        $id = $this->add();

        $this->as([1], [1], [], 'bob');
        $this->assertDenied(function () use ($id) { $this->api->deleteReport($id); });
        $this->assertSame(0, (new ReportsDao())->get($id)['deleted']);
    }

    public function test_add_invalidatesTheHistoryOfThePlacedSites_andPostsTheEvent(): void
    {
        $this->as([1, 2], [1, 2]);
        $this->clearInvalidations();

        $id = $this->add('{"sites":[2]}');

        $this->assertSame([2], $this->invalidatedSites());
        $this->assertSame('DataExplorer.reportSaved', $this->events[0][0]);
        $this->assertSame($id, $this->events[0][1][0]);
    }

    public function test_sqlReport_doesNotInvalidate(): void
    {
        $this->asSuperUser();
        $this->clearInvalidations();
        $this->add('{"sites":[1]}', 'sql', $this->sql('SELECT name FROM ' . Common::prefixTable('log_action')));
        $this->assertSame([], $this->invalidatedSites());
    }

    public function test_adminOfTheSite_cannotUpdateOrDeleteSqlReport_superUserCan(): void
    {
        $this->asSuperUser();
        $sql = $this->sql('SELECT name FROM ' . Common::prefixTable('log_action'));
        $id = $this->add('{"sites":[1]}', 'sql', $sql);

        $this->as([], [], [1], 'bob');
        $this->assertDenied(function () use ($id, $sql) { $this->update($id, '{"sites":[1]}', 'sql', $sql, 'Renamed'); });
        $this->assertDenied(function () use ($id) { $this->update($id); });
        $this->assertDenied(function () use ($id) { $this->api->deleteReport($id); });

        $this->asSuperUser();
        $this->update($id, '{"sites":[1]}', 'sql', $sql, 'Renamed');
        $this->assertSame('Renamed', (new ReportsDao())->get($id)['name']);
        $this->api->deleteReport($id);
        $this->assertSame(1, (new ReportsDao())->get($id)['deleted']);
    }

    public function test_writeUser_cannotConvertItsVisualReportToSql(): void
    {
        $this->as([1], [1]);
        $id = $this->add();

        $this->assertDenied(function () use ($id) {
            $this->update($id, '{"sites":[1]}', 'sql', $this->sql('SELECT name FROM ' . Common::prefixTable('log_action')));
        });
        $this->assertSame('visual', (new ReportsDao())->get($id)['type']);
    }

    public function test_movingAReport_toASiteWithoutWriteAccess_isRefused(): void
    {
        $this->as([1, 2], [1], [], 'alice');
        $id = $this->add();

        $this->assertDenied(function () use ($id) { $this->update($id, '{"sites":[2]}'); });
        $this->assertDenied(function () use ($id) { $this->update($id, '{"sites":[1,2]}'); });
        $this->assertSame([1], (new ReportsDao())->get($id)['placement']->getSiteIds());
    }

    public function test_writeUserOfTheNewSite_cannotTakeAReportOfAnotherSite(): void
    {
        $this->as([2], [2], [], 'alice');
        $id = $this->add('{"sites":[2]}');

        $this->as([1, 2], [1], [], 'alice');
        $this->assertDenied(function () use ($id) { $this->update($id, '{"sites":[1]}'); });
    }

    public function test_updatingOrDeletingADeletedReport_isRefused(): void
    {
        $this->asSuperUser();
        $id = $this->add();
        $this->api->deleteReport($id);

        foreach ([fn () => $this->update($id), fn () => $this->api->deleteReport($id), fn () => $this->update(999999)] as $call) {
            try {
                $call();
                $this->fail('Exception expected');
            } catch (\Exception $e) {
                $this->assertSame(Piwik::translate('DataExplorer_ErrorReportNotFound'), $e->getMessage());
            }
        }
    }

    public function test_update_ofAComputedField_bumpsTheRevision_andInvalidatesThePlacedSites(): void
    {
        $this->as([1, 2], [1, 2]);
        $id = $this->add('{"sites":[1,2]}');
        $this->clearInvalidations();

        $this->update($id, '{"sites":[1,2]}', 'visual', '{"rows":["UserCountry.Country"],"metrics":["nb_actions"]}');

        $report = (new ReportsDao())->get($id);
        $this->assertSame(2, $report['revision']);
        $this->assertSame('alice', $report['created_by']);
        $this->assertSame([1, 2], $this->invalidatedSites());
        $this->assertSame('DataExplorer.reportSaved', end($this->events)[0]);
    }

    public function test_update_ofTheReportType_bumpsTheRevision(): void
    {
        $this->as([1], [1]);
        $id = $this->add();
        $this->clearInvalidations();

        $this->api->updateReport($id, 'Countries', 'visual', 'evolution', '{"metrics":["nb_visits"]}', '{"sites":[1]}', 'General_Visitors', 'Explorations', 10);

        $this->assertSame(2, (new ReportsDao())->get($id)['revision']);
        $this->assertSame([1], $this->invalidatedSites());
    }

    public function test_renameReorderOrRecategorize_keepsTheRevision_andInvalidatesNothing(): void
    {
        $this->as([1], [1]);
        $id = $this->add();
        $this->clearInvalidations();

        $this->api->updateReport($id, 'Renamed', 'visual', 'table', self::VISUAL, '{"sites":[1]}', 'General_Actions', 'Other page', 42);

        $report = (new ReportsDao())->get($id);
        $this->assertSame('Renamed', $report['name']);
        $this->assertSame('General_Actions', $report['category']);
        $this->assertSame(42, $report['order']);
        $this->assertSame(1, $report['revision']);
        $this->assertSame([], $this->invalidatedSites());
        $this->assertSame('DataExplorer.reportSaved', end($this->events)[0]);
    }

    public function test_chartChange_keepsTheRevision_andInvalidatesNothing(): void
    {
        $this->as([1], [1]);
        $id = $this->add();
        $this->clearInvalidations();

        $definition = json_decode(self::VISUAL, true);
        $this->api->updateReport($id, 'Pie', 'visual', 'table', json_encode(['chart' => 'pie'] + $definition), '{"sites":[1]}', 'General_Actions', 'Other page');

        $report = (new ReportsDao())->get($id);
        $this->assertSame('pie', $report['definition']['chart']);
        $this->assertSame(1, $report['revision']);
        $this->assertSame([], $this->invalidatedSites());
    }

    public function test_placementChange_invalidatesTheAddedSitesOnly_andKeepsTheRevision(): void
    {
        $this->as([1, 2, 3], [1, 2, 3]);
        $id = $this->add('{"sites":[1,2]}');
        $this->clearInvalidations();

        $this->update($id, '{"sites":[2,3]}', 'visual', self::VISUAL, 'Moved');

        $report = (new ReportsDao())->get($id);
        $this->assertSame(1, $report['revision']);
        $this->assertSame([2, 3], $report['placement']->getSiteIds());
        $this->assertSame([3], $this->invalidatedSites());
    }

    public function test_placementChange_toAllWebsites_invalidatesTheSitesNotPlacedBefore(): void
    {
        $this->asSuperUser();
        $id = $this->add('{"sites":[1]}');
        $this->clearInvalidations();

        $this->update($id, '{"all":true}');

        $this->assertSame(1, (new ReportsDao())->get($id)['revision']);
        $this->assertSame([2, 3], $this->invalidatedSites());
    }

    public function test_delete_invalidatesNothing(): void
    {
        $this->as([1], [1]);
        $id = $this->add();
        $this->clearInvalidations();

        $this->api->deleteReport($id);

        $this->assertSame([], $this->invalidatedSites());
    }

    public function test_getReports_redactsSqlForNonSuperUsers_andFlagsEditable(): void
    {
        $this->asSuperUser();
        $sqlText = 'SELECT name FROM ' . Common::prefixTable('log_action');
        $sqlId = $this->add('{"sites":[1]}', 'sql', $this->sql($sqlText), 'Raw');
        $this->as([1], [1], [], 'alice');
        $ownId = $this->add('{"sites":[1]}', 'visual', self::VISUAL, 'Own');
        $this->as([1], [1], [], 'bob');
        $otherId = $this->add('{"sites":[1]}', 'visual', self::VISUAL, 'Other');
        $this->asSuperUser();
        $this->add('{"sites":[2]}', 'visual', self::VISUAL, 'Elsewhere');

        $this->as([1], [1], [], 'alice');
        $reports = array_column($this->api->getReports(1), null, 'idreport');

        $this->assertEqualsCanonicalizing([$sqlId, $ownId, $otherId], array_keys($reports));
        $this->assertSame('', $reports[$sqlId]['definition']['sql']);
        $this->assertFalse($reports[$sqlId]['editable']);
        $this->assertTrue($reports[$ownId]['editable']);
        $this->assertFalse($reports[$otherId]['editable']);
        $this->assertSame(['all' => false, 'sites' => [1]], $reports[$ownId]['placement']);

        $this->as([1], [], [], 'carol');
        foreach ($this->api->getReports(1) as $report) {
            $this->assertFalse($report['editable']);
            $this->assertSame('', $report['definition']['sql']);
        }

        $this->asSuperUser();
        $reports = array_column($this->api->getReports(1), null, 'idreport');
        $this->assertSame($sqlText, $reports[$sqlId]['definition']['sql']);
        $this->assertTrue($reports[$sqlId]['editable']);
    }

    public function test_getReports_needsViewAccess(): void
    {
        $this->as([2]);
        $this->assertDenied(function () { $this->api->getReports(1); });
    }

    public function test_getReports_hidesTheSitesTheUserCannotView(): void
    {
        $this->asSuperUser();
        $this->add('{"sites":[1,2]}');

        $this->as([1]);
        $this->assertSame([1], $this->api->getReports(1)[0]['placement']['sites']);
    }

    public function test_add_isListedInTheReportMetadataOfTheSameRequest(): void
    {
        $this->asSuperUser();
        $_GET['idSites'] = '1';
        $cacheKey = CacheId::siteAware('Archiver.RecordBuilders') . '.DataExplorer';
        Cache::getTransientCache()->save($cacheKey, []);
        $names = fn () => array_column(array_filter(CoreAPI::getInstance()->getReportMetadata('1'), fn ($r) => $r['module'] === 'DataExplorer'), 'name');
        $this->assertNotContains('Fresh report', $names());

        $this->add('{"sites":[1]}', 'visual', self::VISUAL, 'Fresh report');

        $this->assertContains('Fresh report', $names());
        $this->assertFalse(Cache::getTransientCache()->contains($cacheKey));
    }

    public function test_categoryAndSubcategory_validation(): void
    {
        $this->as([1], [1]);

        $id = $this->api->addReport('Default place', 'visual', 'table', self::VISUAL, '{"sites":[1]}', '', 'My page', 5000);
        $report = (new ReportsDao())->get($id);
        $this->assertSame(ExploreSubcategory::CATEGORY, $report['category']);
        $this->assertSame('My page', $report['subcategory']);
        $this->assertSame(1000, $report['order']);

        $id = $this->api->addReport('Trimmed', 'visual', 'table', self::VISUAL, '{"sites":[1]}', ExploreSubcategory::CATEGORY, '  <b>New</b> page  ', -4);
        $report = (new ReportsDao())->get($id);
        $this->assertSame('<b>New</b> page', $report['subcategory']);
        $this->assertSame(0, $report['order']);

        $cases = [
            ['Unknown_Category', 'x', Piwik::translate('DataExplorer_ErrorCategoryInvalid')],
            ['General_Visitors', str_repeat('a', 256), Piwik::translate('DataExplorer_ErrorSubcategoryInvalid', [255])],
        ];
        foreach ($cases as [$category, $subcategory, $message]) {
            try {
                $this->api->addReport('Bad', 'visual', 'table', self::VISUAL, '{"sites":[1]}', $category, $subcategory);
                $this->fail('InvalidArgumentException expected');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    public function test_withoutPage_theReportGetsItsOwnPage_numberedInTheDataExplorerCategory(): void
    {
        $this->as([1], [1]);

        $own = $this->api->addReport('Visits by country', 'visual', 'table', self::VISUAL, '{"sites":[1]}', '', '   ');
        $elsewhere = $this->api->addReport('Visits elsewhere', 'visual', 'table', self::VISUAL, '{"sites":[1]}', 'General_Visitors', '');

        $this->assertSame('', (new ReportsDao())->get($own)['subcategory']);
        // numeric like goals and custom reports, so the menu collapses them into a searchable list past 5
        $this->assertSame((string) $own, GetSavedReport::pageId(['idreport' => $own, 'category' => ExploreSubcategory::CATEGORY, 'subcategory' => '']));
        // another plugin's category may number its own pages
        $this->assertSame('dataexplorer-report-' . $elsewhere, GetSavedReport::pageId(['idreport' => $elsewhere, 'category' => 'General_Visitors', 'subcategory' => '']));

        $_GET['idSite'] = 1;
        try {
            Cache::getTransientCache()->flushAll();
            $pages = [];
            foreach (CategoryList::get()->getCategories() as $category) {
                foreach ($category->getSubcategories() as $subcategory) {
                    $pages[$category->getId() . '|' . $subcategory->getId()] = $subcategory->getName();
                }
            }
            $offered = array_column(array_column($this->api->getCategories(1), null, 'id')[ExploreSubcategory::CATEGORY]['subcategories'], 'id');
        } finally {
            unset($_GET['idSite']);
        }
        $this->assertSame('Visits by country', $pages[ExploreSubcategory::CATEGORY . '|' . $own]);
        $this->assertSame('Visits elsewhere', $pages['General_Visitors|dataexplorer-report-' . $elsewhere]);
        $this->assertNotContains((string) $own, $offered, 'a page of its own is not offered to other reports');
    }

    public function test_savedWithTheDefaultCategory_isAPageOfTheReportingMenu(): void
    {
        $this->as([1], [1]);
        $this->api->addReport('Default place', 'visual', 'table', self::VISUAL, '{"sites":[1]}', '', 'My page');

        $categories = array_column($this->api->getCategories(1), null, 'id');
        $this->assertContains('My page', array_column($categories[ExploreSubcategory::CATEGORY]['subcategories'], 'id'));
    }

    public function test_visualReport_neverStoresSqlText(): void
    {
        $this->as([1], [1]);

        $id = $this->add('{"sites":[1]}', 'visual', '{"rows":["UserCountry.Country"],"metrics":["nb_visits"],"sql":"SELECT login FROM user"}');

        $this->assertSame('', (new ReportsDao())->get($id)['definition']['sql']);
        $this->asSuperUser();
        $this->assertSame('', $this->api->getReports(1)[0]['definition']['sql']);
    }

    public function test_sqlReport_storesOnlyItsQuery(): void
    {
        $this->asSuperUser();
        $sql = 'SELECT name FROM ' . Common::prefixTable('log_action');

        $id = $this->add('{"sites":[1]}', 'sql', (string) json_encode(['sql' => $sql, 'rows' => ['x'], 'metrics' => ['y'], 'segment' => 'nope']));

        $this->assertSame(['description' => '', 'technique' => 'freeform', 'sql' => $sql, 'chart' => ''], (new ReportsDao())->get($id)['definition']);
    }

    /**
     * @dataProvider invalidVisualProvider
     */
    public function test_visualDefinition_isResolvedAgainstTheCatalogueOfEveryListedSite(string $definition, string $key): void
    {
        $this->as([1], [1]);
        try {
            $this->add('{"sites":[1]}', 'visual', $definition);
            $this->fail('InvalidArgumentException expected');
        } catch (\InvalidArgumentException $e) {
            $message = Piwik::translate($key);
            $prefix = strpos($message, '%') === false ? $message : strstr($message, '%', true);
            $this->assertStringStartsWith($prefix, $e->getMessage());
        }
        $this->assertSame([], (new ReportsDao())->getAll());
    }

    public function invalidVisualProvider(): array
    {
        return [
            'unknown dimension' => ['{"rows":["Nope.Nope"],"metrics":["nb_visits"]}', 'DataExplorer_ErrorUnknownDimension'],
            'unknown metric' => ['{"rows":["UserCountry.Country"],"metrics":["nope"]}', 'DataExplorer_ErrorUnknownMetric'],
            'invalid segment' => ['{"rows":["UserCountry.Country"],"metrics":["nb_visits"],"segment":"notADimension==1"}', 'DataExplorer_ErrorSegmentInvalid'],
        ];
    }

    public function test_customDimensionOfSite1_refusedOnASiteList_acceptedOnAllWebsites(): void
    {
        $this->asSuperUser();
        \Piwik\Plugins\CustomDimensions\API::getInstance()->configureNewCustomDimension(1, 'Plan', 'visit', true);
        Cache::getTransientCache()->flushAll();
        $ids = array_values(array_filter(
            array_keys((new \Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog())->all(1)),
            fn ($id) => strpos($id, 'CustomDimension.') === 0
        ));
        $this->assertCount(1, $ids);
        $definition = (string) json_encode(['rows' => [$ids[0]], 'metrics' => ['nb_visits']]);

        try {
            $this->add('{"sites":[1,2]}', 'visual', $definition);
            $this->fail('InvalidArgumentException expected');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorUnknownDimension', [$ids[0]]), $e->getMessage());
        }

        $this->assertGreaterThan(0, $this->add('{"sites":[1]}', 'visual', $definition));
        $this->assertGreaterThan(0, $this->add('{"all":true}', 'visual', $definition));
    }

    public function test_unknownSiteIds_areRefused(): void
    {
        $this->asSuperUser();
        try {
            $this->add('{"sites":[1,99]}');
            $this->fail('InvalidArgumentException expected');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorSiteUnknown', [99]), $e->getMessage());
        }
    }

    public function test_invalidDefinitionOrPlacement_isRefusedWithATranslatedMessage(): void
    {
        $this->as([1], [1]);
        foreach ([['{"sites":[1]}', 'not json'], ['nope', self::VISUAL], ['{"sites":[1]}', '{"rows":["UserCountry.Country"],"metrics":[]}']] as [$placement, $definition]) {
            try {
                $this->add($placement, 'visual', $definition);
                $this->fail('InvalidArgumentException expected');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringNotContainsString('DataExplorer_', $e->getMessage());
            }
        }
        $this->assertSame([], (new ReportsDao())->getAll());
    }

    public function test_httpApi_receivesTheJsonParametersUnsanitized(): void
    {
        $this->as([1], [1]);

        $id = Request::processRequest('DataExplorer.addReport', [
            'name' => 'Visits & "pages"',
            'type' => 'visual',
            'reportType' => 'table',
            'definition' => self::VISUAL,
            'placement' => '{"sites":[1]}',
            'category' => 'General_Visitors',
            'subcategory' => 'Q&A',
            'format' => 'original',
        ]);

        $report = (new ReportsDao())->get((int) $id);
        $this->assertSame('Visits & "pages"', $report['name']);
        $this->assertSame('Q&A', $report['subcategory']);
    }

    public function test_httpApi_sqlWithQuotesAndComparisons_reachesTheGuardUnsanitized(): void
    {
        $this->asSuperUser();

        $result = Request::processRequest('DataExplorer.validateSql', [
            'sql' => "SELECT name FROM " . Common::prefixTable('log_action') . " WHERE name <> 'x' AND type > 0",
            'format' => 'original',
        ]);

        $this->assertTrue($result['valid'], json_encode($result));
    }

    public function test_getCategories_listsReportingCategoriesWithTheirSubcategories(): void
    {
        $this->as([1]);

        $categories = array_column($this->api->getCategories(1), null, 'id');

        $this->assertArrayHasKey('General_Visitors', $categories);
        $this->assertSame(Piwik::translate('General_Visitors'), $categories['General_Visitors']['name']);
        $subcategories = array_column($categories['General_Visitors']['subcategories'], 'name', 'id');
        $this->assertSame(Piwik::translate('General_Overview'), $subcategories['General_Overview']);
        $this->assertArrayHasKey(ExploreSubcategory::CATEGORY, $categories);
        $this->assertArrayNotHasKey('Dashboard_Dashboard', $categories);

        $this->as([2]);
        $this->assertDenied(function () { $this->api->getCategories(1); });
    }
}
