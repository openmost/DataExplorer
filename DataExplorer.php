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
    /**
     * Translations used by the Vue explorer, raw SQL editor and save dialog.
     */
    private const CLIENT_SIDE_TRANSLATION_KEYS = [
        'DataExplorer_ManageReports',
        'DataExplorer_ManageIntro',
        'DataExplorer_ManageEmpty',
        'DataExplorer_ManageNoMatch',
        'DataExplorer_ManageSearch',
        'DataExplorer_ManageColumnType',
        'DataExplorer_ManageColumnLocation',
        'DataExplorer_ManageColumnAuthor',
        'DataExplorer_ManageColumnUpdated',
        'DataExplorer_ManageSitesCount',
        'DataExplorer_ManageView',
        'DataExplorer_ManageEdit',
        'DataExplorer_ManageDelete',
        'DataExplorer_ManageDeleteConfirm',
        'DataExplorer_ManageDeleted',
        'DataExplorer_ManageAuthorDeleted',
        'DataExplorer_Export',
        'General_Id',
        'DataExplorer_EditReport',
        'General_Date',
        'General_Name',
        'General_Actions',
        'General_Yes',
        'General_No',
        'DataExplorer_SetupTitle',
        'DataExplorer_SetupIntro',
        'DataExplorer_SetupCreateUserTitle',
        'DataExplorer_SetupCreateUserHelp',
        'DataExplorer_SetupAnyHostHelp',
        'DataExplorer_SetupNoTables',
        'DataExplorer_SetupConfigTitle',
        'DataExplorer_SetupConfigHelp',
        'DataExplorer_DataExplorer',
        'DataExplorer_KpiPreviousPeriod',
        'DataExplorer_Variables',
        'DataExplorer_Dimensions',
        'DataExplorer_Metrics',
        'DataExplorer_Segments',
        'DataExplorer_AddDimensions',
        'DataExplorer_AddMetrics',
        'DataExplorer_AddSegments',
        'DataExplorer_NoDimensions',
        'DataExplorer_NoMetrics',
        'DataExplorer_NoSegments',
        'DataExplorer_DimensionUseHint',
        'DataExplorer_MetricUseHint',
        'DataExplorer_SegmentUseHint',
        'DataExplorer_RemoveVariable',
        'DataExplorer_PickerSearch',
        'DataExplorer_PickerNoMatch',
        'DataExplorer_SavedSegments',
        'DataExplorer_TabSettings',
        'DataExplorer_Visualization',
        'DataExplorer_Visualization_table',
        'DataExplorer_Visualization_bar',
        'DataExplorer_Visualization_pie',
        'DataExplorer_Visualization_line',
        'DataExplorer_Visualization_kpicard',
        'DataExplorer_SegmentComparison',
        'DataExplorer_Rows',
        'DataExplorer_Columns',
        'DataExplorer_Values',
        'DataExplorer_Filters',
        'DataExplorer_DropDimension',
        'DataExplorer_DropMetric',
        'DataExplorer_DropSegment',
        'DataExplorer_DropFilter',
        'DataExplorer_ZoneCount',
        'DataExplorer_ChipRemoveHint',
        'DataExplorer_RemoveFromZone',
        'DataExplorer_RowsNotUsedByVisualization',
        'DataExplorer_KpiFirstMetricOnly',
        'DataExplorer_PieFirstMetricOnly',
        'DataExplorer_PanelNoSegments',
        'DataExplorer_FilterOperatorLabel',
        'DataExplorer_FilterOperator_equals',
        'DataExplorer_FilterOperator_not_equals',
        'DataExplorer_FilterOperator_contains',
        'DataExplorer_FilterOperator_starts_with',
        'DataExplorer_FilterOperator_regex',
        'DataExplorer_FilterOperator_gt',
        'DataExplorer_FilterOperator_lt',
        'DataExplorer_FilterValueLabel',
        'DataExplorer_FilterValuePlaceholder',
        'DataExplorer_RemoveFilter',
        'DataExplorer_Canvas',
        'DataExplorer_Totals',
        'DataExplorer_HintAddMetric',
        'DataExplorer_HintAddRow',
        'DataExplorer_HintIncompatible',
        'DataExplorer_IncompatibleWithDimension',
        'DataExplorer_IncompatibleWithMetric',
        'DataExplorer_UnknownVariable',
        'DataExplorer_ExploreTab',
        'DataExplorer_SqlTab',
        'DataExplorer_OpenReport',
        'DataExplorer_NewExploration',
        'DataExplorer_SaveReport',
        'DataExplorer_ReportCreated',
        'DataExplorer_ReportUpdated',
        'DataExplorer_SaveDialogTitle',
        'DataExplorer_SaveDialogEditTitle',
        'DataExplorer_SaveName',
        'DataExplorer_SaveReportType',
        'DataExplorer_SaveReportTypeHelp',
        'DataExplorer_SavedAs',
        'DataExplorer_ReportType_table',
        'DataExplorer_ReportType_evolution',
        'DataExplorer_ReportType_kpicard',
        'DataExplorer_SavePlacement',
        'DataExplorer_PlacementThisSite',
        'DataExplorer_PlacementSites',
        'DataExplorer_PlacementAll',
        'DataExplorer_SaveSites',
        'DataExplorer_SaveSitesSearch',
        'SegmentEditor_DefaultAllVisits',
        'SegmentEditor_CustomSegment',
        'General_ChooseDate',
        'DataExplorer_SaveCategory',
        'DataExplorer_SaveSubcategory',
        'DataExplorer_SaveNewSubcategoryOption',
        'DataExplorer_SaveNewSubcategory',
        'DataExplorer_DefaultSubcategory',
        'DataExplorer_SaveOrder',
        'DataExplorer_SaveOrderHelp',
        'DataExplorer_SaveAsNew',
        'DataExplorer_SqlOpenSetup',
        'DataExplorer_SqlSchema',
        'DataExplorer_SqlSchemaHelp',
        'DataExplorer_SqlSchemaSearch',
        'DataExplorer_SqlInsertName',
        'DataExplorer_SqlInsertTable',
        'DataExplorer_SqlEditor',
        'DataExplorer_SqlEditorHelp',
        'DataExplorer_SqlChecking',
        'DataExplorer_SqlValid',
        'DataExplorer_SqlRun',
        'DataExplorer_SqlRunShortcut',
        'DataExplorer_SqlRunDisabled',
        'DataExplorer_SqlNoRows',
        'DataExplorer_SqlRowCount',
        'DataExplorer_SqlPlaceholder',
        'DataExplorer_Scope_visit',
        'DataExplorer_Scope_action',
        'DataExplorer_Scope_conversion',
        'DataExplorer_CellType',
        'DataExplorer_CellType_plain',
        'DataExplorer_CellType_bar',
        'DataExplorer_CellType_heatmap',
        'DataExplorer_PanelAddedCount',
        'DataExplorer_ItemRefused',
        'General_Save',
        'General_Cancel',
        'General_Close',
        'General_Others',
        'CoreHome_ThereIsNoDataForThisReport',
    ];

    public function registerEvents()
    {
        return [
            'Template.afterEventsReport' => 'renderOpenmostCommunicationAfterEvents',
            'Widget.filterWidgets' => 'addOpenmostCommunicationWidgets',
            'Template.beforeContent' => 'renderOpenmostCommunication',
            'Db.getTablesInstalled' => 'getTablesInstalled',
            'SitesManager.deleteSite.end' => 'onSiteDeleted',
            'UsersManager.deleteUser' => 'onUserDeleted',
            'Archiver.addRecordBuilders' => 'addRecordBuilders',
            'Report.addReports' => 'addReports',
            // after the other plugins, to see the subcategories they add
            'Category.addSubcategories' => ['function' => 'addSubcategories', 'after' => true],
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            // after ScheduledReports lists the reports
            'ScheduledReports.getReportMetadata' => ['function' => 'removeReportsWithoutKnownColumns', 'after' => true],
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

    public function onUserDeleted($login)
    {
        (new ReportsDao())->clearAuthor((string) $login);
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

        foreach ((new ReportsDao())->getForSiteCached($idSite) as $row) {
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
        foreach ((new ReportsDao())->getForSiteCached($idSite) as $row) {
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

    /**
     * A raw SQL report whose result columns are only known once it ran ("*", an expression without alias) would
     * be sent without columns, so it cannot be scheduled.
     */
    public function removeReportsWithoutKnownColumns(&$availableReportMetadata, $reportType = null, $idSite = null): void
    {
        if (!is_array($availableReportMetadata) || !$idSite) {
            return;
        }

        $dao = new ReportsDao();
        $availableReportMetadata = array_values(array_filter($availableReportMetadata, function ($metadata) use ($dao, $idSite) {
            if (($metadata['module'] ?? '') !== 'DataExplorer' || empty($metadata['parameters'][GetSavedReport::PARAMETER])) {
                return true;
            }

            $row = $dao->getCached((int) $metadata['parameters'][GetSavedReport::PARAMETER]);
            if ($row === null) {
                return false;
            }
            $report = new GetSavedReport();
            $report->initFromRow($row, (int) $idSite, false);

            return $report->hasKnownColumns();
        }));
    }

    public function getStylesheetFiles(array &$stylesheets): void
    {
        $stylesheets[] = 'plugins/DataExplorer/stylesheets/charts.less';
        $stylesheets[] = 'plugins/DataExplorer/stylesheets/explorer.less';
    }

    public function getClientSideTranslationKeys(array &$translationKeys): void
    {
        $translationKeys = array_merge($translationKeys, self::CLIENT_SIDE_TRANSLATION_KEYS);
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
