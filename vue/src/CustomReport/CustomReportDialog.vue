<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <MatomoDialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    @yes="create"
  >
    <div class="ui-confirm dataExplorerCustomReport">
      <h2>{{ translate('DataExplorer_SaveAsCustomReport') }}</h2>
      <div class="dataExplorerCustomReport__help">
        {{ translate('DataExplorer_CustomReportHelp') }}
      </div>
      <Field
        v-model="name"
        uicontrol="text"
        name="dataExplorerCustomReportName"
        :title="translate('General_Name')"
        :full-width="true"
      />
      <div
        v-if="unsupported.length"
        class="dataExplorerCustomReport__warning"
      >{{ translate('DataExplorer_CustomReportUnsupported', unsupported.join(', ')) }}</div>
      <input
        role="yes"
        type="button"
        :value="translate('General_Save')"
      />
      <input
        role="no"
        type="button"
        :value="translate('General_Cancel')"
      />
    </div>
  </MatomoDialog>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
  ref,
  watch,
} from 'vue';
import {
  AjaxHelper,
  Matomo,
  MatomoDialog,
  MatomoUrl,
  translate,
} from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import type { VisualDefinition } from '../types';

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
    MatomoDialog,
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
  },
  emits: ['update:modelValue', 'created', 'failed'],
  setup(props, { emit }) {
    const name = ref('');
    const unsupported = ref<string[]>([]);
    let parameters: CustomReportParameters|null = null;

    function load() {
      parameters = null;
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
        unsupported.value = result.unsupported || [];
      }).catch((e: Error) => {
        emit('failed', e.message);
      });
    }

    watch(() => props.modelValue, (open) => {
      if (open) {
        name.value = props.defaultName;
        load();
      }
    });

    function create() {
      if (!parameters || !name.value.trim()) {
        return;
      }
      AjaxHelper.post<{ value: number|string }>(
        {
          method: 'CustomReports.addCustomReport',
          idSite: Matomo.idSite,
          name: name.value.trim(),
          reportType: parameters.reportType,
          description: '',
          segmentFilter: encodeURIComponent(parameters.segmentFilter),
        },
        {
          dimensionIds: parameters.dimensionIds,
          metricIds: parameters.metricIds,
        },
        { withTokenInUrl: true, createErrorNotification: false },
      ).then((response) => {
        emit('created', { idCustomReport: Number(response.value), name: name.value.trim() });
      }).catch((e: Error) => {
        emit('failed', e.message);
      });
    }

    return {
      name,
      unsupported,
      create,
      translate,
    };
  },
});
</script>
