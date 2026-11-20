<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Catalog;

use Piwik\Cache;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Plugin\LogTablesProvider;
use Piwik\Tracker\LogTable;

/**
 * The log tables of core and of the active plugins the explorer can query: every table core can join to a visit,
 * directly on idvisit or through other log tables (log_crash through log_crash_event). Visits, actions and
 * conversions keep their historical scope names, any other table is its own scope, named after the table.
 *
 * A table is a parent of another when each row of the other has one row of it: the join column is its whole primary
 * key (a visit for an action, a crash for a crash event). A dimension of a parent never repeats the rows of a metric,
 * a dimension of any other table does.
 */
final class LogTables
{
    public const SCOPE_BY_TABLE = [
        'log_visit' => 'visit',
        'log_link_visit_action' => 'action',
        'log_conversion' => 'conversion',
    ];

    private const DATETIME_COLUMN = [
        'log_visit' => 'visit_last_action_time',
        'log_link_visit_action' => 'server_time',
        'log_conversion' => 'server_time',
    ];

    private const GRAIN_COLUMNS = [
        'log_visit' => ['idvisit'],
        'log_link_visit_action' => ['idlink_va'],
        'log_conversion' => ['idvisit', 'idgoal', 'buster'],
    ];

    // read through the action columns that point to it, never queried as a table of its own
    private const ACTION_TABLE = 'log_action';

    private const IDENTIFIER = '/^[a-zA-Z0-9_]+$/';

    /** @var array<string, array{grain: string[], unique: bool, datetime: string, visitColumn: string, ways: array<string, string>, edges: array<string, string>}> */
    private $tables;

    private function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    public static function get(): LogTables
    {
        $cache = Cache::getTransientCache();
        $cacheId = 'DataExplorer.logTables';
        if (!$cache->contains($cacheId)) {
            // DbHelper::getTablesInstalled() only knows the tables core and some plugins declare
            $installed = [];
            foreach (Db::fetchAll('SHOW TABLES LIKE ?', [str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], Common::prefixTable('')) . '%']) as $row) {
                $installed[(string) current($row)] = true;
            }
            $cache->save($cacheId, self::fromLogTables(
                StaticContainer::get(LogTablesProvider::class)->getAllLogTables(),
                fn (string $table) => isset($installed[Common::prefixTable($table)])
            ));
        }

        return $cache->fetch($cacheId);
    }

    /**
     * @param LogTable[] $logTables
     * @param callable(string): bool|null $exists whether the table is in the database (a plugin can be active before
     *        its tables are created)
     */
    public static function fromLogTables(array $logTables, ?callable $exists = null): LogTables
    {
        $tables = [];
        foreach ($logTables as $logTable) {
            $name = (string) $logTable->getName();
            if ($name === self::ACTION_TABLE || !preg_match(self::IDENTIFIER, $name) || ($exists !== null && !$exists($name))) {
                continue;
            }
            $declared = self::GRAIN_COLUMNS[$name] ?? array_values(array_filter((array) $logTable->getPrimaryKey(), 'is_string'));
            $grain = $declared;
            if (!$grain && (string) $logTable->getIdColumn() !== '') {
                $grain = [(string) $logTable->getIdColumn()];
            }
            $ways = [];
            foreach ((array) $logTable->getWaysToJoinToOtherLogTables() as $other => $column) {
                $ways[(string) $other] = (string) $column;
            }
            $tables[$name] = [
                'grain' => $grain,
                // without a declared primary key the id column may repeat (one A/B test row per experiment of a
                // visit): such a table is never taken as having one row per row of another
                'unique' => (bool) $declared,
                'datetime' => self::DATETIME_COLUMN[$name] ?? (string) $logTable->getDateTimeColumn(),
                'visitColumn' => (string) $logTable->getColumnToJoinOnIdVisit(),
                'ways' => $ways,
            ];
        }

        // the columns two tables join on: the visit for tables that both have one, else a declared way
        foreach ($tables as $name => &$table) {
            $table['edges'] = [];
            foreach ($tables as $other => $otherTable) {
                if ($other === $name) {
                    continue;
                }
                if ($table['visitColumn'] !== '' && $otherTable['visitColumn'] !== '') {
                    $table['edges'][$other] = $table['visitColumn'];
                } elseif (isset($table['ways'][$other])) {
                    $table['edges'][$other] = $table['ways'][$other];
                } elseif (isset($otherTable['ways'][$name])) {
                    $table['edges'][$other] = $otherTable['ways'][$name];
                }
            }
        }
        unset($table);

        $joinable = self::joinableOnVisit($tables);
        foreach (array_keys($tables) as $name) {
            if (!isset($joinable[$name]) || !$tables[$name]['grain']) {
                unset($tables[$name]);
            }
        }
        foreach ($tables as &$table) {
            $table['edges'] = array_intersect_key($table['edges'], $tables);
            foreach (array_merge($table['grain'], array_values($table['edges']), [$table['datetime']]) as $column) {
                if ($column !== '' && !preg_match(self::IDENTIFIER, $column)) {
                    $table['grain'] = [];
                }
            }
        }
        unset($table);

        return new LogTables(array_filter($tables, fn (array $table) => (bool) $table['grain']));
    }

    /**
     * @return array<string, true>
     */
    private static function joinableOnVisit(array $tables): array
    {
        $reached = [];
        $queue = [];
        foreach ($tables as $name => $table) {
            if ($table['visitColumn'] !== '') {
                $reached[$name] = true;
                $queue[] = $name;
            }
        }
        while ($queue) {
            $name = array_shift($queue);
            foreach (array_keys($tables[$name]['edges']) as $other) {
                if (!isset($reached[$other])) {
                    $reached[$other] = true;
                    $queue[] = $other;
                }
            }
        }

        return $reached;
    }

    public function has(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    public function scopeOf(string $table): ?string
    {
        if (!isset($this->tables[$table])) {
            return null;
        }

        return self::SCOPE_BY_TABLE[$table] ?? $table;
    }

    public function tableOf(string $scope): ?string
    {
        $table = array_search($scope, self::SCOPE_BY_TABLE, true);
        if ($table === false) {
            $table = $scope;
        }

        return isset($this->tables[$table]) ? $table : null;
    }

    /**
     * @return string[] scopes of every queryable table
     */
    public function scopes(): array
    {
        return array_map(fn (string $table) => (string) $this->scopeOf($table), array_keys($this->tables));
    }

    /**
     * @return string[] the columns identifying one row of the table
     */
    public function grain(string $table): array
    {
        return $this->tables[$table]['grain'] ?? [];
    }

    /**
     * The column the period applies to, '' when the table has none (its rows are dated through their visit).
     */
    public function datetimeColumn(string $table): string
    {
        return $this->tables[$table]['datetime'] ?? '';
    }

    public function joinsOnVisit(string $table): bool
    {
        return ($this->tables[$table]['visitColumn'] ?? '') !== '';
    }

    /**
     * The table a query reads the period and the sites from when $table has no datetime column: its visit, else the
     * first related table that has one (a crash is dated by its crash events).
     */
    public function datedTable(string $table): string
    {
        if ($this->datetimeColumn($table) !== '') {
            return $table;
        }
        if ($this->joinsOnVisit($table)) {
            return 'log_visit';
        }
        foreach (array_keys($this->tables[$table]['edges'] ?? []) as $other) {
            if ($this->datetimeColumn($other) !== '') {
                return $other;
            }
        }

        return 'log_visit';
    }

    /**
     * The tables in an order core can join them in: each one after a table it joins to, from the first one on
     * (log_visit can only follow log_crash once log_crash_event is there). Tables this registry does not know keep
     * their place at the end.
     *
     * @param string[] $tables
     * @return string[]
     */
    public function joinOrder(array $tables): array
    {
        $tables = array_values(array_unique($tables));
        if (count($tables) < 2) {
            return $tables;
        }
        $ordered = [array_shift($tables)];
        while ($tables) {
            $next = null;
            foreach ($tables as $index => $table) {
                foreach ($ordered as $placed) {
                    if (isset($this->tables[$placed]['edges'][$table])) {
                        $next = $index;
                        break 2;
                    }
                }
            }
            $ordered[] = $tables[$next ?? array_key_first($tables)];
            unset($tables[$next ?? array_key_first($tables)]);
        }

        return $ordered;
    }

    /**
     * @return string[] tables having one row for each row of $table, the table itself excluded
     */
    public function parentsOf(string $table): array
    {
        $parents = [];
        $queue = [$table];
        while ($queue) {
            $current = array_shift($queue);
            foreach ($this->tables[$current]['edges'] ?? [] as $other => $column) {
                if ($other !== $table && !isset($parents[$other]) && $this->tables[$other]['unique'] && $this->grain($other) === [$column]) {
                    $parents[$other] = true;
                    $queue[] = $other;
                }
            }
        }

        return array_keys($parents);
    }

    /**
     * Whether a dimension of $dimensionTable gives a row of $metricTable several rows.
     */
    public function repeats(string $metricTable, string $dimensionTable): bool
    {
        return $dimensionTable !== $metricTable && !in_array($dimensionTable, $this->parentsOf($metricTable), true);
    }

    /**
     * @return string[] scopes whose dimensions repeat the rows of the metric table of $scope
     */
    public function repeatingScopes(string $scope): array
    {
        $table = $this->tableOf($scope);
        if ($table === null) {
            return [];
        }

        return array_values(array_map(
            fn (string $other) => (string) $this->scopeOf($other),
            array_filter(array_keys($this->tables), fn (string $other) => $this->repeats($table, $other))
        ));
    }
}
