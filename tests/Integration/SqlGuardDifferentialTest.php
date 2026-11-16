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
use Piwik\Db;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Differential test of SqlGuard against MySQL itself: thousands of generated queries, each with one table reference
 * outside the allowlist in a table-region position (or an allowlisted control). Every query the guard accepts is
 * explained by MySQL, and every table MySQL resolves must be an allowlisted table of the Matomo database.
 *
 * @group DataExplorer
 * @group SqlGuardDifferentialTest
 * @group Plugins
 */
class SqlGuardDifferentialTest extends IntegrationTestCase
{
    private const OUTSIDE_SCHEMA = 'dataexplorer_outside';

    private const MIN_GENERATED = 5000;
    private const MIN_ACCEPTED = 1000;
    private const MIN_EXPLAINED = 500;

    /** @var \PDO */
    private $pdo;

    /** @var string */
    private $database;

    /** @var string */
    private $prefix;

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        $config = Config::getInstance()->database;
        $this->database = (string) $config['dbname'];
        $this->prefix = Common::prefixTable('');

        // A connection of its own: SHOW WARNINGS must follow its EXPLAIN on the same session, unprepared.
        $dsn = 'mysql:host=' . $config['host'] . ';port=' . ($config['port'] ?? 3306) . ';dbname=' . $this->database;
        $this->pdo = new \PDO($dsn, (string) $config['username'], (string) $config['password']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        Db::exec('CREATE DATABASE IF NOT EXISTS ' . self::OUTSIDE_SCHEMA);
        Db::exec('CREATE TABLE IF NOT EXISTS ' . self::OUTSIDE_SCHEMA . '.t (a INT, idsite INT)');
    }

    public function tearDown(): void
    {
        Db::exec('DROP DATABASE IF EXISTS ' . self::OUTSIDE_SCHEMA);
        $this->pdo = null;

        parent::tearDown();
    }

    public function test_theResolvedTablesShowWhatMysqlReads(): void
    {
        $this->assertSame([[$this->database, $this->prefix . 'site'], [self::OUTSIDE_SCHEMA, 't']],
            $this->resolvedTables('SELECT s.idsite FROM ' . $this->prefix . 'site AS s, ' . self::OUTSIDE_SCHEMA . '.t AS `x` WHERE x.a = s.idsite'));
        $this->assertSame([[$this->database, $this->prefix . 'user']],
            $this->resolvedTables('SELECT 1 FROM ((SELECT 1 a FROM DUAL) z, `' . $this->prefix . 'user`)'));
        $this->assertNull($this->resolvedTables('SELECT 1 FROM no_such_table'));
    }

    public function test_everyAcceptedQueryReadsAllowlistedTablesOnly(): void
    {
        $queries = $this->generate();
        $this->assertGreaterThanOrEqual(self::MIN_GENERATED, count($queries));

        $accepted = 0;
        $explained = 0;
        $mismatches = [];
        foreach ($queries as $sql) {
            try {
                $executable = SqlGuard::validate($sql, $this->prefix)->getExecutableSql();
            } catch (RejectedQueryException $e) {
                continue;
            }
            $accepted++;

            $resolved = $this->resolvedTables($executable);
            if ($resolved === null) {
                continue;
            }
            $explained++;
            foreach ($resolved as [$schema, $table]) {
                if (strtolower($schema) !== strtolower($this->database) || !$this->isAllowlisted($table)) {
                    $mismatches[] = $sql . '  =>  ' . $schema . '.' . $table;
                    break;
                }
            }
        }

        fwrite(STDERR, sprintf("\nSqlGuard differential: %d generated, %d accepted, %d explained, %d mismatches\n", count($queries), $accepted, $explained, count($mismatches)));
        $this->assertSame([], $mismatches, 'Accepted queries reading a table outside the allowlist');
        $this->assertGreaterThanOrEqual(self::MIN_ACCEPTED, $accepted);
        $this->assertGreaterThanOrEqual(self::MIN_EXPLAINED, $explained);
    }

    /**
     * Tables MySQL resolves, from the rewritten query EXPLAIN leaves in SHOW WARNINGS: there a table reads
     * `schema`.`table` [`alias`], whatever alias or quoting the query used. The tree format names aliases only.
     *
     * @return array<int, array{0: string, 1: string}>|null null when MySQL refuses the query
     */
    private function resolvedTables(string $executable): ?array
    {
        $sql = str_replace(['{idsite}', '{date_start}', '{date_end}'], ['1', "'2024-01-01'", "'2024-01-31'"], $executable);
        try {
            $this->pdo->query('EXPLAIN ' . $sql)->fetchAll();
            $notes = $this->pdo->query('SHOW WARNINGS')->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return null;
        }

        $tables = [];
        $schemas = array_map('strtolower', $this->pdo->query('SHOW DATABASES')->fetchAll(\PDO::FETCH_COLUMN));
        foreach ($notes as $note) {
            if ((int) $note['Code'] !== 1003) {
                continue;
            }
            // A table is a two-part name, the lookarounds skip the tail of `schema`.`alias`.`column`.
            preg_match_all('/(?<![.`\w])`((?:[^`]|``)+)`\.`((?:[^`]|``)+)`(?!\.)/', (string) $note['Message'], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                // A derived table's column reads `alias`.`column`: only a real schema starts a table name. An alias
                // named like a schema is over-reported, which fails the test rather than hiding a table.
                if (in_array(strtolower($match[1]), $schemas, true)) {
                    $tables[] = [$match[1], $match[2]];
                }
            }
        }

        return $tables;
    }

    private function isAllowlisted(string $table): bool
    {
        $table = strtolower($table);

        return substr($table, 0, strlen($this->prefix)) === $this->prefix
            && SqlGuard::isAllowedTable(substr($table, strlen($this->prefix)));
    }

    /**
     * @return string[]
     */
    private function generate(): array
    {
        mt_srand(20261116);
        $queries = [];

        $hidden = $this->hiddenFactors();
        $controls = $this->controlFactors();
        $placements = $this->placements();
        $trailers = ['', ' WHERE 1 = 1', ' WHERE a1.idsite = {idsite}', ' GROUP BY 1', ' ORDER BY 1 LIMIT 1', ' LIMIT 1',
            ' WINDOW w AS (ORDER BY 1)', ' HAVING 1 = 1'];

        // Every hidden reference in every position of every region shape, in a random placement.
        foreach ($this->regionShapes() as $shape) {
            $slots = substr_count($shape, '{F}');
            for ($position = 0; $position < $slots; $position++) {
                foreach (array_merge($hidden, $controls) as $n => $factor) {
                    for ($k = 0; $k < ($n < count($hidden) ? 4 : 12); $k++) {
                        $region = $this->fillRegion($shape, $position, $factor);
                        $select = 'SELECT 1 FROM ' . $region . $trailers[mt_rand(0, count($trailers) - 1)];
                        $queries[] = str_replace('{Q}', $select, $placements[mt_rand(0, count($placements) - 1)]);
                    }
                }
            }
        }

        // Every placement and trailer around a sample of regions.
        foreach ($placements as $placement) {
            foreach ($trailers as $trailer) {
                for ($k = 0; $k < 6; $k++) {
                    $shapes = $this->regionShapes();
                    $shape = $shapes[mt_rand(0, count($shapes) - 1)];
                    $pool = $k % 2 === 0 ? $hidden : $controls;
                    $factor = $pool[mt_rand(0, count($pool) - 1)];
                    $region = $this->fillRegion($shape, mt_rand(0, substr_count($shape, '{F}') - 1), $factor);
                    $queries[] = str_replace('{Q}', 'SELECT 1 FROM ' . $region . $trailer, $placement);
                }
            }
        }

        foreach ($this->handPickedDoubts() as $sql) {
            $queries[] = $sql;
        }

        return array_values(array_unique($queries));
    }

    /**
     * Region shapes: {F} a table factor, {C} a join condition. Aliases a1..a3 are given in factor order.
     *
     * @return string[]
     */
    private function regionShapes(): array
    {
        return [
            '{F}',
            '{F}, {F}',
            '{F}, {F}, {F}',
            '{F} JOIN {F} ON {C}',
            '{F} LEFT JOIN {F} ON {C}',
            '{F} LEFT OUTER JOIN {F} ON {C}',
            '{F} RIGHT JOIN {F} ON {C}',
            '{F} INNER JOIN {F} ON {C}',
            '{F} CROSS JOIN {F}',
            '{F} CROSS JOIN {F} ON {C}',
            '{F} NATURAL JOIN {F}',
            '{F} NATURAL LEFT JOIN {F}',
            '{F} STRAIGHT_JOIN {F}',
            '{F} STRAIGHT_JOIN {F} ON {C}',
            '{F} JOIN {F} USING (idsite)',
            '{F} LEFT /* c */ JOIN {F} ON {C}',
            '{F}, {F} JOIN {F} ON {C}',
            '{F} JOIN {F} ON {C} JOIN {F} ON {C}',
            '{F} JOIN {F} ON {C} LEFT JOIN {F} USING (idsite)',
            '{F} JOIN {F} ON {C}, {F}',
            '{F} JOIN {F} JOIN {F} ON {C} ON {C}',
            '{F} JOIN {F} ON {C} {F}',
            '{F} {F}',
        ];
    }

    private function fillRegion(string $shape, int $hiddenPosition, string $hiddenFactor): string
    {
        $factors = $this->allowedFactors();
        $conditions = $this->conditions();
        $index = 0;
        $region = preg_replace_callback('/\{F\}/', function () use (&$index, $hiddenPosition, $hiddenFactor, $factors) {
            $alias = 'a' . ($index + 1);
            $factor = $index === $hiddenPosition ? $hiddenFactor : $factors[mt_rand(0, count($factors) - 1)];
            $index++;

            return str_replace('{A}', $alias, $factor);
        }, $shape);

        return (string) preg_replace_callback('/\{C\}/', function () use ($conditions) {
            return $conditions[mt_rand(0, count($conditions) - 1)];
        }, $region);
    }

    /**
     * References the guard must refuse wherever they stand. {A} is the alias of the position.
     *
     * @return string[]
     */
    private function hiddenFactors(): array
    {
        $p = $this->prefix;
        $db = $this->database;
        $out = self::OUTSIDE_SCHEMA;

        return [
            "`{$p}user` {A}",
            "{$p}user",
            "`{$p}option` AS {A}",
            "{$p}access {A}",
            "{$out}.t {A}",
            "`{$out}`.`t` AS {A}",
            "{$out} . t",
            "{$db}.{$p}user {A}",
            "`{$db}`.`{$p}session` {A}",
            "(SELECT login AS idsite FROM `{$p}user`) {A}",
            "(SELECT a AS idsite FROM {$out}.t) AS {A}",
            "(SELECT idsite FROM {$p}site UNION SELECT a FROM {$out}.t) {A}",
            "(SELECT idsite FROM {$p}site UNION (SELECT a FROM {$out}.t)) {A}",
            "({$out}.t) {A}",
            "({$p}site {A}, {$out}.t)",
            "((SELECT 1 AS idsite FROM {$p}site) z{A}, {$out}.t)",
            "((SELECT idsite FROM {$out}.t)) {A}",
            "{$p}site {A} USE INDEX (PRIMARY), {$out}.t",
            "{$p}site {A} PARTITION (p0) JOIN {$out}.t ON 1",
            "{$p}site AS {A} JOIN {$out}.t USING (idsite)",
            "{$p}site {A} STRAIGHT_JOIN {$out}.t",
            "{$p}site {A} CROSS JOIN `{$p}user`",
            "{$p}site {A} JOIN goal ON 1 {$out}.t",
            "{$p}site {A}, LATERAL (SELECT a FROM {$out}.t) x{A}",
        ];
    }

    /**
     * Allowlisted references in the hidden position, so the shapes and placements also run accepted.
     *
     * @return string[]
     */
    private function controlFactors(): array
    {
        $p = $this->prefix;

        return [
            "{$p}site {A}",
            "(SELECT idsite FROM {$p}goal) AS {A}",
            "`{$p}segment` `{A}`",
        ];
    }

    /**
     * Allowlisted table factors in every accepted form. {A} is the alias of the position.
     *
     * @return string[]
     */
    private function allowedFactors(): array
    {
        $p = $this->prefix;
        $db = $this->database;

        return [
            "{$p}site {A}",
            "{$p}site AS {A}",
            "`{$p}site` `{A}`",
            "{$p}goal AS `{A}`",
            "{$db}.{$p}site {A}",
            "`{$db}`.`{$p}goal` AS {A}",
            "`{$db}` . `{$p}site_url` {A}",
            strtoupper($db) . ".{$p}site {A}",
            "{$p}log_visit {A} USE INDEX (index_idsite_datetime)",
            "{$p}log_visit AS {A} IGNORE INDEX (index_idsite_datetime) FORCE KEY (index_idsite_config_datetime)",
            "{$p}log_visit {A} USE INDEX ()",
            "{$p}site PARTITION (p0)",
            "{$p}custom_dimensions {A}",
            "(SELECT idsite FROM {$p}site) {A}",
            "(SELECT idsite FROM {$p}site UNION SELECT idsite FROM {$p}goal) AS {A}",
            "(SELECT s.idsite FROM {$p}site s WHERE EXISTS (SELECT 1 FROM {$p}goal g WHERE g.idsite = s.idsite)) {A}",
            "(SELECT idsite FROM {$p}site ORDER BY idsite LIMIT 1) `{A}`",
            "{$p}site status",
            "{$p}goal `order`",
            'DUAL',
        ];
    }

    /**
     * @return string[]
     */
    private function conditions(): array
    {
        $p = $this->prefix;

        return [
            '1',
            '(1 = 1)',
            'a1.idsite = a2.idsite',
            'a2.idsite = {idsite}',
            "LEFT('abc', 1) = 'a'",
            "RIGHT('abc', 1) = 'c'",
            'CASE WHEN a1.idsite > 0 THEN 1 ELSE 0 END = 1',
            '1 IN (1, 2, 3)',
            "1 IN (SELECT idsite FROM {$p}site)",
            "EXISTS (SELECT 1 FROM {$p}goal g WHERE g.idsite = a1.idsite)",
            "TRIM(LEADING 'x' FROM 'xa') = 'a'",
            'EXTRACT(YEAR FROM NOW()) > 0',
            'CAST(1 AS CHAR) = 1',
            "(SELECT ROW_NUMBER() OVER (PARTITION BY idsite ORDER BY idsite) FROM {$p}site LIMIT 1) = 1",
            "(SELECT idsite FROM {$p}site GROUP BY idsite ORDER BY idsite LIMIT 1) = 1",
            '1 BETWEEN 0 AND 2',
            "'a' LIKE 'a%'",
            'NOW() - INTERVAL 1 DAY < NOW()',
            "'a' = 'a' COLLATE utf8mb4_general_ci",
            'GREATEST(1, 2) IN (2)',
            '(1, 2) = (1, 2)',
        ];
    }

    /**
     * Outer queries a generated SELECT is placed in. "o" is an outer alias correlated subqueries may qualify.
     *
     * @return string[]
     */
    private function placements(): array
    {
        $site = $this->prefix . 'site';

        return [
            '{Q}',
            '({Q})',
            "SELECT 1 FROM {$site} o WHERE 1 IN ({Q})",
            "SELECT 1 FROM {$site} o WHERE EXISTS ({Q})",
            "SELECT ({Q}) FROM {$site} o",
            "SELECT * FROM {$site} o, ({Q}) z",
            "SELECT 1 FROM {$site} o JOIN ({Q}) z ON 1",
            "SELECT 1 FROM {$site} o UNION {Q}",
            "SELECT 1 FROM {$site} o UNION ALL ({Q})",
            "({Q}) UNION (SELECT 1 FROM {$site} o)",
            "{Q} UNION SELECT 1 FROM {$site} o",
            "SELECT 1 FROM {$site} o WHERE o.idsite IN (SELECT 1 FROM {$site} p WHERE p.idsite = o.idsite AND EXISTS ({Q}))",
        ];
    }

    /**
     * Doubts from reading the walker: where its table region ends, where an ON condition stops, what sits in alias
     * position.
     *
     * @return string[]
     */
    private function handPickedDoubts(): array
    {
        $p = $this->prefix;
        $out = self::OUTSIDE_SCHEMA . '.t';
        $user = "`{$p}user`";
        $site = $p . 'site';
        $goal = $p . 'goal';

        return [
            "SELECT 1 FROM {$site} s WINDOW w AS (PARTITION BY s.idsite), {$user} AS (ORDER BY 1)",
            "SELECT 1 FROM {$site} s LIMIT 1, {$out}",
            "SELECT 1 FROM {$site} s GROUP BY s.idsite, {$out}",
            "SELECT 1 FROM {$site} s ORDER BY 1, {$out}",
            "SELECT 1 FROM {$site} s HAVING 1, {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1 {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1 AND ({$out}.a = 1)",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON (SELECT 1) {$user}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1JOIN {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON (1)JOIN {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 'a'JOIN {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1 /*x*/JOIN/*y*/ {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1 -- c\nJOIN {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1 # c\n, {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON s.`order` = 1 JOIN {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON s.order = 1, {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON s. join = 1, {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON LEFT (s.name, 1) = 'a' JOIN {$out} ON 1",
            "SELECT 1 FROM {$site} s JOIN {$goal} g ON 1 LEFT (JOIN) {$out}",
            "SELECT 1 FROM {$site} AS {$user}",
            "SELECT 1 FROM {$site} {$user}, {$goal}",
            "SELECT 1 FROM {$site} AS AS {$user}",
            "SELECT 1 FROM {$site} s s2, {$user}",
            "SELECT 1 FROM {$site} AS 's', {$user}",
            "SELECT 1 FROM {$site} " . self::OUTSIDE_SCHEMA . ", {$out}",
            "SELECT 1 FROM {$site} `" . self::OUTSIDE_SCHEMA . "` WHERE " . self::OUTSIDE_SCHEMA . '.t.a = 1',
            "SELECT 1 FROM {$site} " . $this->database . ', ' . $this->database . '.' . $p . 'user',
            "SELECT 1 FROM DUAL, {$out}",
            "SELECT 1 FROM DUAL JOIN {$out}",
            "SELECT 1 FROM {$site} s USE INDEX (x, {$user})",
            "SELECT 1 FROM {$site} s USE INDEX ((SELECT 1)), {$user}",
            "SELECT 1 FROM {$site} s JOIN {$goal} USING ((idsite)), {$out}",
            "SELECT 1 FROM {$site} s JOIN {$goal} USING (idsite) ON 1, {$out}",
            "SELECT 1 FROM (SELECT 1 a) t (a), {$out}",
            "SELECT 1 FROM (SELECT 1 a) t USE INDEX (x), {$out}",
            "SELECT 1 FROM (SELECT 1 a) t PARTITION (p), {$out}",
            "SELECT 1 FROM {$site} {idsite} {$out}",
            "SELECT 1 FROM {$site} s STRAIGHT_JOIN {$out} ON 1",
            "SELECT STRAIGHT_JOIN 1 FROM {$site} s, {$out}",
            "SELECT 1 FROM {$site} s WHERE 1 UNION SELECT 1 FROM {$site} JOIN {$goal} ON 1 {$out}",
            "SELECT 1 FROM {$site} s UNION SELECT 1 FROM {$out} UNION SELECT 1 FROM {$site}",
            "SELECT s.idsite FROM {$site} s WHERE s.idsite IN (SELECT t.a FROM {$out} t)",
            "SELECT (SELECT 1 FROM {$site} q), q.a FROM {$out} q",
            "SELECT x.a FROM {$site} s WHERE EXISTS (SELECT 1 FROM {$site} x)",
            "SELECT 1 FROM {$site} s, (SELECT 1 a FROM {$site} " . self::OUTSIDE_SCHEMA . ') z, ' . $out,
        ];
    }
}
