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
use Piwik\Columns\MetricsList;
use Piwik\Piwik;
use Piwik\Plugin\ArchivedMetric;

class MetricCatalog
{
    private const AGGREGATION_BY_PATTERN = [
        ArchivedMetric::AGGREGATION_COUNT => 'sum',
        ArchivedMetric::AGGREGATION_COUNT_WITH_NUMERIC_VALUE => 'sum',
        ArchivedMetric::AGGREGATION_SUM => 'sum',
        ArchivedMetric::AGGREGATION_MAX => 'max',
        ArchivedMetric::AGGREGATION_MIN => 'min',
        ArchivedMetric::AGGREGATION_UNIQUE => 'uniq',
    ];

    /**
     * @return array<string, MetricEntry> keyed by metric id
     */
    public function all(int $idSite): array
    {
        $cache = Cache::getTransientCache();
        $cacheId = 'DataExplorer.metrics.' . $idSite;
        if ($cache->contains($cacheId)) {
            return $cache->fetch($cacheId);
        }

        $entries = $this->coreCounters();
        $dimensions = (new DimensionCatalog())->all($idSite);

        $archived = SiteContext::run($idSite, fn () => MetricsList::get()->getMetrics());
        foreach ($archived as $metric) {
            if (!$metric instanceof ArchivedMetric || isset($entries[$metric->getName()])) {
                continue;
            }

            $scope = DimensionCatalog::SCOPE_BY_TABLE[$metric->getDbTableName()] ?? null;
            $aggregation = self::AGGREGATION_BY_PATTERN[(string) $metric->getAggregation()] ?? null;
            $dimension = $metric->getDimension();
            if ($scope === null || $aggregation === null || !$dimension || !isset($dimensions[$dimension->getId()])) {
                continue;
            }

            $name = $metric->getTranslatedName() ?: $metric->getName();
            $entries[$metric->getName()] = new MetricEntry(
                $metric->getName(),
                (string) $name,
                (string) $metric->getCategoryId(),
                $scope,
                (string) $metric->getQuery(),
                $aggregation,
                true
            );
        }

        $cache->save($cacheId, $entries);

        return $entries;
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

        $entries = [];
        foreach ($defs as [$id, $label, $scope, $sql, $aggregation, $cat]) {
            $entries[$id] = new MetricEntry($id, Piwik::translate($label), $cat, $scope, $sql, $aggregation, true);
        }

        return $entries;
    }
}
