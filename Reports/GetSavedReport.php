<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Reports;

use Piwik\Common;
use Piwik\Columns\DimensionsProvider;
use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Request;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\ViewDataTable\Manager as ViewDataTableManager;
use Piwik\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Piwik\Plugins\DataExplorer\Catalog\SiteContext;
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\LabelFormatter;
use Piwik\Plugins\DataExplorer\Query\SavedReportReader;
use Piwik\Plugins\DataExplorer\Query\VisualQueryBuilder;
use Piwik\Plugins\DataExplorer\RecordBuilders\SavedReport;
use Piwik\Plugins\DataExplorer\Sql\ResultColumns;
use Piwik\Plugins\DataExplorer\Visualizations\EChartsBar;
use Piwik\Plugins\DataExplorer\Visualizations\EChartsKpi;
use Piwik\Plugins\DataExplorer\Visualizations\EChartsLine;
use Piwik\Plugins\DataExplorer\Visualizations\EChartsPie;

/**
 * One saved report shown as a Matomo report: in the reporting menu place its author chose, the widget list,
 * dashboards and API.getReportMetadata, on every site it is placed on.
 *
 * Core builds one instance per saved report through Report.addReports, and builds this class again with no
 * argument to render a report widget or a ViewDataTable, so that instance reads the report from the idExploration
 * and idSite request parameters. Only the listed instances are published in the metadata and widget lists.
 */
class GetSavedReport extends Report
{
    public const PARAMETER = 'idExploration';

    /** @var array|null hydrated ReportsDao row */
    private $row;
    /** @var int */
    private $idSite = 0;
    /** @var bool */
    private $listed = false;
    /** @var array<string, string> */
    private $metricNames = [];
    /** @var string[]|null result columns of a raw SQL report, null when only known once it ran */
    private $sqlColumns;
    /** @var string[] metrics that do not add up across the rows of the first row dimension */
    private $nonAdditiveMetrics = [];
    /** @var array<string, string> pivot columns of the period the metadata is built for */
    private $pivotColumns = [];

    protected function init()
    {
        parent::init();

        $this->module = 'DataExplorer';
        $this->action = 'getReport';
        $this->categoryId = ExploreSubcategory::CATEGORY;
        $this->name = Piwik::translate('DataExplorer_DataExplorer');
        $this->order = 100;
        $this->actionToLoadSubTables = $this->action;

        $idReport = Common::getRequestVar(self::PARAMETER, 0, 'int');
        $idSite = Common::getRequestVar('idSite', 0, 'int');
        if ($idReport > 0 && $idSite > 0) {
            $row = (new ReportsDao())->getCached($idReport);
            $placement = $row['placement'] ?? null;
            if ($row !== null && empty($row['deleted']) && $placement instanceof Placement && $placement->includes($idSite)) {
                $this->initFromRow($row, $idSite, false);
            }
        }
    }

    /**
     * @param array $row a hydrated row of ReportsDao placed on $idSite
     * @param bool $listed false for the instance core builds to render the report
     */
    public function initFromRow(array $row, int $idSite, bool $listed = true): void
    {
        $this->row = $row;
        $this->idSite = $idSite;
        $this->listed = $listed;

        $this->name = (string) $row['name'];
        $this->categoryId = (string) $row['category'] !== '' ? (string) $row['category'] : ExploreSubcategory::CATEGORY;
        $this->subcategoryId = (string) $row['subcategory'] !== '' ? (string) $row['subcategory'] : null;
        $this->order = (int) $row['order'];
        $this->parameters = [self::PARAMETER => (int) $row['idreport']];
        $this->dimension = null;
        $this->metrics = [];
        $this->metricNames = [];
        $this->nonAdditiveMetrics = [];
        // core adds its visit ratios to every report by default, as zero columns here
        $this->processedMetrics = [];
        $this->sqlColumns = null;

        if ($row['type'] === ReportDefinition::TYPE_VISUAL) {
            $this->initVisual($row, $idSite);
        } else {
            $this->initSql($row);
        }
    }

    /**
     * False for a raw SQL report whose result columns are only known once it ran: its processed report (scheduled
     * reports) would have no column.
     */
    public function hasKnownColumns(): bool
    {
        return !$this->isSql() || $this->sqlColumns !== null;
    }

    public function getIdReport(): int
    {
        return $this->row === null ? 0 : (int) $this->row['idreport'];
    }

    public function getReportType(): string
    {
        return $this->row === null ? '' : (string) $this->row['report_type'];
    }

    public function isSql(): bool
    {
        return $this->row !== null && $this->row['type'] === ReportDefinition::TYPE_SQL;
    }

    public function getDefaultTypeViewDataTable()
    {
        switch ($this->getReportType()) {
            case ReportDefinition::REPORT_TYPE_EVOLUTION:
                return EChartsLine::ID;
            case ReportDefinition::REPORT_TYPE_KPICARD:
                return EChartsKpi::ID;
        }

        $chart = $this->row !== null && !$this->isSql() ? ($this->row['definition']['chart'] ?? '') : '';
        switch ($chart) {
            case 'bar':
                return EChartsBar::ID;
            case 'pie':
                return EChartsPie::ID;
            default:
                return HtmlTable::ID;
        }
    }

    public function alwaysUseDefaultViewDataTable()
    {
        return $this->getReportType() === ReportDefinition::REPORT_TYPE_EVOLUTION || $this->getReportType() === ReportDefinition::REPORT_TYPE_KPICARD;
    }

    public function getDefaultSortColumn()
    {
        // no default sort, so the rows of a raw SQL report keep the order of the query
        return $this->isSql() ? '' : parent::getDefaultSortColumn();
    }

    public function isEnabled()
    {
        return $this->row !== null;
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
        if (!$this->listed) {
            return;
        }

        $this->pivotColumns = $this->pivotColumnsOf($infos['period'] ?? null, $infos['date'] ?? null);
        try {
            parent::configureReportMetadata($availableReports, $infos);
        } finally {
            $this->pivotColumns = [];
        }
    }

    protected function buildReportMetadata()
    {
        $report = parent::buildReportMetadata();

        if ($this->isSql() && $this->sqlColumns !== null && $this->getReportType() === ReportDefinition::REPORT_TYPE_TABLE) {
            $label = in_array('label', $this->sqlColumns, true) ? 'label' : $this->sqlColumns[0];
            $report['dimension'] = $label;
            unset($report['metrics']['label'], $report['metrics'][$label]);
        }
        if ($this->pivotColumns) {
            $report['metrics'] = array_merge($report['metrics'], $this->pivotColumns);
        }

        return $report;
    }

    /**
     * The pivot columns are values of the column dimension found in the archives, so they are only known for a
     * period: read from the data the processed report of that period is about to fetch.
     *
     * @param mixed $period
     * @param mixed $date
     * @return array<string, string> column => translated name
     */
    private function pivotColumnsOf($period, $date): array
    {
        if ($this->row === null || $this->isSql() || $this->getReportType() !== ReportDefinition::REPORT_TYPE_TABLE
            || empty($this->row['definition']['column']) || !is_string($period) || $period === '' || empty($date)) {
            return [];
        }

        try {
            $segment = Common::getRequestVar('segment', '', 'string');
            $table = (new SavedReportReader())->read($this->row, $this->idSite, $period, (string) $date, $segment);
        } catch (\Exception $e) {
            return [];
        }

        $columns = [];
        $tables = $table instanceof DataTable\Map ? $table->getDataTables() : [$table];
        foreach ($tables as $one) {
            foreach ($one->getRows() as $row) {
                foreach (array_keys($row->getColumns()) as $column) {
                    $position = strpos((string) $column, LabelFormatter::PIVOT_SEPARATOR);
                    if ($position !== false) {
                        $metric = substr((string) $column, 0, $position);
                        $columns[$column] = ($this->metricNames[$metric] ?? $metric) . substr((string) $column, $position);
                    }
                }
            }
        }

        return $columns;
    }

    public function configureWidgets(\Piwik\Widget\WidgetsList $widgetsList, \Piwik\Report\ReportWidgetFactory $factory)
    {
        if ($this->listed) {
            parent::configureWidgets($widgetsList, $factory);
        }
    }

    public function getMetrics()
    {
        return $this->metricNames;
    }

    public function configureView(ViewDataTable $view)
    {
        if ($this->row === null) {
            return;
        }

        $idReport = $this->getIdReport();
        $view->requestConfig->request_parameters_to_modify[self::PARAMETER] = $idReport;
        $view->config->custom_parameters[self::PARAMETER] = $idReport;
        $view->config->custom_parameters['dataExplorerReportType'] = $this->getReportType();
        $view->config->custom_parameters['dataExplorerNonAdditive'] = $this->nonAdditiveMetrics;
        $view->config->addTranslations($this->metricNames);

        if ($this->getReportType() !== ReportDefinition::REPORT_TYPE_TABLE) {
            $view->config->footer_icons = $this->allowedFooterIcons($view, [$this->getDefaultTypeViewDataTable()]);
            return;
        }

        $view->config->show_bar_chart = false;
        $view->config->show_pie_chart = false;
        $view->config->show_tag_cloud = false;
        $view->config->show_goals = false;
        $view->config->show_insights = false;
        $view->config->show_table_all_columns = false;
        $view->config->footer_icons = $this->allowedFooterIcons($view, [HtmlTable::ID, EChartsBar::ID, EChartsPie::ID]);

        if ($this->isSql()) {
            // the rows keep the order of the query
            $view->config->enable_sort = false;
        }

        $definition = is_array($this->row['definition'] ?? null) ? $this->row['definition'] : [];
        if (!$this->isSql() && count((array) ($definition['rows'] ?? [])) > 1) {
            $view->config->show_flatten_table = true;
            // the view the report was saved with, unless the URL asks for the other one
            $request = Request::fromRequest();
            if (($definition['table_view'] ?? 'flat') !== ReportDefinition::TABLE_VIEW_HIERARCHICAL
                && $request->getStringParameter('flat', '') === ''
            ) {
                $view->requestConfig->flat = 1;
                $view->config->custom_parameters['flat'] = 1;
                if ($request->getStringParameter('show_dimensions', '') === '') {
                    $view->requestConfig->show_dimensions = 1;
                    $view->config->custom_parameters['show_dimensions'] = 1;
                }
            }
        }

        $isWidget = Common::getRequestVar('widget', 0, 'int');
        $module = Common::getRequestVar('module', '', 'string');
        if ($this->isSql() || ($isWidget && $module !== 'Widgetize')) {
            // raw SQL runs live, and row evolution would not forward idExploration from a dashboard widget
            $view->config->disable_row_evolution = true;
        }

        $metricNames = $this->metricNames;
        $view->config->filters[] = function (DataTable $table) use ($view, $metricNames) {
            $labelColumn = $table->getMetadata(SavedReportReader::LABEL_COLUMN_METADATA);
            if (is_string($labelColumn) && $labelColumn !== '') {
                $view->config->addTranslation('label', $labelColumn);
            }

            $columns = [];
            foreach ($table->getRows() as $row) {
                foreach (array_keys($row->getColumns()) as $column) {
                    $columns[$column] = $column;
                }
                break;
            }
            unset($columns['label']);
            $view->config->columns_to_display = array_values(array_merge(['label'], $columns));

            foreach ($columns as $column) {
                $position = strpos((string) $column, LabelFormatter::PIVOT_SEPARATOR);
                if ($position !== false) {
                    $metric = substr((string) $column, 0, $position);
                    $view->config->addTranslation($column, ($metricNames[$metric] ?? $metric) . substr((string) $column, $position));
                }
            }
        };
    }

    /**
     * The footer icons core would show, restricted to the given visualizations: visualizations of other plugins
     * accept every report but cannot read DataExplorer data.
     *
     * @param string[] $allowed visualization ids
     */
    private function allowedFooterIcons(ViewDataTable $view, array $allowed): array
    {
        $groups = [];
        foreach (ViewDataTableManager::configureFooterIcons($view) as $group) {
            $group['buttons'] = array_values(array_filter($group['buttons'], fn ($button) => in_array($button['id'] ?? '', $allowed, true)));
            if ($group['buttons']) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    private function initSql(array $row): void
    {
        $sql = is_array($row['definition'] ?? null) && is_string($row['definition']['sql'] ?? null) ? $row['definition']['sql'] : '';
        $this->sqlColumns = ResultColumns::of($sql);
        foreach ($this->sqlColumns ?? [] as $column) {
            $this->metrics[] = $column;
            $this->metricNames[$column] = $column;
        }
    }

    private function initVisual(array $row, int $idSite): void
    {
        try {
            $definition = ReportDefinition::fromReport($row);
            $resolved = (new VisualQueryBuilder())->resolve($definition, $idSite);
        } catch (\InvalidArgumentException $e) {
            // a definition no longer matching the catalogue still shows, with the error, when opened
            return;
        }

        $dailySum = ' (' . Piwik::translate('General_DailySum') . ')';
        foreach ($resolved['metrics'] as $metric) {
            $this->metrics[] = $metric->id;
            $this->metricNames[$metric->id] = $metric->name;
            $first = $resolved['rows'][0] ?? null;
            if ($first !== null && !in_array($first->scope, $metric->additiveScopes(), true)) {
                $this->nonAdditiveMetrics[] = $metric->id;
            }
            if (SavedReport::isNotSummableAcrossDays($metric)) {
                $this->metricNames['sum_daily_' . $metric->id] = $metric->name . $dailySum;
                $this->nonAdditiveMetrics[] = 'sum_daily_' . $metric->id;
            }
        }

        $first = $resolved['rows'][0] ?? null;
        if ($first !== null) {
            $this->dimension = SiteContext::run($idSite, fn () => (new DimensionsProvider())->factory($first->id));
        }
    }
}
