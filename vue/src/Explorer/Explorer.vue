<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorer">
    <div class="dataExplorer__toolbar">
      <div
        v-if="isSuperUser"
        class="dataExplorer__tabs"
        role="tablist"
        :aria-label="translate('DataExplorer_DataExplorer')"
      >
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :id="`dataExplorerTab-${tab.id}`"
          type="button"
          role="tab"
          class="dataExplorer__tab"
          :class="{ 'is-active': activeTab === tab.id }"
          :data-tab="tab.id"
          :aria-selected="activeTab === tab.id ? 'true' : 'false'"
          :aria-controls="`dataExplorerPanel-${tab.id}`"
          @click="selectTab(tab.id)"
        >{{ tab.label }}</button>
      </div>

      <div
        v-if="activeTab === 'explore'"
        class="dataExplorer__reports"
      >
        <span class="dataExplorer__reportsLabel">{{ translate('DataExplorer_OpenReport') }}</span>
        <Field
          uicontrol="select"
          name="dataExplorerOpenReport"
          :full-width="true"
          :options="reportOptions"
          :model-value="editing ? String(editing.idreport) : ''"
          :ui-control-attributes="{ 'aria-label': translate('DataExplorer_OpenReport') }"
          @update:model-value="onPickReport($event)"
        />
      </div>

      <button
        v-if="canSaveAnything && activeTab === 'explore'"
        ref="saveButton"
        type="button"
        class="btn dataExplorer__save"
        :disabled="!isRunnable"
        @click="openSave('visual')"
      >{{ editing && editing.editable && editing.type === 'visual'
        ? translate('DataExplorer_EditReport')
        : translate('DataExplorer_SaveReport') }}</button>
      <button
        v-if="customReports && activeTab === 'explore'"
        type="button"
        class="btn-flat dataExplorer__saveCustomReport"
        :disabled="!isRunnable"
        @click="customReportOpen = true"
      >{{ translate('DataExplorer_SaveAsCustomReport') }}</button>
    </div>

    <div aria-live="polite">
      <Notification
        v-if="notice"
        :context="noticeContext"
        :noclear="true"
      >{{ notice }}</Notification>
    </div>

    <div
      v-show="activeTab === 'explore'"
      id="dataExplorerPanel-explore"
      :role="isSuperUser ? 'tabpanel' : undefined"
      :aria-labelledby="isSuperUser ? 'dataExplorerTab-explore' : undefined"
      class="dataExplorer__panel"
    >
      <Notification
        v-if="catalogError"
        context="error"
        :noclear="true"
      >{{ catalogError }}</Notification>
      <ActivityIndicator :loading="!catalog && !catalogError" />

      <div
        v-if="catalog"
        class="dataExplorer__columns"
      >
        <VariablesPanel
          :dimensions="dimensionViews"
          :metrics="metricViews"
          :segments="segmentViews"
          :dimension-groups="dimensionGroups"
          :metric-groups="metricGroups"
          :segment-groups="segmentGroups"
          @add-variable="addVariable"
          @remove-variable="removeVariable"
          @use="useVariable"
        />
        <TabSettings
          :visualization="state.visualization"
          :limits="catalog.limits"
          :row-items="rowItems"
          :column-items="columnItems"
          :value-items="valueItems"
          :segment-items="segmentItems"
          :filters="filterViews"
          :dimension-options="dimensionViews"
          :metric-options="metricViews"
          :segment-options="segmentViews"
          @set-visualization="state.visualization = $event"
          @add="addToZone"
          @remove="removeFromZone"
          :filter-match="state.filterMatch"
          :table-view="state.tableView"
          @set-filter-match="state.filterMatch = $event"
          @set-table-view="state.tableView = $event"
          @update-filter="updateFilter"
          @remove-filter="removeFilter"
        />
        <Canvas
          :title="canvasTitle"
          :period-text="periodText"
          :segment-text="segmentText"
          @open-period-selector="openPeriodSelector"
          :visualization="state.visualization"
          :table-view="state.tableView"
          :tree="resultTree"
          :loading="loading"
          :error="runError"
          :hint="hint"
          :dimension-names="resultDimensionNames"
          :columns="resultColumnsList"
          :rows="resultRows"
          :metric-names="metricNames"
          :slice="slice"
          :bar-series="barSeries"
          :slice-metric-name="metricNames[state.values[0]] || ''"
          :evolution="evolution"
          :kpi="kpi"
        />
      </div>
    </div>

    <div
      v-if="isSuperUser && sqlOpened"
      v-show="activeTab === 'sql'"
      id="dataExplorerPanel-sql"
      role="tabpanel"
      aria-labelledby="dataExplorerTab-sql"
      class="dataExplorer__panel"
    >
      <RawSqlEditor
        v-model="sql"
        :id-site="idSite"
        :period="period"
        :date="date"
        :setup-url="setupUrl"
        :can-save="true"
        @save="openSave('sql')"
      />
    </div>

    <SaveDialog
      v-if="canSaveAnything"
      v-model="saveOpen"
      :id-site="idSite"
      :is-super-user="isSuperUser"
      :writable-sites="writableSites"
      :type="saveType"
      :report-type="saveReportType"
      :definition="saveDefinition"
      :editing="editing && editing.type === saveType ? editing : null"
      @saved="onSaved"
      @closed="onSaveClosed"
    />
    <CustomReportDialog
      v-if="customReports"
      v-model="customReportOpen"
      :definition="definition"
      :default-name="editing ? editing.name : ''"
      @created="onCustomReportCreated"
      @failed="onCustomReportFailed"
    />
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  onBeforeUnmount,
  onMounted,
  PropType,
  reactive,
  ref,
  watch,
} from 'vue';
import {
  ActivityIndicator,
  AjaxHelper,
  Matomo,
  MatomoUrl,
  Notification,
  NumberFormatter,
  Periods,
  ReportingMenuStore,
  translate,
} from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import Canvas from './Canvas.vue';
import TabSettings from './TabSettings.vue';
import VariablesPanel from './VariablesPanel.vue';
import RawSqlEditor from '../RawSql/RawSqlEditor.vue';
import SaveDialog from '../SaveDialog/SaveDialog.vue';
import CustomReportDialog from '../CustomReport/CustomReportDialog.vue';
import {
  clampToLimits,
  decode,
  defaultState,
  encode,
  EXPLORE_SUBCATEGORY,
  fromDefinition,
  hasRowDimensions,
  HASH_PARAM,
  isFilterComplete,
  REPORT_HASH_PARAM,
  reportTypeOf,
  toDefinition,
} from './explorationState';
import { distinctNames } from './catalogNames';
import {
  chartSeries,
  chartSlice,
  evolutionSeries,
  flattenRows,
  treeRows,
  resultColumns,
  totalValue,
} from './resultTable';
import type {
  ChartSlice,
  EvolutionSeries,
  ResultColumn,
  ResultRow,
  TreeRow,
} from './resultTable';
import type {
  ApiRow,
  Catalog,
  DimensionItem,
  ExplorationState,
  Filter,
  FilterView,
  MetricItem,
  PickerGroup,
  PickerItem,
  SavedReport,
  VariableKind,
  VariableView,
  VisualDefinition,
  WritableSite,
  ZoneItem,
} from '../types';

const RUN_DELAY_MS = 300;
const CHART_TOP_ROWS = 10;

type Tab = 'explore' | 'sql';
type Zone = 'rows' | 'column' | 'values' | 'filters' | 'segment';

interface KpiResult {
  value: number|null;
  evolution: EvolutionSeries;
}

/**
 * The explorer of the "Data Explorer > Explore" page: free-form exploration in three columns
 * (variables, tab settings, canvas) like GA4 Explorations, the raw SQL tab for super users and
 * the save dialog. The exploration lives in the URL hash so a reload or a shared link restores
 * it; a saved report is reopened with idReport in the hash.
 */
export default defineComponent({
  name: 'Explorer',
  components: {
    ActivityIndicator,
    Canvas,
    Notification,
    RawSqlEditor,
    SaveDialog,
    CustomReportDialog,
    Field,
    TabSettings,
    VariablesPanel,
  },
  props: {
    isSuperUser: {
      type: Boolean,
      default: false,
    },
    canSave: {
      type: Boolean,
      default: false,
    },
    writableSites: {
      type: Array as PropType<WritableSite[]>,
      default: () => [],
    },
    setupUrl: {
      type: String,
      default: '',
    },
    // the Custom Reports plugin is activated and the user can write: the exploration can become one
    customReports: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const idSite = parseInt(String(Matomo.idSite), 10);
    const period = computed(() => String(MatomoUrl.parsed.value.period || Matomo.period || 'day'));
    const date = computed(() => String(
      MatomoUrl.parsed.value.date || Matomo.currentDateString || 'today',
    ));
    const segment = computed(() => String(MatomoUrl.parsed.value.segment || ''));

    const catalog = ref<Catalog|null>(null);
    const catalogError = ref('');
    const reports = ref<SavedReport[]>([]);
    const editing = ref<SavedReport|null>(null);
    const state = reactive<ExplorationState>(defaultState());
    const sql = ref('');
    const activeTab = ref<Tab>('explore');
    const sqlOpened = ref(false);

    const loading = ref(false);
    const runError = ref('');
    const tableRows = ref<ApiRow[]>([]);
    const evolutionResult = ref<EvolutionSeries|null>(null);
    const kpiResult = ref<KpiResult|null>(null);

    const notice = ref('');
    const noticeContext = ref<'success'|'warning'|'error'>('success');

    const saveOpen = ref(false);
    const saveType = ref<'visual'|'sql'>('visual');
    const saveButton = ref<HTMLElement|null>(null);

    const canSaveAnything = computed(() => props.canSave || props.isSuperUser);

    const tabs = computed(() => [
      { id: 'explore' as Tab, label: translate('DataExplorer_ExploreTab') },
      { id: 'sql' as Tab, label: translate('DataExplorer_SqlTab') },
    ]);

    // catalogue lookups
    const dimensionsById = computed(() => {
      const map: Record<string, DimensionItem> = {};
      (catalog.value ? catalog.value.dimensions : []).forEach((group) => {
        group.items.forEach((item) => {
          map[item.id] = item;
        });
      });
      return map;
    });

    const metricsById = computed(() => {
      const map: Record<string, MetricItem> = {};
      (catalog.value ? catalog.value.metrics : []).forEach((group) => {
        group.items.forEach((item) => {
          map[item.id] = item;
        });
      });
      return map;
    });

    const segmentNames = computed(() => {
      const map: Record<string, string> = {};
      (catalog.value ? catalog.value.segments : []).forEach((item) => {
        map[item.definition] = item.name;
      });
      return map;
    });

    const metricNames = computed(() => {
      const map: Record<string, string> = {};
      Object.values(metricsById.value).forEach((metric) => {
        map[metric.id] = metric.name;
      });
      return map;
    });

    const periodText = computed(() => {
      try {
        return Periods.parse(period.value, date.value).getPrettyString();
      } catch (e) {
        return `${period.value} ${date.value}`;
      }
    });

    const segmentText = computed(() => {
      if (!segment.value) {
        return translate('SegmentEditor_DefaultAllVisits');
      }
      let decoded = segment.value;
      try {
        decoded = decodeURIComponent(segment.value);
      } catch (e) {
        // keeps the raw value
      }
      return segmentNames.value[segment.value] || segmentNames.value[decoded]
        || translate('SegmentEditor_CustomSegment');
    });

    // the period line of the canvas opens the period selector of the page
    function openPeriodSelector() {
      const toggle = document.querySelector<HTMLElement>('#periodString #date');
      if (toggle) {
        toggle.click();
        toggle.focus();
      }
    }

    const scopeLabel = (scope: string) => translate(`DataExplorer_Scope_${scope}`);

    // the catalogue can hold several items with the same name, the scope tells them apart
    const dimensionLabels = computed(
      () => distinctNames(Object.values(dimensionsById.value), scopeLabel),
    );

    const metricLabels = computed(
      () => distinctNames(Object.values(metricsById.value), scopeLabel),
    );

    function pickerGroups(
      groups: { category: string, items: PickerItem[] }[],
      labels: Record<string, string>,
      reasonOf: (id: string) => string,
    ): PickerGroup[] {
      return groups.map((group) => ({
        category: group.category,
        items: group.items.map(({ id, name }) => ({
          id,
          name: labels[id] || name,
          reason: reasonOf(id),
        })),
      }));
    }

    const segmentGroups = computed<PickerGroup[]>(() => {
      const items = (catalog.value ? catalog.value.segments : [])
        .map((item) => ({ id: item.definition, name: item.name }));
      return items.length ? [{ category: translate('DataExplorer_SavedSegments'), items }] : [];
    });

    // compatibility of the variables with the rest of the exploration
    const usedDimensions = computed(() => [
      ...(hasRowDimensions(state.visualization) ? state.rows : []),
      ...(hasRowDimensions(state.visualization) && state.column ? [state.column] : []),
      ...state.filters.map((filter) => filter.dimension),
    ]);

    function dimensionName(id: string): string {
      return dimensionLabels.value[id] || id;
    }

    function metricName(id: string): string {
      return metricLabels.value[id] || id;
    }

    function metricReason(id: string): string {
      const metric = metricsById.value[id];
      if (!metric) {
        return translate('DataExplorer_UnknownVariable');
      }
      const conflict = usedDimensions.value
        .map((dimensionId) => dimensionsById.value[dimensionId])
        .find((dimension) => dimension && !metric.allowedScopes.includes(dimension.scope));
      return conflict ? translate('DataExplorer_IncompatibleWithDimension', conflict.name) : '';
    }

    function dimensionReason(id: string): string {
      const dimension = dimensionsById.value[id];
      if (!dimension) {
        return translate('DataExplorer_UnknownVariable');
      }
      const conflict = state.values
        .map((metricId) => metricsById.value[metricId])
        .find((metric) => metric && !metric.allowedScopes.includes(dimension.scope));
      return conflict ? translate('DataExplorer_IncompatibleWithMetric', conflict.name) : '';
    }

    const dimensionGroups = computed(() => pickerGroups(
      catalog.value ? catalog.value.dimensions : [],
      dimensionLabels.value,
      dimensionReason,
    ));

    const metricGroups = computed(() => pickerGroups(
      catalog.value ? catalog.value.metrics : [],
      metricLabels.value,
      metricReason,
    ));

    const dimensionViews = computed<VariableView[]>(() => state.variables.dimensions.map((id) => ({
      id,
      name: dimensionName(id),
      reason: dimensionReason(id),
      used: usedDimensions.value.includes(id),
    })));

    const metricViews = computed<VariableView[]>(() => state.variables.metrics.map((id) => ({
      id,
      name: metricName(id),
      reason: metricReason(id),
      used: state.values.includes(id),
    })));

    const segmentViews = computed<VariableView[]>(() => state.variables.segments.map((id) => ({
      id,
      name: segmentNames.value[id] || id,
      reason: '',
      used: state.segment === id,
    })));

    const rowItems = computed<ZoneItem[]>(() => state.rows.map((id) => ({
      id, name: dimensionName(id), reason: dimensionReason(id),
    })));

    const columnItems = computed<ZoneItem[]>(() => (state.column ? [{
      id: state.column, name: dimensionName(state.column), reason: dimensionReason(state.column),
    }] : []));

    const valueItems = computed<ZoneItem[]>(() => state.values.map((id) => ({
      id, name: metricName(id), reason: metricReason(id),
    })));

    const segmentItems = computed<ZoneItem[]>(() => (state.segment ? [{
      id: state.segment, name: segmentNames.value[state.segment] || state.segment, reason: '',
    }] : []));

    const filterViews = computed<FilterView[]>(() => state.filters.map((filter) => ({
      filter,
      name: dimensionName(filter.dimension),
      reason: dimensionReason(filter.dimension),
    })));

    // what the canvas needs before a run
    const hint = computed(() => {
      if (!state.values.length) {
        return translate('DataExplorer_HintAddMetric');
      }
      if (hasRowDimensions(state.visualization) && !state.rows.length) {
        return translate('DataExplorer_HintAddRow');
      }
      const withRows = hasRowDimensions(state.visualization);
      const sent: { reason: string }[] = [
        ...(withRows ? [...rowItems.value, ...columnItems.value] : []),
        ...(state.visualization === 'kpicard' ? valueItems.value.slice(0, 1) : valueItems.value),
        ...filterViews.value.filter((view) => isFilterComplete(view.filter)),
      ];
      const blocked = sent.some((item) => !!item.reason);
      if (blocked) {
        return translate('DataExplorer_HintIncompatible');
      }
      return '';
    });

    const canvasTitle = computed(() => (editing.value && editing.value.type === 'visual'
      ? editing.value.name
      : translate('DataExplorer_Canvas')));

    const isRunnable = computed(() => !!catalog.value && hint.value === '');

    const definition = computed<VisualDefinition>(() => toDefinition(state));

    // what the canvas requests: the bar and pie charts read the first row dimension only
    const requestDefinition = computed<VisualDefinition>(() => {
      const def = definition.value;
      if (state.visualization === 'bar' || state.visualization === 'pie') {
        return { ...def, rows: def.rows.slice(0, 1), column: null };
      }
      return def;
    });

    // results
    const resultDimensionNames = computed(() => requestDefinition.value.rows.map(dimensionName));

    const resultColumnsList = computed<ResultColumn[]>(() => resultColumns(
      tableRows.value,
      requestDefinition.value.metrics,
    ));

    const resultTree = computed<TreeRow[]>(() => treeRows(
      tableRows.value,
      requestDefinition.value.rows.length,
      resultColumnsList.value,
    ));

    const resultRows = computed<ResultRow[]>(() => flattenRows(
      tableRows.value,
      requestDefinition.value.rows.length,
      resultColumnsList.value,
    ));

    // an "Others" slice is the sum of its rows: never for a distinct count that the rows share
    function addsUpAcrossRows(metricId: string): boolean {
      const metric = metricsById.value[metricId];
      const dimension = dimensionsById.value[requestDefinition.value.rows[0]];
      if (!metric || !dimension) {
        return true;
      }
      return (metric.additiveScopes || []).indexOf(dimension.scope) !== -1;
    }

    const slice = computed<ChartSlice|null>(() => (state.values.length
      ? chartSlice(
        tableRows.value,
        state.values[0],
        CHART_TOP_ROWS,
        translate('General_Others'),
        addsUpAcrossRows(state.values[0]),
      )
      : null));

    const barSeries = computed(() => (slice.value
      ? chartSeries(
        tableRows.value,
        slice.value,
        state.values,
        translate('General_Others'),
        addsUpAcrossRows,
      ).map((line) => ({ name: metricNames.value[line.metric] || line.metric, data: line.data }))
      : []));

    const evolution = computed(() => evolutionResult.value);

    const kpi = computed(() => {
      if (!kpiResult.value || !state.values.length) {
        return null;
      }
      const metric = state.values[0];
      const { value } = kpiResult.value;
      const [firstSeries] = kpiResult.value.evolution.series;
      const points = firstSeries ? firstSeries.data : [];
      let evolutionView = null;
      if (period.value !== 'range' && points.length >= 2) {
        const current = points[points.length - 1];
        const previous = points[points.length - 2];
        evolutionView = {
          percent: NumberFormatter.calculateAndFormatEvolution(current, previous),
          trend: Math.sign(current - previous),
          tooltip: translate(
            'DataExplorer_KpiPreviousPeriod',
            NumberFormatter.formatNumber(previous),
          ),
        };
      }
      return {
        metricName: metricName(metric),
        value: value === null
          ? ''
          : NumberFormatter.formatNumber(value, Number.isInteger(value) ? 0 : 2),
        evolution: evolutionView,
        sparkline: points,
        sparklineLabels: kpiResult.value.evolution.labels,
      };
    });

    // runs
    let runTimer: ReturnType<typeof setTimeout>|null = null;
    let runController: AbortController|null = null;
    let runSequence = 0;

    function runVisual(def: VisualDefinition, controller: AbortController): Promise<unknown> {
      return AjaxHelper.post(
        {
          method: 'DataExplorer.runVisual',
          idSite,
          period: period.value,
          date: date.value,
          expanded: 1,
          filter_limit: -1,
          format_metrics: 0,
        },
        { definition: JSON.stringify(def) },
        { createErrorNotification: false, abortController: controller },
      );
    }

    function run() {
      if (runController) {
        runController.abort();
        runController = null;
      }
      runSequence += 1;
      const sequence = runSequence;

      if (!isRunnable.value) {
        loading.value = false;
        runError.value = '';
        return;
      }

      const controller = new AbortController();
      runController = controller;
      loading.value = true;
      runError.value = '';

      const def = requestDefinition.value;
      let request: Promise<void>;

      if (def.report_type === 'evolution') {
        request = runVisual(def, controller).then((result) => {
          if (sequence === runSequence) {
            evolutionResult.value = evolutionSeries(result, def.metrics);
          }
        });
      } else if (def.report_type === 'kpicard') {
        request = Promise.all([
          runVisual(def, controller),
          runVisual({ ...def, report_type: 'evolution' }, controller),
        ]).then(([value, series]) => {
          if (sequence === runSequence) {
            kpiResult.value = {
              value: totalValue(value, def.metrics[0]),
              evolution: evolutionSeries(series, def.metrics.slice(0, 1)),
            };
          }
        });
      } else {
        request = runVisual(def, controller).then((result) => {
          if (sequence === runSequence) {
            tableRows.value = Array.isArray(result) ? result as ApiRow[] : [];
          }
        });
      }

      request.catch((e: Error) => {
        if (sequence === runSequence) {
          runError.value = e.message;
        }
      }).finally(() => {
        if (sequence === runSequence) {
          loading.value = false;
          runController = null;
        }
      });
    }

    function scheduleRun() {
      if (runTimer) {
        clearTimeout(runTimer);
      }
      runTimer = setTimeout(() => {
        runTimer = null;
        run();
      }, RUN_DELAY_MS);
    }

    // URL hash
    let lastEncoded = '';

    function syncHash() {
      if (!Matomo.helper.isReportingPage()) {
        return;
      }
      const encoded = encode(state);
      const current = MatomoUrl.hashParsed.value;
      if (encoded === lastEncoded && current[HASH_PARAM] === encoded) {
        return;
      }
      lastEncoded = encoded;
      const params: Record<string, unknown> = { ...current, [HASH_PARAM]: encoded };
      if (editing.value) {
        params[REPORT_HASH_PARAM] = String(editing.value.idreport);
      } else {
        delete params[REPORT_HASH_PARAM];
      }
      MatomoUrl.updateHash(params);
    }

    function applyState(next: ExplorationState) {
      Object.assign(state, catalog.value ? clampToLimits(next, catalog.value.limits) : next);
      lastEncoded = encode(state);
    }

    function openReport(report: SavedReport, keepState: boolean) {
      editing.value = report;
      if (report.type === 'sql') {
        sql.value = report.definition.sql || '';
        sqlOpened.value = true;
        activeTab.value = 'sql';
        return;
      }
      if (!keepState) {
        applyState(fromDefinition(report.definition, report.report_type));
      }
      activeTab.value = 'explore';
    }

    const openableReports = computed(() => reports.value.filter(
      (report) => report.type === 'visual' || props.isSuperUser,
    ));

    function readHash() {
      const hash = MatomoUrl.parsed.value;
      const idReport = parseInt(String(hash[REPORT_HASH_PARAM] || ''), 10);
      const encoded = typeof hash[HASH_PARAM] === 'string' ? hash[HASH_PARAM] as string : '';

      if (idReport > 0) {
        const report = openableReports.value.find((item) => item.idreport === idReport);
        if (report && (!editing.value || editing.value.idreport !== idReport)) {
          openReport(report, !!encoded);
        }
      } else if (editing.value) {
        editing.value = null;
      }

      if (encoded && encoded !== lastEncoded) {
        applyState(decode(encoded));
      }
    }

    function loadReports(): Promise<void> {
      return AjaxHelper.fetch<SavedReport[]>(
        { method: 'DataExplorer.getReports', idSite, filter_limit: -1 },
        { createErrorNotification: false },
      ).then((result) => {
        reports.value = Array.isArray(result) ? result : [];
      }).catch(() => {
        reports.value = [];
      });
    }

    const customReportOpen = ref(false);

    function onCustomReportCreated(event: { idCustomReport: number, name: string }) {
      noticeContext.value = 'success';
      notice.value = translate('DataExplorer_CustomReportCreated', event.name);
      ReportingMenuStore.reloadMenuItems();
    }

    function onCustomReportFailed(message: string) {
      noticeContext.value = 'error';
      notice.value = message;
    }

    const reportOptions = computed(() => [
      { key: '', value: translate('DataExplorer_NewExploration') },
      ...openableReports.value.map((report) => ({
        key: String(report.idreport),
        value: report.name,
      })),
    ]);

    function onPickReport(value: string) {
      const id = parseInt(String(value), 10);
      const report = reports.value.find((item) => item.idreport === id) || null;
      notice.value = '';
      if (!report) {
        editing.value = null;
        applyState(defaultState());
        sql.value = '';
        syncHash();
        scheduleRun();
        return;
      }
      openReport(report, false);
      lastEncoded = '';
      syncHash();
    }

    function selectTab(tab: Tab) {
      activeTab.value = tab;
      if (tab === 'sql') {
        sqlOpened.value = true;
      }
    }

    // variables and drop zones
    function addVariable(kind: VariableKind, id: string) {
      let list = state.variables.segments;
      if (kind === 'dimension') {
        list = state.variables.dimensions;
      } else if (kind === 'metric') {
        list = state.variables.metrics;
      }
      if (!list.includes(id)) {
        list.push(id);
      }
    }

    function removeVariable(kind: VariableKind, id: string) {
      if (kind === 'dimension') {
        state.variables.dimensions = state.variables.dimensions.filter((item) => item !== id);
        state.rows = state.rows.filter((item) => item !== id);
        if (state.column === id) {
          state.column = null;
        }
        state.filters = state.filters.filter((filter) => filter.dimension !== id);
      } else if (kind === 'metric') {
        state.variables.metrics = state.variables.metrics.filter((item) => item !== id);
        state.values = state.values.filter((item) => item !== id);
      } else {
        state.variables.segments = state.variables.segments.filter((item) => item !== id);
        if (state.segment === id) {
          state.segment = '';
        }
      }
    }

    // incompatible items are refused the same way whether they are dropped, double clicked or
    // picked in a zone menu
    function refusal(zone: Zone, id: string): string {
      if (zone === 'segment') {
        return '';
      }
      return zone === 'values' ? metricReason(id) : dimensionReason(id);
    }

    function addToZone(zone: Zone, id: string) {
      const limits = catalog.value ? catalog.value.limits : null;
      if (!limits) {
        return;
      }
      const reason = refusal(zone, id);
      if (reason) {
        noticeContext.value = 'warning';
        notice.value = translate(
          'DataExplorer_ItemRefused',
          zone === 'values' ? metricName(id) : dimensionName(id),
          reason,
        );
        return;
      }
      if (noticeContext.value === 'warning') {
        notice.value = '';
      }
      if (zone === 'rows') {
        if (!state.rows.includes(id) && state.rows.length < limits.maxDimensions) {
          state.rows.push(id);
          if (state.column === id) {
            state.column = null;
          }
        }
      } else if (zone === 'column') {
        state.column = id;
        state.rows = state.rows.filter((item) => item !== id);
      } else if (zone === 'values') {
        if (!state.values.includes(id) && state.values.length < limits.maxMetrics) {
          state.values.push(id);
        }
      } else if (zone === 'filters') {
        state.filters.push({ dimension: id, operator: 'equals', value: '' });
      } else {
        state.segment = id;
      }
    }

    function removeFromZone(zone: Zone, id: string) {
      if (zone === 'rows') {
        state.rows = state.rows.filter((item) => item !== id);
      } else if (zone === 'column') {
        state.column = null;
      } else if (zone === 'values') {
        state.values = state.values.filter((item) => item !== id);
      } else if (zone === 'segment') {
        state.segment = '';
      }
    }

    function useVariable(kind: VariableKind, id: string) {
      if (kind === 'dimension') {
        if (hasRowDimensions(state.visualization)) {
          addToZone('rows', id);
        } else {
          addToZone('filters', id);
        }
      } else if (kind === 'metric') {
        addToZone('values', id);
      } else {
        addToZone('segment', id);
      }
    }

    function updateFilter(index: number, filter: Filter) {
      state.filters.splice(index, 1, filter);
    }

    function removeFilter(index: number) {
      state.filters.splice(index, 1);
    }

    // saving
    const saveReportType = computed(() => reportTypeOf(state.visualization));

    const saveDefinition = computed<Record<string, unknown>>(() => {
      if (saveType.value === 'sql') {
        return { sql: sql.value };
      }
      const def = { ...definition.value } as Record<string, unknown>;
      delete def.report_type;
      def.chart = state.visualization === 'bar' || state.visualization === 'pie'
        ? state.visualization
        : '';
      return def;
    });

    function openSave(type: 'visual'|'sql') {
      notice.value = '';
      saveType.value = type;
      saveOpen.value = true;
    }

    function onSaved(event: { idReport: number, isNew: boolean }) {
      noticeContext.value = 'success';
      notice.value = event.isNew
        ? translate('DataExplorer_ReportCreated')
        : translate('DataExplorer_ReportUpdated');
      // the reporting menu is loaded once per page, a new report page would wait for a reload
      ReportingMenuStore.reloadMenuItems();
      loadReports().then(() => {
        const report = reports.value.find((item) => item.idreport === event.idReport);
        if (report) {
          editing.value = report;
          lastEncoded = '';
          syncHash();
        }
      });
    }

    function onSaveClosed() {
      nextTick(() => {
        const trigger = saveType.value === 'sql'
          ? document.querySelector('.dataExplorerSql__save') as HTMLElement|null
          : saveButton.value;
        if (trigger) {
          trigger.focus();
        }
      });
    }

    // lifecycle
    const stopWatchers: (() => void)[] = [];

    // A new period, date or segment makes the reporting page render the Explore page again, which
    // reloads the widget and remounts the explorer. The explorer already follows those URL
    // parameters, so it takes over the reload of its own page and only runs the query again.
    function keepPageOnUrlChange(
      args: { category?: string, subcategory?: string, promise?: unknown },
    ) {
      const parsed = MatomoUrl.parsed.value;
      if (args.category === parsed.category
        && args.subcategory === parsed.subcategory
        && args.subcategory === EXPLORE_SUBCATEGORY
      ) {
        args.promise = Promise.resolve();
      }
    }

    onMounted(() => {
      Matomo.on('ReportingPage.loadPage', keepPageOnUrlChange);

      Promise.all([
        AjaxHelper.fetch<Catalog>(
          { method: 'DataExplorer.getCatalog', idSite },
          { createErrorNotification: false },
        ).then((result) => {
          catalog.value = result;
        }).catch((e: Error) => {
          catalogError.value = e.message;
        }),
        loadReports(),
      ]).then(() => {
        readHash();

        stopWatchers.push(watch(
          () => [JSON.stringify(requestDefinition.value), period.value, date.value, segment.value],
          () => scheduleRun(),
        ));
        stopWatchers.push(watch(() => JSON.stringify(state), () => syncHash()));
        stopWatchers.push(watch(
          () => [MatomoUrl.parsed.value[HASH_PARAM], MatomoUrl.parsed.value[REPORT_HASH_PARAM]],
          () => readHash(),
        ));

        run();
      });
    });

    onBeforeUnmount(() => {
      Matomo.off('ReportingPage.loadPage', keepPageOnUrlChange);
      stopWatchers.forEach((stop) => stop());
      if (runTimer) {
        clearTimeout(runTimer);
      }
      if (runController) {
        runController.abort();
      }
    });

    return {
      definition,
      customReportOpen,
      onCustomReportCreated,
      onCustomReportFailed,
      reportOptions,
      resultTree,
      periodText,
      segmentText,
      openPeriodSelector,
      idSite,
      period,
      date,
      catalog,
      catalogError,
      editing,
      state,
      sql,
      activeTab,
      sqlOpened,
      tabs,
      loading,
      runError,
      notice,
      noticeContext,
      saveOpen,
      saveType,
      saveButton,
      canSaveAnything,
      dimensionGroups,
      metricGroups,
      segmentGroups,
      dimensionViews,
      metricViews,
      segmentViews,
      rowItems,
      columnItems,
      valueItems,
      segmentItems,
      filterViews,
      hint,
      isRunnable,
      canvasTitle,
      metricNames,
      resultDimensionNames,
      resultColumnsList,
      resultRows,
      slice,
      barSeries,
      evolution,
      kpi,
      openableReports,
      onPickReport,
      selectTab,
      addVariable,
      removeVariable,
      addToZone,
      removeFromZone,
      useVariable,
      updateFilter,
      removeFilter,
      saveReportType,
      saveDefinition,
      openSave,
      onSaved,
      onSaveClosed,
    };
  },
});
</script>
