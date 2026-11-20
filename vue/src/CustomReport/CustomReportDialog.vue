<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <SidePanel
    :model-value="modelValue"
    :title="translate(customReport
      ? 'DataExplorer_UpdateCustomReport'
      : 'DataExplorer_SaveAsCustomReport')"
    focus-selector="#dataExplorerCustomReportName"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="dataExplorerCustomReport">
      <div
        class="dataExplorerCustomReport__error"
        aria-live="assertive"
      >
        <Notification
          v-if="error"
          context="error"
          :noclear="true"
        >{{ error }}</Notification>
      </div>
      <p class="dataExplorerCustomReport__help">{{ translate(customReport
        ? 'DataExplorer_CustomReportUpdateHelp'
        : 'DataExplorer_CustomReportHelp') }}</p>
      <Field
        v-model="name"
        uicontrol="text"
        name="dataExplorerCustomReportName"
        :title="translate('General_Name')"
        :full-width="true"
      />
      <Field
        v-model="description"
        uicontrol="textarea"
        name="dataExplorerCustomReportDescription"
        :title="translate('DataExplorer_SaveDescription')"
        :full-width="true"
        :maxlength="1000"
      />
      <Field
        v-model="category"
        uicontrol="select"
        name="dataExplorerCustomReportCategory"
        :title="translate('DataExplorer_SaveCategory')"
        :options="categoryOptions"
        :full-width="true"
      />
      <Field
        v-model="subcategory"
        uicontrol="select"
        name="dataExplorerCustomReportSubcategory"
        :title="translate('DataExplorer_SaveSubcategory')"
        :options="subcategoryOptions"
        :full-width="true"
      />
      <div
        v-if="unsupported.length"
        class="dataExplorerCustomReport__warning"
      >{{ translate('DataExplorer_CustomReportUnsupported', unsupported.join(', ')) }}</div>
    </div>
    <template #actions>
      <SaveButton
        :saving="saving"
        :disabled="!ready || !name.trim() || (!!customReport && !modified && !formChanged)"
        :value="translate('General_Save')"
        @confirm="save"
      />
    </template>
  </SidePanel>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  PropType,
  ref,
  watch,
} from 'vue';
import {
  AjaxHelper,
  Matomo,
  MatomoUrl,
  Notification,
  translate,
} from 'CoreHome';
import { Field, SaveButton } from 'CorePluginsAdmin';
import SidePanel from '../SidePanel/SidePanel.vue';
import type { ImportedCustomReport, VisualDefinition } from '../types';

// the reporting page Custom Reports creates for the report when none is picked
const NEW_PAGE = '';
const DEFAULT_CATEGORY = 'CustomReports_CustomReports';

interface CustomReportCategory {
  uniqueId: string;
  name: string;
  subcategories: { uniqueId: string, name: string }[];
}

interface CustomReportParameters {
  reportType: string;
  dimensionIds: string[];
  metricIds: string[];
  segmentFilter: string;
  unsupported: string[];
}

/**
 * Saves the exploration as a report of the Custom Reports plugin, when it is installed: the
 * explorer becomes a way to build custom reports. The parameters (same dimension and metric ids,
 * filters as a segment) come from DataExplorer.getCustomReportParameters.
 */
export default defineComponent({
  name: 'CustomReportDialog',
  components: {
    Field,
    Notification,
    SaveButton,
    SidePanel,
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    definition: {
      type: Object as PropType<VisualDefinition>,
      required: true,
    },
    defaultName: {
      type: String,
      default: '',
    },
    defaultDescription: {
      type: String,
      default: '',
    },
    // the exploration differs from the custom report it was opened from
    modified: {
      type: Boolean,
      default: false,
    },
    // the custom report the exploration was opened from: saving updates it
    customReport: {
      type: Object as PropType<ImportedCustomReport|null>,
      default: null,
    },
  },
  emits: ['update:modelValue', 'created', 'updated', 'failed'],
  setup(props, { emit }) {
    const name = ref('');
    const description = ref('');
    // the settings as prefilled: an update needs the report or its settings to change
    const prefilled = ref('');
    const unsupported = ref<string[]>([]);
    const error = ref('');
    const saving = ref(false);
    const ready = ref(false);
    const categories = ref<CustomReportCategory[]>([]);
    const category = ref(DEFAULT_CATEGORY);
    const subcategory = ref(NEW_PAGE);
    let parameters: CustomReportParameters|null = null;

    const categoryOptions = computed(() => categories.value.map((item) => ({
      key: item.uniqueId,
      value: item.name,
    })));

    const subcategoryOptions = computed(() => {
      const found = categories.value.find((item) => item.uniqueId === category.value);
      return [
        { key: NEW_PAGE, value: translate('DataExplorer_SaveNewSubcategoryOption') },
        ...(found ? found.subcategories : []).map((item) => ({
          key: item.uniqueId,
          value: item.name,
        })),
      ];
    });

    // a page belongs to its category
    watch(category, () => {
      subcategory.value = NEW_PAGE;
    });

    function loadCategories() {
      AjaxHelper.fetch<CustomReportCategory[]>(
        { method: 'CustomReports.getAvailableCategories', idSite: Matomo.idSite, filter_limit: -1 },
        { createErrorNotification: false },
      ).then((result) => {
        categories.value = Array.isArray(result) ? result : [];
        // the select shows its value once its options exist
        category.value = props.customReport && props.customReport.category
          ? props.customReport.category
          : DEFAULT_CATEGORY;
        // after the category watcher, which resets the page
        nextTick(() => {
          subcategory.value = props.customReport ? props.customReport.subcategory : NEW_PAGE;
          prefilled.value = JSON.stringify({
            name: name.value,
            description: description.value,
            category: category.value,
            subcategory: subcategory.value,
          });
        });
      }).catch(() => {
        categories.value = [];
      });
    }

    function load() {
      parameters = null;
      ready.value = false;
      error.value = '';
      unsupported.value = [];
      AjaxHelper.post<CustomReportParameters>(
        {
          method: 'DataExplorer.getCustomReportParameters',
          idSite: Matomo.idSite,
          period: MatomoUrl.parsed.value.period || Matomo.period,
          date: MatomoUrl.parsed.value.date || Matomo.currentDateString,
        },
        { definition: JSON.stringify(props.definition) },
        { createErrorNotification: false },
      ).then((result) => {
        parameters = result;
        ready.value = true;
        unsupported.value = result.unsupported || [];
      }).catch((e: Error) => {
        emit('failed', e.message);
      });
    }

    watch(() => props.modelValue, (open) => {
      if (open) {
        prefilled.value = '';
        name.value = props.customReport ? props.customReport.name : props.defaultName;
        description.value = props.customReport
          ? props.customReport.description
          : props.defaultDescription;
        category.value = '';
        subcategory.value = NEW_PAGE;
        load();
        loadCategories();
      }
    });

    function save() {
      if (!parameters || !name.value.trim()) {
        return;
      }
      error.value = '';
      saving.value = true;
      const report = props.customReport;
      const common = {
        name: name.value.trim(),
        reportType: parameters.reportType,
        description: description.value.trim(),
        segmentFilter: encodeURIComponent(parameters.segmentFilter),
        categoryId: category.value || DEFAULT_CATEGORY,
        ...(subcategory.value !== NEW_PAGE ? { subcategoryId: subcategory.value } : {}),
      };
      const lists: Record<string, (string|number)[]> = {
        dimensionIds: parameters.dimensionIds,
        metricIds: parameters.metricIds,
      };
      if (report) {
        // an update replaces every field: what the explorer does not edit is sent back as it was
        lists.subCategoryReportIds = report.childReportIds;
        if (report.idSite !== 0 && report.multipleIdSites.length) {
          lists.multipleIdSites = report.multipleIdSites;
        }
      }

      AjaxHelper.post<{ value: number|string }>(
        report
          ? {
            method: 'CustomReports.updateCustomReport',
            idSite: report.idSite === 0 ? 'all' : report.idSite,
            idCustomReport: report.idCustomReport,
            ...common,
          }
          : { method: 'CustomReports.addCustomReport', idSite: Matomo.idSite, ...common },
        lists,
        { withTokenInUrl: true, createErrorNotification: false },
      ).then((response) => {
        emit(report ? 'updated' : 'created', {
          idCustomReport: report ? report.idCustomReport : Number(response.value),
          name: common.name,
          description: common.description,
          category: common.categoryId,
          subcategory: subcategory.value,
        });
        emit('update:modelValue', false);
      }).catch((e: Error) => {
        error.value = e.message;
      }).finally(() => {
        saving.value = false;
      });
    }

    const formChanged = computed(() => prefilled.value !== '' && JSON.stringify({
      name: name.value,
      description: description.value,
      category: category.value,
      subcategory: subcategory.value,
    }) !== prefilled.value);

    return {
      name,
      description,
      formChanged,
      unsupported,
      error,
      saving,
      ready,
      category,
      subcategory,
      categoryOptions,
      subcategoryOptions,
      save,
      translate,
    };
  },
});
</script>
