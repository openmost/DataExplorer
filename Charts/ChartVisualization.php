<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Charts;

use Piwik\Common;
use Piwik\Columns\Dimension;
use Piwik\DataTable;
use Piwik\DataTable\DataTableInterface;
use Piwik\Metrics;
use Piwik\Metrics\Formatter;
use Piwik\Period;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugin\Visualization;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;

/**
 * A DataExplorer ECharts visualization: the DataTable is serialized for a Vue component of the plugin, which
 * draws it with ECharts loaded on demand.
 */
abstract class ChartVisualization extends Visualization
{
    public const TEMPLATE_FILE = '@DataExplorer/_chart.twig';

    /** points of an evolution chart or KPI card sparkline when the request names one period */
    public const DEFAULT_POINTS = 30;

    /**
     * Vue component exported by the plugin, eg "EChartsLine".
     */
    abstract protected function vueComponent(): string;

    /**
     * Props of the Vue component, computed from the filtered DataTable.
     */
    abstract protected function chartProps(DataTableInterface $dataTable): array;

    public function beforeRender()
    {
        parent::beforeRender();

        $this->config->show_flatten_table = false;
        $this->config->show_table_all_columns = false;
        $this->config->show_exclude_low_population = false;
        $this->config->show_pagination_control = false;
        $this->config->show_offset_information = false;
        $this->config->show_limit_control = false;
        $this->config->show_search = false;
        $this->config->show_insights = false;
        $this->config->show_goals = false;

        $this->assignTemplateVar('vueEntry', 'DataExplorer.' . $this->vueComponent());
        $attributes = [];
        $props = $this->dataTable instanceof DataTableInterface ? $this->chartProps($this->dataTable) : [];
        foreach ($props as $name => $value) {
            // vue-entry reads kebab-case attributes back as camelCase props
            $attributes[strtolower((string) preg_replace('/[A-Z]/', '-$0', $name))] = $value;
        }
        $this->assignTemplateVar('chartProps', $attributes);
    }

    /**
     * Only for DataExplorer reports of the given report type.
     */
    protected static function isDataExplorerReport(ViewDataTable $view, string $reportType): bool
    {
        return $view->requestConfig->getApiModuleToRequest() === 'DataExplorer'
            && ($view->config->custom_parameters['dataExplorerReportType'] ?? '') === $reportType;
    }

    /**
     * Requests several periods ending at the selected one: lastN of the selected period, or the days of a
     * selected range. A request that already names several periods is kept.
     */
    protected function requestPeriods(int $lastN): void
    {
        $period = Common::getRequestVar('period', 'day', 'string');
        $date = Common::getRequestVar('date', 'today', 'string');

        if ($period === 'range') {
            $this->requestConfig->request_parameters_to_modify['period'] = 'day';
            $this->requestConfig->request_parameters_to_modify['date'] = $date;
            return;
        }

        if (!Period::isMultiplePeriod($date, $period)) {
            [$range] = Evolution::getDateRangeAndLastN($period, $date, $lastN);
            $this->requestConfig->request_parameters_to_modify['date'] = $range;
        }
    }

    /**
     * Metric columns to draw: the columns to display, or every column of the data but the label.
     *
     * @return string[]
     */
    protected function metricColumns(DataTable $table): array
    {
        $columns = array_values(array_diff((array) $this->config->columns_to_display, ['label']));
        if ($columns) {
            return $columns;
        }

        $row = $table->getFirstRow();

        return $row === false ? [] : array_values(array_diff(array_keys($row->getColumns()), ['label']));
    }

    /**
     * Metric columns of the first period that has any.
     *
     * @param array[] $periods result of tablesByPeriod()
     * @return string[]
     */
    protected function firstMetricColumns(array $periods): array
    {
        foreach ($periods as $period) {
            $columns = $this->metricColumns($period['table']);
            if ($columns) {
                return $columns;
            }
        }

        return [];
    }

    protected function translate(string $column): string
    {
        return (string) ($this->config->translations[$column] ?? $column);
    }

    /**
     * @return DataTable[] tables of a date indexed Map (or the single table) in date order, with their label
     */
    protected function tablesByPeriod(DataTableInterface $dataTable): array
    {
        if ($dataTable instanceof DataTable) {
            return [['label' => '', 'table' => $dataTable]];
        }

        $result = [];
        foreach ($dataTable->getDataTables() as $key => $table) {
            $period = $table->getMetadata('period');
            $result[] = [
                'label' => $period instanceof Period ? $period->getLocalizedShortString() : (string) $key,
                'table' => $table,
            ];
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return float|int|null
     */
    protected static function number($value)
    {
        if ($value === false || $value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return $value + 0;
    }

    /**
     * @param int|float $value
     */
    protected function formatValue(string $column, $value): string
    {
        $formatter = new Formatter();
        $types = method_exists(Metrics::class, 'getDefaultMetricSemanticTypes') ? Metrics::getDefaultMetricSemanticTypes() : [];

        switch ($types[$column] ?? '') {
            case Dimension::TYPE_MONEY:
                return (string) $formatter->getPrettyMoney($value, (int) Common::getRequestVar('idSite', 0, 'int'));
            case Dimension::TYPE_DURATION_S:
                return (string) $formatter->getPrettyTimeFromSeconds($value, true);
            default:
                return (string) $formatter->getPrettyNumber($value, is_float($value) && floor($value) != $value ? 2 : 0);
        }
    }
}
