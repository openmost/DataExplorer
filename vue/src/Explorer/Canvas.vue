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
      <div class="dataExplorerCanvas__tab">
        <span
          class="dataExplorerCanvas__tabIcon"
          :class="tabIcon"
          aria-hidden="true"
        ></span>
        <h3 class="dataExplorerCanvas__title">{{ title }}</h3>
      </div>
      <div
        v-if="periodText"
        class="dataExplorerCanvas__context"
      >
        <button
          type="button"
          class="dataExplorerCanvas__pill dataExplorerCanvas__period"
          :title="translate('General_ChooseDate', periodText)"
          @click="$emit('openPeriodSelector')"
        >
          <span
            class="icon-calendar"
            aria-hidden="true"
          ></span>
          <span>{{ periodText }}</span>
        </button>
        <span class="dataExplorerCanvas__pill">
          <span
            class="icon-segment"
            aria-hidden="true"
          ></span>
          <span>{{ segmentText }}</span>
        </span>
      </div>
      <div
        v-if="canExport"
        class="dataExplorerCanvas__export"
      >
        <button
          ref="exportButton"
          type="button"
          class="dataExplorerIconButton dataExplorerCanvas__exportButton"
          :title="translate('DataExplorer_Export')"
          :aria-label="translate('DataExplorer_Export')"
          aria-haspopup="menu"
          :aria-expanded="exportOpen ? 'true' : 'false'"
          @click="exportOpen = !exportOpen"
        >
          <span
            class="icon-download"
            aria-hidden="true"
          ></span>
        </button>
        <ul
          v-if="exportOpen"
          class="dataExplorerDropZone__menu dataExplorerCanvas__exportMenu"
          role="menu"
          v-focus-anywhere-but-here="{ blur: closeExport }"
          @keydown.esc.stop.prevent="closeExport(true)"
        >
          <li
            v-for="format in exportFormats"
            :key="format.id"
            role="none"
          >
            <button
              type="button"
              role="menuitem"
              @click="exportAs(format.id)"
            >{{ format.label }}</button>
          </li>
        </ul>
      </div>
      <ActivityIndicator :loading="loading" />
    </div>

    <div
      class="dataExplorerCanvas__status"
      aria-live="polite"
    >
      <Notification
        v-if="error"
        context="error"
        :noclear="true"
      >{{ error }}</Notification>
      <div
        v-else-if="hint"
        class="dataExplorerCanvas__hint"
      >{{ hint }}</div>
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
        <div
          v-else-if="!loading"
          class="dataExplorerCanvas__hint"
        >{{ translate('CoreHome_ThereIsNoDataForThisReport') }}</div>
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
  FocusAnywhereButHere,
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
import { VISUALIZATION_ICONS } from './explorationState';
import {
  Cell,
  download,
  exportFileName,
  ExportFormat,
  tableData,
  toDelimited,
} from './exportData';
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
  directives: {
    FocusAnywhereButHere,
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

    const exportOpen = ref(false);
    const exportButton = ref<HTMLElement|null>(null);
    const exportFormats = [
      { id: 'tsv', label: 'TSV' },
      { id: 'csv', label: 'CSV' },
      { id: 'pdf', label: 'PDF' },
    ];

    function exportRows(): { header: Cell[], body: Cell[][] }|null {
      if (props.visualization === 'line' && props.evolution) {
        const series = lineSeries.value;
        return {
          header: [translate('General_Date'), ...series.map((line) => line.name)],
          body: props.evolution.labels.map((label, index) => [
            label,
            ...series.map((line) => line.data[index]),
          ]),
        };
      }
      if (props.visualization === 'kpicard' && props.kpi) {
        return { header: [props.kpi.metricName], body: [[props.kpi.value]] };
      }
      if (!props.rows.length) {
        return null;
      }
      return tableData(
        props.dimensionNames,
        props.columns,
        sortedRows.value,
        props.metricNames,
        translate('DataExplorer_Totals'),
      );
    }

    const canExport = computed(() => !props.error && !props.hint && exportRows() !== null);

    function closeExport(restoreFocus = false) {
      exportOpen.value = false;
      if (restoreFocus === true && exportButton.value) {
        exportButton.value.focus();
      }
    }

    function printCanvas() {
      const root = document.documentElement;
      const done = () => {
        root.classList.remove('dataExplorer-printing');
        window.removeEventListener('afterprint', done);
      };
      root.classList.add('dataExplorer-printing');
      window.addEventListener('afterprint', done);
      window.print();
    }

    function exportAs(formatId: string) {
      closeExport(true);
      if (formatId === 'pdf') {
        printCanvas();
        return;
      }
      const data = exportRows();
      if (!data) {
        return;
      }
      const fileFormat = formatId as ExportFormat;
      download(
        toDelimited(data.header, data.body, fileFormat),
        exportFileName(props.title, props.periodText, fileFormat),
        fileFormat,
      );
    }

    const tabIcon = computed(() => VISUALIZATION_ICONS[props.visualization as Visualization]);

    return {
      exportOpen,
      exportButton,
      exportFormats,
      canExport,
      closeExport,
      exportAs,
      tabIcon,
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
