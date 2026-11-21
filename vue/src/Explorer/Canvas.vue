<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerColumn dataExplorerCanvas">
    <div class="dataExplorerCanvas__header">
      <div class="dataExplorerCanvas__tab">
        <span
          class="dataExplorerCanvas__tabIcon"
          :class="tabIcon"
          aria-hidden="true"
        />
        <h3 class="dataExplorerCanvas__title">{{ title }}</h3>
      </div>
      <div
        v-if="periodText"
        class="dataExplorerCanvas__context"
      >
        <button
          type="button"
          class="dataExplorerCanvas__pill dataExplorerCanvas__period"
          :title="translate('General_ChooseDate', periodDates || periodText)"
          @click="$emit('openPeriodSelector')"
        >
          <span
            class="icon-calendar"
            aria-hidden="true"
          />
          <span>{{ periodText }}</span>
        </button>
        <span class="dataExplorerCanvas__pill">
          <span
            class="icon-segment"
            aria-hidden="true"
          />
          <span>{{ segmentText }}</span>
        </span>
      </div>
    </div>

    <div
      class="dataExplorerCanvas__status"
      aria-live="polite"
    >
      <div
        v-if="hint"
        class="dataExplorerCanvas__hint"
      >{{ hint }}</div>
    </div>

    <div
      v-if="!hint && widgetParams"
      ref="stage"
      class="dataExplorerCanvas__stage"
    >
      <WidgetLoader
        class="dataExplorerCanvas__widget"
        :widget-params="widgetParams"
      />
    </div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  onBeforeUnmount,
  PropType,
  ref,
  watch,
} from 'vue';
import { translate, WidgetLoader } from 'CoreHome';
import { VISUALIZATION_ICONS } from './explorationState';
import type { Visualization } from '../types';

/**
 * Right column: the exploration in the native Matomo visualization of its report type, loaded
 * from DataExplorer.preview like the saved report it would become.
 */
export default defineComponent({
  name: 'ExplorerCanvas',
  components: {
    WidgetLoader,
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
    // the dates of the period, when the pill shows its name (Last 28 days)
    periodDates: {
      type: String,
      default: '',
    },
    // the tab icon, when the canvas shows something else than a visualization of the free form
    icon: {
      type: String,
      default: '',
    },
    visualization: {
      type: String as PropType<Visualization>,
      required: true,
    },
    hint: {
      type: String,
      default: '',
    },
    widgetParams: {
      type: Object as PropType<Record<string, string>|null>,
      default: null,
    },
  },
  emits: ['openPeriodSelector', 'viewChanged'],
  setup(props, { emit }) {
    const tabIcon = computed(() => props.icon
      || VISUALIZATION_ICONS[props.visualization as Visualization]);
    const stage = ref<HTMLElement|null>(null);

    // the core footer (visualization icons, "Make it flat") reloads the table by itself: its new
    // view and layout are read from the DataTable instance once loaded, so Report settings follow
    function onLoaded(event: JQuery.TriggeredEvent) {
      const table = $(event.target as HTMLElement).closest('.dataTable');
      // core triggers the event before it attaches the DataTable instance to the element
      setTimeout(() => {
        const control = table.data('uiControlObject') as { param?: Record<string, unknown> }|undefined;
        if (!control || !control.param) {
          return;
        }
        emit('viewChanged', {
          viewDataTable: String(control.param.viewDataTable || ''),
          flat: String(control.param.flat || '0') === '1',
        });
      });
    }

    let bound: HTMLElement|null = null;
    function bind(element: HTMLElement|null) {
      if (bound) {
        $(bound).off('piwik:dataTableLoaded', onLoaded);
      }
      bound = element;
      if (bound) {
        $(bound).on('piwik:dataTableLoaded', onLoaded);
      }
    }
    watch(stage, bind);
    onBeforeUnmount(() => bind(null));

    return {
      stage,
      tabIcon,
      translate,
    };
  },
});
</script>
