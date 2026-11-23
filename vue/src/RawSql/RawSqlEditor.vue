<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerSql">
    <ActivityIndicator :loading="statusLoading" />
    <ContentBlock
      v-if="!statusLoading && !isReady"
      :content-title="translate('DataExplorer_SqlTab')"
    >
      <Notification
        context="warning"
        :noclear="true"
      >{{ status ? status.message : '' }}</Notification>
      <div>
        <a
          class="btn dataExplorerSql__setupLink"
          :href="setupUrl"
        >{{ translate('DataExplorer_SqlOpenSetup') }}</a>
      </div>
    </ContentBlock>

    <div
      v-else-if="isReady"
      class="dataExplorerSql__layout"
    >
      <SchemaBrowser
        class="dataExplorerColumn"
        :schema="schema"
        :loading="schemaLoading"
        @insert="insert"
      />

      <div class="dataExplorerColumn dataExplorerSql__main">
        <h3
          id="dataExplorerSqlEditorLabel"
          class="dataExplorerColumn__title"
        >{{ translate('DataExplorer_SqlEditor') }}</h3>
        <div class="dataExplorerSql__help">{{ translate('DataExplorer_SqlEditorHelp') }}</div>
        <ActivityIndicator :loading="!editorReady" />
        <div
          ref="container"
          class="dataExplorerSql__editor"
        />

        <div
          class="dataExplorerSql__validation"
          aria-live="polite"
        >
          <div
            v-if="validity === 'pending'"
            class="dataExplorerSql__checking"
          >{{ translate('DataExplorer_SqlChecking') }}</div>
          <ul
            v-else-if="problems.length"
            class="dataExplorerSql__problems"
          >
            <li
              v-for="(problem, index) in problems"
              :key="index"
              :class="`is-${problem.severity}`"
            >
              <span
                :class="problem.severity === 'error' ? 'icon-error' : 'icon-warning'"
                aria-hidden="true"
              />
              {{ problem.message }}
            </li>
          </ul>
          <div
            v-else-if="validity === 'valid'"
            class="dataExplorerSql__valid"
          >
            <span class="icon-ok" aria-hidden="true" />
            {{ translate('DataExplorer_SqlValid') }}
          </div>
        </div>

        <div class="dataExplorerSql__actions">
          <button
            type="button"
            class="btn dataExplorerSql__run"
            :disabled="!canRun"
            :title="runTitle"
            @click="run"
          >{{ translate('DataExplorer_SqlRun') }}</button>
          <button
            v-if="canSave"
            type="button"
            class="btn btn-outline dataExplorerSql__save"
            :disabled="validity !== 'valid'"
            @click="$emit('save')"
          >{{ editing
            ? translate('DataExplorer_EditReport')
            : translate('DataExplorer_SaveReport') }}</button>
        </div>

        <div
          v-if="resultParams"
          class="dataExplorerSql__resultBlock"
        >
          <div
            v-if="resultLoaded"
            class="dataExplorerVisualizations dataExplorerSql__visualizations"
            role="group"
            :aria-label="translate('DataExplorer_Visualization')"
          >
            <button
              v-for="option in visualizations"
              :key="option.id"
              type="button"
              class="dataExplorerIconButton dataExplorerVisualizations__button"
              :class="{ 'is-active': option.id === visualization }"
              :aria-pressed="option.id === visualization ? 'true' : 'false'"
              :aria-label="option.label"
              :title="option.label"
              @click="$emit('setVisualization', option.id)"
            >
              <span :class="option.icon" aria-hidden="true" />
            </button>
          </div>
          <p
            v-if="resultLoaded && visualization === 'kpicard'"
            class="dataExplorerSql__hint"
          >{{ translate('DataExplorer_SqlKpiHint') }}</p>
          <div
            ref="resultElement"
            class="dataExplorerSql__resultWidget"
          >
            <WidgetLoader :widget-params="resultParams" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  onBeforeUnmount,
  nextTick,
  onMounted,
  PropType,
  ref,
  watch,
} from 'vue';
import {
  ActivityIndicator,
  AjaxHelper,
  ContentBlock,
  Notification,
  translate,
  WidgetLoader,
} from 'CoreHome';
import SchemaBrowser from './SchemaBrowser.vue';
import { toDiagnostics } from './sqlDiagnostics';
import { runKeymap } from './runKeymap';
import { createCodeTheme } from '../codeTheme';
import {
  availableVisualizations,
  isChart,
  reportTypeOf,
  VISUALIZATION_BY_VIEW,
  VISUALIZATION_ICONS,
  VIEW_BY_VISUALIZATION,
} from '../Explorer/explorationState';
import type { Diagnostic, EditorView } from './codeMirrorModules';
import type { SqlStatus, SqlValidation, Visualization } from '../types';

type CodeMirrorModules = typeof import('./codeMirrorModules');

type Validity = 'empty' | 'pending' | 'valid' | 'invalid';

let modules: Promise<CodeMirrorModules> | null = null;

function loadCodeMirror(): Promise<CodeMirrorModules> {
  if (!modules) {
    modules = import('./codeMirrorModules');
  }
  return modules;
}

/**
 * Raw SQL tab (super users): CodeMirror editor with MySQL highlighting, completion of the allowed
 * tables and columns, live validation by the server guard, the schema of the allowed tables, the
 * result table and its CSV export. Run stays disabled while the query is not valid.
 */
function starterQuery(visitTable: string): string {
  return [
    'SELECT',
    '  DATE(visit_last_action_time) AS day,',
    '  COUNT(*) AS visits,',
    '  SUM(visit_total_actions) AS actions',
    `FROM ${visitTable}`,
    'WHERE idsite = {idsite}',
    '  AND visit_last_action_time BETWEEN {date_start} AND {date_end}',
    'GROUP BY day',
    'ORDER BY day',
  ].join('\n');
}

export default defineComponent({
  name: 'RawSqlEditor',
  components: {
    ActivityIndicator,
    ContentBlock,
    Notification,
    SchemaBrowser,
    WidgetLoader,
  },
  props: {
    modelValue: {
      type: String,
      default: '',
    },
    idSite: {
      type: Number,
      required: true,
    },
    period: {
      type: String,
      required: true,
    },
    date: {
      type: String,
      required: true,
    },
    setupUrl: {
      type: String,
      default: '',
    },
    canSave: {
      type: Boolean,
      default: false,
    },
    // a saved query this user may change is open: the button opens it for an update
    editing: {
      type: Boolean,
      default: false,
    },
    visualization: {
      type: String as PropType<Visualization>,
      default: 'table',
    },
    treemap: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['update:modelValue', 'save', 'validity', 'setVisualization'],
  setup(props, { emit }) {
    const container = ref<HTMLElement|null>(null);
    const editorReady = ref(false);
    const schema = ref<Record<string, string[]>>({});
    const schemaLoading = ref(false);
    const validity = ref<Validity>(props.modelValue.trim() ? 'pending' : 'empty');
    const problems = ref<Diagnostic[]>([]);
    // the result of Run: the native visualization of the query, like a saved raw SQL report
    const resultParams = ref<Record<string, string>|null>(null);
    // the chart buttons wait for the first result of a query, not shown above a loading table
    const resultLoaded = ref(false);
    const ranSql = ref('');
    const visualizations = availableVisualizations(props.treemap).map((id) => ({
      id,
      icon: VISUALIZATION_ICONS[id],
      label: translate(`DataExplorer_Visualization_${id}`),
    }));

    // CodeMirror objects must not be wrapped in Vue proxies
    let view: EditorView|null = null;
    let isApplyingModelValue = false;
    let validationSequence = 0;
    let unmounted = false;

    // asked when the tab opens: the status connects to the read-only database user
    const status = ref<SqlStatus|null>(null);
    const statusLoading = ref(true);

    const isReady = computed(() => !!status.value && status.value.state === 'ready');

    const canRun = computed(() => editorReady.value
      && validity.value === 'valid');

    const runTitle = computed(() => (canRun.value
      ? translate('DataExplorer_SqlRunShortcut')
      : translate('DataExplorer_SqlRunDisabled')));

    watch(validity, (value) => {
      emit('validity', value);
    }, { immediate: true });

    function validate(sql: string): Promise<Diagnostic[]> {
      validationSequence += 1;
      const sequence = validationSequence;

      if (!sql.trim()) {
        validity.value = 'empty';
        problems.value = [];
        return Promise.resolve([]);
      }

      return AjaxHelper.post<SqlValidation>(
        { method: 'DataExplorer.validateSql' },
        { sql },
        { createErrorNotification: false },
      ).then((result) => {
        const diagnostics = toDiagnostics(result, sql.length);
        if (sequence === validationSequence) {
          validity.value = result.valid ? 'valid' : 'invalid';
          problems.value = diagnostics;
        }
        return diagnostics;
      }).catch((e: Error) => {
        if (sequence === validationSequence) {
          validity.value = 'invalid';
          problems.value = [{
            from: 0, to: sql.length, severity: 'error', message: e.message,
          }];
        }
        return problems.value;
      });
    }

    function load() {
      const chart = isChart(props.visualization) ? props.visualization : '';
      resultParams.value = {
        module: 'DataExplorer',
        action: 'preview',
        definition: JSON.stringify({
          type: 'sql',
          name: 'preview',
          sql: ranSql.value,
          report_type: reportTypeOf(props.visualization),
          chart,
        }),
        period: props.period,
        date: props.date,
        viewDataTable: VIEW_BY_VISUALIZATION[props.visualization],
      };
    }

    function run() {
      if (!canRun.value || !view) {
        return;
      }
      ranSql.value = view.state.doc.toString();
      resultLoaded.value = false;
      load();
    }

    // a chart picked in the footer of the result becomes the one of the query, as in the Free
    // form canvas; core triggers the event before it attaches the DataTable instance
    const resultElement = ref<HTMLElement|null>(null);
    function onLoaded(event: JQuery.TriggeredEvent) {
      const table = $(event.target as HTMLElement).closest('.dataTable');
      setTimeout(() => {
        const control = table.data('uiControlObject') as
          { param?: Record<string, unknown> }|undefined;
        const picked = control && control.param
          ? VISUALIZATION_BY_VIEW[String(control.param.viewDataTable || '')]
          : undefined;
        if (picked && picked !== props.visualization) {
          emit('setVisualization', picked);
        }
      });
    }
    let bound: HTMLElement|null = null;
    // every chart is not a DataTable that triggers piwik:dataTableLoaded: the report HTML
    // inserted by the widget loader marks the end of the loading
    const contentObserver = new MutationObserver(() => {
      if (bound && bound.querySelector('.theWidgetContent > *')) {
        resultLoaded.value = true;
      }
    });
    watch(resultElement, (element) => {
      if (bound) {
        $(bound).off('piwik:dataTableLoaded', onLoaded);
        contentObserver.disconnect();
      }
      bound = element;
      if (bound) {
        $(bound).on('piwik:dataTableLoaded', onLoaded);
        contentObserver.observe(bound, { childList: true, subtree: true });
      }
    });

    // the chart, period or date of a result already shown applies at once; the query waits for Run
    watch(() => [props.visualization, props.period, props.date], () => {
      if (resultParams.value) {
        load();
      }
    });

    function insert(text: string) {
      if (!view) {
        return;
      }
      view.dispatch({ ...view.state.replaceSelection(text), scrollIntoView: true });
      view.focus();
    }

    function createEditor(cm: CodeMirrorModules) {
      if (unmounted || !container.value) {
        return;
      }
      view = new cm.EditorView({
        parent: container.value,
        state: cm.EditorState.create({
          doc: props.modelValue,
          extensions: [
            cm.basicSetup,
            cm.sql({ dialect: cm.MySQL, schema: schema.value, upperCaseKeywords: true }),
            createCodeTheme({
              EditorView: cm.EditorView,
              HighlightStyle: cm.HighlightStyle,
              syntaxHighlighting: cm.syntaxHighlighting,
              tags: cm.tags,
            }),
            cm.linter((editor) => validate(editor.state.doc.toString()), { delay: 400 }),
            cm.lintGutter(),
            // the editor clips its overflow: tooltips go to the body
            cm.tooltips({ parent: document.body }),
            cm.placeholder(translate('DataExplorer_SqlPlaceholder')),
            runKeymap(cm, run),
            cm.EditorView.lineWrapping,
            // CodeMirror owns the class attribute of its root element
            cm.EditorView.editorAttributes.of({ class: 'dataExplorerSql__codemirror' }),
            cm.EditorView.contentAttributes.of({
              spellcheck: 'false',
              'aria-labelledby': 'dataExplorerSqlEditorLabel',
            }),
            cm.EditorView.updateListener.of((update) => {
              if (!update.docChanged || isApplyingModelValue) {
                return;
              }
              const value = update.state.doc.toString();
              validationSequence += 1;
              validity.value = value.trim() ? 'pending' : 'empty';
              problems.value = [];
              emit('update:modelValue', value);
            }),
          ],
        }),
      });
      editorReady.value = true;
    }

    // an empty editor (nothing opened, nothing typed: a text the user clears comes back from the
    // editor itself and never gets here) starts on a query that runs as is, to show the way
    function offerStarter(value: string): boolean {
      const visits = Object.keys(schema.value).find((table) => /(^|_)log_visit$/.test(table));
      if (value.trim() !== '' || !visits) {
        return false;
      }
      emit('update:modelValue', starterQuery(visits));
      return true;
    }

    function start() {
      if (!isReady.value || view) {
        return;
      }
      schemaLoading.value = true;
      const schemaRequest = AjaxHelper.fetch<Record<string, string[]>>(
        { method: 'DataExplorer.getSqlSchema' },
        { createErrorNotification: false },
      ).then((result) => {
        schema.value = result && typeof result === 'object' && !Array.isArray(result) ? result : {};
        offerStarter(props.modelValue);
      }).catch(() => {
        // completion falls back to keywords, the guard still validates every query
        schema.value = {};
      }).finally(() => {
        schemaLoading.value = false;
      });

      Promise.all([loadCodeMirror(), schemaRequest]).then(([cm]) => createEditor(cm));
    }

    onMounted(() => {
      AjaxHelper.fetch<SqlStatus>(
        { method: 'DataExplorer.getSqlStatus' },
        { createErrorNotification: false },
      ).then((result) => {
        status.value = result;
      }).catch((e: Error) => {
        status.value = { state: 'error', message: e.message };
      }).finally(() => {
        statusLoading.value = false;
        nextTick(start);
      });
    });

    // a saved query opened in the explorer replaces the text
    watch(() => props.modelValue, (value) => {
      if (!view || value === view.state.doc.toString()) {
        return;
      }
      // another query opened: the result of the previous one no longer belongs here
      resultParams.value = null;
      resultLoaded.value = false;
      if (offerStarter(value)) {
        return;
      }
      isApplyingModelValue = true;
      view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: value } });
      isApplyingModelValue = false;
      validity.value = value.trim() ? 'pending' : 'empty';
    });

    onBeforeUnmount(() => {
      unmounted = true;
      contentObserver.disconnect();
      if (view) {
        view.destroy();
        view = null;
      }
    });

    return {
      container,
      editorReady,
      schema,
      schemaLoading,
      validity,
      problems,
      resultParams,
      resultLoaded,
      resultElement,
      visualizations,
      isReady,
      status,
      statusLoading,
      canRun,
      runTitle,
      run,
      insert,
    };
  },
});
</script>
