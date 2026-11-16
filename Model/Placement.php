<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Model;

use Piwik\Piwik;

/**
 * Where a saved report is shown: every website, or an explicit list of sites.
 */
class Placement
{
    /** @var bool */
    private $all;

    /** @var int[] */
    private $siteIds;

    /**
     * @param int[] $siteIds
     */
    private function __construct(bool $all, array $siteIds)
    {
        $this->all = $all;
        $this->siteIds = $siteIds;
    }

    /**
     * @param array $raw {"all": true} or {"sites": [int, ...]}
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $raw): Placement
    {
        if (!empty($raw['all'])) {
            return new Placement(true, []);
        }

        $sites = $raw['sites'] ?? null;
        if (!is_array($sites)) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorPlacementInvalid'));
        }

        $ids = [];
        foreach ($sites as $idSite) {
            if (!is_int($idSite) && !(is_string($idSite) && ctype_digit($idSite))) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorPlacementInvalid'));
            }
            $idSite = (int) $idSite;
            if ($idSite < 1) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorPlacementInvalid'));
            }
            $ids[$idSite] = $idSite;
        }

        if (!$ids) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorPlacementInvalid'));
        }

        return new Placement(false, array_values($ids));
    }

    /**
     * Placement of a report that was left with no site (stored, shown nowhere).
     */
    public static function none(): Placement
    {
        return new Placement(false, []);
    }

    public function isAllWebsites(): bool
    {
        return $this->all;
    }

    /**
     * @return int[]
     */
    public function getSiteIds(): array
    {
        return $this->siteIds;
    }

    public function includes(int $idSite): bool
    {
        return $this->all || in_array($idSite, $this->siteIds, true);
    }
}
