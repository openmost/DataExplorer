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
use Piwik\Columns\DimensionsProvider;
use Piwik\Columns\Join;
use Piwik\Piwik;

class DimensionCatalog
{
    /** @deprecated read LogTables, kept for the callers of the historical scopes */
    public const SCOPE_BY_TABLE = LogTables::SCOPE_BY_TABLE;

    /**
     * Internal row id columns of log_link_visit_action, both labelled "Actions" by core and
     * meaningless as a grouping: the metrics derived from them (nb_actions, ...) stay available.
     */
    public const TECHNICAL_DIMENSION_IDS = [
        'CoreHome.LinkVisitActionId',
        'CoreHome.LinkVisitActionIdPages',
    ];

    private const ACTION_TABLE = 'log_action';

    /**
     * The dimensions of core and of the active plugins on every log table the explorer can query. A dimension
     * without a table column (the dimensions of a computed report, a funnel step) cannot be queried and is left out,
     * as Custom Reports does.
     *
     * @return array<string, DimensionEntry> keyed by Dimension::getId()
     */
    public function all(int $idSite): array
    {
        $cache = Cache::getTransientCache();
        $cacheId = 'DataExplorer.dimensions.' . $idSite;
        if ($cache->contains($cacheId)) {
            return $cache->fetch($cacheId);
        }

        $tables = LogTables::get();
        $entries = [];
        $all = SiteContext::run($idSite, fn () => (new DimensionsProvider())->getAllDimensions());
        foreach ($all as $dimension) {
            $entry = self::entryOf($dimension, $tables);
            if ($entry !== null && !in_array($entry->id, self::TECHNICAL_DIMENSION_IDS, true)) {
                $entries[$entry->id] = $entry;
            }
        }

        $cache->save($cacheId, $entries);

        return $entries;
    }

    public static function entryOf(Dimension $dimension, LogTables $tables): ?DimensionEntry
    {
        $table = (string) $dimension->getDbTableName();
        $column = (string) $dimension->getColumnName();
        if ($column === '' || $table === '') {
            return null;
        }
        $join = $dimension->getDbColumnJoin();

        // a column of the actions themselves (the action type) is read through the URL action of each action row
        if ($table === self::ACTION_TABLE && $join === null) {
            $join = new Join(self::ACTION_TABLE, 'idaction', $column);
            $table = 'log_link_visit_action';
            $column = 'idaction_url';
        }

        $scope = $tables->scopeOf($table);
        if ($scope === null) {
            return null;
        }

        // some plugins leave a technical dimension unnamed, or name it with a translation key
        $name = trim((string) $dimension->getName());
        if (preg_match('/^[A-Za-z0-9]+_[A-Za-z0-9_]+$/', $name)) {
            $name = Piwik::translate($name);
        }
        if ($name === '') {
            return null;
        }

        return new DimensionEntry(
            $dimension->getId(),
            $name,
            (string) $dimension->getCategoryId(),
            $scope,
            $table,
            $column,
            $join,
            $dimension->getDbDiscriminator(),
            (string) $dimension->getSegmentName(),
            (string) $dimension->getType(),
            $join === null ? self::expressionOf((string) $dimension->getSqlSegment(), $table, $column) : ''
        );
    }

    /**
     * The SQL segment of a dimension when it computes a value from its own column only (HOUR(...), a number of days
     * from seconds): a function of that column and of numbers, never another column or table.
     */
    public static function expressionOf(string $sqlSegment, string $table, string $column): string
    {
        $sqlSegment = trim($sqlSegment);
        if ($sqlSegment === '' || $sqlSegment === $table . '.' . $column) {
            return '';
        }
        $rest = str_replace($table . '.' . $column, '', $sqlSegment, $count);
        if ($count === 0 || !preg_match('/^[A-Za-z_(),\s0-9.\/*+-]*$/', $rest) || preg_match('/[a-z_]+\.[a-z_]/i', $rest)) {
            return '';
        }

        return $sqlSegment;
    }
}
