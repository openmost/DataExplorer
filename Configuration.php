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
    public const KEY_MAX_ROWS_DEFAULT = 'datatable_archiving_maximum_rows_dataexplorer_default';
    public const KEY_MAX_ROWS_DIMENSIONS = 'datatable_archiving_maximum_rows_dataexplorer_dimensions_';
    public const KEY_MAX_ROWS_SUBTABLE_DEFAULT = 'datatable_archiving_maximum_rows_subtable_dataexplorer_default';
    public const KEY_MAX_ROWS_SUBTABLE_DIMENSIONS = 'datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_';
    public const KEY_SQL_ROW_LIMIT = 'dataexplorer_sql_row_limit';
    public const KEY_MAX_DIMENSIONS = 'dataexplorer_max_dimensions';

    public const DEFAULT_MAX_DIMENSIONS = 3;
    public const MAX_DIMENSIONS_CEILING = 9;

    /** used when core [General] has no valid value either */
    public const FALLBACK_MAX_ROWS = 500;
    public const DEFAULT_MAX_ROWS_SUBTABLE = 100;
    public const FALLBACK_SQL_ROW_LIMIT = 50000;
    public const DEFAULT_RAW_SQL_ROW_LIMIT = 10000;
    public const DEFAULT_MAX_EXECUTION_TIME_SECONDS = 30;
    public const DEFAULT_RAW_SQL_CACHE_TTL = 300;
    public const DEFAULT_INVALIDATION_MONTHS = 6;

    /**
     * Maximum number of row dimensions of an exploration, 1 to 9.
     */
    public static function getMaxDimensions(): int
    {
        return min(self::MAX_DIMENSIONS_CEILING, self::getInt(self::KEY_MAX_DIMENSIONS, self::DEFAULT_MAX_DIMENSIONS, 1));
    }

    /**
     * Rows kept in the main table of a report with $dimensionCount row dimensions, the rest is summed into "Others".
     * Same caps for the live preview and for archiving, so a preview shows what the saved report will show.
     */
    public static function getMaxRowsInMainTable(int $dimensionCount): int
    {
        $default = self::getInt(self::KEY_MAX_ROWS_DEFAULT, self::getCoreInt('datatable_archiving_maximum_rows_standard', self::FALLBACK_MAX_ROWS), 1);

        return self::getInt(self::KEY_MAX_ROWS_DIMENSIONS . $dimensionCount, $default, 1);
    }

    /**
     * Rows kept in each subtable of a report with $dimensionCount row dimensions (2 or 3).
     */
    public static function getMaxRowsInSubTable(int $dimensionCount): int
    {
        $default = self::getInt(self::KEY_MAX_ROWS_SUBTABLE_DEFAULT, self::DEFAULT_MAX_ROWS_SUBTABLE, 1);

        return self::getInt(self::KEY_MAX_ROWS_SUBTABLE_DIMENSIONS . $dimensionCount, $default, 1);
    }

    /**
     * Hard cap on the rows a single SQL statement of the visual explorer may transfer.
     */
    public static function getSqlRowLimit(): int
    {
        return self::getInt(self::KEY_SQL_ROW_LIMIT, self::getCoreInt('archiving_ranking_query_row_limit', self::FALLBACK_SQL_ROW_LIMIT), 1);
    }

    /**
     * Writes the row caps to config.ini.php when absent, with the core values of the moment, so administrators
     * find them in the [DataExplorer] section.
     */
    public static function install(): void
    {
        $config = Config::getInstance();
        $section = self::getSection();

        $defaults = [
            self::KEY_MAX_ROWS_DEFAULT => self::getMaxRowsInMainTable(0),
            self::KEY_MAX_ROWS_SUBTABLE_DEFAULT => self::getMaxRowsInSubTable(0),
            self::KEY_SQL_ROW_LIMIT => self::getSqlRowLimit(),
        ];
        for ($i = 1; $i <= self::getMaxDimensions(); $i++) {
            $defaults[self::KEY_MAX_ROWS_DIMENSIONS . $i] = self::getMaxRowsInMainTable($i);
            if ($i > 1) {
                $defaults[self::KEY_MAX_ROWS_SUBTABLE_DIMENSIONS . $i] = self::getMaxRowsInSubTable($i);
            }
        }

        $changed = false;
        foreach ($defaults as $key => $value) {
            if (!isset($section[$key]) || $section[$key] === '') {
                $section[$key] = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $config->DataExplorer = $section;
            $config->forceSave();
        }
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

    private static function getCoreInt(string $key, int $default): int
    {
        $general = Config::getInstance()->General;
        $value = is_array($general) ? ($general[$key] ?? null) : null;

        return is_numeric($value) && (int) $value >= 1 ? (int) $value : $default;
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
