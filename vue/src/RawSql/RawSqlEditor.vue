<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerSql">
    <ContentBlock
      v-if="!isReady"
      :content-title="translate('DataExplorer_SqlTab')"
    >
      <Notification
        context="warning"
        :noclear="true"
      >{{ status ? status.message : '' }}</Notification>
      <p>
        <a
          class="btn dataExplorerSql__setupLink"
          :href="setupUrl"
        >{{ translate('DataExplorer_SqlOpenSetup') }}</a>
      </p>
    </ContentBlock>

    <div
      v-else
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
        <p class="dataExplorerSql__help">{{ translate('DataExplorer_SqlEditorHelp') }}</p>
        <ActivityIndicator :loading="!editorReady" />
        <div
          ref="container"
          class="dataExplorerSql__editor"
        ></div>

        <div
          class="dataExplorerSql__validation"
          aria-live="polite"
        >
          <p
            v-if="validity === 'pending'"
            class="dataExplorerSql__checking"
          >{{ translate('DataExplorer_SqlChecking') }}</p>
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
              ></span>
              {{ problem.message }}
            </li>
          </ul>
          <p
            v-else-if="validity === 'valid'"
            class="dataExplorerSql__valid"
          >
            <span class="icon-ok" aria-hidden="true"></span>
            {{ translate('DataExplorer_SqlValid') }}
          </p>
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
            class="btn btn-flat dataExplorerSql__save"
            :disabled="validity !== 'valid'"
            @click="$emit('save')"
          >{{ translate('DataExplorer_SaveReport') }}</button>
          <button
            v-if="columns.length"
            type="button"
            class="btn btn-flat dataExplorerSql__csv"
            @click="exportCsv"
          >
            <span class="icon-export" aria-hidden="true"></span>
            {{ translate('DataExplorer_ExportCsv') }}
          </button>
          <ActivityIndicator :loading="running" />
        </div>

        <div aria-live="polite">
          <Notification
            v-if="runError"
            context="error"
            :noclear="true"
          >{{ runError }}</Notification>
          <p
            v-else-if="hasRun && !rows.length && !running"
            class="dataExplorerCanvas__hint"
          >{{ translate('DataExplorer_SqlNoRows') }}</p>
          <p
            v-else-if="rows.length"
            class="dataExplorerSql__count"
          >{{ translate('DataExplorer_SqlRowCount', rows.length) }}</p>
        </div>

        <div
          v-if="rows.length"
          class="dataExplorerCanvas__table"
        >
          <table class="entityTable dataTable dataExplorerResult dataExplorerSql__result">
            <thead>
              <tr>
                <th
                  v-for="column in columns"
                  :key="column"
                  scope="col"
                >{{ column }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in rows"
                :key="index"
              >
                <td
                  v-for="column in columns"
                  :key="column"
                >{{ cell(row[column]) }}</td>
              </tr>
            </tbody>
          </table>
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
} from 'CoreHome';
import SchemaBrowser from './SchemaBrowser.vue';
import { toDiagnostics } from './sqlDiagnostics';
import { createCodeTheme } from '../codeTheme';
import { toCsv } from '../Explorer/resultTable';
import type { Diagnostic, EditorView } from './codeMirrorModules';
import type { SqlStatus, SqlValidation } from '../types';

type CodeMirrorModules = typeof import('./codeMirrorModules');

type Validity = 'empty' | 'pending' | 'valid' | 'invalid';

let modules: Promise<CodeMirrorModules> | null = null;

function loadCodeMirror(): Promise<CodeMirrorModules> {
  if (!modules) {
    modules = import(/* webpackChunkName: "codemirror" */ './codeMirrorModules');
  }
  return modules;
}

/**
 * Raw SQL tab (super users): CodeMirror editor with MySQL highlighting, completion of the allowed
 * tables and columns, live validation by the server guard, the schema of the allowed tables, the
 * result table and its CSV export. Run stays disabled while the query is not valid.
 */
export default defineComponent({
  name: 'RawSqlEditor',
  components: {
    ActivityIndicator,
    ContentBlock,
    Notification,
    SchemaBrowser,
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
    status: {
      type: Object as PropType<SqlStatus|null>,
      default: null,
    },
    setupUrl: {
      type: String,
      default: '',
    },
    canSave: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['update:modelValue', 'save', 'validity'],
  setup(props, { emit }) {
    const container = ref<HTMLElement|null>(null);
    const editorReady = ref(false);
    const schema = ref<Record<string, string[]>>({});
    const schemaLoading = ref(false);
    const validity = ref<Validity>(props.modelValue.trim() ? 'pending' : 'empty');
    const problems = ref<Diagnostic[]>([]);
    const running = ref(false);
    const hasRun = ref(false);
    const runError = ref('');
    const rows = ref<Record<string, unknown>[]>([]);

    // CodeMirror objects must not be wrapped in Vue proxies
    let view: EditorView|null = null;
    let isApplyingModelValue = false;
    let validationSequence = 0;
    let runSequence = 0;
    let unmounted = false;

    const isReady = computed(() => !!props.status && props.status.state === 'ready');

    const canRun = computed(() => editorReady.value
      && validity.value === 'valid'
      && !running.value);

    const runTitle = computed(() => (canRun.value
      ? translate('DataExplorer_SqlRunShortcut')
      : translate('DataExplorer_SqlRunDisabled')));

    const columns = computed(() => {
      const keys: string[] = [];
      rows.value.forEach((row) => {
        Object.keys(row).forEach((key) => {
          if (!keys.includes(key)) {
            keys.push(key);
          }
        });
      });
      return keys;
    });

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

    function run() {
      if (!canRun.value || !view) {
        return;
      }
      runSequence += 1;
      const sequence = runSequence;
      running.value = true;
      runError.value = '';

      AjaxHelper.post<Record<string, unknown>[]>(
        {
          method: 'DataExplorer.runSql',
          idSite: props.idSite,
          period: props.period,
          date: props.date,
          filter_limit: -1,
          format_metrics: 0,
        },
        { sql: view.state.doc.toString() },
        { createErrorNotification: false },
      ).then((result) => {
        if (sequence !== runSequence) {
          return;
        }
        rows.value = Array.isArray(result) ? result : [];
      }).catch((e: Error) => {
        if (sequence !== runSequence) {
          return;
        }
        rows.value = [];
        runError.value = e.message;
      }).finally(() => {
        if (sequence === runSequence) {
          running.value = false;
          hasRun.value = true;
        }
      });
    }

    function insert(text: string) {
      if (!view) {
        return;
      }
      view.dispatch({ ...view.state.replaceSelection(text), scrollIntoView: true });
      view.focus();
    }

    function cell(value: unknown): string {
      if (value === null || typeof value === 'undefined') {
        return '';
      }
      return typeof value === 'object' ? JSON.stringify(value) : String(value);
    }

    function exportCsv() {
      const csv = toCsv(columns.value, rows.value.map((row) => columns.value.map((column) => {
        const value = row[column];
        return typeof value === 'number' ? value : cell(value);
      })));
      const blob = new Blob([`${String.fromCharCode(0xfeff)}${csv}`], { type: 'text/csv;charset=utf-8' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'data-explorer-query.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(link.href);
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
            cm.keymap.of([{
              key: 'Mod-Enter',
              run: () => {
                run();
                return true;
              },
            }]),
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
      }).catch(() => {
        // completion falls back to keywords, the guard still validates every query
        schema.value = {};
      }).finally(() => {
        schemaLoading.value = false;
      });

      Promise.all([loadCodeMirror(), schemaRequest]).then(([cm]) => createEditor(cm));
    }

    onMounted(start);

    // a saved query opened in the explorer replaces the text
    watch(() => props.modelValue, (value) => {
      if (!view || value === view.state.doc.toString()) {
        return;
      }
      isApplyingModelValue = true;
      view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: value } });
      isApplyingModelValue = false;
      validity.value = value.trim() ? 'pending' : 'empty';
    });

    onBeforeUnmount(() => {
      unmounted = true;
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
      running,
      hasRun,
      runError,
      rows,
      columns,
      isReady,
      canRun,
      runTitle,
      run,
      insert,
      cell,
      exportCsv,
    };
  },
});
</script>
