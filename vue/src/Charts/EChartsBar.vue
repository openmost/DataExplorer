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
    :aria-label="metricName"
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

/**
 * First metric of a table, top rows plus "Others" (DataExplorerBar visualization).
 */
export default defineComponent({
  name: 'EChartsBar',
  props: {
    labels: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
    values: {
      type: Array as PropType<number[]>,
      default: () => [],
    },
    metricName: {
      type: String,
      default: '',
    },
  },
  setup(props) {
    const chart = ref<HTMLElement|null>(null);

    const option = computed(() => {
      const theme = chartTheme();

      return {
        color: theme.series,
        textStyle: { color: theme.text },
        tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
        grid: {
          left: 8,
          right: 16,
          top: 16,
          bottom: 8,
          containLabel: true,
        },
        xAxis: {
          type: 'category',
          data: props.labels,
          axisLine: { lineStyle: { color: theme.border } },
          axisLabel: {
            color: theme.textLight,
            interval: 0,
            rotate: props.labels.length > 6 ? 30 : 0,
          },
        },
        yAxis: {
          type: 'value',
          axisLabel: { color: theme.textLight },
          splitLine: { lineStyle: { color: theme.border } },
        },
        series: [{
          name: props.metricName,
          type: 'bar',
          data: props.values,
        }],
      };
    });

    useEChart(chart, option);

    return {
      chart,
    };
  },
});
</script>
