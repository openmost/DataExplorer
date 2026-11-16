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
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
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
use Piwik\Plugins\DataExplorer\Visualizations\EChartsKpi;
use Piwik\Plugins\DataExplorer\Visualizations\EChartsLine;

/**
 * One saved report shown as a Matomo report: in the reporting menu place its author chose, the widget list,
 * dashboards and API.getReportMetadata, on every site it is placed on.
 *
 * Core builds one instance per saved report through Report.addReports, and builds this class again with no
 * argument to render a report widget or a ViewDataTable, so that instance reads the report from the idReport
 * and idSite request parameters. Only the listed instances are published in the metadata and widget lists.
 */
class GetSavedReport extends Report
{
    public const PARAMETER = 'idReport';

    /** @var array|null hydrated ReportsDao row */
    private $row;
    /** @var int */
    private $idSite = 0;
    /** @var bool */
    private $listed = false;
    /** @var array<string, string> */
    private $metricNames = [];

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
            $row = (new ReportsDao())->get($idReport);
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

        if ($row['type'] === ReportDefinition::TYPE_VISUAL) {
            $this->initVisual($row, $idSite);
        }
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
            default:
                return HtmlTable::ID;
        }
    }

    public function alwaysUseDefaultViewDataTable()
    {
        return $this->getReportType() === ReportDefinition::REPORT_TYPE_EVOLUTION || $this->getReportType() === ReportDefinition::REPORT_TYPE_KPICARD;
    }

    public function isEnabled()
    {
        return $this->row !== null;
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
        if ($this->listed) {
            parent::configureReportMetadata($availableReports, $infos);
        }
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
        $view->config->addTranslations($this->metricNames);

        if ($this->getReportType() !== ReportDefinition::REPORT_TYPE_TABLE) {
            return;
        }

        $view->config->show_bar_chart = false;
        $view->config->show_pie_chart = false;
        $view->config->show_tag_cloud = false;
        $view->config->show_goals = false;
        $view->config->show_insights = false;
        $view->config->show_table_all_columns = false;

        $isWidget = Common::getRequestVar('widget', 0, 'int');
        $module = Common::getRequestVar('module', '', 'string');
        if ($this->isSql() || ($isWidget && $module !== 'Widgetize')) {
            // raw SQL runs live, and row evolution would not forward idReport from a dashboard widget
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
            if (SavedReport::isNotSummableAcrossDays($metric)) {
                $this->metricNames['sum_daily_' . $metric->id] = $metric->name . $dailySum;
            }
        }

        $first = $resolved['rows'][0] ?? null;
        if ($first !== null) {
            $this->dimension = SiteContext::run($idSite, fn () => (new DimensionsProvider())->factory($first->id));
        }
    }
}
