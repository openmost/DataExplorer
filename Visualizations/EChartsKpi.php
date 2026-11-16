<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Visualizations;

use Piwik\Common;
use Piwik\DataTable\DataTableInterface;
use Piwik\DataTable\Filter\CalculateEvolutionFilter;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\DataExplorer\Charts\ChartVisualization;

/**
 * KPI card: the metric of the selected period in large, its evolution against the previous period and a
 * sparkline of the last periods. A date range shows its value only.
 */
class EChartsKpi extends ChartVisualization
{
    public const ID = 'DataExplorerKpi';
    public const FOOTER_ICON = 'icon-chart-line';
    public const FOOTER_ICON_TITLE = 'DataExplorer_KpiCard';

    public static function canDisplayViewDataTable(ViewDataTable $view)
    {
        return false;
    }

    public function beforeLoadDataTable()
    {
        parent::beforeLoadDataTable();

        if (Common::getRequestVar('period', 'day', 'string') !== 'range') {
            $this->requestPeriods(self::DEFAULT_POINTS);
        }
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->config->show_footer = false;
        $this->config->show_all_views_icons = false;
    }

    protected function vueComponent(): string
    {
        return 'KpiCard';
    }

    protected function chartProps(DataTableInterface $dataTable): array
    {
        $periods = $this->tablesByPeriod($dataTable);

        $metric = $this->firstMetricColumns($periods)[0] ?? '';

        $values = [];
        $labels = [];
        foreach ($periods as $period) {
            $row = $period['table']->getFirstRow();
            $values[] = $row === false ? 0 : (self::number($row->getColumn($metric)) ?? 0);
            $labels[] = $period['label'];
        }

        $current = $values ? end($values) : 0;
        $evolution = null;
        if (count($values) > 1) {
            $previous = $values[count($values) - 2];
            $evolution = [
                'percent' => CalculateEvolutionFilter::calculate($current, $previous, 1, true, true),
                'trend' => $current <=> $previous,
                'tooltip' => Piwik::translate('DataExplorer_KpiPreviousPeriod', [$this->formatValue($metric, $previous)]),
            ];
        }

        return [
            'metricName' => $this->translate($metric),
            'value' => $this->formatValue($metric, $current),
            'evolution' => $evolution,
            'sparkline' => count($values) > 1 ? $values : [],
            'sparklineLabels' => count($values) > 1 ? $labels : [],
        ];
    }
}
