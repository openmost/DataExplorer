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
 * Share of the first metric of a table, top rows plus "Others" (DataExplorerPie visualization).
 */
export default defineComponent({
  name: 'EChartsPie',
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
        tooltip: { trigger: 'item' },
        legend: {
          type: 'scroll',
          orient: 'vertical',
          right: 0,
          top: 'middle',
          textStyle: { color: theme.text },
        },
        series: [{
          name: props.metricName,
          type: 'pie',
          radius: ['35%', '70%'],
          center: ['40%', '50%'],
          itemStyle: { borderColor: theme.background, borderWidth: 1 },
          label: { color: theme.text },
          data: props.labels.map((label, index) => ({ name: label, value: props.values[index] })),
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
