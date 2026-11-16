<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerKpi">
    <div class="dataExplorerKpi__value">{{ displayValue }}</div>
    <div
      v-if="evolution"
      class="dataExplorerKpi__evolution"
      :class="trendClass"
      :title="evolution.tooltip"
    >
      <span
        class="dataExplorerKpi__arrow"
        :class="arrowClass"
        aria-hidden="true"
      ></span>
      {{ evolution.percent }}
    </div>
    <div
      v-if="sparkline.length"
      ref="chart"
      class="dataExplorerKpi__sparkline"
      role="img"
      :aria-label="metricName"
    ></div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  ref,
} from 'vue';
import useEChart, { chartTheme } from './useEChart';

export interface KpiEvolution {
  percent: string;
  trend: number;
  tooltip: string;
}

/**
 * KPI card (DataExplorerKpi visualization): the value of the selected period, its evolution
 * against the previous period and a sparkline of the last periods. Same layout as the Openmost
 * KPI widgets: large centered value, evolution pill below, so both fit side by side in a
 * dashboard.
 */
export default defineComponent({
  name: 'KpiCard',
  props: {
    metricName: {
      type: String,
      default: '',
    },
    // formatted server side (number, money or duration)
    value: {
      type: [String, Number],
      default: '',
    },
    evolution: {
      type: Object as PropType<KpiEvolution|null>,
      default: null,
    },
    sparkline: {
      type: Array as PropType<number[]>,
      default: () => [],
    },
    sparklineLabels: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
  },
  setup(props) {
    const chart = ref<HTMLElement|null>(null);

    const displayValue = computed(() => (
      props.value === '' || props.value === null ? '-' : props.value
    ));

    const trendClass = computed(() => {
      if (!props.evolution || props.evolution.trend === 0) {
        return 'is-flat';
      }
      return props.evolution.trend > 0 ? 'is-up' : 'is-down';
    });

    const arrowClass = computed(() => {
      if (!props.evolution || props.evolution.trend === 0) {
        return '';
      }
      return props.evolution.trend > 0 ? 'icon-arrow-top' : 'icon-arrow-bottom';
    });

    const option = computed(() => {
      const theme = chartTheme();

      return {
        color: [theme.series[0]],
        animation: false,
        tooltip: { trigger: 'axis', confine: true },
        grid: {
          left: 2,
          right: 2,
          top: 4,
          bottom: 2,
        },
        xAxis: {
          type: 'category',
          show: false,
          boundaryGap: false,
          data: props.sparklineLabels,
        },
        yAxis: {
          type: 'value',
          show: false,
          scale: true,
        },
        series: [{
          name: props.metricName,
          type: 'line',
          data: props.sparkline,
          showSymbol: false,
          lineStyle: { width: 1.5 },
          areaStyle: { opacity: 0.15 },
        }],
      };
    });

    useEChart(chart, option);

    return {
      chart,
      displayValue,
      trendClass,
      arrowClass,
    };
  },
});
</script>
