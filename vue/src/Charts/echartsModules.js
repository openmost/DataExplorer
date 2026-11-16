/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// Loaded on demand by useEChart, so pages without a chart never download ECharts.
// Plain JavaScript typed by echartsModules.d.ts: the TypeScript version of the Matomo 5
// build cannot parse the ECharts declarations. Only the chart types, components and
// renderer used by DataExplorer are registered (tree shaking).

import { init, use } from 'echarts/core';
import { BarChart, LineChart, PieChart } from 'echarts/charts';
import {
  DataZoomComponent,
  GridComponent,
  LegendComponent,
  TooltipComponent,
} from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

use([
  LineChart,
  BarChart,
  PieChart,
  GridComponent,
  TooltipComponent,
  LegendComponent,
  DataZoomComponent,
  CanvasRenderer,
]);

export { init };
