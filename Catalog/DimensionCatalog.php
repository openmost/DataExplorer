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
use Piwik\Columns\DimensionsProvider;

class DimensionCatalog
{
    public const SCOPE_BY_TABLE = [
        'log_visit' => 'visit',
        'log_link_visit_action' => 'action',
        'log_conversion' => 'conversion',
    ];

    /**
     * @return array<string, DimensionEntry> keyed by Dimension::getId()
     */
    public function all(int $idSite): array
    {
        $cache = Cache::getTransientCache();
        $cacheId = 'DataExplorer.dimensions.' . $idSite;
        if ($cache->contains($cacheId)) {
            return $cache->fetch($cacheId);
        }

        $entries = [];
        $all = SiteContext::run($idSite, fn () => (new DimensionsProvider())->getAllDimensions());
        foreach ($all as $dimension) {
            $table = (string) $dimension->getDbTableName();
            $column = (string) $dimension->getColumnName();
            if ($column === '' || !isset(self::SCOPE_BY_TABLE[$table])) {
                continue;
            }

            $id = $dimension->getId();
            $entries[$id] = new DimensionEntry(
                $id,
                (string) $dimension->getName(),
                (string) $dimension->getCategoryId(),
                self::SCOPE_BY_TABLE[$table],
                $table,
                $column,
                $dimension->getDbColumnJoin(),
                $dimension->getDbDiscriminator(),
                (string) $dimension->getSegmentName(),
                (string) $dimension->getType()
            );
        }

        $cache->save($cacheId, $entries);

        return $entries;
    }
}
