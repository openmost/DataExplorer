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
use Piwik\Plugins\DataExplorer\Sql\RawSqlRunner;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;

/**
 * @group DataExplorer
 * @group RawSqlPrepareTest
 * @group Plugins
 */
class RawSqlPrepareTest extends TestCase
{
    /**
     * @dataProvider queries
     */
    public function test_rowCapAndPlaceholders(string $sql, string $expected, array $placeholders = []): void
    {
        $this->assertSame([$expected, $placeholders], RawSqlRunner::prepare(SqlGuard::validate($sql, 'matomo_', 'matomo'), 100));
    }

    public function queries(): array
    {
        return [
            'no limit' => ['SELECT idsite FROM matomo_site ORDER BY idsite DESC', "SELECT idsite FROM matomo_site ORDER BY idsite DESC\nLIMIT 100"],
            'limit above the cap' => ['SELECT idsite FROM matomo_site LIMIT 20000', "SELECT idsite FROM matomo_site LIMIT 100\n"],
            'limit below the cap' => ['SELECT idsite FROM matomo_site LIMIT 7', "SELECT idsite FROM matomo_site LIMIT 7\n"],
            'offset, count' => ['SELECT idsite FROM matomo_site LIMIT 20000, 300', "SELECT idsite FROM matomo_site LIMIT 20000, 100\n"],
            'count offset' => ['SELECT idsite FROM matomo_site LIMIT 300 OFFSET 20000', "SELECT idsite FROM matomo_site LIMIT 100 OFFSET 20000\n"],
            'comment after limit' => ['SELECT idsite FROM matomo_site LIMIT /* n */ 300', "SELECT idsite FROM matomo_site LIMIT   100\n"],
            'limit in a subquery only' => [
                'SELECT d.idsite FROM (SELECT idsite FROM matomo_site LIMIT 300) d WHERE d.idsite = {idsite}',
                "SELECT d.idsite FROM (SELECT idsite FROM matomo_site LIMIT 300) d WHERE d.idsite = ?\nLIMIT 100",
                ['{idsite}'],
            ],
            'limit of a parenthesized branch before the last union' => [
                '(SELECT idsite FROM matomo_site LIMIT 300) UNION SELECT idsite FROM matomo_goal',
                "(SELECT idsite FROM matomo_site LIMIT 300) UNION SELECT idsite FROM matomo_goal\nLIMIT 100",
            ],
            'limit of the last branch bounds the union' => [
                'SELECT idsite FROM matomo_site UNION SELECT idsite FROM matomo_goal ORDER BY 1 LIMIT 300',
                "SELECT idsite FROM matomo_site UNION SELECT idsite FROM matomo_goal ORDER BY 1 LIMIT 100\n",
            ],
            'placeholders in order, trailing comment and delimiter' => [
                "SELECT idsite FROM matomo_log_visit WHERE idsite = {idsite} AND visit_last_action_time >= {date_start} -- x\n;",
                "SELECT idsite FROM matomo_log_visit WHERE idsite = ? AND visit_last_action_time >= ?\nLIMIT 100",
                ['{idsite}', '{date_start}'],
            ],
        ];
    }
}
