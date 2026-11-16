<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\Common;
use Piwik\Config;
use Piwik\FrontController;
use Piwik\NoAccessException;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * What the explorer page needs from the server: the SQL editor schema and the explorer widget mount point.
 *
 * @group DataExplorer
 * @group ExplorerUiTest
 * @group Plugins
 */
class ExplorerUiTest extends IntegrationTestCase
{
    /** @var array */
    private $backupGet;
    /** @var array */
    private $originalSection;

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site <one> & "co"');
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site 2');
        Fixture::loadAllTranslations();
        $this->backupGet = $_GET;
        $this->originalSection = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = [];
    }

    public function tearDown(): void
    {
        $_GET = $this->backupGet;
        Config::getInstance()->DataExplorer = $this->originalSection;

        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_getSqlSchema_listsTheAllowedTablesWithTheirColumns(): void
    {
        FakeAccess::clearAccess(true);

        $schema = API::getInstance()->getSqlSchema();

        $logVisit = Common::prefixTable('log_visit');
        $this->assertArrayHasKey($logVisit, $schema);
        $this->assertContains('idvisit', $schema[$logVisit]);
        $this->assertContains('idsite', $schema[$logVisit]);
        $this->assertArrayHasKey(Common::prefixTable('log_action'), $schema);
        $this->assertArrayNotHasKey(Common::prefixTable('user'), $schema);
        $this->assertArrayNotHasKey(Common::prefixTable('option'), $schema);
        $this->assertArrayNotHasKey(Common::prefixTable('dataexplorer_report'), $schema);
        $prefixLength = strlen(Common::prefixTable(''));
        foreach (array_keys($schema) as $table) {
            $this->assertTrue(SqlGuard::isAllowedTable(substr($table, $prefixLength)), $table);
        }
    }

    public function test_getSqlSchema_isRefusedToNonSuperUsers(): void
    {
        FakeAccess::clearAccess(false, [1], [1], 'admin');

        $this->expectException(NoAccessException::class);
        API::getInstance()->getSqlSchema();
    }

    public function test_explorerWidget_viewUser_mountsTheExplorerWithoutRawSql(): void
    {
        FakeAccess::clearAccess(false, [], [1], 'viewer');

        $props = $this->explorerProps();

        $this->assertFalse($props['is-super-user']);
        $this->assertFalse($props['can-save']);
        $this->assertNull($props['sql-status']);
        $this->assertSame([], $props['writable-sites']);
    }

    public function test_explorerWidget_writeUser_canSaveOnTheSitesItCanWrite(): void
    {
        FakeAccess::clearAccess(false, [], [1, 2], 'writer', [1]);

        $props = $this->explorerProps();

        $this->assertTrue($props['can-save']);
        $this->assertSame([['id' => 1, 'name' => 'Site <one> & "co"']], $props['writable-sites']);
    }

    public function test_explorerWidget_superUser_getsTheRawSqlStatusAndTheSetupLink(): void
    {
        FakeAccess::clearAccess(true);

        $props = $this->explorerProps();

        $this->assertTrue($props['is-super-user']);
        $this->assertSame('not_configured', $props['sql-status']['state']);
        $this->assertNotSame('', $props['sql-status']['message']);
        $this->assertStringContainsString('module=DataExplorer', $props['setup-url']);
        $this->assertStringContainsString('action=setup', $props['setup-url']);
        $this->assertCount(2, $props['writable-sites']);
    }

    public function test_explorerWidget_isRefusedWithoutViewAccess(): void
    {
        FakeAccess::clearAccess(false, [], [2], 'other');

        // core refuses a widget that is not enabled for the user before rendering it
        $level = ob_get_level();
        $refused = null;
        try {
            $this->dispatch();
        } catch (\Exception $e) {
            $refused = $e;
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        $this->assertNotNull($refused, 'The explorer widget rendered without view access.');
    }

    public function test_clientSideTranslations_includeTheExplorerKeys(): void
    {
        $keys = [];
        \Piwik\Piwik::postEvent('Translate.getClientSideTranslationKeys', [&$keys]);

        $this->assertContains('DataExplorer_Rows', $keys);
        $this->assertContains('DataExplorer_SaveDialogTitle', $keys);
        $this->assertContains('DataExplorer_SqlRun', $keys);
    }

    private function dispatch(): string
    {
        $_GET = ['module' => 'DataExplorer', 'action' => 'explorerWidget', 'idSite' => '1', 'period' => 'day', 'date' => '2024-03-01', 'widget' => '1'];

        return (string) FrontController::getInstance()->fetchDispatch('DataExplorer', 'explorerWidget');
    }

    /**
     * @return array attribute => decoded JSON value of the explorer mount point
     */
    private function explorerProps(): array
    {
        $html = $this->dispatch();
        $this->assertSame(1, preg_match('/<div class="dataExplorerExplorer" vue-entry="DataExplorer.Explorer"([^>]*)>/', $html, $match), $html);
        preg_match_all('/ ([a-z-]+)="([^"]*)"/', $match[1], $attributes, PREG_SET_ORDER);

        $props = [];
        foreach ($attributes as [, $name, $value]) {
            $props[$name] = json_decode(html_entity_decode($value, ENT_QUOTES), true);
        }

        return $props;
    }
}
