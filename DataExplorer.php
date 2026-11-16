<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer;

use Piwik\Access;
use Piwik\Category\Subcategory;
use Piwik\Common;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Plugin\Report;
use Piwik\Plugins\DataExplorer\RecordBuilders\SavedReport;
use Piwik\Plugins\DataExplorer\Reports\GetSavedReport;
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
            'Report.addReports' => 'addReports',
            // after the other plugins, to see the subcategories they add
            'Category.addSubcategories' => ['function' => 'addSubcategories', 'after' => true],
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
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
     * One SavedReport per visual report of every site: the event carries no site and core caches the list per
     * requested site, while one request can archive several sites. Each builder checks the archived site against
     * its placement in isEnabled().
     */
    public function addRecordBuilders(array &$recordBuilders): void
    {
        foreach ((new ReportsDao())->getAll() as $report) {
            if ($report['type'] === ReportDefinition::TYPE_VISUAL) {
                $recordBuilders[] = new SavedReport($report);
            }
        }
    }

    /**
     * One report per saved report placed on the requested site.
     *
     * @param Report[] $reports
     */
    public function addReports(array &$reports): void
    {
        $idSite = self::requestedSite();
        if ($idSite === null) {
            return;
        }

        foreach ((new ReportsDao())->getForSite($idSite) as $row) {
            $report = new GetSavedReport();
            $report->initFromRow($row, $idSite);
            $reports[] = $report;
        }
    }

    /**
     * Creates the subcategories chosen for saved reports that no plugin declares in their category.
     *
     * @param Subcategory[] $subcategories
     */
    public function addSubcategories(array &$subcategories): void
    {
        $idSite = self::requestedSite();
        if ($idSite === null) {
            return;
        }

        $existing = [];
        foreach ($subcategories as $subcategory) {
            $existing[$subcategory->getCategoryId() . '|' . $subcategory->getId()] = true;
        }
        foreach (PluginManager::getInstance()->findMultipleComponents('Categories', Subcategory::class) as $class) {
            $subcategory = StaticContainer::getContainer()->make($class);
            $existing[$subcategory->getCategoryId() . '|' . $subcategory->getId()] = true;
        }

        $order = 200;
        foreach ((new ReportsDao())->getForSite($idSite) as $row) {
            $key = $row['category'] . '|' . $row['subcategory'];
            if ($row['category'] === '' || $row['subcategory'] === '' || isset($existing[$key])) {
                continue;
            }
            $existing[$key] = true;

            $subcategory = new Subcategory();
            $subcategory->setCategoryId($row['category']);
            $subcategory->setId($row['subcategory']);
            $subcategory->setName($row['subcategory']);
            $subcategory->setOrder($order++);
            $subcategories[] = $subcategory;
        }
    }

    public function getStylesheetFiles(array &$stylesheets): void
    {
        $stylesheets[] = 'plugins/DataExplorer/stylesheets/charts.less';
    }

    /**
     * The site of the request, idSites for API.getReportMetadata, like core reports registered per site.
     */
    private static function requestedSite(): ?int
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');
        if ($idSite > 0) {
            return $idSite;
        }

        $idSites = Common::getRequestVar('idSites', '', null);
        if (is_array($idSites) && count($idSites) === 1) {
            $idSites = reset($idSites);
        }
        if (is_scalar($idSites) && ctype_digit((string) $idSites) && (int) $idSites > 0) {
            return (int) $idSites;
        }

        return null;
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
