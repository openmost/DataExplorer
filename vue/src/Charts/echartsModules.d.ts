/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// Subset of the ECharts API used by the charts, see echartsModules.js

export type EChartsOption = Record<string, unknown>;

export interface EChartsInstance {
  setOption(option: EChartsOption, notMerge?: boolean): void;
  resize(): void;
  dispose(): void;
}

export declare function init(
  element: HTMLElement,
  theme?: string | null,
  options?: { renderer?: 'canvas' | 'svg' },
): EChartsInstance;
