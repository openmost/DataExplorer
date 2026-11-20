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
use Piwik\Columns\Dimension;
use Piwik\Columns\Discriminator;
use Piwik\Columns\Join;
use Piwik\Columns\MetricsList;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Plugin\Metric;
use Piwik\Plugin\ProcessedMetric;

class MetricCatalog
{
    public const ACTION_ALIAS_PREFIX = 'dataexplorer_atype_';

    private const AGGREGATION_BY_PATTERN = [
        ArchivedMetric::AGGREGATION_COUNT => 'sum',
        ArchivedMetric::AGGREGATION_COUNT_WITH_NUMERIC_VALUE => 'sum',
        ArchivedMetric::AGGREGATION_SUM => 'sum',
        ArchivedMetric::AGGREGATION_MAX => 'max',
        ArchivedMetric::AGGREGATION_MIN => 'min',
        ArchivedMetric::AGGREGATION_UNIQUE => 'uniq',
    ];

    /**
     * The metrics of core and of the active plugins, as Custom Reports lists them: archived metrics of the log tables
     * the explorer queries, and processed metrics (rates, averages) whose metrics it can all compute.
     *
     * @return array<string, MetricEntry> keyed by metric id
     */
    public function all(int $idSite): array
    {
        $cache = Cache::getTransientCache();
        $cacheId = 'DataExplorer.metrics.' . $idSite;
        if ($cache->contains($cacheId)) {
            return $cache->fetch($cacheId);
        }

        // the names are read in the site context too: goal metrics name their goal, which Goals loads for the
        // request site, one the user may not be allowed to view
        $entries = SiteContext::run($idSite, fn () => $this->build());

        $cache->save($cacheId, $entries);

        return $entries;
    }

    /**
     * @return array<string, MetricEntry>
     */
    private function build(): array
    {
        $entries = $this->coreCounters();
        $processed = [];
        foreach (MetricsList::get()->getMetrics() as $metric) {
            $name = (string) $metric->getName();
            if ($name === '' || isset($entries[$name])) {
                continue;
            }
            if ($metric instanceof ProcessedMetric) {
                $processed[$name] = $metric;
                continue;
            }
            if ($metric instanceof ArchivedMetric) {
                $entry = self::archivedEntry($metric);
                if ($entry !== null) {
                    $entries[$name] = $entry;
                }
            }
        }

        foreach ($processed as $name => $metric) {
            if (self::computable($name, $entries, $processed, [])) {
                $translated = $metric->getTranslatedName() ?: $name;
                $entries[$name] = new MetricEntry(
                    $name,
                    (string) $translated,
                    (string) $metric->getCategoryId(),
                    '',
                    '',
                    MetricEntry::PROCESSED,
                    true,
                    array_values(array_map('strval', (array) $metric->getDependentMetrics()))
                );
            }
        }

        return $entries;
    }

    /**
     * The core object of a processed metric, to compute and format it.
     */
    public function processedMetric(string $id, int $idSite): ?ProcessedMetric
    {
        $metric = SiteContext::run($idSite, fn () => MetricsList::get()->getMetric($id));

        return $metric instanceof ProcessedMetric ? $metric : null;
    }

    /**
     * An archived metric of one of the queried log tables. The discriminator of its dimension (an action type, a
     * goal) goes inside the aggregate, so the metric can share a query with the others: every column of its table it
     * reads only counts on the rows the discriminator keeps.
     */
    public static function archivedEntry(ArchivedMetric $metric): ?MetricEntry
    {
        $table = (string) $metric->getDbTableName();
        $scope = LogTables::get()->scopeOf($table);
        $aggregation = self::aggregationOf((string) $metric->getAggregation());
        $query = trim((string) $metric->getQuery());
        if ($scope === null || $aggregation === null || $query === '') {
            return null;
        }

        // besides its own table, only a table with one row per row of it (the visit of a conversion): the join
        // repeats nothing, so the metric still counts each row of its table once
        preg_match_all('/\b(log_[a-z_]+)\.`?[a-zA-Z0-9_]+`?/', $query, $tables);
        if (array_diff(array_unique($tables[1]), [$table], LogTables::get()->parentsOf($table))) {
            return null;
        }

        $dimension = $metric->getDimension();
        $discriminator = $dimension ? $dimension->getDbDiscriminator() : null;
        if ($discriminator instanceof Discriminator) {
            $condition = self::discriminatorCondition($discriminator, $table, $dimension);
            if ($condition === null) {
                return null;
            }
            $query = (string) preg_replace_callback(
                '/\b' . preg_quote($table, '/') . '\.`?([a-zA-Z0-9_]+)`?/',
                fn (array $match) => 'CASE WHEN ' . $condition . ' THEN ' . $table . '.' . $match[1] . ' END',
                $query
            );
        }

        $name = $metric->getTranslatedName() ?: $metric->getName();

        return new MetricEntry(
            (string) $metric->getName(),
            (string) $name,
            (string) $metric->getCategoryId(),
            $scope,
            $query,
            $aggregation,
            true
        );
    }

    /**
     * How the values of rows and periods combine, from the outer function of the aggregation: core patterns and the
     * custom ones of plugins (sum(case %s when 1 then 1 else 0 end), sum(ifnull(%s, 0)), ...).
     */
    public static function aggregationOf(string $pattern): ?string
    {
        if (isset(self::AGGREGATION_BY_PATTERN[$pattern])) {
            return self::AGGREGATION_BY_PATTERN[$pattern];
        }
        if (!preg_match('/^\s*(count|sum|max|min)\s*\(\s*(distinct\b)?/i', $pattern, $match)) {
            return null;
        }
        $function = strtolower($match[1]);
        if ($function === 'count') {
            return empty($match[2]) ? 'sum' : 'uniq';
        }

        return $function;
    }

    /**
     * The SQL condition of a discriminator, numeric values only like Custom Reports: a column of the metric table,
     * or the type of the action an action row points to.
     */
    private static function discriminatorCondition(Discriminator $discriminator, string $table, ?Dimension $dimension): ?string
    {
        $value = $discriminator->getValue();
        $column = (string) $discriminator->getColumn();
        if (!is_numeric($value) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return null;
        }
        $value = (int) $value;

        if ($discriminator->getTable() === $table) {
            return $table . '.' . $column . ' = ' . $value;
        }

        if ($discriminator->getTable() !== 'log_action') {
            return null;
        }

        // the action column of the dimension (an entry page of the visit, a page title...), else for an action row
        // its URL action, whose type tells a pageview, a download, an outlink or an event apart
        $join = $dimension ? $dimension->getDbColumnJoin() : null;
        if ($join instanceof Join && $join->getTable() === 'log_action' && $dimension) {
            $actionColumn = (string) $dimension->getColumnName();
        } elseif ($table === 'log_link_visit_action') {
            $actionColumn = 'idaction_url';
        } else {
            return null;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $actionColumn)) {
            return null;
        }

        return self::actionAlias($actionColumn) . '.' . $column . ' = ' . $value;
    }

    /**
     * The alias of the log_action row an action column points to: VisualQueryBuilder LEFT JOINs it once per query
     * for every metric reading it (one action per row, so no row is repeated).
     */
    public static function actionAlias(string $actionColumn): string
    {
        return self::ACTION_ALIAS_PREFIX . $actionColumn;
    }

    /**
     * Whether every metric a processed metric depends on is in the catalogue, or is itself computable.
     *
     * @param array<string, MetricEntry> $entries
     * @param array<string, ProcessedMetric> $processed
     * @param array<string, true> $seen
     */
    private static function computable(string $name, array $entries, array $processed, array $seen): bool
    {
        if (isset($seen[$name]) || !isset($processed[$name])) {
            return false;
        }
        $seen[$name] = true;
        $dependents = (array) $processed[$name]->getDependentMetrics();
        if (!$dependents) {
            return false;
        }
        foreach ($dependents as $dependent) {
            $dependent = (string) $dependent;
            if (!isset($entries[$dependent]) && !self::computable($dependent, $entries, $processed, $seen)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, MetricEntry>
     */
    private function coreCounters(): array
    {
        $category = 'General_Visitors';
        $defs = [
            ['nb_visits', 'General_ColumnNbVisits', 'visit', 'COUNT(DISTINCT log_visit.idvisit)', 'uniq', $category],
            ['nb_uniq_visitors', 'General_ColumnNbUniqVisitors', 'visit', 'COUNT(DISTINCT log_visit.idvisitor)', 'uniq', $category],
            ['nb_actions', 'General_ColumnNbActions', 'action', 'COUNT(log_link_visit_action.idlink_va)', 'sum', 'General_Actions'],
            ['nb_conversions', 'Goals_ColumnConversions', 'conversion', 'COUNT(log_conversion.idgoal)', 'sum', 'Goals_Goals'],
            ['revenue', 'General_ColumnRevenue', 'conversion', 'SUM(log_conversion.revenue)', 'sum', 'Goals_Goals'],
            ['sum_visit_length', 'General_ColumnSumVisitLength', 'visit', 'SUM(log_visit.visit_total_time)', 'sum', $category],
            ['bounce_count', 'General_ColumnBounces', 'visit', 'SUM(CASE WHEN log_visit.visit_total_actions = 1 THEN 1 ELSE 0 END)', 'sum', $category],
        ];

        // the columns of the Events reports, per event action: the visit level "Total events" counts every event of
        // the visits, so it is not the number of events of a row
        if (PluginManager::getInstance()->isPluginActivated('Events')) {
            $defs[] = ['nb_events', 'Events_Events', 'action', 'COUNT(log_link_visit_action.idaction_event_category)', 'sum', 'Events_Events'];
            $defs[] = ['sum_event_value', 'Events_EventValue', 'action', 'SUM(CASE WHEN log_link_visit_action.idaction_event_category IS NULL THEN 0 ELSE COALESCE(log_link_visit_action.custom_float, 0) END)', 'sum', 'Events_Events'];
        }

        $entries = [];
        foreach ($defs as [$id, $label, $scope, $sql, $aggregation, $cat]) {
            $entries[$id] = new MetricEntry($id, Piwik::translate($label), $cat, $scope, $sql, $aggregation, true);
        }

        return $entries;
    }
}
