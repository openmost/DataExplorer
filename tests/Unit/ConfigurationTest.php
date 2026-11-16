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
use Piwik\Plugins\DataExplorer\Configuration;

/**
 * @group DataExplorer
 * @group ConfigurationTest
 * @group Plugins
 */
class ConfigurationTest extends TestCase
{
    private $originalSection;
    private $originalDatabase;
    private $originalGeneral;

    protected function setUp(): void
    {
        parent::setUp();
        $config = Config::getInstance();
        $this->originalSection = $config->DataExplorer;
        $this->originalDatabase = $config->database;
        $this->originalGeneral = $config->General;
        $config->DataExplorer = [];
    }

    protected function tearDown(): void
    {
        $config = Config::getInstance();
        $config->DataExplorer = $this->originalSection;
        $config->database = $this->originalDatabase;
        $config->General = $this->originalGeneral;
        parent::tearDown();
    }

    public function test_defaults(): void
    {
        $this->assertSame(10000, Configuration::getRawSqlRowLimit());
        $this->assertSame(30, Configuration::getMaxExecutionTimeSeconds());
        $this->assertSame(300, Configuration::getRawSqlCacheTtl());
        $this->assertSame(6, Configuration::getInvalidationMonths());
    }

    public function test_overridesInConfigWin(): void
    {
        Config::getInstance()->DataExplorer = [
            'raw_sql_row_limit' => '200',
            'max_execution_time_seconds' => 5,
            'raw_sql_cache_ttl' => 0,
            'invalidation_months' => 12,
        ];

        $this->assertSame(200, Configuration::getRawSqlRowLimit());
        $this->assertSame(5, Configuration::getMaxExecutionTimeSeconds());
        $this->assertSame(0, Configuration::getRawSqlCacheTtl());
        $this->assertSame(12, Configuration::getInvalidationMonths());
    }

    public function test_rawSqlInputLimits(): void
    {
        $this->assertSame(65536, Configuration::getSqlMaxLength());
        $this->assertSame(50, Configuration::getSqlMaxSubqueries());

        Config::getInstance()->DataExplorer = ['dataexplorer_sql_max_length' => '1000', 'dataexplorer_sql_max_subqueries' => 0];
        $this->assertSame(1000, Configuration::getSqlMaxLength());
        $this->assertSame(0, Configuration::getSqlMaxSubqueries());

        Config::getInstance()->DataExplorer = ['dataexplorer_sql_max_length' => 0, 'dataexplorer_sql_max_subqueries' => -1];
        $this->assertSame(65536, Configuration::getSqlMaxLength());
        $this->assertSame(50, Configuration::getSqlMaxSubqueries());
    }

    public function test_invalidOverrideFallsBackToDefault(): void
    {
        Config::getInstance()->DataExplorer = ['raw_sql_row_limit' => -4];

        $this->assertSame(10000, Configuration::getRawSqlRowLimit());
    }

    public function test_rowCaps_default_toCoreGeneralValues(): void
    {
        $config = Config::getInstance();
        $general = $config->General;
        $general['datatable_archiving_maximum_rows_standard'] = 321;
        $general['archiving_ranking_query_row_limit'] = 4321;
        $config->General = $general;

        $this->assertSame(321, Configuration::getMaxRowsInMainTable(1));
        $this->assertSame(321, Configuration::getMaxRowsInMainTable(3));
        $this->assertSame(100, Configuration::getMaxRowsInSubTable(2));
        $this->assertSame(4321, Configuration::getSqlRowLimit());
    }

    public function test_rowCaps_perDimensionOverrideWins_thenDefaultKey(): void
    {
        Config::getInstance()->DataExplorer = [
            'datatable_archiving_maximum_rows_dataexplorer_default' => 50,
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_2' => '20',
            'datatable_archiving_maximum_rows_subtable_dataexplorer_default' => 7,
            'datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_3' => 3,
            'dataexplorer_sql_row_limit' => 999,
        ];

        $this->assertSame(50, Configuration::getMaxRowsInMainTable(1));
        $this->assertSame(20, Configuration::getMaxRowsInMainTable(2));
        $this->assertSame(7, Configuration::getMaxRowsInSubTable(2));
        $this->assertSame(3, Configuration::getMaxRowsInSubTable(3));
        $this->assertSame(999, Configuration::getSqlRowLimit());
    }

    public function test_rowCaps_invalidValues_fallBack(): void
    {
        $general = Config::getInstance()->General;
        Config::getInstance()->DataExplorer = [
            'datatable_archiving_maximum_rows_dataexplorer_default' => 'abc',
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 0,
            'datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_2' => -1,
            'dataexplorer_sql_row_limit' => 'x',
        ];

        $this->assertSame((int) $general['datatable_archiving_maximum_rows_standard'], Configuration::getMaxRowsInMainTable(1));
        $this->assertSame(100, Configuration::getMaxRowsInSubTable(2));
        $this->assertSame((int) $general['archiving_ranking_query_row_limit'], Configuration::getSqlRowLimit());
    }

    public function test_maxDimensions(): void
    {
        $this->assertSame(3, Configuration::getMaxDimensions());

        foreach ([['5', 5], [1, 1], [9, 9], [12, 9], [0, 3], [-2, 3], ['abc', 3]] as [$value, $expected]) {
            Config::getInstance()->DataExplorer = ['dataexplorer_max_dimensions' => $value];
            $this->assertSame($expected, Configuration::getMaxDimensions(), var_export($value, true));
        }
    }

    public function test_install_writesOnlyAbsentKeys_upToMaxDimensions(): void
    {
        Config::getInstance()->DataExplorer = [
            'dataexplorer_max_dimensions' => 4,
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_2' => 42,
        ];

        Configuration::install();
        $section = Config::getInstance()->DataExplorer;

        $this->assertSame(42, $section['datatable_archiving_maximum_rows_dataexplorer_dimensions_2']);
        $this->assertSame(Configuration::getMaxRowsInMainTable(1), $section['datatable_archiving_maximum_rows_dataexplorer_dimensions_1']);
        $this->assertArrayHasKey('datatable_archiving_maximum_rows_dataexplorer_dimensions_4', $section);
        $this->assertArrayNotHasKey('datatable_archiving_maximum_rows_dataexplorer_dimensions_5', $section);
        $this->assertArrayHasKey('datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_4', $section);
        $this->assertArrayNotHasKey('datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_1', $section);
        $this->assertSame(100, $section['datatable_archiving_maximum_rows_subtable_dataexplorer_default']);
        $this->assertArrayHasKey('datatable_archiving_maximum_rows_dataexplorer_default', $section);
        $this->assertArrayHasKey('dataexplorer_sql_row_limit', $section);
    }

    public function test_readOnlyDbConfig_isNullWithoutUsername(): void
    {
        Config::getInstance()->DataExplorer = ['readonly_password' => 'x'];

        $this->assertNull(Configuration::getReadOnlyDbConfig());
    }

    public function test_readOnlyDbConfig_fallsBackToMainDatabaseSettings(): void
    {
        Config::getInstance()->database = [
            'host' => 'db.example', 'port' => '3307', 'dbname' => 'matomo', 'tables_prefix' => 'mt_',
            'username' => 'main', 'password' => 'secret',
        ];
        Config::getInstance()->DataExplorer = ['readonly_username' => 'ro', 'readonly_password' => 'ropass'];

        $this->assertSame([
            'host' => 'db.example',
            'port' => '3307',
            'username' => 'ro',
            'password' => 'ropass',
            'dbname' => 'matomo',
            'tables_prefix' => 'mt_',
            'unix_socket' => '',
            'charset' => '',
            'collation' => '',
            'adapter' => 'PDO\\MYSQL',
        ], Configuration::getReadOnlyDbConfig());
    }

    public function test_readOnlyDbConfig_carriesSocketCharsetAndAdapterOfTheMainDatabase(): void
    {
        Config::getInstance()->database = [
            'host' => 'localhost', 'port' => '3306', 'dbname' => 'matomo', 'tables_prefix' => '',
            'unix_socket' => '/tmp/mysql.sock', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_general_ci', 'adapter' => 'MYSQLI',
        ];
        Config::getInstance()->DataExplorer = ['readonly_username' => 'ro'];

        $config = Configuration::getReadOnlyDbConfig();
        $this->assertSame('/tmp/mysql.sock', $config['unix_socket']);
        $this->assertSame('utf8mb4', $config['charset']);
        $this->assertSame('utf8mb4_general_ci', $config['collation']);
        $this->assertSame('MYSQLI', $config['adapter']);
        $this->assertSame('3306', $config['port']);
    }

    public function test_readOnlyDbConfig_overridesOfSocketCharsetAndAdapterWin(): void
    {
        Config::getInstance()->database = [
            'host' => 'localhost', 'dbname' => 'matomo', 'unix_socket' => '/tmp/mysql.sock', 'charset' => 'utf8', 'adapter' => 'MYSQLI',
        ];
        Config::getInstance()->DataExplorer = [
            'readonly_username' => 'ro', 'readonly_unix_socket' => '/var/run/ro.sock', 'readonly_charset' => 'utf8mb4',
            'readonly_collation' => 'utf8mb4_bin', 'readonly_adapter' => 'PDO\\MYSQL',
        ];

        $config = Configuration::getReadOnlyDbConfig();
        $this->assertSame('/var/run/ro.sock', $config['unix_socket']);
        $this->assertSame('utf8mb4', $config['charset']);
        $this->assertSame('utf8mb4_bin', $config['collation']);
        $this->assertSame('PDO\\MYSQL', $config['adapter']);
        $this->assertSame('3306', $config['port']);
    }

    public function test_readOnlyDbConfig_anOtherHostDropsTheMainSocket(): void
    {
        Config::getInstance()->database = ['host' => 'localhost', 'dbname' => 'matomo', 'unix_socket' => '/tmp/mysql.sock'];
        Config::getInstance()->DataExplorer = ['readonly_username' => 'ro', 'readonly_host' => 'replica'];

        $this->assertSame('', Configuration::getReadOnlyDbConfig()['unix_socket']);
    }

    public function test_readOnlyDbConfig_explicitSettingsWin(): void
    {
        Config::getInstance()->database = ['host' => 'db.example', 'port' => '3306', 'dbname' => 'matomo', 'tables_prefix' => ''];
        Config::getInstance()->DataExplorer = [
            'readonly_username' => 'ro', 'readonly_host' => 'replica', 'readonly_port' => '3310', 'readonly_dbname' => 'copy',
        ];

        $config = Configuration::getReadOnlyDbConfig();
        $this->assertSame('replica', $config['host']);
        $this->assertSame('3310', $config['port']);
        $this->assertSame('copy', $config['dbname']);
        $this->assertSame('', $config['password']);
    }
}
