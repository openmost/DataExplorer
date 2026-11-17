<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Visualizations;

use Piwik\DataTable;
use Piwik\DataTable\DataTableInterface;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\DataExplorer\Charts\ChartVisualization;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;

/**
 * First metric of a DataExplorer table, top rows plus "Others" when the metric can be summed across rows: a
 * distinct count, a maximum or a minimum shows the top rows only.
 */
class EChartsBar extends ChartVisualization
{
    public const ID = 'DataExplorerBar';
    public const FOOTER_ICON = 'icon-chart-bar';
    public const FOOTER_ICON_TITLE = 'General_VBarGraph';

    public const TOP_ROWS = 10;

    public static function canDisplayViewDataTable(ViewDataTable $view)
    {
        return self::isDataExplorerReport($view, ReportDefinition::REPORT_TYPE_TABLE);
    }

    public function beforeLoadDataTable()
    {
        parent::beforeLoadDataTable();

        $this->checkRequestIsNotForMultiplePeriods();
        $this->requestConfig->filter_limit = -1;
    }

    protected function vueComponent(): string
    {
        return 'EChartsBar';
    }

    /**
     * The top rows of the first metric, one series per metric, and "Others" for the metrics that add up across
     * rows (a distinct count shared by the rows has no "Others" bar).
     */
    protected function chartProps(DataTableInterface $dataTable): array
    {
        if (!$dataTable instanceof DataTable) {
            return ['labels' => [], 'values' => [], 'metricName' => '', 'series' => []];
        }

        $columns = $this->metricColumns($dataTable);
        $metric = $columns[0] ?? '';

        $rows = [];
        foreach ($dataTable->getRowsWithoutSummaryRow() as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[$column] = self::number($row->getColumn($column)) ?? 0;
            }
            $rows[] = ['label' => (string) $row->getColumn('label'), 'values' => $values];
        }
        usort($rows, fn (array $a, array $b) => ($b['values'][$metric] ?? 0) <=> ($a['values'][$metric] ?? 0));

        $kept = array_slice($rows, 0, self::TOP_ROWS);
        $rest = array_slice($rows, self::TOP_ROWS);
        $summary = $dataTable->getRowFromId(DataTable::ID_SUMMARY_ROW);
        $nonAdditive = (array) ($this->config->custom_parameters['dataExplorerNonAdditive'] ?? []);
        $showOthers = ($rest || $summary) && !in_array($metric, $nonAdditive, true);

        $labels = array_column($kept, 'label');
        if ($showOthers) {
            $labels[] = Piwik::translate('General_Others');
        }

        $series = [];
        foreach ($columns as $column) {
            $data = array_map(fn (array $row) => $row['values'][$column], $kept);
            if ($showOthers) {
                if (in_array($column, $nonAdditive, true)) {
                    $data[] = null;
                } else {
                    $others = array_sum(array_map(fn (array $row) => $row['values'][$column], $rest));
                    $data[] = $others + ($summary ? (self::number($summary->getColumn($column)) ?? 0) : 0);
                }
            }
            $series[] = ['name' => $this->translate($column), 'data' => $data];
        }

        return [
            'labels' => $labels,
            'values' => $series[0]['data'] ?? [],
            'metricName' => $series[0]['name'] ?? '',
            'series' => $series,
        ];
    }
}
