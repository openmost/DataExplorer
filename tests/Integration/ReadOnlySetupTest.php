<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\Common;
use Piwik\Config;
use Piwik\NoAccessException;
use Piwik\Plugins\DataExplorer\Controller;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlySetup;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DataExplorer
 * @group ReadOnlySetupTest
 * @group Plugins
 */
class ReadOnlySetupTest extends IntegrationTestCase
{
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
        Fixture::createSuperUser();
        $this->originalSection = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = [];
        ReadOnlyConnection::reset();
    }

    public function tearDown(): void
    {
        Config::getInstance()->DataExplorer = $this->originalSection;
        ReadOnlyConnection::reset();
        parent::tearDown();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_grantStatements_listEachExistingAllowedTable(): void
    {
        $sql = (new ReadOnlySetup())->sqlSnippet();
        $database = Config::getInstance()->database['dbname'];

        $this->assertStringContainsString("CREATE USER 'matomo_readonly'@'localhost' IDENTIFIED BY '", $sql);
        $this->assertStringContainsString('GRANT SELECT ON `' . $database . '`.`' . Common::prefixTable('log_visit') . "` TO 'matomo_readonly'@'localhost';", $sql);
        $this->assertStringContainsString('`' . Common::prefixTable('site') . '`', $sql);
        foreach (['user', 'option', 'access', 'session', 'site_setting', 'archive_invalidations', 'log_profiling', 'plugin_setting'] as $table) {
            $this->assertStringNotContainsString('`' . Common::prefixTable($table) . '`', $sql, $table);
        }
        $this->assertStringNotContainsString('.*', $sql);
    }

    public function test_configuredUserNameIsUsed_butNeverItsPassword(): void
    {
        Config::getInstance()->DataExplorer = ['readonly_username' => "ro'user", 'readonly_password' => 'Sup3rSecret!'];
        $setup = new ReadOnlySetup();

        $this->assertStringContainsString("'ro''user'@'localhost'", $setup->sqlSnippet());
        $this->assertStringContainsString('readonly_username = "ro\'user"', $setup->iniBlock());
        $this->assertStringNotContainsString('Sup3rSecret!', $setup->sqlSnippet() . $setup->iniBlock());
    }

    public function test_iniBlock(): void
    {
        $ini = (new ReadOnlySetup())->iniBlock();

        $this->assertStringStartsWith("[DataExplorer]\nreadonly_username = \"matomo_readonly\"\nreadonly_password = \"", $ini);
    }

    public function test_status_notConfigured(): void
    {
        $status = (new ReadOnlySetup())->status();

        $this->assertSame('not_configured', $status['state']);
        $this->assertNotSame('', $status['message']);
    }

    public function test_status_connectionFailure_isReported(): void
    {
        Config::getInstance()->DataExplorer = ['readonly_username' => 'dataexplorer_nobody', 'readonly_password' => 'wrong'];

        $status = (new ReadOnlySetup())->status();

        $this->assertSame('connection_failed', $status['state']);
        $this->assertStringNotContainsString('wrong', $status['message']);
    }

    public function test_setupPage_rendersTheSnippets(): void
    {
        FakeAccess::clearAccess(true);
        Fixture::createWebsite('2024-01-01 00:00:00');
        $_GET['idSite'] = 1;

        try {
            $html = (new Controller())->setup();
        } finally {
            unset($_GET['idSite']);
        }

        $this->assertStringContainsString('CoreHome.ContentBlock', $html);
        $this->assertStringContainsString('GRANT SELECT ON', $html);
        $this->assertStringContainsString('[DataExplorer]', $html);
    }

    public function test_setupPage_isSuperUserOnly(): void
    {
        FakeAccess::clearAccess(false, [1], [], 'admin');

        $this->expectException(NoAccessException::class);
        (new Controller())->setup();
    }
}
