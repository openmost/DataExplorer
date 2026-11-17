<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <MatomoDialog
    :model-value="modelValue"
    :options="dialogOptions"
    @update:model-value="$emit('update:modelValue', $event)"
    @close-end="$emit('closed')"
  >
    <div class="ui-confirm dataExplorerSaveDialog">
      <h2 id="dataExplorerSaveDialogTitle">{{ title }}</h2>
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
          uicontrol="text"
          name="dataExplorerSaveName"
          v-model="form.name"
          :title="translate('DataExplorer_SaveName')"
          :maxlength="255"
          :required="true"
        />

        <Field
          v-if="type === 'sql'"
          uicontrol="select"
          name="dataExplorerSaveReportType"
          v-model="form.reportType"
          :title="translate('DataExplorer_SaveReportType')"
          :options="reportTypeOptions"
          :inline-help="translate('DataExplorer_SaveReportTypeHelp')"
        />
        <div
          v-else
          class="dataExplorerSaveDialog__reportType"
        >{{ translate('DataExplorer_SavedAs', reportTypeLabel) }}</div>

        <Field
          uicontrol="radio"
          name="dataExplorerSavePlacement"
          v-model="form.placement"
          :title="translate('DataExplorer_SavePlacement')"
          :options="placementOptions"
        />

        <Field
          v-if="form.placement === 'sites' && isSuperUser"
          uicontrol="text"
          name="dataExplorerSaveSitesSearch"
          v-model="siteSearch"
          :title="translate('DataExplorer_SaveSitesSearch')"
        />

        <Field
          v-if="form.placement === 'sites'"
          uicontrol="multiselect"
          name="dataExplorerSaveSites"
          v-model="form.sites"
          :title="translate('DataExplorer_SaveSites')"
          :options="siteOptions"
        />

        <Field
          uicontrol="select"
          name="dataExplorerSaveCategory"
          v-model="form.category"
          :title="translate('DataExplorer_SaveCategory')"
          :options="categoryOptions"
        />

        <Field
          uicontrol="select"
          name="dataExplorerSaveSubcategory"
          v-model="form.subcategory"
          :title="translate('DataExplorer_SaveSubcategory')"
          :options="subcategoryOptions"
        />

        <Field
          v-if="form.subcategory === newSubcategoryKey"
          uicontrol="text"
          name="dataExplorerSaveNewSubcategory"
          v-model="form.newSubcategory"
          :title="translate('DataExplorer_SaveNewSubcategory')"
          :maxlength="255"
        />

        <Field
          uicontrol="text"
          var-type="integer"
          name="dataExplorerSaveOrder"
          v-model="form.order"
          :title="translate('DataExplorer_SaveOrder')"
          :inline-help="translate('DataExplorer_SaveOrderHelp')"
        />

        <div class="dataExplorerSaveDialog__actions">
          <SaveButton
            v-if="canUpdate"
            class="dataExplorerSaveDialog__update"
            :saving="saving"
            :value="translate('General_Save')"
            @confirm="save(false)"
          />
          <SaveButton
            class="dataExplorerSaveDialog__create"
            :saving="saving"
            :value="editing ? translate('DataExplorer_SaveAsNew') : translate('General_Save')"
            @confirm="save(true)"
          />
        </div>
      </div>
      <input
        type="button"
        role="cancel"
        :value="translate('General_Cancel')"
      />
    </div>
  </MatomoDialog>
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
  MatomoDialog,
  Notification,
  translate,
} from 'CoreHome';
import { Field, SaveButton } from 'CorePluginsAdmin';
import type {
  ReportCategory,
  ReportType,
  SavedReport,
  WritableSite,
} from '../types';

const DEFAULT_CATEGORY = 'DataExplorer_DataExplorer';
const NEW_SUBCATEGORY = '__dataexplorer_new__';
const DEFAULT_ORDER = 100;
// super users can write to every website: the list is searched on demand, never loaded whole
const SITE_SEARCH_LIMIT = 50;
const SITE_SEARCH_DELAY_MS = 300;

type Placement = 'site' | 'sites' | 'all';

interface SaveForm {
  name: string;
  reportType: ReportType;
  placement: Placement;
  sites: string[];
  category: string;
  subcategory: string;
  newSubcategory: string;
  order: string|number;
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
    MatomoDialog,
    Notification,
    SaveButton,
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
      type: String as PropType<'visual'|'sql'>,
      required: true,
    },
    // report type of a visual exploration, chosen in the dialog for raw SQL
    reportType: {
      type: String as PropType<ReportType>,
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
  },
  emits: ['update:modelValue', 'saved', 'closed'],
  setup(props, { emit }) {
    const categories = ref<ReportCategory[]>([]);
    const saving = ref(false);
    const error = ref('');

    const form = reactive<SaveForm>({
      name: '',
      reportType: 'table',
      placement: 'site',
      sites: [],
      category: DEFAULT_CATEGORY,
      subcategory: NEW_SUBCATEGORY,
      newSubcategory: '',
      order: DEFAULT_ORDER,
    });

    const dialogOptions = {
      focusSelector: '#dataExplorerSaveName',
    };

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

    const reportTypeLabel = computed(
      () => translate(`DataExplorer_ReportType_${props.reportType}`),
    );

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
      if (siteSearchTimer) {
        clearTimeout(siteSearchTimer);
      }
      siteSearchTimer = setTimeout(searchSites, SITE_SEARCH_DELAY_MS);
    });

    const siteOptions = computed(() => {
      if (!props.isSuperUser) {
        return props.writableSites.map((site) => ({
          key: String(site.id),
          value: site.name,
        }));
      }
      const ids = form.sites.concat(foundSites.value.filter((id) => form.sites.indexOf(id) === -1));
      return ids.map((id) => ({ key: id, value: siteNames[id] || id }));
    });

    const categoryOptions = computed(() => categories.value.map((category) => ({
      key: category.id,
      value: category.name,
    })));

    const subcategoryOptions = computed(() => {
      const category = categories.value.find((item) => item.id === form.category);
      const options = (category ? category.subcategories : []).map((subcategory) => ({
        key: subcategory.id,
        value: subcategory.name,
      }));
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

    function prefill() {
      error.value = '';
      const report = props.editing;
      form.name = report ? report.name : '';
      form.reportType = report ? report.report_type : props.reportType;
      form.order = report ? report.order : DEFAULT_ORDER;

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

      form.category = report ? report.category : DEFAULT_CATEGORY;
      if (report && subcategoryExists(report.category, report.subcategory)) {
        form.subcategory = report.subcategory;
        form.newSubcategory = '';
      } else {
        form.subcategory = NEW_SUBCATEGORY;
        form.newSubcategory = report
          ? report.subcategory
          : translate('DataExplorer_DefaultSubcategory');
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
          if (props.isSuperUser) {
            loadSelectedSiteNames();
            searchSites();
          }
        });
      }
    }, { immediate: true });

    // a new category starts on its first page
    watch(() => form.category, (category, previous) => {
      if (previous && category !== previous && !subcategoryExists(category, form.subcategory)) {
        form.subcategory = NEW_SUBCATEGORY;
      }
    });

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
      const reportType = props.type === 'sql' ? form.reportType : props.reportType;
      const postParams: Record<string, string|number> = {
        name: form.name,
        type: props.type,
        reportType,
        definition: JSON.stringify({ ...props.definition, report_type: reportType }),
        placement: placementJson(),
        category: form.category,
        subcategory: form.subcategory === NEW_SUBCATEGORY ? form.newSubcategory : form.subcategory,
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
      dialogOptions,
      title,
      canUpdate,
      reportTypeOptions,
      reportTypeLabel,
      placementOptions,
      siteOptions,
      siteSearch,
      categoryOptions,
      subcategoryOptions,
      newSubcategoryKey: NEW_SUBCATEGORY,
      save,
    };
  },
});
</script>
