<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <SidePanel
    :model-value="modelValue"
    :title="title"
    focus-selector="#dataExplorerSaveName"
    @update:model-value="$emit('update:modelValue', $event)"
    @closed="$emit('closed')"
  >
    <div class="dataExplorerSaveDialog">
      <div class="dataExplorerSaveDialog__form">
        <div
          class="dataExplorerSaveDialog__error"
          aria-live="assertive"
        >
          <Notification
            v-if="error"
            context="error"
            :noclear="true"
          >{{ error }}</Notification>
        </div>

        <Field
          :full-width="true"
          uicontrol="text"
          name="dataExplorerSaveName"
          v-model="form.name"
          :title="translate('DataExplorer_SaveName')"
          :maxlength="255"
          :required="true"
        />

        <Field
          :full-width="true"
          uicontrol="textarea"
          name="dataExplorerSaveDescription"
          v-model="form.description"
          :title="translate('DataExplorer_SaveDescription')"
          :inline-help="translate('DataExplorer_SaveDescriptionHelp')"
          :maxlength="1000"
        />

        <div class="dataExplorerSaveDialog__reportType">{{
          translate('DataExplorer_SavedAs', reportTypeLabel) }}</div>

        <Field
          :full-width="true"
          uicontrol="radio"
          name="dataExplorerSavePlacement"
          v-model="form.placement"
          :title="translate('DataExplorer_SavePlacement')"
          :options="placementOptions"
        />

        <div
          v-if="form.placement === 'sites'"
          class="dataExplorerSitePicker"
          role="group"
          :aria-label="translate('DataExplorer_SaveSites')"
        >
          <div class="dataExplorerSitePicker__label">{{ translate('DataExplorer_SaveSites') }}</div>
          <ul
            v-if="form.sites.length"
            class="dataExplorerSitePicker__chips"
          >
            <li
              v-for="id in form.sites"
              :key="id"
              class="dataExplorerSitePicker__chip"
            >
              <span>{{ siteNames[id] || id }}</span>
              <button
                type="button"
                class="dataExplorerSitePicker__remove"
                :title="translate('General_Remove')"
                :aria-label="`${translate('General_Remove')} ${siteNames[id] || id}`"
                @click="toggleSite(id)"
              >
                <span
                  class="icon-close"
                  aria-hidden="true"
                ></span>
              </button>
            </li>
          </ul>
          <SearchInput
            v-model="siteSearch"
            :placeholder="translate('DataExplorer_SaveSitesSearch')"
            :aria-label="translate('DataExplorer_SaveSitesSearch')"
          />
          <ul class="dataExplorerSitePicker__list">
            <li
              v-for="site in siteOptions"
              :key="site.key"
            >
              <label class="dataExplorerSitePicker__option">
                <input
                  type="checkbox"
                  :checked="form.sites.indexOf(site.key) !== -1"
                  @change="toggleSite(site.key)"
                />
                <span>{{ site.value }}</span>
              </label>
            </li>
            <li
              v-if="!siteOptions.length"
              class="dataExplorerSitePicker__empty"
            >{{ translate('General_SearchNoResults') }}</li>
          </ul>
        </div>

        <Field
          :full-width="true"
          uicontrol="select"
          name="dataExplorerSaveCategory"
          v-model="form.category"
          :title="translate('DataExplorer_SaveCategory')"
          :options="categoryOptions"
        />

        <Field
          :full-width="true"
          uicontrol="select"
          name="dataExplorerSaveSubcategory"
          v-model="form.subcategory"
          :title="translate('DataExplorer_SaveSubcategory')"
          :options="subcategoryOptions"
        />

        <Field
          :full-width="true"
          v-if="form.subcategory === newSubcategoryKey"
          uicontrol="text"
          name="dataExplorerSaveNewSubcategory"
          v-model="form.newSubcategory"
          :title="translate('DataExplorer_SaveNewSubcategory')"
          :maxlength="255"
        />

        <Field
          :full-width="true"
          uicontrol="text"
          var-type="integer"
          name="dataExplorerSaveOrder"
          v-model="form.order"
          :title="translate('DataExplorer_SaveOrder')"
          :inline-help="translate('DataExplorer_SaveOrderHelp')"
        />

        <Field
          v-if="type !== 'sql'"
          :full-width="true"
          uicontrol="checkbox"
          name="dataExplorerSaveProcessSegments"
          v-model="form.processSegments"
          :title="translate('DataExplorer_SaveProcessSegments')"
          :inline-help="translate('DataExplorer_SaveProcessSegmentsHelp')"
        />
      </div>
    </div>
    <template #actions>
      <SaveButton
        v-if="canUpdate"
        class="dataExplorerSaveDialog__update"
        :saving="saving"
        :disabled="!modified && !formChanged"
        :value="translate('General_Save')"
        @confirm="save(false)"
      />
      <SaveButton
        class="dataExplorerSaveDialog__create"
        :saving="saving"
        :value="editing ? translate('DataExplorer_SaveAsNew') : translate('General_Save')"
        @confirm="save(true)"
      />
    </template>
  </SidePanel>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  reactive,
  ref,
  watch,
} from 'vue';
import {
  AjaxHelper,
  Notification,
  SearchInput,
  translate,
} from 'CoreHome';
import { Field, SaveButton } from 'CorePluginsAdmin';
import SidePanel from '../SidePanel/SidePanel.vue';
import { isChart } from '../Explorer/explorationState';
import type {
  ReportCategory,
  ReportType,
  SavedReport,
  SaveSuggestion,
  WritableSite,
} from '../types';

const DEFAULT_CATEGORY = 'DataExplorer_DataExplorer';
const NEW_SUBCATEGORY = '__dataexplorer_new__';
// saved with no page: the report gets a page of its own, named after it
const OWN_PAGE = '__dataexplorer_own__';
const DEFAULT_ORDER = 100;
// super users can write to every website: the list is searched on demand, never loaded whole
const SITE_SEARCH_LIMIT = 50;
const SITE_SEARCH_DELAY_MS = 300;

type Placement = 'site' | 'sites' | 'all';

interface SaveForm {
  name: string;
  description: string;
  reportType: ReportType | 'funnel';
  placement: Placement;
  sites: string[];
  category: string;
  subcategory: string;
  newSubcategory: string;
  order: string|number;
  processSegments: boolean;
}

/**
 * Saves an exploration or a raw SQL query as a report: name, placement (this site, several
 * sites, all websites for super users), menu category and page, order. An opened report is
 * updated, or saved as a new one.
 */
export default defineComponent({
  name: 'SaveDialog',
  components: {
    Field,
    Notification,
    SaveButton,
    SearchInput,
    SidePanel,
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    idSite: {
      type: Number,
      required: true,
    },
    isSuperUser: {
      type: Boolean,
      default: false,
    },
    writableSites: {
      type: Array as PropType<WritableSite[]>,
      default: () => [],
    },
    type: {
      type: String as PropType<'visual'|'sql'|'funnel'>,
      required: true,
    },
    // report type of a visual exploration, chosen in the dialog for raw SQL
    reportType: {
      type: String as PropType<ReportType | 'funnel'>,
      default: 'table',
    },
    definition: {
      type: Object as PropType<Record<string, unknown>>,
      required: true,
    },
    editing: {
      type: Object as PropType<SavedReport|null>,
      default: null,
    },
    // the exploration or the query differs from the opened report
    modified: {
      type: Boolean,
      default: false,
    },
    // where a report opened from elsewhere (a custom report) goes when nothing saved is open
    suggestion: {
      type: Object as PropType<SaveSuggestion|null>,
      default: null,
    },
  },
  emits: ['update:modelValue', 'saved', 'closed'],
  setup(props, { emit }) {
    const categories = ref<ReportCategory[]>([]);
    const saving = ref(false);
    const error = ref('');

    const form = reactive<SaveForm>({
      name: '',
      description: '',
      reportType: 'table',
      placement: 'site',
      sites: [],
      category: DEFAULT_CATEGORY,
      subcategory: OWN_PAGE,
      newSubcategory: '',
      order: DEFAULT_ORDER,
      processSegments: true,
    });

    const title = computed(() => translate(props.editing
      ? 'DataExplorer_SaveDialogEditTitle'
      : 'DataExplorer_SaveDialogTitle'));

    const canUpdate = computed(() => !!props.editing
      && props.editing.editable
      && props.editing.type === props.type);

    const reportTypeOptions = computed(() => ['table', 'evolution', 'kpicard'].map((key) => ({
      key,
      value: translate(`DataExplorer_ReportType_${key}`),
    })));

    // a chart is a table report drawn as a chart: name the chart, not the table
    const reportTypeLabel = computed(() => {
      const { chart } = props.definition;
      if (isChart(chart)) {
        return translate(`DataExplorer_Visualization_${chart}`);
      }
      return translate(`DataExplorer_ReportType_${props.reportType}`);
    });

    const placementOptions = computed(() => {
      const options = [
        { key: 'site', value: translate('DataExplorer_PlacementThisSite') },
        { key: 'sites', value: translate('DataExplorer_PlacementSites') },
      ];
      if (props.isSuperUser) {
        options.push({ key: 'all', value: translate('DataExplorer_PlacementAll') });
      }
      return options;
    });

    const siteNames = reactive<Record<string, string>>({});
    const foundSites = ref<string[]>([]);
    const siteSearch = ref('');
    let siteSearchTimer: ReturnType<typeof setTimeout>|null = null;
    let siteSearchRun = 0;

    function rememberSites(sites: { idsite?: unknown, id?: unknown, name?: unknown }[]): string[] {
      return sites.map((site) => {
        const id = String(site.idsite !== undefined ? site.idsite : site.id);
        siteNames[id] = String(site.name);
        return id;
      });
    }

    props.writableSites.forEach((site) => {
      siteNames[String(site.id)] = site.name;
    });

    function searchSites(): void {
      siteSearchRun += 1;
      const run = siteSearchRun;
      AjaxHelper.fetch<{ idsite: number, name: string }[]>(
        {
          // getPatternMatchSites refuses an empty pattern (HTTP 400): the dialog lists
          // the sites before any search
          method: 'SitesManager.getSitesWithMinimumAccess',
          permission: 'write',
          pattern: siteSearch.value.trim(),
          limit: SITE_SEARCH_LIMIT,
        },
        { createErrorNotification: false },
      ).then((result) => {
        if (run === siteSearchRun) {
          foundSites.value = rememberSites(Array.isArray(result) ? result : []);
        }
      }).catch((e: Error) => {
        error.value = e.message;
      });
    }

    // names of selected sites outside the search results (an opened report)
    function loadSelectedSiteNames(): void {
      form.sites.filter((id) => !siteNames[id]).forEach((id) => {
        AjaxHelper.fetch<{ idsite: number, name: string }>(
          { method: 'SitesManager.getSiteFromId', idSite: id },
          { createErrorNotification: false },
        ).then((site) => {
          rememberSites([site]);
        }).catch(() => {
          siteNames[id] = id;
        });
      });
    }

    watch(siteSearch, () => {
      if (!props.isSuperUser) {
        return;
      }
      if (siteSearchTimer) {
        clearTimeout(siteSearchTimer);
      }
      siteSearchTimer = setTimeout(searchSites, SITE_SEARCH_DELAY_MS);
    });

    // the sites to tick: the writable sites matching the search (super users search them on the
    // server, every website would be too many to list)
    const siteOptions = computed(() => {
      if (!props.isSuperUser) {
        const query = siteSearch.value.trim().toLowerCase();
        return props.writableSites
          .filter((site) => !query || site.name.toLowerCase().indexOf(query) !== -1)
          .map((site) => ({ key: String(site.id), value: site.name }));
      }
      return foundSites.value.map((id) => ({ key: id, value: siteNames[id] || id }));
    });

    function toggleSite(id: string): void {
      const index = form.sites.indexOf(id);
      if (index === -1) {
        form.sites.push(id);
      } else {
        form.sites.splice(index, 1);
      }
    }

    const categoryOptions = computed(() => categories.value.map((category) => ({
      key: category.id,
      value: category.name,
    })));

    const subcategoryOptions = computed(() => {
      const category = categories.value.find((item) => item.id === form.category);
      const options = [{
        key: OWN_PAGE,
        value: translate('DataExplorer_SaveOwnPageOption'),
      }];
      (category ? category.subcategories : []).forEach((subcategory) => {
        options.push({ key: subcategory.id, value: subcategory.name });
      });
      options.push({
        key: NEW_SUBCATEGORY,
        value: translate('DataExplorer_SaveNewSubcategoryOption'),
      });
      return options;
    });

    function subcategoryExists(category: string, subcategory: string): boolean {
      const found = categories.value.find((item) => item.id === category);
      return !!found && found.subcategories.some((item) => item.id === subcategory);
    }

    function isOnlyThisSite(sites: number[]): boolean {
      return sites.length === 1 && sites[0] === props.idSite;
    }

    // the settings as prefilled: an update needs the report or its settings to change
    const prefilled = ref('');
    const formChanged = computed(() => prefilled.value !== ''
      && JSON.stringify(form) !== prefilled.value);

    function prefill() {
      error.value = '';
      prefilled.value = '';
      const report = props.editing;
      const { suggestion } = props;
      form.name = '';
      form.description = '';
      if (report) {
        form.name = report.name;
        form.description = String(report.definition.description || '');
      } else if (suggestion) {
        form.name = suggestion.name;
        form.description = suggestion.description;
      }
      form.reportType = props.reportType;
      form.order = report ? report.order : DEFAULT_ORDER;
      form.processSegments = !report || report.definition.process_segments !== false;

      if (report && report.placement.all) {
        form.placement = props.isSuperUser ? 'all' : 'site';
        form.sites = [String(props.idSite)];
      } else if (report && !isOnlyThisSite(report.placement.sites)) {
        form.placement = 'sites';
        form.sites = report.placement.sites.map(String);
      } else {
        form.placement = 'site';
        form.sites = [String(props.idSite)];
      }

      let place: { category: string, subcategory: string } = { category: DEFAULT_CATEGORY, subcategory: '' };
      if (report) {
        place = report;
      } else if (suggestion && categories.value.some((item) => item.id === suggestion.category)) {
        place = suggestion;
      }
      form.category = place.category;
      form.newSubcategory = '';
      if (place.subcategory === '') {
        form.subcategory = OWN_PAGE;
      } else if (subcategoryExists(place.category, place.subcategory)) {
        form.subcategory = place.subcategory;
      } else {
        form.subcategory = NEW_SUBCATEGORY;
        form.newSubcategory = place.subcategory;
      }
    }

    function loadCategories(): Promise<void> {
      if (categories.value.length) {
        return Promise.resolve();
      }
      return AjaxHelper.fetch<ReportCategory[]>(
        { method: 'DataExplorer.getCategories', idSite: props.idSite },
        { createErrorNotification: false },
      ).then((result) => {
        categories.value = Array.isArray(result) ? result : [];
      }).catch((e: Error) => {
        error.value = e.message;
      });
    }

    watch(() => props.modelValue, (open) => {
      if (open) {
        loadCategories().then(prefill).then(() => {
          prefilled.value = JSON.stringify(form);
          if (props.isSuperUser) {
            loadSelectedSiteNames();
            searchSites();
          }
        });
      }
    }, { immediate: true });

    // a new category starts on its first page
    watch(() => form.category, (category, previous) => {
      if (previous && category !== previous && form.subcategory !== OWN_PAGE
        && !subcategoryExists(category, form.subcategory)) {
        form.subcategory = OWN_PAGE;
      }
    });

    function pageOf(subcategory: string): string {
      if (subcategory === OWN_PAGE) {
        return '';
      }
      return subcategory === NEW_SUBCATEGORY ? form.newSubcategory : subcategory;
    }

    function placementJson(): string {
      if (form.placement === 'all') {
        return JSON.stringify({ all: true });
      }
      if (form.placement === 'sites') {
        return JSON.stringify({ sites: form.sites.map((id) => parseInt(id, 10)) });
      }
      return JSON.stringify({ sites: [props.idSite] });
    }

    function save(asNew: boolean) {
      error.value = '';
      saving.value = true;

      const isUpdate = !asNew && canUpdate.value && props.editing;
      // the visualization picked above the result or in Report settings, both tabs alike
      const { reportType } = props;
      const postParams: Record<string, string|number> = {
        name: form.name,
        type: props.type,
        reportType,
        definition: JSON.stringify({
          ...props.definition,
          report_type: reportType,
          description: form.description.trim(),
          process_segments: form.processSegments,
        }),
        placement: placementJson(),
        category: form.category,
        subcategory: pageOf(form.subcategory),
        order: parseInt(String(form.order), 10) || 0,
      };
      if (isUpdate && props.editing) {
        postParams.idReport = props.editing.idreport;
      }

      AjaxHelper.post<{ value?: number }>(
        {
          method: isUpdate ? 'DataExplorer.updateReport' : 'DataExplorer.addReport',
          idSite: props.idSite,
        },
        postParams,
        { createErrorNotification: false },
      ).then((result) => {
        const idReport = isUpdate && props.editing
          ? props.editing.idreport
          : Number(result && typeof result === 'object' ? result.value : result);
        emit('saved', { idReport, isNew: !isUpdate });
        emit('update:modelValue', false);
      }).catch((e: Error) => {
        error.value = e.message;
      }).finally(() => {
        saving.value = false;
      });
    }

    return {
      form,
      saving,
      error,
      title,
      canUpdate,
      formChanged,
      reportTypeOptions,
      reportTypeLabel,
      placementOptions,
      siteOptions,
      siteSearch,
      siteNames,
      toggleSite,
      categoryOptions,
      subcategoryOptions,
      newSubcategoryKey: NEW_SUBCATEGORY,
      save,
    };
  },
});
</script>
