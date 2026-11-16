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

    public function test_milliseconds(): void
    {
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=1500', SessionLimits::forCurrentServerMilliseconds($this->db('8.0.36'), 1500));
        $this->assertSame('SET SESSION max_statement_time=1.500', SessionLimits::forCurrentServerMilliseconds($this->db('10.11.6-MariaDB'), 1500));
    }

    public function test_isTimeout_recognisesMysqlAndMariadbErrors(): void
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

        $this->assertTrue(SessionLimits::isTimeout($db, new \Exception('SQLSTATE[HY000]: General error: 3024 Query execution was interrupted, maximum statement execution time exceeded')));
        $this->assertTrue(SessionLimits::isTimeout($db, new \Exception('SQLSTATE[70100]: <<Unknown error>>: 1969 Query execution was interrupted (max_statement_time exceeded)')));
        $this->assertFalse(SessionLimits::isTimeout($db, new \Exception('SQLSTATE[42S22]: Column not found: 1054 Unknown column')));
    }

    public function test_restoreStatement_setsBackTheCurrentSessionValue(): void
    {
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=2500', SessionLimits::restoreStatement($this->db('8.0.36', ['SELECT @@SESSION.max_execution_time' => '2500'])));
        $this->assertSame('SET SESSION max_statement_time=1.500', SessionLimits::restoreStatement($this->db('10.11.6-MariaDB', ['SELECT @@SESSION.max_statement_time' => '1.500000'])));
        $this->assertSame('SET SESSION max_statement_time=0', SessionLimits::restoreStatement($this->db('10.11.6-MariaDB', ['SELECT @@SESSION.max_statement_time' => '0.000000'])));
    }

    public function test_restoreStatement_unreadableValueMeansNoLimit(): void
    {
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=0', SessionLimits::restoreStatement($this->db('8.0.36', ['SELECT @@SESSION.max_execution_time' => '1; DROP TABLE x'])));
        $this->assertSame('SET SESSION MAX_EXECUTION_TIME=0', SessionLimits::restoreStatement($this->db('8.0.37', ['SELECT @@SESSION.max_execution_time' => false])));
    }

    private function db(string $version, array $answers = []): AdapterInterface
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['fetchOne'])->getMock();
        $answers['SELECT VERSION()'] = $version;
        $db->method('fetchOne')->willReturnCallback(function (string $sql) use ($answers) {
            if (!array_key_exists($sql, $answers)) {
                throw new \LogicException('Unexpected query ' . $sql);
            }

            return $answers[$sql];
        });

        return $db;
    }
}
