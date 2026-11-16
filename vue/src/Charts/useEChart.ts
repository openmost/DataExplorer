/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import {
  ComputedRef,
  onBeforeUnmount,
  onMounted,
  Ref,
  ref,
  watch,
} from 'vue';
import type { EChartsInstance, EChartsOption } from './echartsModules';

type EChartsModules = typeof import('./echartsModules');

let modules: Promise<EChartsModules> | null = null;

function loadECharts(): Promise<EChartsModules> {
  if (!modules) {
    modules = import(/* webpackChunkName: "echarts" */ './echartsModules');
  }
  return modules;
}

// core Morpheus graph series colors, used when the theme does not define its own
const FALLBACK_SERIES = [
  '#0277BD',
  '#FF8F00',
  '#AD1457',
  '#6A1B9A',
  '#558B2F',
  '#00838F',
  '#283593',
  '#D84315',
];

export interface ChartTheme {
  version: number;
  series: string[];
  text: string;
  textLight: string;
  border: string;
  background: string;
  success: string;
  danger: string;
}

// bumped when the user switches between light and dark mode, so chart options read the new colors
const themeVersion = ref(0);
window.addEventListener('themeModeChange', () => {
  // the theme stylesheet applies on the next frame
  window.requestAnimationFrame(() => {
    themeVersion.value += 1;
  });
});

function cssVariable(style: CSSStyleDeclaration, name: string, fallback: string): string {
  const value = style.getPropertyValue(name).trim();
  return value || fallback;
}

/**
 * Colors of the active Matomo theme (light, dark or custom), read from its CSS variables with
 * the core defaults as fallback for Matomo versions that do not expose them.
 */
export function chartTheme(): ChartTheme {
  const style = window.getComputedStyle(document.documentElement);
  const base = cssVariable(style, '--theme-color-base-series', FALLBACK_SERIES[0]);

  return {
    // read here so that computed chart options follow theme mode changes
    version: themeVersion.value,
    series: [
      base,
      ...FALLBACK_SERIES.filter((color) => color.toLowerCase() !== base.toLowerCase()),
    ],
    text: cssVariable(style, '--theme-color-text', '#212121'),
    textLight: cssVariable(style, '--theme-color-text-light', '#444444'),
    border: cssVariable(style, '--theme-color-border-light', '#dddddd'),
    background: cssVariable(style, '--theme-color-background-base', '#ffffff'),
    success: cssVariable(style, '--theme-color-success', '#43a047'),
    danger: cssVariable(style, '--theme-color-danger', '#c0322e'),
  };
}

/**
 * Draws option into el once ECharts is loaded, redraws when option changes, follows the size
 * of the container and frees the chart when the component is unmounted.
 */
export default function useEChart(
  el: Ref<HTMLElement|null>,
  option: ComputedRef<EChartsOption>,
): void {
  let chart: EChartsInstance | null = null;
  let observer: ResizeObserver | null = null;
  let unmounted = false;

  const resize = () => {
    if (chart) {
      chart.resize();
    }
  };

  onMounted(() => {
    loadECharts().then(({ init }) => {
      if (unmounted || !el.value) {
        return;
      }

      chart = init(el.value, null, { renderer: 'canvas' });
      chart.setOption(option.value, true);

      if (typeof ResizeObserver !== 'undefined') {
        observer = new ResizeObserver(resize);
        observer.observe(el.value);
      } else {
        window.addEventListener('resize', resize);
      }
    });
  });

  watch(option, (value) => {
    if (chart) {
      chart.setOption(value, true);
    }
  });

  onBeforeUnmount(() => {
    unmounted = true;
    if (observer) {
      observer.disconnect();
    } else {
      window.removeEventListener('resize', resize);
    }
    if (chart) {
      chart.dispose();
      chart = null;
    }
  });
}
