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
 * First metric of a DataExplorer table, top rows plus "Others".
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

    protected function chartProps(DataTableInterface $dataTable): array
    {
        if (!$dataTable instanceof DataTable) {
            return ['labels' => [], 'values' => [], 'metricName' => ''];
        }

        $columns = $this->metricColumns($dataTable);
        $metric = $columns[0] ?? '';

        $rows = [];
        foreach ($dataTable->getRowsWithoutSummaryRow() as $row) {
            $rows[] = ['label' => (string) $row->getColumn('label'), 'value' => self::number($row->getColumn($metric)) ?? 0];
        }
        usort($rows, fn (array $a, array $b) => $b['value'] <=> $a['value']);

        $others = 0;
        $hasOthers = false;
        foreach (array_slice($rows, self::TOP_ROWS) as $row) {
            $others += $row['value'];
            $hasOthers = true;
        }
        $summary = $dataTable->getRowFromId(DataTable::ID_SUMMARY_ROW);
        if ($summary) {
            $others += self::number($summary->getColumn($metric)) ?? 0;
            $hasOthers = true;
        }

        $rows = array_slice($rows, 0, self::TOP_ROWS);
        if ($hasOthers) {
            $rows[] = ['label' => Piwik::translate('General_Others'), 'value' => $others];
        }

        return [
            'labels' => array_column($rows, 'label'),
            'values' => array_column($rows, 'value'),
            'metricName' => $this->translate($metric),
        ];
    }
}
