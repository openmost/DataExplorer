<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Access;

use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Model\Placement;

/**
 * Server-side access rules, see the role table of the design spec.
 */
class ReportPermissions
{
    public function checkCanExplore(int $idSite): void
    {
        Piwik::checkUserHasViewAccess($idSite);
    }

    public function checkCanRunRawSql(): void
    {
        Piwik::checkUserHasSuperUserAccess();
    }

    public function checkCanSave(Placement $placement, string $type): void
    {
        if ($type === 'sql' || $placement->isAllWebsites()) {
            Piwik::checkUserHasSuperUserAccess();
            return;
        }

        $siteIds = $placement->getSiteIds();
        if (!$siteIds) {
            throw new NoAccessException(Piwik::translate('DataExplorer_ErrorReportNotPlaced'));
        }

        Piwik::checkUserHasWriteAccess($siteIds);
    }

    /**
     * @param array $report row from ReportsDao::get()
     */
    public function checkCanEdit(array $report): void
    {
        if (Piwik::hasUserSuperUserAccess()) {
            return;
        }

        /** @var Placement $placement */
        $placement = $report['placement'];
        $siteIds = $placement->getSiteIds();
        if ($placement->isAllWebsites() || !$siteIds) {
            Piwik::checkUserHasSuperUserAccess();
            return;
        }

        $isAuthor = $report['created_by'] === Piwik::getCurrentUserLogin();
        if ($isAuthor && Piwik::isUserHasWriteAccess($siteIds)) {
            return;
        }

        if (Piwik::isUserHasAdminAccess($siteIds)) {
            return;
        }

        throw new NoAccessException(Piwik::translate('DataExplorer_ErrorEditNotAllowed'));
    }

    /**
     * @param array $report row from ReportsDao::get()
     */
    public function checkCanView(array $report, int $idSite): void
    {
        if (!empty($report['deleted'])) {
            throw new NoAccessException(Piwik::translate('DataExplorer_ErrorReportNotAvailable'));
        }

        /** @var Placement $placement */
        $placement = $report['placement'];
        if (!$placement->includes($idSite)) {
            throw new NoAccessException(Piwik::translate('DataExplorer_ErrorReportNotAvailable'));
        }

        Piwik::checkUserHasViewAccess($idSite);
    }
}
