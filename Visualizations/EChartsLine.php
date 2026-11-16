<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Visualizations;

use Piwik\DataTable\DataTableInterface;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\DataExplorer\Charts\ChartVisualization;

/**
 * Evolution of one or more metrics over time, one point per period.
 */
class EChartsLine extends ChartVisualization
{
    public const ID = 'DataExplorerLine';
    public const FOOTER_ICON = 'icon-chart-line';
    public const FOOTER_ICON_TITLE = 'General_EvolutionGraph';

    public static function canDisplayViewDataTable(ViewDataTable $view)
    {
        return false;
    }

    public function beforeLoadDataTable()
    {
        parent::beforeLoadDataTable();

        $this->requestPeriods(self::DEFAULT_POINTS);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->config->show_all_views_icons = false;
        $this->config->show_periods = false;
    }

    protected function vueComponent(): string
    {
        return 'EChartsLine';
    }

    protected function chartProps(DataTableInterface $dataTable): array
    {
        $periods = $this->tablesByPeriod($dataTable);

        $columns = $this->firstMetricColumns($periods);

        $labels = [];
        $series = [];
        foreach ($columns as $column) {
            $series[$column] = ['name' => $this->translate($column), 'data' => []];
        }
        foreach ($periods as $period) {
            $labels[] = $period['label'];
            $row = $period['table']->getFirstRow();
            foreach ($columns as $column) {
                $series[$column]['data'][] = $row === false ? null : self::number($row->getColumn($column));
            }
        }

        return ['labels' => $labels, 'series' => array_values($series)];
    }
}
