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
use Piwik\Db\Adapter\Pdo\Mysql;
use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Sql\NotConfiguredException;
use Piwik\Plugins\DataExplorer\Sql\NotReadOnlyException;
use Piwik\Plugins\DataExplorer\Sql\RawSqlRunner;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Runs raw SQL as real MySQL users of its own: one with SELECT on the allowed tables, one that may also write.
 *
 * @group DataExplorer
 * @group RawSqlRunnerTest
 * @group Plugins
 */
class RawSqlRunnerTest extends IntegrationTestCase
{
    private const READ_ONLY_USER = 'dataexplorer_ro';
    private const WRITER_USER = 'dataexplorer_rw';
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
        Fixture::createSuperUser();
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site 1', 'https://one.example');
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Site 2', 'https://two.example');
        Fixture::createWebsite('2024-01-01 00:00:00', 0, 'Paris', 'https://paris.example', 1, null, null, 'Europe/Paris');

        $this->password = 'p' . bin2hex(random_bytes(12));
        $this->dropUsers();
        $tables = $this->allowedTables();
        $this->createUser(self::READ_ONLY_USER, array_map(function ($table) {
            return 'SELECT ON ' . $this->qualified($table);
        }, $tables));
        $this->createUser(self::WRITER_USER, ['SELECT ON ' . $this->qualified(Common::prefixTable('site')), 'INSERT ON ' . $this->qualified(Common::prefixTable('site'))]);

        $this->originalSection = Config::getInstance()->DataExplorer;
        $this->configure(self::READ_ONLY_USER);
        ReadOnlyConnection::reset();
        Cache::getLazyCache()->flushAll();
    }

    public function tearDown(): void
    {
        ReadOnlyConnection::reset();
        Config::getInstance()->DataExplorer = $this->originalSection;
        $this->dropUsers();
        $left = Db::fetchOne('SELECT COUNT(*) FROM mysql.user WHERE user IN (?, ?)', [self::READ_ONLY_USER, self::WRITER_USER]);

        parent::tearDown();

        $this->assertEquals(0, $left, 'test MySQL users left behind');
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_notConfigured_throws(): void
    {
        Config::getInstance()->DataExplorer = [];

        try {
            ReadOnlyConnection::get();
            $this->fail('expected NotConfiguredException');
        } catch (NotConfiguredException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_RawSqlNotConfigured'), $e->getMessage());
        }

        $this->expectException(NotConfiguredException::class);
        (new RawSqlRunner())->run('SELECT 1', 1, 'day', '2026-03-01');
    }

    public function test_readOnlyUser_passesTheGrantCheck(): void
    {
        ReadOnlyConnection::assertReadOnly();

        $this->assertStringStartsWith(self::READ_ONLY_USER . '@', (string) ReadOnlyConnection::get()->fetchOne('SELECT CURRENT_USER()'));
    }

    public function test_userWithInsert_isRefused(): void
    {
        $this->configure(self::WRITER_USER);

        try {
            ReadOnlyConnection::assertReadOnly();
            $this->fail('expected NotReadOnlyException');
        } catch (NotReadOnlyException $e) {
            $this->assertStringContainsString('INSERT', $e->getMessage());
        }

        $this->expectException(NotReadOnlyException::class);
        (new RawSqlRunner())->run('SELECT idsite FROM ' . Common::prefixTable('site'), 1, 'day', '2026-03-01');
    }

    public function test_validQuery_returnsRows_withIdsiteBound(): void
    {
        $sql = 'SELECT idsite, name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite} ORDER BY idsite';

        $table = (new RawSqlRunner())->run($sql, 1, 'day', '2026-03-01');

        $this->assertSame([['idsite' => 1, 'name' => 'Site 1']], $this->rows($table));
    }

    public function test_placeholderInsideAStringOrComment_isNotBound(): void
    {
        $sql = "SELECT '{idsite}' AS literal, idsite /* {date_start} */ FROM " . Common::prefixTable('site') . " WHERE idsite = {idsite} AND '{date_end}' <> ''";

        $table = (new RawSqlRunner())->run($sql, 2, 'day', '2026-03-01');

        $this->assertSame([['literal' => '{idsite}', 'idsite' => 2]], $this->rows($table));
    }

    public function test_rowCap_isApplied(): void
    {
        Config::getInstance()->DataExplorer = Config::getInstance()->DataExplorer + ['raw_sql_row_limit' => 2];

        $table = (new RawSqlRunner())->run('SELECT idsite FROM ' . Common::prefixTable('site') . ' ORDER BY idsite', 1, 'day', '2026-03-01');

        $this->assertSame([['idsite' => 1], ['idsite' => 2]], $this->rows($table));
    }

    public function test_dateBounds_areTheSitePeriodInUtc_acrossDst(): void
    {
        $this->assertSame(
            ['{date_start}' => '2026-03-28 23:00:00', '{date_end}' => '2026-03-29 21:59:59'],
            RawSqlRunner::dateBounds(3, 'day', '2026-03-29')
        );

        $sql = 'SELECT idsite FROM ' . Common::prefixTable('site') . " WHERE idsite = {idsite}"
            . " AND {date_start} = '2026-03-28 23:00:00' AND {date_end} = '2026-03-29 21:59:59'";
        $this->assertSame([['idsite' => 3]], $this->rows((new RawSqlRunner())->run($sql, 3, 'day', '2026-03-29')));
    }

    public function test_nonSuperUser_getsNoAccess(): void
    {
        FakeAccess::clearAccess(false, [], [1], 'viewer');

        $this->expectException(NoAccessException::class);
        API::getInstance()->runSql(1, 'day', '2026-03-01', 'SELECT idsite FROM ' . Common::prefixTable('site'));
    }

    public function test_api_runSql_returnsTheTable(): void
    {
        $table = API::getInstance()->runSql(1, 'day', '2026-03-01', 'SELECT idsite FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}');

        $this->assertSame([['idsite' => 1]], $this->rows($table));
    }

    public function test_cachedSecondCall_doesNotHitTheDatabase(): void
    {
        $connections = 0;
        $runner = new RawSqlRunner(function () use (&$connections) {
            $connections++;

            return ReadOnlyConnection::get();
        });
        $sql = 'SELECT idsite FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}';

        $first = $runner->run($sql, 1, 'day', '2026-03-01', true);
        $second = $runner->run($sql, 1, 'day', '2026-03-01', true);
        $this->assertSame(1, $connections);
        $this->assertSame($this->rows($first), $this->rows($second));

        $runner->run($sql, 2, 'day', '2026-03-01', true);
        $runner->run($sql, 1, 'day', '2026-03-01');
        $this->assertSame(3, $connections, 'another site or no cache runs the query');
    }

    public function test_anonymousViewer_ofASavedReport_isServedFromTheCacheOnly(): void
    {
        $connections = 0;
        $runner = new RawSqlRunner(function () use (&$connections) {
            $connections++;

            return ReadOnlyConnection::get();
        });
        $report = [
            'idreport' => 1,
            'type' => 'sql',
            'report_type' => 'table',
            'definition' => ['sql' => 'SELECT idsite FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}'],
            'created_by' => 'root',
            'deleted' => 0,
            'placement' => Placement::fromArray(['sites' => [1]]),
        ];

        FakeAccess::clearAccess(false, [], [1], 'anonymous');
        try {
            $runner->runSaved($report, 1, 'day', '2026-03-01');
            $this->fail('a cache miss must not run the query for an anonymous viewer');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorSqlNotCachedYet'), $e->getMessage());
        }
        $this->assertSame(0, $connections);

        FakeAccess::clearAccess(false, [], [1], 'viewer');
        $warm = $runner->runSaved($report, 1, 'day', '2026-03-01');
        $this->assertSame(1, $connections);

        FakeAccess::clearAccess(false, [], [1], 'anonymous');
        $cached = $runner->runSaved($report, 1, 'day', '2026-03-01');
        $this->assertSame(1, $connections);
        $this->assertSame($this->rows($warm), $this->rows($cached));
    }

    public function test_sessionLimit_isRestored_andTheTransactionEnded(): void
    {
        $db = ReadOnlyConnection::get();
        $db->query('SET SESSION max_execution_time = 4321');

        (new RawSqlRunner())->run('SELECT idsite FROM ' . Common::prefixTable('site'), 1, 'day', '2026-03-01');

        $this->assertEquals(4321, $db->fetchOne('SELECT @@SESSION.max_execution_time'));
        // A new transaction may start: the read-only one was ended.
        $db->query('START TRANSACTION');
        $db->query('ROLLBACK');
    }

    public function test_sessionLimit_isRestored_afterAFailure(): void
    {
        $db = ReadOnlyConnection::get();
        $db->query('SET SESSION max_execution_time = 4321');

        try {
            (new RawSqlRunner())->run('SELECT no_such_column FROM ' . Common::prefixTable('site'), 1, 'day', '2026-03-01');
            $this->fail('expected a failure');
        } catch (\Exception $e) {
            $this->assertStringContainsString('no_such_column', $e->getMessage());
        }

        $this->assertEquals(4321, $db->fetchOne('SELECT @@SESSION.max_execution_time'));
    }

    public function test_execution_isAudited_withoutValuesOrRows(): void
    {
        $events = [];
        Piwik::addAction('DataExplorer.rawSqlExecuted', function ($info) use (&$events) {
            $events[] = $info;
        });
        $sql = 'SELECT idsite, name FROM ' . Common::prefixTable('site') . ' WHERE idsite = {idsite}';

        (new RawSqlRunner())->run($sql, 2, 'day', '2026-03-01');

        $this->assertCount(1, $events);
        $this->assertSame(['login', 'queryHash', 'idSite', 'status', 'durationMs', 'rowCount'], array_keys($events[0]));
        $this->assertSame('ok', $events[0]['status']);
        $this->assertSame(md5($sql), $events[0]['queryHash']);
        $this->assertSame(1, $events[0]['rowCount']);
        $this->assertSame(2, $events[0]['idSite']);
        $this->assertStringNotContainsString('Site 2', json_encode($events[0]));
    }

    public function test_failedExecution_isAudited(): void
    {
        $events = $this->listenToAudit();
        $sql = 'SELECT no_such_column FROM ' . Common::prefixTable('site');

        try {
            (new RawSqlRunner())->run($sql, 1, 'day', '2026-03-01');
            $this->fail('expected a failure');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('no_such_column', $e->getMessage());
        }

        $this->assertCount(1, $events->list);
        $this->assertSame('error', $events->list[0]['status']);
        $this->assertNull($events->list[0]['rowCount']);
        $this->assertSame(md5($sql), $events->list[0]['queryHash']);
    }

    public function test_timedOutExecution_isAudited(): void
    {
        $events = $this->listenToAudit();
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['query', 'fetchOne', 'fetchAll'])->getMock();
        $db->method('fetchOne')->willReturnCallback(function ($sql) {
            return $sql === 'SELECT VERSION()' ? '8.0.36' : '0';
        });
        $db->method('fetchAll')->willReturnCallback(function ($sql) {
            if (strpos($sql, 'SHOW GRANTS') === 0) {
                return [['Grants' => 'GRANT USAGE ON *.* TO `ro`@`localhost`']];
            }
            throw new \Exception('SQLSTATE[HY000]: General error: 3024 Query execution was interrupted, maximum statement execution time exceeded');
        });

        try {
            (new RawSqlRunner(function () use ($db) {
                return $db;
            }))->run('SELECT idsite FROM ' . Common::prefixTable('site'), 1, 'day', '2026-03-01');
            $this->fail('expected a timeout');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorRawSqlTimeout', [30]), $e->getMessage());
        }

        $this->assertCount(1, $events->list);
        $this->assertSame('timeout', $events->list[0]['status']);
        $this->assertNull($events->list[0]['rowCount']);
    }

    public function test_duplicateColumnNames_work(): void
    {
        $site = Common::prefixTable('site');
        $sql = "SELECT a.idsite, b.idsite, a.name FROM {$site} a JOIN {$site} b ON b.idsite = a.idsite WHERE a.idsite = {idsite}";

        $this->assertCount(1, (new RawSqlRunner())->run($sql, 2, 'day', '2026-03-01')->getRows());
        $sql = "SELECT * FROM {$site} a JOIN {$site} b USING (idsite) WHERE a.idsite = {idsite}";
        $this->assertCount(1, (new RawSqlRunner())->run($sql, 2, 'day', '2026-03-01')->getRows());
    }

    /**
     * @dataProvider cappedQueries
     */
    public function test_rowCap_keepsTheTopRowsOfTheOrder(string $tail, array $expected): void
    {
        Config::getInstance()->DataExplorer = Config::getInstance()->DataExplorer + ['raw_sql_row_limit' => 2];
        $site = Common::prefixTable('site');

        $rows = $this->rows((new RawSqlRunner())->run(str_replace('{site}', $site, $tail), 1, 'day', '2026-03-01'));

        $this->assertSame($expected, array_column($rows, 'idsite'));
    }

    public function cappedQueries(): array
    {
        return [
            'order without limit' => ['SELECT idsite FROM {site} ORDER BY idsite DESC', [3, 2]],
            'own limit above the cap' => ['SELECT idsite FROM {site} ORDER BY idsite DESC LIMIT 20000', [3, 2]],
            'own limit below the cap' => ['SELECT idsite FROM {site} ORDER BY idsite DESC LIMIT 1', [3]],
            'offset and count' => ['SELECT idsite FROM {site} ORDER BY idsite DESC LIMIT 1, 5', [2, 1]],
            'count and offset' => ['SELECT idsite FROM {site} ORDER BY idsite DESC LIMIT 5 OFFSET 1', [2, 1]],
            'union' => ['SELECT idsite FROM {site} UNION ALL SELECT idsite FROM {site} ORDER BY 1 DESC', [3, 3]],
            'limit in a derived table only' => ['SELECT d.idsite FROM (SELECT idsite FROM {site} ORDER BY idsite LIMIT 3) d ORDER BY d.idsite DESC', [3, 2]],
        ];
    }

    public function test_coreConnectionSession_isUntouched(): void
    {
        $modes = [Db::get()->fetchOne('SELECT @@SESSION.sql_mode'), Db::getReader()->fetchOne('SELECT @@SESSION.sql_mode')];
        $readOnly = [Db::get()->fetchOne('SELECT @@SESSION.transaction_read_only'), Db::getReader()->fetchOne('SELECT @@SESSION.transaction_read_only')];

        API::getInstance()->runSql(1, 'day', '2026-03-01', 'SELECT idsite FROM ' . Common::prefixTable('site'));

        $this->assertSame($modes, [Db::get()->fetchOne('SELECT @@SESSION.sql_mode'), Db::getReader()->fetchOne('SELECT @@SESSION.sql_mode')]);
        $this->assertSame($readOnly, [Db::get()->fetchOne('SELECT @@SESSION.transaction_read_only'), Db::getReader()->fetchOne('SELECT @@SESSION.transaction_read_only')]);
        $this->assertNotSame(RawSqlRunner::SQL_MODE, $modes[0]);
    }

    public function test_rejectedQuery_neverReachesTheDatabase(): void
    {
        $connections = 0;
        $runner = new RawSqlRunner(function () use (&$connections) {
            $connections++;

            return ReadOnlyConnection::get();
        });

        try {
            $runner->run('SELECT login FROM ' . Common::prefixTable('user'), 1, 'day', '2026-03-01');
            $this->fail('expected a refusal');
        } catch (\Exception $e) {
            $this->assertSame(0, $connections);
        }
    }

    public function test_api_validateSql_reportsValidAndInvalidQueries(): void
    {
        $site = Common::prefixTable('site');
        $this->assertSame(['valid' => true, 'errors' => [], 'warnings' => []], API::getInstance()->validateSql("SELECT idsite FROM {$site} WHERE idsite = {idsite}"));

        $sql = "SELECT '\u{1F600}', a FROM " . Common::prefixTable('access');
        $result = API::getInstance()->validateSql($sql);
        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['errors']);
        $error = $result['errors'][0];
        $offset = mb_strpos($sql, Common::prefixTable('access'));
        $this->assertSame($offset, $error['offset']);
        $this->assertSame($offset + 1, $error['offsetUtf16'], 'the emoji is two UTF-16 units');
        $this->assertSame(mb_strlen(Common::prefixTable('access')), $error['length']);
        $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedTable', [Common::prefixTable('access')]), $error['message']);
    }

    public function test_api_validateSql_wholeQueryRefusal_hasNoOffset(): void
    {
        Config::getInstance()->DataExplorer = Config::getInstance()->DataExplorer + ['dataexplorer_sql_max_length' => 10];

        $result = API::getInstance()->validateSql('SELECT idsite FROM ' . Common::prefixTable('site'));

        $this->assertFalse($result['valid']);
        $this->assertNull($result['errors'][0]['offset']);
        $this->assertNull($result['errors'][0]['offsetUtf16']);
    }

    public function test_api_validateSql_warnsWhenNotSiteScoped(): void
    {
        $sql = 'SELECT COUNT(*) FROM ' . Common::prefixTable('log_visit') . ' v';

        $result = API::getInstance()->validateSql($sql);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['warnings']);
        $this->assertSame(Piwik::translate('DataExplorer_SqlWarningNotSiteScoped', ['v']), $result['warnings'][0]['message']);
        $this->assertSame(mb_strpos($sql, Common::prefixTable('log_visit')), $result['warnings'][0]['offset']);
    }

    public function test_api_validateSql_isSuperUserOnly(): void
    {
        FakeAccess::clearAccess(false, [1], [], 'admin');

        $this->expectException(NoAccessException::class);
        API::getInstance()->validateSql('SELECT 1');
    }

    private function listenToAudit(): \stdClass
    {
        $events = new \stdClass();
        $events->list = [];
        Piwik::addAction('DataExplorer.rawSqlExecuted', function ($info) use ($events) {
            $events->list[] = $info;
        });

        return $events;
    }

    private function configure(string $username): void
    {
        $section = Config::getInstance()->DataExplorer;
        $section = is_array($section) ? $section : [];
        Config::getInstance()->DataExplorer = array_merge($section, ['readonly_username' => $username, 'readonly_password' => $this->password]);
        ReadOnlyConnection::reset();
    }

    private function rows(DataTable $table): array
    {
        $rows = [];
        foreach ($table->getRows() as $row) {
            $rows[] = $row->getColumns();
        }

        return $rows;
    }

    /**
     * @return string[] prefixed names of the allowed tables of the test database
     */
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

    private function qualified(string $table): string
    {
        return '`' . Config::getInstance()->database['dbname'] . '`.`' . $table . '`';
    }

    /**
     * @param string[] $grants "PRIVILEGE ON object" each
     */
    private function createUser(string $user, array $grants): void
    {
        foreach (self::HOSTS as $host) {
            Db::exec(sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", $user, $host, $this->password));
            foreach ($grants as $grant) {
                Db::exec(sprintf("GRANT %s TO '%s'@'%s'", $grant, $user, $host));
            }
        }
    }

    private function dropUsers(): void
    {
        foreach ([self::READ_ONLY_USER, self::WRITER_USER] as $user) {
            foreach (self::HOSTS as $host) {
                Db::exec(sprintf("DROP USER IF EXISTS '%s'@'%s'", $user, $host));
            }
        }
    }
}
