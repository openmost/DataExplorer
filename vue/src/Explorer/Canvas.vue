<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    class="dataExplorerColumn dataExplorerCanvas"
    :aria-busy="loading ? 'true' : 'false'"
  >
    <div class="dataExplorerCanvas__header">
      <h3 class="dataExplorerColumn__title">{{ title }}</h3>
      <ActivityIndicator :loading="loading" />
    </div>

    <p
      v-if="periodText"
      class="dataExplorerCanvas__context"
    >
      <button
        type="button"
        class="dataExplorerCanvas__period"
        :title="translate('General_ChooseDate', periodText)"
        @click="$emit('openPeriodSelector')"
      >{{ periodText }}</button>
      <span aria-hidden="true"> · </span>
      <span>{{ segmentText }}</span>
    </p>

    <div
      class="dataExplorerCanvas__status"
      aria-live="polite"
    >
      <Notification
        v-if="error"
        context="error"
        :noclear="true"
      >{{ error }}</Notification>
      <p
        v-else-if="hint"
        class="dataExplorerCanvas__hint"
      >{{ hint }}</p>
    </div>

    <div
      v-if="!error && !hint"
      class="dataExplorerCanvas__result"
      :class="{ 'is-stale': loading }"
    >
      <div
        v-if="visualization === 'table'"
        class="dataExplorerCanvas__table"
      >
        <table
          class="entityTable dataTable dataExplorerResult"
          :data-cell-type="cellType"
          v-if="rows.length"
        >
          <thead>
            <tr v-if="hasPivot">
              <th
                :colspan="dimensionNames.length"
                class="dataExplorerResult__corner"
              ></th>
              <th
                v-for="group in pivotGroups"
                :key="group.key"
                :colspan="group.span"
                class="dataExplorerResult__pivot"
                scope="colgroup"
              >{{ group.label }}</th>
            </tr>
            <tr>
              <th
                v-for="(name, index) in dimensionNames"
                :key="`d${index}`"
                scope="col"
                :aria-sort="ariaSort(`d${index}`)"
              >
                <button
                  type="button"
                  class="dataExplorerResult__sort"
                  @click="sortBy(`d${index}`)"
                >{{ name }}<span
                  :class="sortIcon(`d${index}`)"
                  aria-hidden="true"
                ></span></button>
              </th>
              <th
                v-for="column in columns"
                :key="column.key"
                scope="col"
                class="dataExplorerResult__metric"
                :aria-sort="ariaSort(column.key)"
              >
                <button
                  type="button"
                  class="dataExplorerResult__sort"
                  @click="sortBy(column.key)"
                >{{ metricNames[column.metric] || column.metric }}<span
                  :class="sortIcon(column.key)"
                  aria-hidden="true"
                ></span></button>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, rowIndex) in sortedRows"
              :key="rowIndex"
            >
              <td
                v-for="(label, index) in row.labels"
                :key="`d${index}`"
                class="label"
              >{{ label }}</td>
              <td
                v-for="column in columns"
                :key="column.key"
                class="dataExplorerResult__metric"
                :class="cellClass(row.values[column.key], column.key)"
              >{{ format(row.values[column.key]) }}</td>
            </tr>
          </tbody>
        </table>
        <p
          v-else-if="!loading"
          class="dataExplorerCanvas__hint"
        >{{ translate('CoreHome_ThereIsNoDataForThisReport') }}</p>
      </div>

      <div
        v-else-if="(visualization === 'bar' || visualization === 'pie') && slice"
        class="dataExplorerChart"
      >
        <EChartsBar
          v-if="visualization === 'bar'"
          :labels="slice.labels"
          :values="slice.values"
          :metric-name="sliceMetricName"
        />
        <EChartsPie
          v-else
          :labels="slice.labels"
          :values="slice.values"
          :metric-name="sliceMetricName"
        />
      </div>

      <div
        v-else-if="visualization === 'line' && evolution"
        class="dataExplorerChart"
      >
        <EChartsLine
          :labels="evolution.labels"
          :series="lineSeries"
        />
      </div>

      <div
        v-else-if="visualization === 'kpicard' && kpi"
        class="dataExplorerCanvas__kpi"
      >
        <div class="dataExplorerCanvas__kpiName">{{ kpi.metricName }}</div>
        <KpiCard
          :metric-name="kpi.metricName"
          :value="kpi.value"
          :evolution="kpi.evolution"
          :sparkline="kpi.sparkline"
          :sparkline-labels="kpi.sparklineLabels"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  ref,
} from 'vue';
import {
  ActivityIndicator,
  Notification,
  NumberFormatter,
  translate,
} from 'CoreHome';
import EChartsBar from '../Charts/EChartsBar.vue';
import EChartsLine from '../Charts/EChartsLine.vue';
import EChartsPie from '../Charts/EChartsPie.vue';
import KpiCard from '../Charts/KpiCard.vue';
import type {
  ChartSlice,
  EvolutionSeries,
  ResultColumn,
  ResultRow,
} from './resultTable';
import { cellBucket, columnMaxima } from './resultTable';
import type { CellType, Visualization } from '../types';

export interface KpiView {
  metricName: string;
  value: string;
  evolution: { percent: string, trend: number, tooltip: string }|null;
  sparkline: number[];
  sparklineLabels: string[];
}

/**
 * Right column: the result of the exploration in the chosen visualization.
 */
export default defineComponent({
  name: 'Canvas',
  components: {
    ActivityIndicator,
    Notification,
    EChartsBar,
    EChartsLine,
    EChartsPie,
    KpiCard,
  },
  props: {
    title: {
      type: String,
      required: true,
    },
    // the period and segment of the result, the period opens the native selector
    periodText: {
      type: String,
      default: '',
    },
    segmentText: {
      type: String,
      default: '',
    },
    visualization: {
      type: String as PropType<Visualization>,
      required: true,
    },
    cellType: {
      type: String as PropType<CellType>,
      default: 'plain',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: '',
    },
    hint: {
      type: String,
      default: '',
    },
    dimensionNames: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
    columns: {
      type: Array as PropType<ResultColumn[]>,
      default: () => [],
    },
    rows: {
      type: Array as PropType<ResultRow[]>,
      default: () => [],
    },
    metricNames: {
      type: Object as PropType<Record<string, string>>,
      default: () => ({}),
    },
    slice: {
      type: Object as PropType<ChartSlice|null>,
      default: null,
    },
    sliceMetricName: {
      type: String,
      default: '',
    },
    evolution: {
      type: Object as PropType<EvolutionSeries|null>,
      default: null,
    },
    kpi: {
      type: Object as PropType<KpiView|null>,
      default: null,
    },
  },
  emits: ['openPeriodSelector'],
  setup(props) {
    // '' keeps the server order (ranked by the first metric)
    const sortKey = ref('');
    const sortDescending = ref(true);

    const hasPivot = computed(() => props.columns.some((column) => column.pivot !== null));

    const pivotGroups = computed(() => {
      const groups: { key: string, label: string, span: number }[] = [];
      props.columns.forEach((column) => {
        const key = column.pivot === null ? '' : `p${column.pivot}`;
        const last = groups[groups.length - 1];
        if (last && last.key === key) {
          last.span += 1;
        } else {
          groups.push({
            key,
            label: column.pivot === null ? translate('DataExplorer_Totals') : column.pivot,
            span: 1,
          });
        }
      });
      return groups;
    });

    const sortedRows = computed(() => {
      if (!sortKey.value) {
        return props.rows;
      }
      const key = sortKey.value;
      const direction = sortDescending.value ? -1 : 1;
      const isDimension = /^d\d+$/.test(key) && !props.columns.some((column) => column.key === key);
      return [...props.rows].sort((a, b) => {
        if (isDimension) {
          const index = parseInt(key.substring(1), 10);
          return direction * (a.labels[index] || '').localeCompare(b.labels[index] || '');
        }
        return direction * ((a.values[key] || 0) - (b.values[key] || 0));
      });
    });

    function sortBy(key: string) {
      if (sortKey.value === key) {
        sortDescending.value = !sortDescending.value;
      } else {
        sortKey.value = key;
        sortDescending.value = !/^d\d+$/.test(key);
      }
    }

    function ariaSort(key: string) {
      if (sortKey.value !== key) {
        return 'none';
      }
      return sortDescending.value ? 'descending' : 'ascending';
    }

    function sortIcon(key: string) {
      if (sortKey.value !== key) {
        return '';
      }
      return sortDescending.value ? 'icon-arrow-bottom' : 'icon-arrow-top';
    }

    function format(value: number|undefined) {
      if (typeof value !== 'number') {
        return '';
      }
      return NumberFormatter.formatNumber(value, Number.isInteger(value) ? 0 : 2);
    }

    const maxima = computed(() => (props.cellType === 'plain'
      ? {}
      : columnMaxima(props.rows, props.columns)));

    // one class per tenth of the column maximum, the stylesheet draws the bar or the color
    function cellClass(value: number|undefined, key: string): string {
      if (props.cellType === 'plain' || typeof value !== 'number') {
        return '';
      }
      const bucket = cellBucket(value, maxima.value[key] || 0);
      const prefix = props.cellType === 'bar' ? 'dataExplorerCell--bar' : 'dataExplorerCell--heat';
      return `${prefix}-${bucket}`;
    }

    const lineSeries = computed(() => (props.evolution
      ? props.evolution.series.map((line) => ({
        name: props.metricNames[line.metric] || line.metric,
        data: line.data,
      }))
      : []));

    return {
      hasPivot,
      pivotGroups,
      sortedRows,
      sortBy,
      ariaSort,
      sortIcon,
      format,
      cellClass,
      lineSeries,
    };
  },
});
</script>
