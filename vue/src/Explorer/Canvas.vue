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
  PropType,
} from 'vue';
import { translate, WidgetLoader } from 'CoreHome';
import { VISUALIZATION_ICONS } from './explorationState';
import type { Visualization } from '../types';

/**
 * Right column: the exploration in the native Matomo visualization of its report type, loaded
 * from DataExplorer.preview like the saved report it would become.
 */
export default defineComponent({
  name: 'Canvas',
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
  emits: ['openPeriodSelector'],
  setup(props) {
    const tabIcon = computed(() => VISUALIZATION_ICONS[props.visualization as Visualization]);

    return {
      tabIcon,
      translate,
    };
  },
});
</script>
