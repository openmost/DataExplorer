<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Catalog;

/**
 * Which metric can be read next to which dimension without a misleading number.
 *
 * - same scope: always
 * - action dimension: visit metrics only when they are distinct counts per row (visits, unique visitors)
 * - visit dimension: action and conversion metrics, joined on idvisit
 * - conversion dimension: conversion metrics only
 */
class ScopeCompatibility
{
    private const DISTINCT_VISIT_METRICS = ['nb_visits', 'nb_uniq_visitors'];

    public function isAllowed(string $dimensionScope, MetricEntry $metric): bool
    {
        if ($dimensionScope === $metric->scope) {
            return true;
        }

        if ($dimensionScope === 'action') {
            return $metric->scope === 'visit' && in_array($metric->id, self::DISTINCT_VISIT_METRICS, true);
        }

        if ($dimensionScope === 'visit') {
            return $metric->scope === 'action' || $metric->scope === 'conversion';
        }

        return false;
    }

    /**
     * @return string[] dimension scopes the metric can be combined with
     */
    public function allowedScopes(MetricEntry $metric): array
    {
        return array_values(array_filter(
            ['visit', 'action', 'conversion'],
            fn (string $scope) => $this->isAllowed($scope, $metric)
        ));
    }
}
