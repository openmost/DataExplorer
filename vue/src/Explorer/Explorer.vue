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
        class="dataExplorer__reports"
      >
        <span class="dataExplorer__reportsLabel">{{ translate('DataExplorer_OpenReport') }}</span>
        <ReportPicker
          :model-value="pickedReport"
          :groups="reportGroups"
          :new-label="translate('DataExplorer_NewExploration')"
          :label="translate('DataExplorer_OpenReport')"
          @update:model-value="onPickReport($event)"
        />
        <span
          v-if="isModified"
          class="dataExplorer__modified"
        >{{ translate('DataExplorer_Modified') }}</span>
      </div>

      <button
        v-if="canSaveAnything && activeTab === 'explore'"
        ref="saveButton"
        type="button"
        class="btn dataExplorer__save"
        :class="{ 'btn-outline': !!updatableCustomReport }"
        :disabled="!isRunnable"
        @click="openSave('visual')"
      >{{ isEditingVisual
        ? translate('DataExplorer_EditReport')
        : translate('DataExplorer_SaveReport') }}</button>
      <button
        v-if="customReports && activeTab === 'explore'"
        type="button"
        class="btn dataExplorer__saveCustomReport"
        :class="{ 'btn-outline': !updatableCustomReport }"
        :disabled="!isRunnable"
        @click="customReportOpen = true"
      >{{ translate(updatableCustomReport
        ? 'DataExplorer_UpdateCustomReport'
        : 'DataExplorer_SaveAsCustomReport') }}</button>
    </div>

    <div aria-live="polite">
      <Notification
        v-if="notice"
        :context="noticeContext"
        :noclear="true"
      >{{ notice }}<template v-if="noticeLink && noticeLink.notice === notice">
        <a :href="noticeLink.url">{{ noticeLink.label }}</a></template></Notification>
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
          :row-items="rowZoneItems"
          :column-items="columnItems"
          :value-items="valueZoneItems"
          :segment-items="segmentItems"
          :filters="filterViews"
          :dimension-options="dimensionViews"
          :metric-options="metricViews"
          :segment-options="segmentViews"
          @set-visualization="state.visualization = $event"
          @add="addToZone"
          @remove="removeFromZone"
          :table-view="state.tableView"
          @set-table-view="state.tableView = $event"
          @add-filter-to-group="addFilterToGroup"
          @move-filter="moveFilter"
          @update-filter="updateFilter"
          @remove-filter="removeFilter"
        />
        <ExplorerCanvas
          :title="canvasTitle"
          :period-text="periodText"
          :period-dates="periodDates"
          :segment-text="segmentText"
          @open-period-selector="openPeriodSelector"
          :visualization="state.visualization"
          :hint="hint"
          :widget-params="previewParams"
          @view-changed="onCanvasView"
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
        :editing="isEditingSql"
        :visualization="sqlVisualization"
        @set-visualization="sqlVisualization = $event"
        @save="openSave('sql')"
      />
    </div>

    <MatomoDialog
      v-model="leaveConfirmOpen"
      @yes="confirmLeave"
    >
      <div class="ui-confirm">
        <h2>{{ translate('DataExplorer_LeaveUnsavedConfirm') }}</h2>
        <!-- the yes/no roles are how MatomoDialog finds its buttons -->
        <!-- eslint-disable vuejs-accessibility/aria-role -->
        <input
          role="yes"
          type="button"
          :value="translate('General_Yes')"
        />
        <input
          role="no"
          type="button"
          :value="translate('General_No')"
        />
        <!-- eslint-enable vuejs-accessibility/aria-role -->
      </div>
    </MatomoDialog>
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
      :suggestion="saveSuggestion"
      :modified="isModified"
      @saved="onSaved"
      @closed="onSaveClosed"
    />
    <CustomReportDialog
      v-if="customReports"
      v-model="customReportOpen"
      :definition="definition"
      :default-name="editing ? editing.name : ''"
      :default-description="editing ? String(editing.definition.description || '') : ''"
      :custom-report="updatableCustomReport"
      :modified="isModified"
      @created="onCustomReportCreated"
      @updated="onCustomReportUpdated"
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
  MatomoDialog,
  MatomoUrl,
  Notification,
  Periods,
  ReportingMenuStore,
  translate,
} from 'CoreHome';
import ExplorerCanvas from './Canvas.vue';
import TabSettings from './TabSettings.vue';
import ReportPicker from './ReportPicker.vue';
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
  VISUALIZATION_BY_VIEW,
  VIEW_BY_VISUALIZATION,
  toDefinition,
} from './explorationState';
import { distinctNames } from './catalogNames';
import type {
  ImportedCustomReport,
  ReportPickerGroup,
  SaveSuggestion,
  UnsupportedEntry,
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
  Visualization,
  WritableSite,
  ZoneItem,
} from '../types';

const RUN_DELAY_MS = 300;

type Tab = 'explore' | 'sql';
type Zone = 'rows' | 'column' | 'values' | 'filters' | 'segment';

/**
 * The explorer of the "Data Explorer > Explore" page: free-form exploration in three columns
 * (variables, tab settings, canvas) like GA4 Explorations, the raw SQL tab for super users and
 * the save dialog. The exploration lives in the URL hash so a reload or a shared link restores
 * it; a saved report is reopened with idReport in the hash.
 */
// the chart of the Raw SQL result in the URL, beside the exploration
const SQL_VIEW_PARAM = 'sqlView';

// period of a new visit of the explorer: the last 28 complete days, today excluded, like GA4
const DEFAULT_PERIOD = 'range';
const DEFAULT_DATE = 'previous28';

export default defineComponent({
  name: 'Explorer',
  components: {
    ActivityIndicator,
    ExplorerCanvas,
    MatomoDialog,
    Notification,
    RawSqlEditor,
    SaveDialog,
    CustomReportDialog,
    ReportPicker,
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
    // the chart of the Raw SQL result, saved with the query like the one of an exploration
    const sqlVisualization = ref<Visualization>('table');
    const activeTab = ref<Tab>('explore');

    // what an opened report computes and shows, to tell whether the exploration changed since
    const snapshotOf = (value: ExplorationState) => JSON.stringify({
      definition: toDefinition(value),
      visualization: value.visualization,
    });
    // '' when nothing saved is open
    const openedSnapshot = ref('');
    // the query of an opened raw SQL report, null when none is open
    const openedSql = ref<string|null>(null);
    const openedSqlVisualization = ref<Visualization>('table');
    const isModified = computed(() => {
      if (openedSql.value !== null && editing.value && editing.value.type === 'sql') {
        return sql.value.trim() !== openedSql.value.trim()
          || sqlVisualization.value !== openedSqlVisualization.value;
      }
      return openedSnapshot.value !== '' && snapshotOf(state) !== openedSnapshot.value;
    });

    const isEditingVisual = computed(() => !!editing.value && editing.value.editable
      && editing.value.type === 'visual');
    const isEditingSql = computed(() => !!editing.value && editing.value.editable
      && editing.value.type === 'sql');

    // the page of a custom report in the reporting menu: Custom Reports names its own page after
    // the report id in its default category, after the report name elsewhere
    function customReportUrl(report: ImportedCustomReport): string {
      let { subcategory } = report;
      if (subcategory === '') {
        subcategory = report.category === 'CustomReports_CustomReports'
          ? String(report.idCustomReport)
          : report.name;
      }
      const hash = MatomoUrl.stringify({
        ...MatomoUrl.hashParsed.value,
        category: report.category,
        subcategory,
        [HASH_PARAM]: undefined,
        [REPORT_HASH_PARAM]: undefined,
      });
      return `?${MatomoUrl.stringify(MatomoUrl.urlParsed.value)}#?${hash}`;
    }
    const sqlOpened = ref(false);

    // what the canvas loads: the native visualization of the exploration, null while it cannot run
    const previewParams = ref<Record<string, string>|null>(null);

    const notice = ref('');
    // what the opened custom report has that the explorer cannot read, shown struck through
    const importedUnsupported = ref<UnsupportedEntry[]>([]);
    // a link shown with one notice only: any other notice replaces it
    const noticeLink = ref<{ notice: string, url: string, label: string }|null>(null);
    const noticeContext = ref<'success'|'info'|'warning'|'error'>('success');

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

    const periodDates = computed(() => {
      try {
        return Periods.parse(period.value, date.value).getPrettyString();
      } catch (e) {
        return `${period.value} ${date.value}`;
      }
    });

    // a keyword range reads as its name, like the GA4 date picker; its dates stay in the tooltip
    const periodText = computed(() => {
      const keyword = period.value === 'range' ? /^(last|previous)(\d+)$/.exec(date.value) : null;
      if (!keyword) {
        return periodDates.value;
      }
      return keyword[1] === 'previous'
        ? translate('General_LastDaysShort', keyword[2])
        : translate('General_LastDays', keyword[2]);
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

    const KNOWN_SCOPES = ['visit', 'action', 'conversion'];
    // the scope of a plugin table is its name: log_media reads "media"
    const scopeLabel = (scope: string) => (KNOWN_SCOPES.includes(scope)
      ? translate(`DataExplorer_Scope_${scope}`)
      : scope.replace(/^log_/, '').replace(/_/g, ' '));

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

    // dimensions read by the exploration, for the per row counting hint of the metrics
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
      return metricsById.value[id] ? '' : translate('DataExplorer_UnknownVariable');
    }

    function dimensionReason(id: string): string {
      return dimensionsById.value[id] ? '' : translate('DataExplorer_UnknownVariable');
    }

    // a visit has several actions and conversions, an action shares its visit with the
    // conversions and the other way round: under such a dimension the metric counts each of
    // its rows once in every row of the report
    const perRowHints: Record<string, string> = {
      visit: 'DataExplorer_MetricPerVisitHint',
      action: 'DataExplorer_MetricPerActionHint',
      conversion: 'DataExplorer_MetricPerConversionHint',
    };

    function metricHint(id: string): string {
      const metric = metricsById.value[id];
      if (!metric) {
        return '';
      }
      const repeats = usedDimensions.value
        .map((dimensionId) => dimensionsById.value[dimensionId])
        .some((dimension) => dimension && (metric.repeatedBy || []).includes(dimension.scope));
      return repeats ? translate(perRowHints[metric.scope] || 'DataExplorer_MetricPerRowHint') : '';
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
      id, name: metricName(id), reason: metricReason(id), hint: metricHint(id),
    })));

    const missingOf = (kind: UnsupportedEntry['kind']) => importedUnsupported.value
      .filter((entry) => entry.kind === kind)
      .map((entry) => ({
        id: entry.id, name: entry.name, reason: entry.reason, missing: true,
      }));
    const rowZoneItems = computed<ZoneItem[]>(() => [...rowItems.value, ...missingOf('dimension')]);
    const valueZoneItems = computed<ZoneItem[]>(() => [...valueItems.value, ...missingOf('metric')]);

    const segmentItems = computed<ZoneItem[]>(() => (state.segment ? [{
      id: state.segment, name: segmentNames.value[state.segment] || state.segment, reason: '',
    }] : []));

    const filterViews = computed<FilterView[]>(() => state.filters.map((filter, index) => ({
      filter,
      index,
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
        return {
          ...def,
          rows: def.rows.slice(0, 1),
          column: null,
          chart: state.visualization,
        };
      }
      return def;
    });

    // the view and layout are sent explicitly: core would otherwise reuse the view this user last
    // picked in a table footer, and Report settings would no longer describe the canvas
    function viewParams(): Record<string, string> {
      const params: Record<string, string> = {
        viewDataTable: VIEW_BY_VISUALIZATION[state.visualization],
      };
      if (state.visualization === 'table' && state.rows.length > 1) {
        const flat = state.tableView === 'flat';
        params.flat = flat ? '1' : '0';
        params.show_dimensions = flat ? '1' : '0';
      }
      return params;
    }

    function run() {
      previewParams.value = isRunnable.value
        ? {
          module: 'DataExplorer',
          action: 'preview',
          definition: JSON.stringify(requestDefinition.value),
          period: period.value,
          date: date.value,
          ...viewParams(),
        }
        : null;
    }

    // a view or layout picked in the canvas footer becomes the one of the exploration
    function onCanvasView(event: { viewDataTable: string, flat: boolean }) {
      const visualization = VISUALIZATION_BY_VIEW[event.viewDataTable];
      if (visualization && visualization !== state.visualization) {
        state.visualization = visualization;
      }
      if (state.visualization === 'table' && state.rows.length > 1) {
        const tableView = event.flat ? 'flat' : 'hierarchical';
        if (tableView !== state.tableView) {
          state.tableView = tableView;
        }
      }
    }

    let runTimer: ReturnType<typeof setTimeout>|null = null;

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
      if (encoded === lastEncoded && current[HASH_PARAM] === encoded
        && (current[SQL_VIEW_PARAM] || 'table') === sqlVisualization.value) {
        return;
      }
      lastEncoded = encoded;
      const params: Record<string, unknown> = { ...current, [HASH_PARAM]: encoded };
      if (sqlVisualization.value !== 'table') {
        params[SQL_VIEW_PARAM] = sqlVisualization.value;
      } else {
        delete params[SQL_VIEW_PARAM];
      }
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
      openedSql.value = null;
      if (report.type === 'sql') {
        sql.value = report.definition.sql || '';
        sqlVisualization.value = fromDefinition(
          { chart: report.definition.chart },
          report.report_type,
        ).visualization;
        openedSqlVisualization.value = sqlVisualization.value;
        openedSql.value = sql.value;
        openedSnapshot.value = '';
        sqlOpened.value = true;
        activeTab.value = 'sql';
        return;
      }
      if (!keepState) {
        applyState(fromDefinition(report.definition, report.report_type));
      }
      openedSnapshot.value = snapshotOf(fromDefinition(report.definition, report.report_type));
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
        openedSnapshot.value = '';
        openedSql.value = null;
      }

      if (encoded && encoded !== lastEncoded) {
        applyState(decode(encoded));
      }
      const sqlView = String(hash[SQL_VIEW_PARAM] || 'table') as Visualization;
      const known = !!VIEW_BY_VISUALIZATION[sqlView];
      if (known && sqlView !== sqlVisualization.value) {
        sqlVisualization.value = sqlView;
      }
    }

    function loadReports(): Promise<void> {
      return AjaxHelper.fetch<SavedReport[]>(
        { method: 'DataExplorer.getReports', idSite, filter_limit: -1 },
        { createErrorNotification: false, abortable: false },
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

    // custom reports open as a new exploration, their key prefixed so it never matches a report id
    const CUSTOM_REPORT_KEY = 'customReport-';
    const customReportList = ref<{ idcustomreport: number, name: string, place?: string }[]>([]);
    const importedCustomReport = ref<ImportedCustomReport|null>(null);

    // the custom report button updates the opened custom report when this user may edit it
    const updatableCustomReport = computed(() => (
      !editing.value && importedCustomReport.value && importedCustomReport.value.editable
        ? importedCustomReport.value
        : null));

    // saved as an exploration, an opened custom report keeps its name, description and place
    const saveSuggestion = computed<SaveSuggestion|null>(() => {
      const report = importedCustomReport.value;
      return !editing.value && report ? {
        name: report.name,
        description: report.description,
        category: report.category,
        subcategory: report.subcategory,
      } : null;
    });

    function loadCustomReports(): Promise<void> {
      if (!props.customReports) {
        return Promise.resolve();
      }
      return AjaxHelper.fetch<{ idcustomreport: number, name: string, place?: string }[]>(
        { method: 'DataExplorer.getCustomReports', idSite, filter_limit: -1 },
        { createErrorNotification: false, abortable: false },
      ).then((result) => {
        customReportList.value = Array.isArray(result) ? result : [];
      }).catch(() => {
        customReportList.value = [];
      });
    }

    function onCustomReportUpdated(event: {
      name: string, description: string, category: string, subcategory: string,
    }) {
      if (importedCustomReport.value) {
        importedCustomReport.value = { ...importedCustomReport.value, ...event };
      }
      noticeContext.value = 'success';
      notice.value = translate('DataExplorer_CustomReportUpdated', event.name);
      noticeLink.value = importedCustomReport.value
        ? {
          notice: notice.value,
          url: customReportUrl(importedCustomReport.value),
          label: translate('DataExplorer_OpenCustomReport'),
        }
        : null;
      openedSnapshot.value = snapshotOf(state);
      ReportingMenuStore.reloadMenuItems();
      loadCustomReports();
    }

    const pickedReport = computed(() => {
      if (editing.value) {
        return String(editing.value.idreport);
      }
      return importedCustomReport.value
        ? `${CUSTOM_REPORT_KEY}${importedCustomReport.value.idCustomReport}`
        : '';
    });

    // without Custom Reports the explorations are the only group, and need no title
    const reportGroups = computed<ReportPickerGroup[]>(() => [
      {
        label: props.customReports ? translate('DataExplorer_ExplorationsGroup') : '',
        items: openableReports.value.map((report) => ({
          key: String(report.idreport),
          name: report.name,
          place: report.place || '',
        })),
      },
      {
        label: translate('DataExplorer_CustomReportsGroup'),
        items: customReportList.value.map((report) => ({
          key: `${CUSTOM_REPORT_KEY}${report.idcustomreport}`,
          name: report.name,
          place: report.place || '',
        })),
      },
    ]);

    function openCustomReport(idCustomReport: number) {
      const report = customReportList.value.find((item) => item.idcustomreport === idCustomReport);
      AjaxHelper.fetch<{
        definition: VisualDefinition,
        unsupported: string[],
        unsupportedEntries?: UnsupportedEntry[],
        customReport: ImportedCustomReport,
      }>(
        { method: 'DataExplorer.getCustomReportDefinition', idSite, idCustomReport },
        { createErrorNotification: false },
      ).then((result) => {
        editing.value = null;
        importedCustomReport.value = result.customReport;
        importedUnsupported.value = result.unsupportedEntries || [];
        activeTab.value = 'explore';
        applyState(fromDefinition(result.definition, result.definition.report_type));
        openedSnapshot.value = snapshotOf(state);
        noticeContext.value = result.unsupported.length ? 'warning' : 'info';
        notice.value = [
          translate(
            result.customReport.editable
              ? 'DataExplorer_CustomReportOpened'
              : 'DataExplorer_CustomReportOpenedReadOnly',
            report ? report.name : String(idCustomReport),
          ),
          result.unsupported.length ? translate('DataExplorer_CustomReportNotOpenedHint') : '',
        ].filter((text) => text !== '').join(' ');
        syncHash();
        scheduleRun();
      }).catch((e: Error) => {
        noticeContext.value = 'error';
        notice.value = e.message;
      });
    }

    function pickReport(value: string) {
      notice.value = '';
      if (String(value).startsWith(CUSTOM_REPORT_KEY)) {
        openCustomReport(parseInt(String(value).slice(CUSTOM_REPORT_KEY.length), 10));
        return;
      }
      importedCustomReport.value = null;
      importedUnsupported.value = [];
      const id = parseInt(String(value), 10);
      const report = reports.value.find((item) => item.idreport === id) || null;
      if (!report) {
        editing.value = null;
        openedSnapshot.value = '';
        openedSql.value = null;
        applyState(defaultState());
        sql.value = '';
        sqlVisualization.value = 'table';
        syncHash();
        scheduleRun();
        return;
      }
      openReport(report, false);
      lastEncoded = '';
      syncHash();
    }

    const leaveConfirmOpen = ref(false);
    let pendingPick: string|null = null;

    // another report, or a new exploration, replaces the changes not saved yet: asked first
    function onPickReport(value: string) {
      if (isModified.value && String(value) !== pickedReport.value) {
        pendingPick = String(value);
        leaveConfirmOpen.value = true;
        return;
      }
      pickReport(value);
    }

    function confirmLeave() {
      if (pendingPick !== null) {
        pickReport(pendingPick);
      }
      pendingPick = null;
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
        const group = state.filters.reduce((max, filter) => Math.max(max, filter.group + 1), 0);
        state.filters.push({
          dimension: id, operator: 'equals', value: '', group,
        });
      } else {
        state.segment = id;
      }
    }

    function removeFromZone(zone: Zone, id: string) {
      if (importedUnsupported.value.some((entry) => entry.id === id)) {
        importedUnsupported.value = importedUnsupported.value.filter((entry) => entry.id !== id);
        return;
      }
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

    // in place: the rows of the Filters zone are keyed by their filter object
    function updateFilter(index: number, filter: Filter) {
      Object.assign(state.filters[index], filter);
    }

    function removeFilter(index: number) {
      state.filters.splice(index, 1);
      const order = new Map<number, number>();
      state.filters.forEach((filter) => {
        if (!order.has(filter.group)) {
          order.set(filter.group, order.size);
        }
        filter.group = order.get(filter.group) as number;
      });
    }

    // a filter dragged to another group (OR) or to a new one (AND, group -1), groups renumbered
    function moveFilter(index: number, group: number) {
      const filter = state.filters[index];
      if (!filter) {
        return;
      }
      const target = group < 0
        ? state.filters.reduce((max, item) => Math.max(max, item.group + 1), 0)
        : group;
      if (target === filter.group) {
        return;
      }
      state.filters.splice(index, 1);
      const last = state.filters.map((item) => item.group).lastIndexOf(target);
      const at = last < 0 ? state.filters.length : last + 1;
      state.filters.splice(at, 0, { ...filter, group: target });
      const order = new Map<number, number>();
      state.filters.forEach((item) => {
        if (!order.has(item.group)) {
          order.set(item.group, order.size);
        }
        item.group = order.get(item.group) as number;
      });
    }

    // a filter ORed with those of the group, placed after them
    function addFilterToGroup(group: number, id: string) {
      const filter: Filter = {
        dimension: id, operator: 'equals', value: '', group,
      };
      const last = state.filters.map((item) => item.group).lastIndexOf(group);
      state.filters.splice(last + 1, 0, filter);
    }

    // saving
    const saveReportType = computed(() => reportTypeOf(saveType.value === 'sql'
      ? sqlVisualization.value
      : state.visualization));

    const saveDefinition = computed<Record<string, unknown>>(() => {
      if (saveType.value === 'sql') {
        return {
          sql: sql.value,
          chart: sqlVisualization.value === 'bar' || sqlVisualization.value === 'pie'
            ? sqlVisualization.value
            : '',
        };
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
          importedCustomReport.value = null;
          importedUnsupported.value = [];
          openedSnapshot.value = report.type === 'visual' ? snapshotOf(state) : '';
          openedSql.value = report.type === 'sql' ? sql.value : null;
          openedSqlVisualization.value = sqlVisualization.value;
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

    // entering the explorer (no exploration nor report in the URL: not a reload, not a shared link)
    // looks at the last 28 days; a period picked inside the explorer is then kept
    function startOnLast28Days() {
      const current = MatomoUrl.hashParsed.value;
      if (!Matomo.helper.isReportingPage() || current[HASH_PARAM] || current[REPORT_HASH_PARAM]) {
        return;
      }
      if (current.period === DEFAULT_PERIOD && current.date === DEFAULT_DATE) {
        return;
      }
      MatomoUrl.updateHash({ ...current, period: DEFAULT_PERIOD, date: DEFAULT_DATE });
    }

    onMounted(() => {
      Matomo.on('ReportingPage.loadPage', keepPageOnUrlChange);
      startOnLast28Days();

      Promise.all([
        AjaxHelper.fetch<Catalog>(
          { method: 'DataExplorer.getCatalog', idSite },
          // not tied to the period, so kept when entering switches it to the last 28 days
          { createErrorNotification: false, abortable: false },
        ).then((result) => {
          catalog.value = result;
        }).catch((e: Error) => {
          catalogError.value = e.message;
        }),
        loadReports(),
        loadCustomReports(),
      ]).then(() => {
        readHash();

        stopWatchers.push(watch(
          // a string, not an array: a hash update (a variable added) re-reads the URL, and a new
          // array would count as a change and reload the canvas
          () => JSON.stringify([requestDefinition.value, period.value, date.value, segment.value]),
          () => scheduleRun(),
        ));
        stopWatchers.push(watch(() => JSON.stringify(state), () => syncHash()));
        stopWatchers.push(watch(sqlVisualization, () => syncHash()));
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
    });

    return {
      definition,
      customReportOpen,
      onCustomReportCreated,
      onCustomReportFailed,
      reportGroups,
      pickedReport,
      sqlVisualization,
      onCanvasView,
      updatableCustomReport,
      isModified,
      isEditingVisual,
      isEditingSql,
      leaveConfirmOpen,
      confirmLeave,
      noticeLink,
      rowZoneItems,
      valueZoneItems,
      saveSuggestion,
      onCustomReportUpdated,
      periodText,
      periodDates,
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
      previewParams,
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
      addFilterToGroup,
      moveFilter,
      saveReportType,
      saveDefinition,
      openSave,
      onSaved,
      onSaveClosed,
    };
  },
});
</script>
