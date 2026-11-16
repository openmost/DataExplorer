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
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Date;
use Piwik\Db;
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
    private const SETUP_USER = 'dataexplorer_setup';
    private const HOSTS = ['localhost', '127.0.0.1', '::1'];

    /** @var mixed */
    private $originalSection;

    /** @var mixed */
    private $originalDatabase;

    /** @var string */
    private $password;

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
        $this->originalDatabase = Config::getInstance()->database;
        $this->password = 'p' . bin2hex(random_bytes(12));
        $this->dropUser();
        Config::getInstance()->DataExplorer = [];
        ReadOnlyConnection::reset();
    }

    public function tearDown(): void
    {
        Config::getInstance()->DataExplorer = $this->originalSection;
        Config::getInstance()->database = $this->originalDatabase;
        ReadOnlyConnection::reset();
        $this->dropUser();
        $left = Db::fetchOne('SELECT COUNT(*) FROM mysql.user WHERE user = ?', [self::SETUP_USER]);
        parent::tearDown();
        $this->assertEquals(0, $left, 'test MySQL user left behind');
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_grantStatements_listEachExistingAllowedTable(): void
    {
        ArchiveTableCreator::getNumericTable(Date::factory('2024-01-15'), true);
        $sql = (new ReadOnlySetup())->sqlSnippet();
        $database = Config::getInstance()->database['dbname'];

        $this->assertStringContainsString("CREATE USER 'matomo_readonly'@'127.0.0.1' IDENTIFIED BY '", $sql);
        $this->assertStringContainsString('GRANT SELECT ON `' . $database . '`.`' . Common::prefixTable('log_visit') . "` TO 'matomo_readonly'@'127.0.0.1';", $sql);
        $this->assertStringContainsString('`' . Common::prefixTable('site') . '`', $sql);
        foreach (['user', 'option', 'access', 'session', 'site_setting', 'archive_invalidations', 'archive_numeric_2024_01', 'log_profiling', 'plugin_setting'] as $table) {
            $this->assertStringNotContainsString('`' . Common::prefixTable($table) . '`', $sql, $table);
        }
        $this->assertStringNotContainsString('.*', $sql);
    }

    public function test_configuredUserNameIsUsed_butNeverItsPassword(): void
    {
        Config::getInstance()->DataExplorer = ['readonly_username' => "ro'user", 'readonly_password' => 'Sup3rSecret!'];
        $setup = new ReadOnlySetup();

        $this->assertStringContainsString("'ro''user'@'127.0.0.1'", $setup->sqlSnippet());
        $this->assertStringContainsString('readonly_username = "ro\'user"', $setup->iniBlock());
        $this->assertStringNotContainsString('Sup3rSecret!', $setup->sqlSnippet() . $setup->iniBlock());
    }

    /**
     * @dataProvider hosts
     */
    public function test_accountHost_followsTheDatabaseHost(array $database, string $expected, bool $anyHost): void
    {
        Config::getInstance()->database = array_merge($this->originalDatabase, ['unix_socket' => ''], $database);
        $setup = new ReadOnlySetup();

        $this->assertStringContainsString("CREATE USER 'matomo_readonly'@'" . $expected . "'", $setup->sqlSnippet());
        $this->assertSame($anyHost, $setup->isAnyHost());
    }

    public function hosts(): array
    {
        return [
            'localhost' => [['host' => 'localhost'], 'localhost', false],
            'ipv4 loopback' => [['host' => '127.0.0.1'], '127.0.0.1', false],
            'ipv6 loopback' => [['host' => '::1'], '::1', false],
            'socket' => [['host' => '127.0.0.1', 'unix_socket' => '/tmp/mysql.sock'], 'localhost', false],
            'remote' => [['host' => 'db.example'], '%', true],
        ];
    }

    public function test_status_missingGrants_listsTheMissingLines(): void
    {
        $this->createUser([Common::prefixTable('site')]);
        Config::getInstance()->DataExplorer = ['readonly_username' => self::SETUP_USER, 'readonly_password' => $this->password];
        $setup = new ReadOnlySetup();

        $status = $setup->status();

        $this->assertSame('missing_grants', $status['state']);
        $lines = $setup->missingGrantLines();
        $this->assertContains('GRANT SELECT ON `' . Config::getInstance()->database['dbname'] . '`.`' . Common::prefixTable('log_visit') . "` TO '" . self::SETUP_USER . "'@'127.0.0.1';", $lines);
        $this->assertStringNotContainsString('`' . Common::prefixTable('site') . '`', implode("\n", $lines));
    }

    public function test_status_ready_whenEveryAllowedTableIsGranted(): void
    {
        $setup = new ReadOnlySetup();
        $this->createUser($setup->tables());
        Config::getInstance()->DataExplorer = ['readonly_username' => self::SETUP_USER, 'readonly_password' => $this->password];

        $this->assertSame('ready', $setup->status()['state']);
        $this->assertSame([], $setup->missingGrantLines());
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

    private function createUser(array $tables): void
    {
        $database = Config::getInstance()->database['dbname'];
        foreach (self::HOSTS as $host) {
            Db::exec(sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", self::SETUP_USER, $host, $this->password));
            foreach ($tables as $table) {
                Db::exec(sprintf("GRANT SELECT ON `%s`.`%s` TO '%s'@'%s'", $database, $table, self::SETUP_USER, $host));
            }
        }
    }

    private function dropUser(): void
    {
        foreach (self::HOSTS as $host) {
            Db::exec(sprintf("DROP USER IF EXISTS '%s'@'%s'", self::SETUP_USER, $host));
        }
    }

    public function test_setupPage_isSuperUserOnly(): void
    {
        FakeAccess::clearAccess(false, [1], [], 'admin');

        $this->expectException(NoAccessException::class);
        (new Controller())->setup();
    }
}
