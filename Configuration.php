<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer;

use Piwik\Config;

/**
 * Limits and read-only connection of the plugin, all overridable in the [DataExplorer] section of config.ini.php.
 */
class Configuration
{
    public const DEFAULT_PREVIEW_ROW_LIMIT = 1000;
    public const DEFAULT_RAW_SQL_ROW_LIMIT = 10000;
    public const DEFAULT_MAX_EXECUTION_TIME_SECONDS = 30;
    public const DEFAULT_RAW_SQL_CACHE_TTL = 300;
    public const DEFAULT_INVALIDATION_MONTHS = 6;

    public static function getPreviewRowLimit(): int
    {
        return self::getInt('preview_row_limit', self::DEFAULT_PREVIEW_ROW_LIMIT, 1);
    }

    public static function getRawSqlRowLimit(): int
    {
        return self::getInt('raw_sql_row_limit', self::DEFAULT_RAW_SQL_ROW_LIMIT, 1);
    }

    public static function getMaxExecutionTimeSeconds(): int
    {
        return self::getInt('max_execution_time_seconds', self::DEFAULT_MAX_EXECUTION_TIME_SECONDS, 1);
    }

    public static function getRawSqlCacheTtl(): int
    {
        // 0 is valid and disables the cache
        return self::getInt('raw_sql_cache_ttl', self::DEFAULT_RAW_SQL_CACHE_TTL, 0);
    }

    public static function getInvalidationMonths(): int
    {
        return self::getInt('invalidation_months', self::DEFAULT_INVALIDATION_MONTHS, 1);
    }

    /**
     * @return array{host: string, port: string, username: string, password: string, dbname: string, tables_prefix: string}|null
     *         null when no read-only user is configured
     */
    public static function getReadOnlyDbConfig(): ?array
    {
        $username = self::getString('readonly_username');
        if ($username === '') {
            return null;
        }

        $database = Config::getInstance()->database;
        $database = is_array($database) ? $database : [];

        return [
            'host' => self::getString('readonly_host') ?: (string) ($database['host'] ?? ''),
            'port' => self::getString('readonly_port') ?: (string) ($database['port'] ?? '3306'),
            'username' => $username,
            'password' => self::getString('readonly_password'),
            'dbname' => self::getString('readonly_dbname') ?: (string) ($database['dbname'] ?? ''),
            'tables_prefix' => self::getString('readonly_tables_prefix') ?: (string) ($database['tables_prefix'] ?? ''),
        ];
    }

    private static function getSection(): array
    {
        $section = Config::getInstance()->DataExplorer;

        return is_array($section) ? $section : [];
    }

    private static function getString(string $key): string
    {
        $value = self::getSection()[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function getInt(string $key, int $default, int $min): int
    {
        $value = self::getSection()[$key] ?? null;
        if (!is_numeric($value) || (int) $value < $min) {
            return $default;
        }

        return (int) $value;
    }
}
