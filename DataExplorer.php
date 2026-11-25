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
        'DataExplorer_SaveAsCustomReport',
        'DataExplorer_CustomReportHelp',
        'DataExplorer_CustomReportUnsupported',
        'DataExplorer_CustomReportCreated',
        'General_Save',
        'DataExplorer_ExportCsv',
        'General_Search',
        'DataExplorer_EditReport',
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
        'DataExplorer_Visualization_treemap',
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
        'DataExplorer_TreemapFirstMetricOnly',
        'DataExplorer_PanelNoSegments',
        'DataExplorer_FilterOperatorLabel',
        'DataExplorer_FilterOperator_equals',
        'DataExplorer_FilterOperator_not_equals',
        'DataExplorer_FilterOperator_contains',
        'DataExplorer_FilterOperator_starts_with',
        'DataExplorer_FilterOperator_ends_with',
        'DataExplorer_TableView',
        'DataExplorer_TableViewFlat',
        'DataExplorer_TableViewHierarchical',
        'DataExplorer_FilterConditions',
        'DataExplorer_FilterApply',
        'DataExplorer_FilterEdit',
        'General_Cancel',
        'DataExplorer_FilterOperator_regex',
        'DataExplorer_FilterOperator_gt',
        'DataExplorer_FilterOperator_lt',
        'DataExplorer_FilterOperator_not_contains',
        'DataExplorer_FilterOperator_not_starts_with',
        'DataExplorer_FilterOperator_not_ends_with',
        'DataExplorer_FilterOperator_gte',
        'DataExplorer_FilterOperator_lte',
        'DataExplorer_FilterAnd',
        'DataExplorer_FilterOr',
        'DataExplorer_FilterAddOr',
        'DataExplorer_FilterAddOrLabel',
        'DataExplorer_FilterGroupLabel',
        'DataExplorer_FilterGroupAnyLabel',
        'DataExplorer_CustomReportsGroup',
        'DataExplorer_ExplorationsGroup',
        'DataExplorer_Modified',
        'DataExplorer_LeaveUnsavedConfirm',
        'DataExplorer_OpenCustomReport',
        'DataExplorer_CustomReportNotOpenedHint',
        'DataExplorer_FilterAddAnd',
        'DataExplorer_FilterAddAndLabel',
        'DataExplorer_FilterDropNewGroup',
        'General_LastDays',
        'General_LastDaysShort',
        'DataExplorer_UpdateCustomReport',
        'DataExplorer_CustomReportUpdateHelp',
        'DataExplorer_CustomReportUpdated',
        'DataExplorer_SaveDescription',
        'DataExplorer_SaveDescriptionHelp',
        'DataExplorer_ReportPickerSearch',
        'DataExplorer_CustomReportOpened',
        'DataExplorer_CustomReportOpenedReadOnly',
        'DataExplorer_SqlKpiHint',
        'DataExplorer_FilterValueLabel',
        'DataExplorer_FilterShowValues',
        'DataExplorer_FilterValuePlaceholder',
        'DataExplorer_RemoveFilter',
        'DataExplorer_Canvas',
        'DataExplorer_HintAddMetric',
        'DataExplorer_HintAddRow',
        'DataExplorer_HintIncompatible',
        'DataExplorer_MetricPerVisitHint',
        'DataExplorer_MetricPerActionHint',
        'DataExplorer_MetricPerConversionHint',
        'DataExplorer_MetricPerRowHint',
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
        'General_Remove',
        'General_SearchNoResults',
        'SegmentEditor_DefaultAllVisits',
        'SegmentEditor_CustomSegment',
        'General_ChooseDate',
        'DataExplorer_SaveCategory',
        'DataExplorer_SaveSubcategory',
        'DataExplorer_SaveNewSubcategoryOption',
        'DataExplorer_SaveNewSubcategory',
        'DataExplorer_SaveOwnPageOption',
        'DataExplorer_SaveOrder',
        'DataExplorer_SaveOrderHelp',
        'DataExplorer_SaveProcessSegments',
        'DataExplorer_SaveProcessSegmentsHelp',
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
        'DataExplorer_PanelAddedCount',
        'DataExplorer_ItemRefused',
        'General_Save',
        'General_Cancel',
        'General_Close',
        'DataExplorer_ReportType_funnel',
        'DataExplorer_FunnelTab',
        'DataExplorer_FunnelCanvas',
        'DataExplorer_FunnelHint',
        'DataExplorer_FunnelType',
        'DataExplorer_FunnelClosed',
        'DataExplorer_FunnelOpen',
        'DataExplorer_FunnelClosedHelp',
        'DataExplorer_FunnelOpenHelp',
        'DataExplorer_FunnelSteps',
        'DataExplorer_FunnelEditSteps',
        'DataExplorer_FunnelOptional',
        'DataExplorer_FunnelNoCondition',
        'DataExplorer_FunnelBreakdown',
        'DataExplorer_FunnelBreakdownRows',
        'DataExplorer_FunnelStepDefaultName',
        'DataExplorer_FunnelFollowLabel',
        'DataExplorer_FunnelFollowIndirect',
        'DataExplorer_FunnelFollowDirect',
        'DataExplorer_FunnelWithin',
        'DataExplorer_FunnelWithinValue',
        'DataExplorer_FunnelWithinUnit',
        'DataExplorer_FunnelUnit_seconds',
        'DataExplorer_FunnelUnit_minutes',
        'DataExplorer_FunnelUnit_hours',
        'DataExplorer_FunnelUnit_days',
        'DataExplorer_FunnelStepName',
        'DataExplorer_FunnelStepActions',
        'DataExplorer_FunnelCopyStep',
        'DataExplorer_FunnelAddStepAbove',
        'DataExplorer_FunnelAddStepBelow',
        'DataExplorer_FunnelDeleteStep',
        'DataExplorer_FunnelAddCondition',
        'DataExplorer_FunnelAddConditionLabel',
        'DataExplorer_FunnelAddStep',
        'DataExplorer_FunnelStepNameRequired',
        'DataExplorer_FunnelWithinInvalid',
        'DataExplorer_FunnelStepCopyName',
        'DataExplorer_FunnelPassesStarted',
        'DataExplorer_FunnelPassesCompleted',
        'DataExplorer_FunnelConversionRate',
        'DataExplorer_FunnelAbandonedShort',
        'DataExplorer_FunnelStep',
        'DataExplorer_FunnelEntered',
        'DataExplorer_FunnelStepRate',
        'DataExplorer_FunnelAbandoned',
        'DataExplorer_FunnelAbandonRate',
        'DataExplorer_FunnelOverallRate',
        'DataExplorer_FunnelAvgTime',
        'DataExplorer_ErrorFunnelDimension',
        'DataExplorer_ErrorFunnelBreakdown',
        'CoreHome_ThereIsNoDataForThisReport',
        'Intl_NSecondsShort',
        'Intl_NMinutesShort',
        'Intl_NHoursShort',
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
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
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
     * One SavedReport per visual report and funnel of every site: the event carries no site and core caches the
     * list per requested site, while one request can archive several sites. Each builder checks the archived site
     * against its placement in isEnabled().
     */
    public function addRecordBuilders(array &$recordBuilders): void
    {
        foreach ((new ReportsDao())->getAll() as $report) {
            if (in_array($report['type'], [ReportDefinition::TYPE_VISUAL, ReportDefinition::TYPE_FUNNEL], true)) {
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
            $id = GetSavedReport::pageId($row);
            $key = $row['category'] . '|' . $id;
            if ($row['category'] === '' || isset($existing[$key])) {
                continue;
            }
            $existing[$key] = true;

            $subcategory = new Subcategory();
            $subcategory->setCategoryId($row['category']);
            $subcategory->setId($id);
            // a page of its own is named after its report
            $subcategory->setName($row['subcategory'] !== '' ? $row['subcategory'] : $row['name']);
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
        $stylesheets[] = 'plugins/DataExplorer/stylesheets/explorer.less';
    }

    public function getJsFiles(array &$jsFiles): void
    {
        $jsFiles[] = 'plugins/DataExplorer/javascripts/treemapColors.js';
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
        if (!in_array($report['type'] ?? '', [ReportDefinition::TYPE_VISUAL, ReportDefinition::TYPE_FUNNEL], true) || !$placement instanceof Placement) {
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
