<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer;

use Piwik\Access;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\RecordBuilders\SavedReport;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Site;

class DataExplorer extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'Template.afterEventsReport' => 'renderOpenmostCommunicationAfterEvents',
            'Widget.filterWidgets' => 'addOpenmostCommunicationWidgets',
            'Template.beforeContent' => 'renderOpenmostCommunication',
            'Db.getTablesInstalled' => 'getTablesInstalled',
            'SitesManager.deleteSite.end' => 'onSiteDeleted',
            'Archiver.addRecordBuilders' => 'addRecordBuilders',
        ];
    }

    public function install()
    {
        ReportsDao::install();
        Configuration::install();
    }

    public function uninstall()
    {
        ReportsDao::uninstall();
    }

    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled = array_merge($allTablesInstalled, ReportsDao::getTableNames());
    }

    public function onSiteDeleted($idSite)
    {
        (new ReportsDao())->removeSite((int) $idSite);
    }

    /**
     * One SavedReport per visual report. The event carries no site: the reports of the requested site when the
     * request names one (core caches the builders per requested site), else every report; each builder checks
     * the archived site against its placement in isEnabled().
     */
    public function addRecordBuilders(array &$recordBuilders): void
    {
        $idSite = Common::getRequestVar('idSite', '', 'string');
        $dao = new ReportsDao();
        $reports = ctype_digit($idSite) ? $dao->getForSite((int) $idSite) : $dao->getAll();

        foreach ($reports as $report) {
            if ($report['type'] === ReportDefinition::TYPE_VISUAL) {
                $recordBuilders[] = new SavedReport($report);
            }
        }
    }

    /**
     * Invalidates the DataExplorer archives of every placed site over the last invalidation_months months, so the
     * next core:archive run builds the report (new revision, new record name) over that history.
     *
     * @param array $report a hydrated row of ReportsDao
     */
    public function invalidateHistory(array $report): void
    {
        $placement = $report['placement'] ?? null;
        if (($report['type'] ?? '') !== ReportDefinition::TYPE_VISUAL || !$placement instanceof Placement) {
            return;
        }

        $siteIds = $placement->isAllWebsites()
            ? Access::doAsSuperUser(fn () => SitesManagerAPI::getInstance()->getAllSitesId())
            : $placement->getSiteIds();

        $sitesByToday = [];
        foreach ($siteIds as $idSite) {
            $today = Date::factoryInTimezone('today', Site::getTimezoneFor($idSite))->toString();
            $sitesByToday[$today][] = (int) $idSite;
        }

        $invalidator = StaticContainer::get(ArchiveInvalidator::class);
        $months = Configuration::getInvalidationMonths();
        foreach ($sitesByToday as $today => $ids) {
            $end = Date::factory($today);
            $dates = [];
            for ($day = $end->subPeriod($months, 'month'); !$day->isLater($end); $day = $day->addDay(1)) {
                $dates[] = $day;
            }

            $invalidator->markArchivesAsInvalidated($ids, $dates, 'day', null, true, false, $this->getPluginName());
        }
    }

    public function renderOpenmostCommunication(&$out, $layout, $module = '', $action = '')
    {
        OpenmostCommunication::beforeContent($out, (string) $layout, (string) $module, (string) $action, $this->getPluginName());
    }

    public function addOpenmostCommunicationWidgets($list)
    {
        OpenmostCommunication::filterWidgets($list, $this->getPluginName());
    }

    public function renderOpenmostCommunicationAfterEvents(&$out, $dataTable = null)
    {
        OpenmostCommunication::afterEventsReport($out, $this->getPluginName());
    }
}
