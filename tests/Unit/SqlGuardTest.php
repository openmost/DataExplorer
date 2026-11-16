<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Config;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;

/**
 * @group DataExplorer
 * @group SqlGuardTest
 * @group Plugins
 */
class SqlGuardTest extends TestCase
{
    private const PREFIX = 'matomo_';
    private const DB = 'matomo5';

    private $originalSection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSection = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = [];
    }

    protected function tearDown(): void
    {
        Config::getInstance()->DataExplorer = $this->originalSection;
        parent::tearDown();
    }

    public function test_acceptsAGroupedSelect(): void
    {
        $query = SqlGuard::validate('SELECT idsite, COUNT(*) FROM matomo_log_visit GROUP BY idsite', self::PREFIX, self::DB);

        $this->assertSame(['log_visit'], $query->getTables());
        $this->assertCount(1, $query->getStatements());
        $this->assertSame($query->getStatement(), $query->getStatements()[0]);
    }

    public function test_acceptsAUnionOfAllowedSelects(): void
    {
        $query = SqlGuard::validate(
            'SELECT idvisit FROM matomo_log_visit UNION ALL SELECT idvisit FROM matomo_log_conversion',
            self::PREFIX,
            self::DB
        );

        $this->assertSame(['log_visit', 'log_conversion'], $query->getTables());
        $this->assertCount(2, $query->getStatements());
    }

    public function test_acceptsAParenthesizedUnionWithoutListingABranchTwice(): void
    {
        $query = SqlGuard::validate(
            '(SELECT idvisit FROM matomo_log_visit) UNION (SELECT idvisit FROM matomo_log_conversion)',
            self::PREFIX,
            self::DB
        );

        $this->assertSame(['log_visit', 'log_conversion'], $query->getTables());
        $this->assertCount(2, $query->getStatements());
    }

    public function test_acceptsAStringLiteralThatLooksLikeAStatement(): void
    {
        $query = SqlGuard::validate("SELECT 'DROP TABLE' AS x, 'a; DELETE' FROM matomo_log_visit", self::PREFIX, self::DB);

        $this->assertSame(['log_visit'], $query->getTables());
    }

    public function test_keywordsAndTableNamesAreCaseInsensitive(): void
    {
        $this->assertSame(['log_visit'], SqlGuard::validate('SeLeCt idsite FrOm matomo_log_visit', self::PREFIX, self::DB)->getTables());
        $this->assertSame(['log_visit'], SqlGuard::validate('SELECT * FROM MATOMO_LOG_VISIT', self::PREFIX, self::DB)->getTables());
        $this->assertSame(['log_visit'], SqlGuard::validate('SELECT * FROM `Matomo_Log_Visit`', self::PREFIX, self::DB)->getTables());
    }

    public function test_acceptsOneTrailingSemicolon(): void
    {
        $this->assertSame(['log_visit'], SqlGuard::validate("SELECT idvisit FROM matomo_log_visit ;\n ", self::PREFIX, self::DB)->getTables());
    }

    public function test_acceptsPlainComments(): void
    {
        $sql = "-- leading\nSELECT idvisit /* inline */ FROM matomo_log_visit # bash style\n WHERE idsite = 1 -- trailing";

        $this->assertSame(['log_visit'], SqlGuard::validate($sql, self::PREFIX, self::DB)->getTables());
    }

    public function test_acceptsTheMatomoDatabaseAsQualifier(): void
    {
        $query = SqlGuard::validate('SELECT v.idvisit FROM matomo5.matomo_log_visit v JOIN `matomo5`.`matomo_site` s ON s.idsite = v.idsite', self::PREFIX, self::DB);

        $this->assertSame(['log_visit', 'site'], $query->getTables());
    }

    public function test_aDatabaseNamedLikeThePrefixIsNotTakenForATable(): void
    {
        $this->assertSame(['log_visit'], SqlGuard::validate('SELECT 1 FROM matomo_prod.matomo_log_visit', self::PREFIX, 'matomo_prod')->getTables());
    }

    public function test_defaultsToTheConfiguredDatabase(): void
    {
        $dbName = Config::getInstance()->database['dbname'];

        $this->assertSame(['log_visit'], SqlGuard::validate('SELECT 1 FROM `' . $dbName . '`.matomo_log_visit', self::PREFIX)->getTables());
    }

    public function test_acceptsARealisticAnalyticsQuery(): void
    {
        $sql = <<<SQL
SELECT CONV(HEX(v.idvisitor), 16, 10) AS visitor, DATE_FORMAT(v.visit_last_action_time, '%Y-%m-%d') AS day,
       CASE WHEN v.visit_total_actions > 1 THEN 'engaged' ELSE 'bounce' END AS kind,
       COUNT(DISTINCT c.idgoal) AS goals, SUM(IFNULL(c.revenue, 0)) AS revenue,
       (SELECT MAX(a.name) FROM matomo_log_action a WHERE a.idaction = v.visit_entry_idaction_url) AS entry,
       ROW_NUMBER() OVER (PARTITION BY v.idsite ORDER BY v.idvisit DESC) AS rn
FROM matomo_log_visit v
LEFT JOIN matomo_log_conversion c ON c.idvisit = v.idvisit AND c.idsite = v.idsite
INNER JOIN (SELECT idsite, `group` FROM matomo_site) s ON s.idsite = v.idsite
WHERE v.idsite = 1 AND v.visit_last_action_time >= NOW() - INTERVAL 7 DAY
  AND EXISTS (SELECT 1 FROM matomo_goal g WHERE g.idsite = v.idsite)
  AND v.referer_name NOT LIKE 'x%' AND v.config_os IS NOT NULL AND v.idvisit NOT IN (1, 2)
GROUP BY visitor, day, kind, entry, rn
HAVING goals >= 0
ORDER BY revenue DESC
LIMIT 10
SQL;
        $query = SqlGuard::validate($sql, self::PREFIX, self::DB);

        $this->assertSame(['log_visit', 'log_conversion', 'log_action', 'site', 'goal'], $query->getTables());
        $this->assertCount(4, $query->getStatements());
    }

    /**
     * @dataProvider getAllowedTables
     */
    public function test_acceptsEveryAllowedTable(string $table): void
    {
        $this->assertSame([$table], SqlGuard::validate('SELECT * FROM matomo_' . $table, self::PREFIX, self::DB)->getTables());
    }

    public function getAllowedTables(): array
    {
        $tables = [
            'log_visit', 'log_link_visit_action', 'log_conversion', 'log_conversion_item', 'log_action',
            'log_page_view_time', 'archive_numeric_2024_01', 'archive_blob_2026_12', 'site', 'site_url', 'goal',
            'custom_dimensions', 'segment',
        ];

        return array_combine($tables, array_map(function ($table) {
            return [$table];
        }, $tables));
    }

    public function test_allowedTablesListsTheFixedNamesAndTheArchivePatterns(): void
    {
        $this->assertSame([
            'log_visit', 'log_link_visit_action', 'log_conversion', 'log_conversion_item', 'log_action',
            'log_page_view_time', 'archive_numeric_*', 'archive_blob_*', 'site', 'site_url', 'goal',
            'custom_dimensions', 'segment',
        ], SqlGuard::allowedTables());
        $this->assertTrue(SqlGuard::isAllowedTable('archive_blob_2025_03'));
        $this->assertTrue(SqlGuard::isAllowedTable('LOG_VISIT'));
        $this->assertFalse(SqlGuard::isAllowedTable('archive_blob_2025_3'));
        $this->assertFalse(SqlGuard::isAllowedTable('plugin_setting'));
    }

    public function test_refusesAQueryLongerThanTheConfiguredLength(): void
    {
        Config::getInstance()->DataExplorer = ['dataexplorer_sql_max_length' => 40];
        SqlGuard::validate('SELECT idvisit FROM matomo_log_visit', self::PREFIX, self::DB);

        try {
            SqlGuard::validate('SELECT idvisit FROM matomo_log_visit WHERE 1', self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedTooLong', ['40']), $e->getMessage());
        }
    }

    public function test_refusesMoreSubqueriesThanConfigured(): void
    {
        Config::getInstance()->DataExplorer = ['dataexplorer_sql_max_subqueries' => 2];
        $sql = 'SELECT idvisit FROM matomo_log_visit WHERE idvisit IN (SELECT idvisit FROM matomo_log_conversion)'
            . ' UNION SELECT idvisit FROM matomo_log_visit';
        $this->assertCount(3, SqlGuard::validate($sql, self::PREFIX, self::DB)->getStatements());

        try {
            SqlGuard::validate($sql . ' UNION SELECT 1', self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedTooManySubqueries', ['2']), $e->getMessage());
        }
    }

    public function test_deeplyNestedSubqueriesAreRefusedQuickly(): void
    {
        $sql = 'SELECT idvisit FROM matomo_log_visit WHERE idvisit IN ';
        $sql .= str_repeat('(SELECT idvisit FROM matomo_log_visit WHERE idvisit IN ', 300) . '(1)' . str_repeat(')', 300);

        $start = microtime(true);
        try {
            SqlGuard::validate($sql, self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedTooManySubqueries', ['50']), $e->getMessage());
        }
        $this->assertLessThan(1.0, microtime(true) - $start);
    }

    public function test_aMaximumLengthQueryValidatesQuickly(): void
    {
        $sql = 'SELECT idvisit FROM matomo_log_visit WHERE idvisit IN (0';
        while (strlen($sql) < 65000) {
            $sql .= ', ' . strlen($sql) . " + 1 AND 'abc' <> 'd'";
        }
        $sql = substr($sql, 0, strrpos($sql, ',')) . ') AND idsite = {idsite}';
        $this->assertLessThanOrEqual(65536, strlen($sql));

        $start = microtime(true);
        $this->assertSame(['log_visit'], SqlGuard::validate($sql, self::PREFIX, self::DB)->getTables());
        $this->assertLessThan(1.0, microtime(true) - $start);
    }

    public function test_executableSqlHasNoCommentNorDelimiter(): void
    {
        $query = SqlGuard::validate("SELECT idvisit /* a */ FROM matomo_log_visit # b\nWHERE idsite = {idsite} -- x", self::PREFIX, self::DB);
        $wrapped = 'SELECT * FROM (' . $query->getExecutableSql() . ') t LIMIT 1';

        $this->assertStringEndsWith(') t LIMIT 1', $wrapped);
        $this->assertStringEndsWith("\n", $query->getExecutableSql());
        $this->assertStringNotContainsString('/*', $query->getExecutableSql());
        $this->assertStringNotContainsString('#', $query->getExecutableSql());
        $this->assertStringNotContainsString('--', $query->getExecutableSql());
        $this->assertStringContainsString('idsite = {idsite}', $query->getExecutableSql());
        $this->assertSame("SELECT idvisit FROM matomo_log_visit\n", SqlGuard::validate('SELECT idvisit FROM matomo_log_visit -- x', self::PREFIX, self::DB)->getExecutableSql());
        $this->assertSame("SELECT idvisit FROM matomo_log_visit\n", SqlGuard::validate("SELECT idvisit FROM matomo_log_visit;\n", self::PREFIX, self::DB)->getExecutableSql());
    }

    /**
     * @dataProvider getEmptyPrefixDecimalPoints
     */
    public function test_emptyPrefixRefusesTheDecimalPointBeforeFrom(string $sql): void
    {
        $this->expectException(RejectedQueryException::class);
        SqlGuard::validate($sql, '', self::DB);
    }

    public function getEmptyPrefixDecimalPoints(): array
    {
        return [
            ['SELECT 1.FROM `user`'],
            ['SELECT login, password, 1.FROM `user`'],
            ['SELECT 1.FROM `option`'],
            ['SELECT 1.FROM user_token_auth'],
        ];
    }

    /**
     * @dataProvider getEmptyPrefixSubqueries
     */
    public function test_emptyPrefixStillChecksSubqueryTables(string $sql): void
    {
        $this->expectException(RejectedQueryException::class);
        SqlGuard::validate($sql, '', self::DB);
    }

    public function getEmptyPrefixSubqueries(): array
    {
        return [
            'select list' => ['SELECT idvisit, (SELECT login FROM `user` LIMIT 1) FROM log_visit'],
            'where' => ['SELECT idvisit FROM log_visit WHERE idsite IN (SELECT idsite FROM access)'],
            'having' => ['SELECT idsite, COUNT(*) c FROM log_visit GROUP BY idsite HAVING c > (SELECT COUNT(*) FROM plugin_setting)'],
        ];
    }

    public function test_acceptsTheSiteAndPeriodPlaceholders(): void
    {
        $sql = 'SELECT idvisit FROM matomo_log_visit v WHERE v.idsite = {idsite}'
            . ' AND v.visit_last_action_time >= {date_start} AND v.visit_last_action_time <= {date_end}';

        $this->assertSame(['log_visit'], SqlGuard::validate($sql, self::PREFIX, self::DB)->getTables());
    }

    public function test_acceptsAnEmptyPrefix(): void
    {
        $this->assertSame(['log_visit'], SqlGuard::validate('SELECT 1 FROM log_visit', '', self::DB)->getTables());

        $this->expectException(RejectedQueryException::class);
        SqlGuard::validate('SELECT 1 FROM `option`', '', self::DB);
    }

    public function test_emptyPrefixRefusesAHiddenUnionBranch(): void
    {
        $this->expectException(RejectedQueryException::class);
        SqlGuard::validate('SELECT idvisit FROM log_visit WHERE idvisit IN ((SELECT 1) UNION SELECT idvisit FROM `user`) UNION (SELECT idvisit FROM log_visit)', '', self::DB);
    }

    public function test_acceptsNestedParenthesizedUnionBranches(): void
    {
        $sql = 'SELECT idvisit FROM matomo_log_visit WHERE idvisit IN (SELECT idvisit FROM matomo_log_conversion UNION (SELECT idvisit FROM matomo_log_link_visit_action UNION (SELECT idvisit FROM matomo_site)))'
            . ' UNION (SELECT idvisit FROM matomo_goal) ORDER BY 1';
        $query = SqlGuard::validate($sql, self::PREFIX, self::DB);

        $this->assertSame(['log_visit', 'log_conversion', 'log_link_visit_action', 'site', 'goal'], $query->getTables());
        $this->assertCount(5, $query->getStatements());
    }

    public function test_listsEverySubqueryAndUnionBranchOnce(): void
    {
        $sql = 'SELECT idvisit FROM matomo_log_visit WHERE idvisit IN (SELECT idvisit FROM matomo_log_conversion)'
            . ' UNION SELECT idvisit FROM (SELECT idvisit FROM matomo_log_link_visit_action UNION SELECT idvisit FROM matomo_log_visit) t';
        $query = SqlGuard::validate($sql, self::PREFIX, self::DB);

        $this->assertCount(5, $query->getStatements());
        $this->assertSame(['log_visit', 'log_conversion', 'log_link_visit_action'], $query->getTables());
    }

    public function test_aRejectionIsTranslatedAndNamesTheOffendingToken(): void
    {
        try {
            SqlGuard::validate("SELECT LOAD_FILE('/etc/passwd')", self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame('LOAD_FILE', $e->getOffendingToken());
            $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedToken', ['LOAD_FILE']), $e->getMessage());
        }
    }

    /**
     * @dataProvider getOffsets
     */
    public function test_aRejectionGivesTheOffsetOfTheOffendingToken(string $sql, ?int $offset, string $token): void
    {
        try {
            SqlGuard::validate($sql, self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame($offset, $e->getOffset());
            $this->assertSame($token, $e->getOffendingToken());
        }
    }

    public function getOffsets(): array
    {
        return [
            'forbidden table' => ['SELECT idvisit FROM matomo_user', 20, 'matomo_user'],
            'forbidden table in a subquery' => ['SELECT idvisit FROM matomo_log_visit WHERE idvisit IN (SELECT idvisit FROM matomo_access)', 75, 'matomo_access'],
            'table from another database' => ['SELECT idvisit FROM otherdb.matomo_log_visit', 20, 'otherdb.matomo_log_visit'],
            'forbidden function' => ['SELECT idvisit, SLEEP(1) FROM matomo_log_visit', 16, 'SLEEP'],
            'second statement' => ['SELECT 1 FROM matomo_log_visit; DROP TABLE x', 32, 'DROP'],
            'multibyte characters count once' => ["SELECT 'été' AS x, LOAD_FILE('/x')", 19, 'LOAD_FILE'],
            'length cap' => [str_repeat(' ', 65537) . 'SELECT 1', null, ''],
        ];
    }

    public function test_aParserErrorCarriesItsMessageAndOffset(): void
    {
        try {
            SqlGuard::validate('SELECT FROM WHERE', self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame(7, $e->getOffset());
            $this->assertSame('FROM', $e->getOffendingToken());
            $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedParser', ['FROM', 'An expression was expected.']), $e->getMessage());
            $english = json_decode((string) file_get_contents(__DIR__ . '/../../lang/en.json'), true);
            $this->assertStringContainsString('%2$s', $english['DataExplorer']['SqlRejectedParser']);
        }

        try {
            SqlGuard::validate("SELECT idvisit FROM matomo_log_visit WHERE idsite = 'abc", self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame(52, $e->getOffset());
        }
    }

    public function test_anExcludedTableIsNamedInTheRejection(): void
    {
        try {
            SqlGuard::validate('SELECT * FROM `matomo_user`', self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException');
        } catch (RejectedQueryException $e) {
            $this->assertSame('matomo_user', $e->getOffendingToken());
            $this->assertSame(Piwik::translate('DataExplorer_SqlRejectedTable', ['matomo_user']), $e->getMessage());
        }
    }

    /**
     * @dataProvider getRefusedQueries
     */
    public function test_refuses(string $sql, string $expectedToken): void
    {
        try {
            SqlGuard::validate($sql, self::PREFIX, self::DB);
            $this->fail('Expected RejectedQueryException for: ' . $sql);
        } catch (RejectedQueryException $e) {
            $this->assertNotSame('', $e->getMessage());
            $this->assertStringContainsStringIgnoringCase($expectedToken, $e->getOffendingToken());
        }
    }

    public function getRefusedQueries(): array
    {
        return [
            'second statement' => ['SELECT 1; DROP TABLE matomo_log_visit', 'DROP'],
            'second select' => ['SELECT 1 FROM matomo_log_visit; SELECT 2 FROM matomo_log_visit', 'SELECT'],
            'two semicolons' => ['SELECT 1 FROM matomo_log_visit;;', ';'],
            'executable comment' => ['SELECT /*!50000 SLEEP(5) */ 1', '/*!'],
            'mariadb executable comment' => ['SELECT /*M!100000 SLEEP(5) */ 1', '/*M!'],
            'optimizer hint' => ['SELECT /*+ MAX_EXECUTION_TIME(100000000) */ idvisit FROM matomo_log_visit', '/*+'],
            'load file' => ["SELECT LOAD_FILE('/etc/passwd')", 'LOAD_FILE'],
            'excluded table' => ['SELECT * FROM `matomo_user`', 'matomo_user'],
            'into outfile' => ["SELECT * FROM matomo_log_visit INTO OUTFILE '/tmp/x'", 'INTO'],
            'into dumpfile' => ["SELECT * FROM matomo_log_visit LIMIT 1 INTO DUMPFILE '/tmp/x'", 'INTO'],
            'into variable' => ['SELECT idvisit INTO @v FROM matomo_log_visit', 'INTO'],
            'system variable' => ['SELECT @@version', '@@version'],
            'global system variable' => ['SELECT @@global.max_execution_time', '@@'],
            'user variable' => ['SELECT @x', '@x'],
            'assignment' => ['SELECT idvisit FROM matomo_log_visit WHERE (@a := 1)', '@a'],
            'excluded table in derived table' => ['SELECT * FROM (SELECT * FROM matomo_session) s', 'matomo_session'],
            'information schema' => ['SELECT * FROM information_schema.tables', 'information_schema'],
            'mysql schema' => ['SELECT * FROM mysql.user', 'mysql'],
            'performance schema' => ['SELECT * FROM performance_schema.threads', 'performance_schema'],
            'sys schema' => ['SELECT * FROM sys.version', 'sys'],
            'other database' => ['SELECT * FROM otherdb.matomo_log_visit', 'otherdb'],
            'update' => ["UPDATE matomo_site SET name='x'", 'UPDATE'],
            'delete' => ['DELETE FROM matomo_log_visit', 'DELETE'],
            'insert' => ['INSERT INTO matomo_log_visit (idsite) VALUES (1)', 'INSERT'],
            'excluded table in where subquery' => ['SELECT * FROM matomo_log_visit WHERE idvisit IN (SELECT idvisit FROM matomo_user_token_auth)', 'matomo_user_token_auth'],
            'excluded table in select list subquery' => ['SELECT idvisit, (SELECT login FROM matomo_access LIMIT 1) AS l FROM matomo_log_visit', 'matomo_access'],
            'benchmark' => ['SELECT BENCHMARK(1000000, MD5(1))', 'BENCHMARK'],
            'excluded table in union' => ['SELECT 1 UNION SELECT * FROM matomo_option', 'matomo_option'],
            'plugin settings' => ['SELECT * FROM matomo_plugin_setting', 'matomo_plugin_setting'],
            'site settings' => ['SELECT * FROM matomo_site_setting', 'matomo_site_setting'],
            'scheduled reports' => ['SELECT * FROM matomo_report', 'matomo_report'],
            'unknown future table' => ['SELECT * FROM matomo_foo', 'matomo_foo'],
            'unknown table in subquery' => ['SELECT * FROM matomo_log_visit WHERE idvisit IN (SELECT idvisit FROM matomo_foo)', 'matomo_foo'],
            'archive table with a malformed date' => ['SELECT * FROM matomo_archive_numeric_2024_1', 'matomo_archive_numeric_2024_1'],
            'archive table of another kind' => ['SELECT * FROM matomo_archive_invalidations', 'matomo_archive_invalidations'],
            'decimal point before from' => ['SELECT idvisit, 1.FROM matomo_log_visit WHERE idsite = 2', '1'],
            'decimal point before from on another database' => ['SELECT 1.FROM otherdb.secret', '1'],
            'decimal point before from in union' => ['SELECT 1.FROM matomo_log_visit WHERE idsite=1 UNION SELECT 1.FROM otherdb.t', '1'],
            'decimal point before from in subquery' => ['SELECT idvisit FROM matomo_log_visit WHERE idsite = {idsite} AND idvisit IN (SELECT 1.FROM matomo_log_visit)', '1'],
            'string before dot' => ["SELECT 'a'.FROM matomo_log_visit", '.'],
            'number and space before dot' => ['SELECT 1 .FROM matomo_log_visit', '.'],
            'decimal before dot' => ['SELECT 1.5.FROM matomo_log_visit', '.'],
            'hexadecimal before dot' => ['SELECT 0x1.FROM matomo_log_visit', '.'],
            'exponent before dot' => ['SELECT 1e1.FROM matomo_log_visit', '.'],
            'identifier starting with a digit' => ['SELECT 1abc FROM matomo_log_visit', '1abc'],
            'non ascii identifier' => ['SELECT idvisit FROM matomo_log_visit v WHERE v.idsite = 1 AND é = 1', 'é'],
            'hidden union branch in derived table' => ["SELECT z.a FROM matomo_log_visit, ((SELECT 'x' a) UNION SELECT LEFT(email, 3) FROM platform_accounts.users) z UNION (SELECT idvisit FROM matomo_log_visit)", 'UNION'],
            'hidden union branch in IN' => ['SELECT idvisit FROM matomo_log_visit WHERE idvisit IN ((SELECT 1) UNION SELECT a FROM otherdb.secret) UNION (SELECT idvisit FROM matomo_log_visit)', 'UNION'],
            'hidden union branch in ANY' => ['SELECT idvisit FROM matomo_log_visit WHERE idvisit = ANY ((SELECT 1) UNION SELECT a FROM otherdb.secret) UNION (SELECT idvisit FROM matomo_log_visit)', 'UNION'],
            'union of parenthesized branches inside a non-select parenthesis' => ['SELECT a FROM matomo_log_visit WHERE idsite IN ((SELECT 1) UNION (SELECT 2))', 'UNION'],
            'nested parenthesized union' => ['SELECT idvisit FROM matomo_log_visit WHERE idvisit IN (((SELECT 1) UNION SELECT login FROM otherdb.u))', 'UNION'],
            'select inside a trim' => ["SELECT TRIM('x' FROM (SELECT login FROM otherdb.u)) FROM matomo_log_visit", 'FROM'],
            'from inside a non-function parenthesis' => ['SELECT (1 FROM otherdb.t) FROM matomo_log_visit', 'FROM'],
            'second select in one branch' => ['SELECT 1 FROM matomo_log_visit WHERE 1 = 1 AND SELECT 2', 'SELECT'],
            'unprefixed table' => ['SELECT * FROM log_visit', 'log_visit'],
            'uppercase excluded table' => ['SELECT * FROM MATOMO_USER', 'MATOMO_USER'],
            'for update' => ['SELECT * FROM matomo_log_visit FOR UPDATE', 'FOR'],
            'for share' => ['SELECT * FROM matomo_log_visit FOR SHARE', 'FOR'],
            'lock in share mode' => ['SELECT * FROM matomo_log_visit LOCK IN SHARE MODE', 'LOCK'],
            'procedure analyse' => ['SELECT * FROM matomo_log_visit PROCEDURE ANALYSE()', 'PROCEDURE'],
            'cte' => ['WITH c AS (SELECT idvisit FROM matomo_log_visit) SELECT * FROM c', 'WITH'],
            'handler' => ['HANDLER matomo_log_visit OPEN', 'HANDLER'],
            'call' => ['CALL p()', 'CALL'],
            'do' => ['DO SLEEP(1)', 'DO'],
            'set' => ['SET @a = 1', 'SET'],
            'explain' => ['EXPLAIN SELECT * FROM matomo_log_visit', 'EXPLAIN'],
            'show' => ['SHOW TABLES', 'SHOW'],
            'table statement in union' => ['SELECT 1 UNION TABLE matomo_user', 'TABLE'],
            'table statement in derived table' => ['SELECT * FROM (TABLE matomo_log_visit) t', 'TABLE'],
            'parenthesized join' => ['SELECT * FROM (matomo_log_visit JOIN matomo_site ON 1)', 'JOIN'],
            'table function' => ["SELECT * FROM JSON_TABLE('[]', '\$[*]' COLUMNS (a INT PATH '\$')) j", 'COLUMNS'],
            'unknown placeholder' => ['SELECT idvisit FROM matomo_log_visit WHERE idsite = {site}', '{'],
            'spaced placeholder' => ['SELECT idvisit FROM matomo_log_visit WHERE idsite = { idsite }', '{'],
            'odbc escape' => ["SELECT {d '2024-01-01'} FROM matomo_log_visit", '{'],
            'odbc escape hiding a subquery' => ['SELECT {x (SELECT 1 FROM otherdb.t)} FROM matomo_log_visit', '{'],
            'parenthesized derived table' => ['SELECT * FROM (matomo_log_visit) t', '(matomo_log_visit)'],
            'lateral' => ['SELECT * FROM matomo_log_visit v, LATERAL (SELECT 1) x', 'LATERAL'],
            'other database in comma join' => ['SELECT * FROM matomo_log_visit a, (SELECT 1 FROM matomo_site s, otherdb.t) b', 'otherdb'],
            'other database in parenthesized union branch' => ['SELECT * FROM (SELECT 1 UNION (SELECT a FROM otherdb.t)) x', 'otherdb'],
            'other database in exists' => ['SELECT * FROM matomo_log_visit WHERE EXISTS(SELECT 1 FROM otherdb.t)', 'otherdb'],
            'other database in join using' => ['SELECT * FROM matomo_log_visit AS v LEFT JOIN otherdb.t USING (idvisit)', 'otherdb'],
            'sleep' => ['SELECT SLEEP(1)', 'SLEEP'],
            'get lock' => ["SELECT GET_LOCK('a', 1)", 'GET_LOCK'],
            'release lock' => ["SELECT RELEASE_LOCK('a')", 'RELEASE_LOCK'],
            'master pos wait' => ["SELECT MASTER_POS_WAIT('a', 1)", 'MASTER_POS_WAIT'],
            'sys exec' => ["SELECT sys_exec('id')", 'sys_exec'],
            'user' => ['SELECT USER()', 'USER'],
            'current user' => ['SELECT CURRENT_USER', 'CURRENT_USER'],
            'system user' => ['SELECT SYSTEM_USER()', 'SYSTEM_USER'],
            'session user' => ['SELECT SESSION_USER()', 'SESSION_USER'],
            'database' => ['SELECT DATABASE()', 'DATABASE'],
            'version' => ['SELECT VERSION()', 'VERSION'],
            'connection id' => ['SELECT CONNECTION_ID()', 'CONNECTION_ID'],
            'last insert id' => ['SELECT LAST_INSERT_ID()', 'LAST_INSERT_ID'],
            'row count' => ['SELECT ROW_COUNT()', 'ROW_COUNT'],
            'found rows' => ['SELECT FOUND_ROWS()', 'FOUND_ROWS'],
            'uuid short' => ['SELECT UUID_SHORT()', 'UUID_SHORT'],
            'stored function' => ['SELECT myfunc(idvisit) FROM matomo_log_visit', 'myfunc'],
            'qualified stored function' => ['SELECT otherdb.md5(idvisit) FROM matomo_log_visit', 'md5'],
            'quoted function name' => ['SELECT `sleep`(1)', '`sleep`'],
            'placeholder' => ['SELECT * FROM matomo_log_visit WHERE idsite = ?', '?'],
            'named placeholder' => ['SELECT * FROM matomo_log_visit WHERE idsite = :site', ':site'],
            'double quoted string' => ['SELECT "matomo_user" FROM matomo_log_visit', '"matomo_user"'],
            'backslash in string' => ["SELECT 'a\\nb' FROM matomo_log_visit", '\\'],
            'unterminated string' => ["SELECT 'abc FROM matomo_log_visit", "'abc"],
            'unterminated comment' => ['SELECT 1 FROM matomo_log_visit /* open', '/*'],
            'unbalanced parenthesis' => ['SELECT (1 FROM matomo_log_visit', '('],
            'control character' => ["SELECT idvisit FROM matomo_log_visit --\x0c, (SELECT 1)", "\x0c"],
            'comment opening only for mysql' => ['SELECT idvisit FROM matomo_log_visit /*/ WHERE idsite = 1 /*/', '/*/'],
            'closing comment alone' => ['SELECT idvisit */ FROM matomo_log_visit', '*/'],
            'nul byte' => ["SELECT idvisit FROM matomo_log_visit\0", "\0"],
            'empty' => ['  ', ''],
            'max statement time option' => ['SELECT MAX_STATEMENT_TIME = 1000000 idvisit FROM matomo_log_visit', 'MAX_STATEMENT_TIME'],
        ];
    }
}
