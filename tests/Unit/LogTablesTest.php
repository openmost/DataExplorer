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
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Catalog\LogTables;
use Piwik\Tracker\LogTable;

/**
 * @group DataExplorer
 * @group LogTablesTest
 * @group Plugins
 */
class LogTablesTest extends TestCase
{
    private function table(string $name, string $visitColumn, array $primaryKey, string $datetime = '', array $ways = [], string $idColumn = ''): LogTable
    {
        return new class ($name, $visitColumn, $primaryKey, $datetime, $ways, $idColumn) extends LogTable {
            public function __construct(
                private string $name,
                private string $visitColumn,
                private array $primaryKey,
                private string $datetime,
                private array $ways,
                private string $idColumn
            ) {
            }

            public function getName()
            {
                return $this->name;
            }

            public function getIdColumn()
            {
                return $this->idColumn !== '' ? $this->idColumn : ($this->primaryKey[0] ?? '');
            }

            public function getColumnToJoinOnIdVisit()
            {
                return $this->visitColumn;
            }

            public function getPrimaryKey()
            {
                return $this->primaryKey;
            }

            public function getDateTimeColumn()
            {
                return $this->datetime;
            }

            public function getWaysToJoinToOtherLogTables()
            {
                return $this->ways;
            }
        };
    }

    private function tables(?callable $exists = null): LogTables
    {
        return LogTables::fromLogTables([
            $this->table('log_visit', 'idvisit', ['idvisit'], 'visit_last_action_time'),
            $this->table('log_link_visit_action', 'idvisit', ['idlink_va'], 'server_time'),
            $this->table('log_conversion', 'idvisit', ['idvisit', 'idgoal', 'buster'], 'server_time'),
            $this->table('log_action', '', ['idaction']),
            $this->table('log_conversion_item', 'idvisit', ['idvisit', 'idorder', 'idaction_sku']),
            $this->table('log_crash', '', ['idlogcrash'], '', ['log_crash_event' => 'idlogcrash']),
            $this->table('log_crash_event', 'idvisit', ['idlogcrashevent'], 'server_time'),
            $this->table('log_abtesting', 'idvisit', [], 'server_time', [], 'idvisit'),
            $this->table('log_orphan', '', ['id']),
        ], $exists);
    }

    public function test_historicalScopes_andTablesAsTheirOwnScope(): void
    {
        $tables = $this->tables();

        $this->assertSame('action', $tables->scopeOf('log_link_visit_action'));
        $this->assertSame('log_crash', $tables->scopeOf('log_crash'));
        $this->assertSame('log_conversion_item', $tables->tableOf('log_conversion_item'));
        $this->assertSame('log_visit', $tables->tableOf('visit'));
        $this->assertNull($tables->scopeOf('log_action'), 'actions are read through the action rows');
        $this->assertNull($tables->scopeOf('log_orphan'), 'not joinable to a visit');
        $this->assertSame(['idvisit', 'idgoal', 'buster'], $tables->grain('log_conversion'));
    }

    public function test_repeats_keepsTheHistoricalRules(): void
    {
        $tables = $this->tables();

        $this->assertTrue($tables->repeats('log_visit', 'log_link_visit_action'));
        $this->assertTrue($tables->repeats('log_visit', 'log_conversion'));
        $this->assertTrue($tables->repeats('log_link_visit_action', 'log_conversion'));
        $this->assertTrue($tables->repeats('log_conversion', 'log_link_visit_action'));
        $this->assertFalse($tables->repeats('log_link_visit_action', 'log_visit'));
        $this->assertFalse($tables->repeats('log_conversion', 'log_visit'));
        $this->assertFalse($tables->repeats('log_visit', 'log_visit'));
    }

    public function test_repeats_newTables_fromTheirKeys(): void
    {
        $tables = $this->tables();

        $this->assertTrue($tables->repeats('log_visit', 'log_conversion_item'));
        $this->assertFalse($tables->repeats('log_conversion_item', 'log_visit'));
        $this->assertFalse($tables->repeats('log_crash_event', 'log_crash'), 'one crash per crash event');
        $this->assertTrue($tables->repeats('log_crash', 'log_crash_event'));
        $this->assertTrue($tables->repeats('log_crash', 'log_visit'), 'a crash spans several visits');
        $this->assertTrue($tables->repeats('log_visit', 'log_abtesting'), 'no declared key, never taken as one row per visit');
    }

    public function test_datedTable_andJoinOrder(): void
    {
        $tables = $this->tables();

        $this->assertSame('log_visit', $tables->datedTable('log_conversion_item'));
        $this->assertSame('log_crash_event', $tables->datedTable('log_crash'));
        $this->assertSame('log_link_visit_action', $tables->datedTable('log_link_visit_action'));
        $this->assertSame(['log_crash', 'log_crash_event', 'log_visit'], $tables->joinOrder(['log_crash', 'log_visit', 'log_crash_event']));
        $this->assertSame(['log_visit', 'log_link_visit_action', 'log_conversion'], $tables->joinOrder(['log_visit', 'log_link_visit_action', 'log_conversion']));
    }

    public function test_tablesMissingFromTheDatabase_areLeftOut(): void
    {
        $tables = $this->tables(fn (string $table) => $table !== 'log_conversion_item');

        $this->assertFalse($tables->has('log_conversion_item'));
        $this->assertTrue($tables->has('log_visit'));
    }

    public function test_dimensionExpression_onlyForFunctionsOfItsOwnColumn(): void
    {
        $this->assertSame('HOUR(log_media.server_time)', DimensionCatalog::expressionOf('HOUR(log_media.server_time)', 'log_media', 'server_time'));
        $this->assertSame('ROUND(log_visit.visitor_seconds_since_first / 86400)', DimensionCatalog::expressionOf('ROUND(log_visit.visitor_seconds_since_first / 86400)', 'log_visit', 'visitor_seconds_since_first'));
        $this->assertSame('', DimensionCatalog::expressionOf('log_visit.referer_name', 'log_visit', 'referer_name'));
        $this->assertSame('', DimensionCatalog::expressionOf('CONCAT(log_visit.a, log_action.name)', 'log_visit', 'a'));
        $this->assertSame('', DimensionCatalog::expressionOf('(SELECT 1)', 'log_visit', 'a'));
    }
}
