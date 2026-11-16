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
use Piwik\Db\Adapter\Pdo\Mysql;
use Piwik\Db\AdapterInterface;
use Piwik\Plugins\DataExplorer\Sql\SessionLimits;

/**
 * @group DataExplorer
 * @group SessionLimitsTest
 * @group Plugins
 */
class SessionLimitsTest extends TestCase
{
    public function test_mysql_usesMaxExecutionTimeInMilliseconds(): void
    {
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=30000', SessionLimits::forCurrentServer($this->db('8.0.36'), 30));
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=0', SessionLimits::forCurrentServer($this->db('8.4.0-log'), 0));
    }

    public function test_mariadb_usesMaxStatementTimeInSeconds(): void
    {
        $this->assertSame('SET SESSION max_statement_time=30', SessionLimits::forCurrentServer($this->db('10.11.6-MariaDB-0+deb12u1'), 30));
        $this->assertSame('SET SESSION max_statement_time=0', SessionLimits::forCurrentServer($this->db('5.5.5-10.6.12-mariadb'), 0));
    }

    public function test_negativeSeconds_meanNoLimit(): void
    {
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=0', SessionLimits::forCurrentServer($this->db('8.0.36'), -5));
    }

    private function db(string $version): AdapterInterface
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['fetchOne'])->getMock();
        $db->method('fetchOne')->with('SELECT VERSION()')->willReturn($version);

        return $db;
    }
}
