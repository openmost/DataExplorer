<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    ref="chart"
    class="dataExplorerChart__canvas"
    role="img"
    :aria-label="ariaLabel"
  ></div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  ref,
} from 'vue';
import useEChart, { chartTheme } from './useEChart';

export interface LineSeries {
  name: string;
  data: (number|null)[];
}

/**
 * Evolution of one or more metrics, one point per period (DataExplorerLine visualization).
 */
export default defineComponent({
  name: 'EChartsLine',
  props: {
    labels: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
    series: {
      type: Array as PropType<LineSeries[]>,
      default: () => [],
    },
  },
  setup(props) {
    const chart = ref<HTMLElement|null>(null);

    const option = computed(() => {
      const theme = chartTheme();
      const axisLine = { lineStyle: { color: theme.border } };

      return {
        color: theme.series,
        textStyle: { color: theme.text },
        tooltip: { trigger: 'axis' },
        legend: {
          show: props.series.length > 1,
          top: 0,
          textStyle: { color: theme.text },
        },
        grid: {
          left: 8,
          right: 16,
          top: props.series.length > 1 ? 32 : 16,
          bottom: props.labels.length > 40 ? 48 : 8,
          containLabel: true,
        },
        dataZoom: props.labels.length > 40
          ? [{ type: 'inside' }, { type: 'slider', height: 20 }]
          : [],
        xAxis: {
          type: 'category',
          boundaryGap: false,
          data: props.labels,
          axisLine,
          axisLabel: { color: theme.textLight },
        },
        yAxis: {
          type: 'value',
          axisLabel: { color: theme.textLight },
          splitLine: { lineStyle: { color: theme.border } },
        },
        series: props.series.map((serie) => ({
          name: serie.name,
          type: 'line',
          data: serie.data,
          showSymbol: props.labels.length <= 31,
          connectNulls: false,
        })),
      };
    });

    useEChart(chart, option);

    const ariaLabel = computed(() => props.series.map((serie) => serie.name).join(', '));

    return {
      chart,
      ariaLabel,
    };
  },
});
</script>
