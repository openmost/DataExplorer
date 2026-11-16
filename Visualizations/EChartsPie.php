<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Visualizations;

/**
 * Share of the first metric of a DataExplorer table, top rows plus "Others".
 */
class EChartsPie extends EChartsBar
{
    public const ID = 'DataExplorerPie';
    public const FOOTER_ICON = 'icon-chart-pie';
    public const FOOTER_ICON_TITLE = 'General_Piechart';

    protected function vueComponent(): string
    {
        return 'EChartsPie';
    }
}
