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

    protected function setUp(): void
    {
        parent::setUp();
        $config = Config::getInstance();
        $this->originalSection = $config->DataExplorer;
        $this->originalDatabase = $config->database;
        $config->DataExplorer = [];
    }

    protected function tearDown(): void
    {
        $config = Config::getInstance();
        $config->DataExplorer = $this->originalSection;
        $config->database = $this->originalDatabase;
        parent::tearDown();
    }

    public function test_defaults(): void
    {
        $this->assertSame(1000, Configuration::getPreviewRowLimit());
        $this->assertSame(10000, Configuration::getRawSqlRowLimit());
        $this->assertSame(30, Configuration::getMaxExecutionTimeSeconds());
        $this->assertSame(300, Configuration::getRawSqlCacheTtl());
        $this->assertSame(6, Configuration::getInvalidationMonths());
    }

    public function test_overridesInConfigWin(): void
    {
        Config::getInstance()->DataExplorer = [
            'preview_row_limit' => 50,
            'raw_sql_row_limit' => '200',
            'max_execution_time_seconds' => 5,
            'raw_sql_cache_ttl' => 0,
            'invalidation_months' => 12,
        ];

        $this->assertSame(50, Configuration::getPreviewRowLimit());
        $this->assertSame(200, Configuration::getRawSqlRowLimit());
        $this->assertSame(5, Configuration::getMaxExecutionTimeSeconds());
        $this->assertSame(0, Configuration::getRawSqlCacheTtl());
        $this->assertSame(12, Configuration::getInvalidationMonths());
    }

    public function test_invalidOverrideFallsBackToDefault(): void
    {
        Config::getInstance()->DataExplorer = ['preview_row_limit' => 'abc', 'raw_sql_row_limit' => -4];

        $this->assertSame(1000, Configuration::getPreviewRowLimit());
        $this->assertSame(10000, Configuration::getRawSqlRowLimit());
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
        ], Configuration::getReadOnlyDbConfig());
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
