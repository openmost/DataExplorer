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
use Piwik\Plugins\DataExplorer\Sql\ParsedQuery;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Plugins\DataExplorer\Sql\SiteScopeProof;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;

/**
 * @group DataExplorer
 * @group SiteScopeProofTest
 * @group Plugins
 */
class SiteScopeProofTest extends TestCase
{
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

    /**
     * @dataProvider provenQueries
     */
    public function test_provesQueriesFilteredOnTheDisplayedSite(string $sql): void
    {
        $this->assertNull($this->check($sql));
    }

    public function provenQueries(): array
    {
        return [
            'where filter' => ['SELECT COUNT(*) FROM matomo_log_visit v WHERE v.idsite = {idsite} AND v.visit_last_action_time >= {date_start}'],
            'reversed operands' => ['SELECT COUNT(*) FROM matomo_log_visit v WHERE {idsite} = v.idsite'],
            'filter in the join condition' => ['SELECT COUNT(*) FROM matomo_log_visit v JOIN matomo_log_link_visit_action a ON a.idvisit = v.idvisit AND a.idsite = {idsite} WHERE v.idsite = {idsite}'],
            'filter in the condition of a left join' => ['SELECT v.idvisit FROM matomo_log_visit v LEFT JOIN matomo_log_conversion c ON c.idvisit = v.idvisit AND c.idsite = {idsite} WHERE v.idsite = {idsite}'],
            'three tables' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_link_visit_action a ON a.idsite = {idsite} AND a.idvisit = v.idvisit JOIN matomo_log_conversion c ON c.idvisit = v.idvisit AND c.idsite = {idsite} WHERE v.idsite = {idsite}'],
            'table name as qualifier' => ['SELECT 1 FROM matomo_log_visit WHERE matomo_log_visit.idsite = {idsite}'],
            'backticked names' => ['SELECT 1 FROM `matomo_log_visit` AS `v` WHERE `v`.`idsite` = {idsite}'],
            'unqualified column on the only table' => ['SELECT 1 FROM matomo_log_visit WHERE idsite = {idsite}'],
            'single element IN' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite IN ({idsite})'],
            'filter next to an OR in parentheses' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite = {idsite} AND (v.visit_total_actions > 1 OR v.visit_goal_converted = 1)'],
            'parenthesized conjunction' => ['SELECT 1 FROM matomo_log_visit v WHERE (v.visit_total_actions > 1 AND v.idsite = {idsite}) AND v.idvisit > 0'],
            'filter after a BETWEEN' => ['SELECT 1 FROM matomo_log_visit v WHERE v.visit_last_action_time BETWEEN {date_start} AND {date_end} AND v.idsite = {idsite}'],
            'filter after a CASE' => ['SELECT 1 FROM matomo_log_visit v WHERE (CASE WHEN v.idvisit > 1 AND v.idvisit < 9 THEN 1 ELSE 0 END) = 1 AND v.idsite = {idsite}'],
            'filter next to a CASE in a group' => ['SELECT 1 FROM matomo_log_visit v WHERE (v.idsite = {idsite} AND CASE WHEN v.idvisit > 1 AND v.idvisit < 9 THEN 1 ELSE 0 END)'],
            'union with every branch filtered' => ['SELECT idvisit FROM matomo_log_visit WHERE idsite = {idsite} UNION ALL SELECT c.idvisit FROM matomo_log_conversion c WHERE c.idsite = {idsite}'],
            'parenthesized union branches filtered' => ['(SELECT idvisit FROM matomo_log_visit WHERE idsite = {idsite}) UNION (SELECT idvisit FROM matomo_log_conversion WHERE idsite = {idsite})'],
            'filtered subquery' => ['SELECT v.idvisit FROM matomo_log_visit v WHERE v.idsite = {idsite} AND v.idvisit IN (SELECT c.idvisit FROM matomo_log_conversion c WHERE c.idsite = {idsite})'],
            'filtered derived table' => ['SELECT d.n FROM (SELECT COUNT(*) AS n FROM matomo_log_visit WHERE idsite = {idsite}) d'],
            'site table' => ['SELECT name, ts_created FROM matomo_site WHERE idsite = {idsite}'],
            'goal joined to site' => ['SELECT g.name FROM matomo_goal g JOIN matomo_site s ON s.idsite = {idsite} WHERE g.idsite = {idsite}'],
            'segment on its site column' => ['SELECT name FROM matomo_segment s WHERE s.enable_only_idsite = {idsite} AND s.deleted = 0'],
            'custom dimensions' => ['SELECT name FROM matomo_custom_dimensions WHERE idsite = {idsite}'],
            'site url and page view time' => ['SELECT u.url FROM matomo_site_url u JOIN matomo_log_page_view_time p ON p.idsite = {idsite} WHERE u.idsite = {idsite}'],
            'natural join' => ['SELECT 1 FROM matomo_log_visit v NATURAL JOIN matomo_log_conversion c WHERE v.idsite = {idsite} AND c.idsite = {idsite}'],
            'using' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_conversion c USING (idvisit) WHERE v.idsite = {idsite} AND c.idsite = {idsite}'],
            'log_action is shared' => ['SELECT a.name FROM matomo_log_link_visit_action l JOIN matomo_log_action a ON a.idaction = l.idaction_url WHERE l.idsite = {idsite}'],
            'comments around the filter' => ["SELECT 1 FROM matomo_log_visit v /* x */ WHERE v.idsite /* y */ = {idsite} -- z\n AND 1 = 1"],
            'filter before GROUP BY' => ['SELECT v.idsite, COUNT(*) FROM matomo_log_visit v WHERE v.idsite = {idsite} GROUP BY v.idsite ORDER BY 2 DESC LIMIT 5'],
        ];
    }

    /**
     * @dataProvider unprovenQueries
     */
    public function test_refusesQueriesNotProvenToReadTheDisplayedSiteOnly(string $sql, string $expected): void
    {
        $this->assertSame($expected, $this->check($sql));
    }

    public function unprovenQueries(): array
    {
        return [
            'no filter' => ['SELECT COUNT(*) FROM matomo_log_visit', 'log_visit'],
            'only one side of a join filtered' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_link_visit_action a ON a.idvisit = v.idvisit WHERE v.idsite = {idsite}', 'a'],
            'three tables, one join unproven' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_link_visit_action a ON a.idsite = {idsite} JOIN matomo_log_conversion c ON c.idvisit = v.idvisit WHERE v.idsite = {idsite}', 'c'],
            'or above the filter' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite = {idsite} OR 1=1', 'v'],
            'filter in a disjunction in parentheses' => ['SELECT 1 FROM matomo_log_visit v WHERE (v.idsite = {idsite} OR 1=0)', 'v'],
            'xor' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite = {idsite} XOR 1=1', 'v'],
            'pipes' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite = {idsite} || 1=1', 'v'],
            'not' => ['SELECT 1 FROM matomo_log_visit v WHERE NOT (v.idsite = {idsite})', 'v'],
            'filter inside a BETWEEN' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idvisit BETWEEN 1 AND v.idsite = {idsite}', 'v'],
            'filter inside a CASE' => ['SELECT 1 FROM matomo_log_visit v WHERE (CASE WHEN 1 = 0 AND v.idsite = {idsite} AND 1 = 1 THEN 1 ELSE 1 END)', 'v'],
            'filter inside a CASE with a column named end' => ['SELECT 1 FROM matomo_log_visit v WHERE (CASE WHEN end AND v.idsite = {idsite} AND 1 THEN 1 END)', 'v'],
            'filter inside a function' => ['SELECT 1 FROM matomo_log_visit v WHERE IF(v.idsite = {idsite}, 1, 1)', 'v'],
            'filter inside an IN subquery' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idvisit IN (SELECT 1 WHERE 1 AND v.idsite = {idsite})', 'v'],
            'filter inside a subquery standing as a conjunct' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idvisit > 0 AND (SELECT 1 WHERE 1 AND v.idsite = {idsite})', 'v'],
            'null safe equality' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite <=> {idsite}', 'v'],
            'other operator' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite >= {idsite}', 'v'],
            'filter on another column' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idvisit = {idsite}', 'v'],
            'in list of two' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite IN ({idsite}, 1)', 'v'],
            'filter in having' => ['SELECT v.idsite FROM matomo_log_visit v GROUP BY v.idsite HAVING v.idsite = {idsite}', 'v'],
            'filter qualified by the other table' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_conversion c ON v.idsite = {idsite} WHERE c.idvisit = v.idvisit', 'v'],
            'filter of the left table in a left join condition' => ['SELECT 1 FROM matomo_log_visit v LEFT JOIN matomo_log_conversion c ON c.idvisit = v.idvisit AND v.idsite = {idsite} AND c.idsite = {idsite}', 'v'],
            'right join keeps every row of the joined table' => ['SELECT 1 FROM matomo_log_visit v RIGHT JOIN matomo_log_conversion c ON c.idsite = {idsite} AND c.idvisit = v.idvisit WHERE v.idsite = {idsite}', 'c'],
            'natural right join target filtered in where only on the other table' => ['SELECT 1 FROM matomo_log_visit v NATURAL RIGHT JOIN matomo_log_conversion c WHERE v.idsite = {idsite}', 'c'],
            'using does not filter on the site' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_conversion c USING (idsite) WHERE v.idsite = {idsite}', 'c'],
            'unqualified column with two tables' => ['SELECT 1 FROM matomo_log_visit v JOIN matomo_log_conversion c ON c.idvisit = v.idvisit WHERE idsite = {idsite} AND c.idsite = {idsite}', 'v'],
            'alias in another case' => ['SELECT 1 FROM matomo_log_visit v WHERE V.idsite = {idsite}', 'v'],
            'table name once aliased' => ['SELECT 1 FROM matomo_log_visit v WHERE matomo_log_visit.idsite = {idsite}', 'v'],
            'union with one unfiltered branch' => ['SELECT idvisit FROM matomo_log_visit WHERE idsite = {idsite} UNION SELECT idvisit FROM matomo_log_conversion', 'log_conversion'],
            'parenthesized union with one unfiltered branch' => ['(SELECT idvisit FROM matomo_log_visit WHERE idsite = {idsite}) UNION (SELECT idvisit FROM matomo_log_conversion c)', 'c'],
            'unfiltered subquery' => ['SELECT v.idvisit FROM matomo_log_visit v WHERE v.idsite = {idsite} AND v.idvisit IN (SELECT idvisit FROM matomo_log_conversion)', 'log_conversion'],
            'correlated subquery without its own filter' => ['SELECT v.idvisit FROM matomo_log_visit v WHERE v.idsite = {idsite} AND EXISTS (SELECT 1 FROM matomo_log_conversion c WHERE c.idvisit = v.idvisit)', 'c'],
            'scalar subquery in the select list' => ['SELECT (SELECT COUNT(*) FROM matomo_goal g) AS goals FROM matomo_log_visit v WHERE v.idsite = {idsite}', 'g'],
            'derived table filtered inside, outer table not' => ['SELECT d.n FROM (SELECT COUNT(*) AS n, idvisit FROM matomo_log_visit WHERE idsite = {idsite}) d JOIN matomo_log_conversion c ON c.idvisit = d.idvisit', 'c'],
            'derived table filtered outside only' => ['SELECT d.idsite FROM (SELECT idsite FROM matomo_log_visit) d WHERE d.idsite = {idsite}', 'log_visit'],
            'outer filter does not cover the subquery' => ['SELECT v.idvisit FROM matomo_log_visit v WHERE v.idsite = {idsite} AND v.idvisit IN (SELECT v.idvisit FROM matomo_log_conversion c)', 'c'],
            'segment on idsite' => ['SELECT name FROM matomo_segment s WHERE s.idsite = {idsite}', 's'],
            'segment unfiltered' => ['SELECT name FROM matomo_segment', 'segment'],
            'site filtered on another column' => ['SELECT name FROM matomo_site WHERE main_url = {idsite}', 'site'],
            'first failing in source order' => ['SELECT 1 FROM matomo_site_url u JOIN matomo_goal g ON g.idsite = u.idsite', 'u'],
            'filter compared to a literal' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite = 1', 'v'],
            'collated filter' => ['SELECT 1 FROM matomo_log_visit v WHERE v.idsite = {idsite} COLLATE utf8mb4_bin', 'v'],
            'database qualified column' => ['SELECT 1 FROM matomo_log_visit WHERE matomo5.matomo_log_visit.idsite = {idsite}', 'log_visit'],
        ];
    }

    /**
     * MySQL reads "x RIGHT JOIN a NATURAL JOIN c ON X" as "x RIGHT JOIN (a NATURAL JOIN c) ON X", keeping every row of
     * c: such a query must never be published.
     *
     * @dataProvider conditionsOfAnEnclosingJoin
     */
    public function test_aConditionOfAnEnclosingJoinIsNeverCreditedToTheTarget(string $sql): void
    {
        try {
            $unproven = $this->check($sql);
        } catch (RejectedQueryException $e) {
            $unproven = $e->getOffendingToken();
        }
        $this->assertNotNull($unproven);
    }

    public function conditionsOfAnEnclosingJoin(): array
    {
        return [
            ['SELECT s.idsegment, s.enable_only_idsite FROM matomo_log_action la RIGHT JOIN matomo_log_visit v NATURAL JOIN matomo_segment s ON s.enable_only_idsite = {idsite} WHERE v.idsite = {idsite}'],
            ['SELECT u.idsite, u.url FROM matomo_log_action la RIGHT JOIN matomo_segment s NATURAL JOIN matomo_site_url u ON u.idsite = {idsite} WHERE s.enable_only_idsite = {idsite}'],
            ['SELECT u.idsite, u.url FROM (SELECT 1 AS zz) d RIGHT JOIN matomo_segment s NATURAL JOIN matomo_site_url u ON u.idsite = {idsite} WHERE s.enable_only_idsite = {idsite}'],
            ['SELECT s.enable_only_idsite FROM matomo_log_action la RIGHT OUTER JOIN matomo_site_url u NATURAL JOIN matomo_segment s ON s.enable_only_idsite = {idsite} WHERE u.idsite = {idsite}'],
            ['SELECT 1 FROM matomo_log_visit x LEFT JOIN matomo_log_visit a JOIN matomo_log_conversion c ON c.idsite = {idsite} WHERE x.idsite = {idsite} AND a.idsite = {idsite}'],
            ['SELECT 1 FROM matomo_log_visit x RIGHT JOIN matomo_log_visit a JOIN matomo_log_conversion c ON c.idsite = {idsite} WHERE x.idsite = {idsite} AND a.idsite = {idsite}'],
        ];
    }

    public function test_failsClosedWithoutRecordedTableReferences(): void
    {
        $guarded = SqlGuard::validate('SELECT 1 FROM matomo_log_visit v WHERE v.idsite = {idsite}', 'matomo_', self::DB);
        $query = new ParsedQuery($guarded->getStatement(), $guarded->getStatements(), $guarded->getTables(), $guarded->getExecutableSql(), $guarded->getTokens(), []);

        $this->assertSame('log_visit', SiteScopeProof::check($query));
    }

    public function test_aQueryOnSharedTablesOnlyNeedsNoProof(): void
    {
        $this->assertNull($this->check('SELECT name, type FROM matomo_log_action WHERE type = 1'));
        $this->assertNull($this->check('SELECT 1'));
        $this->assertNull($this->check('SELECT 1 FROM DUAL'));
    }

    public function test_worksWithAnEmptyPrefix(): void
    {
        $this->assertNull($this->check('SELECT 1 FROM log_visit v WHERE v.idsite = {idsite}', ''));
        $this->assertNull($this->check('SELECT 1 FROM log_visit WHERE log_visit.idsite = {idsite}', ''));
        $this->assertSame('log_conversion', $this->check('SELECT 1 FROM log_visit v, log_conversion WHERE v.idsite = {idsite}', ''));
    }

    public function test_returnsTheUnprefixedTableNameWhateverItsCase(): void
    {
        $this->assertSame('log_visit', $this->check('SELECT 1 FROM MATOMO_LOG_VISIT'));
        $this->assertSame('log_visit', $this->check('SELECT 1 FROM matomo5.`Matomo_Log_Visit`'));
    }

    private function check(string $sql, string $prefix = 'matomo_'): ?string
    {
        return SiteScopeProof::check(SqlGuard::validate($sql, $prefix, self::DB));
    }
}
