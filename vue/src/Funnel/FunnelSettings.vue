<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerColumn dataExplorerSettings dataExplorerFunnelSettings">
    <h3 class="dataExplorerColumn__title">{{ translate('DataExplorer_TabSettings') }}</h3>

    <div class="dataExplorerSettings__section">
      <h4 id="dataExplorerFunnelTypeTitle">{{ translate('DataExplorer_FunnelType') }}</h4>
      <div
        class="dataExplorerSegmented"
        role="radiogroup"
        aria-labelledby="dataExplorerFunnelTypeTitle"
      >
        <button
          v-for="option in types"
          :key="option.id"
          type="button"
          role="radio"
          class="dataExplorerSegmented__button"
          :class="{ 'is-active': funnel.open === option.open }"
          :aria-checked="funnel.open === option.open ? 'true' : 'false'"
          :title="option.help"
          @click="$emit('update', { ...funnel, open: option.open })"
        >{{ option.label }}</button>
      </div>
      <p class="dataExplorerSettings__hint">{{ funnel.open
        ? translate('DataExplorer_FunnelOpenHelp')
        : translate('DataExplorer_FunnelClosedHelp') }}</p>
    </div>

    <div class="dataExplorerSettings__section">
      <div class="dataExplorerFunnelSettings__stepsHead">
        <h4>{{ translate('DataExplorer_FunnelSteps') }}</h4>
        <button
          type="button"
          class="dataExplorerIconButton dataExplorerFunnelSettings__edit"
          :aria-label="translate('DataExplorer_FunnelEditSteps')"
          :title="translate('DataExplorer_FunnelEditSteps')"
          @click="editorOpen = true"
        >
          <span class="icon-edit" aria-hidden="true"></span>
        </button>
      </div>
      <ol class="dataExplorerFunnelSettings__steps">
        <li
          v-for="(step, index) in funnel.steps"
          :key="index"
          class="dataExplorerFunnelSettings__step"
          :class="{ 'is-incomplete': !isComplete(step) }"
        >
          <button
            type="button"
            class="dataExplorerFunnelSettings__stepButton"
            @click="editorOpen = true"
          >
            <span class="dataExplorerFunnel__stepNumber">{{ index + 1 }}</span>
            <span class="dataExplorerFunnelSettings__stepText">
              <span class="dataExplorerFunnelSettings__stepName">{{ step.name }}</span>
              <span class="dataExplorerFunnelSettings__stepConditions">{{ summary(step) }}</span>
            </span>
            <span
              v-if="step.optional"
              class="dataExplorerFunnelSettings__tag"
            >{{ translate('DataExplorer_FunnelOptional') }}</span>
          </button>
        </li>
      </ol>
    </div>

    <DropZone
      zone="breakdown"
      accepts="dimension"
      :title="translate('DataExplorer_FunnelBreakdown')"
      :items="breakdownItems"
      :options="dimensionOptions"
      :max="1"
      :placeholder="translate('DataExplorer_DropDimension')"
      :empty-menu-text="translate('DataExplorer_NoDimensions')"
      @add="$emit('update', { ...funnel, breakdown: $event })"
      @remove="$emit('update', { ...funnel, breakdown: null })"
    />
    <div
      v-if="funnel.breakdown"
      class="dataExplorerSettings__section"
    >
      <Field
        uicontrol="select"
        name="dataExplorerFunnelBreakdownRows"
        :title="translate('DataExplorer_FunnelBreakdownRows')"
        :options="breakdownRowOptions"
        :model-value="String(funnel.breakdownRows)"
        @update:model-value="$emit('update', { ...funnel, breakdownRows: parseInt($event, 10) })"
      />
    </div>

    <FunnelStepsEditor
      v-model="editorOpen"
      :steps="funnel.steps"
      :dimension-options="dimensionOptions"
      @apply="$emit('update', { ...funnel, steps: $event })"
    />
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  ref,
} from 'vue';
import { translate } from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import DropZone from '../Explorer/DropZone.vue';
import FunnelStepsEditor from './FunnelStepsEditor.vue';
import { BREAKDOWN_ROWS } from '../Explorer/funnelState';
import { isFilterComplete } from '../Explorer/explorationState';
import type {
  FunnelState,
  FunnelStep,
  VariableView,
  ZoneItem,
} from '../types';

/**
 * Middle column of the Funnel tab: closed or open funnel, the steps (edited in a side panel like
 * the GA4 step editor) and the breakdown dimension.
 */
export default defineComponent({
  name: 'FunnelSettings',
  components: {
    DropZone,
    Field,
    FunnelStepsEditor,
  },
  props: {
    funnel: {
      type: Object as PropType<FunnelState>,
      required: true,
    },
    dimensionOptions: {
      type: Array as PropType<VariableView[]>,
      default: () => [],
    },
    // the breakdown dimension, with why it cannot break a funnel down
    breakdownItems: {
      type: Array as PropType<ZoneItem[]>,
      default: () => [],
    },
  },
  emits: ['update'],
  setup(props) {
    const editorOpen = ref(false);

    const types = [
      {
        id: 'closed',
        open: false,
        label: translate('DataExplorer_FunnelClosed'),
        help: translate('DataExplorer_FunnelClosedHelp'),
      },
      {
        id: 'open',
        open: true,
        label: translate('DataExplorer_FunnelOpen'),
        help: translate('DataExplorer_FunnelOpenHelp'),
      },
    ];

    const names = computed(() => {
      const map: Record<string, string> = {};
      props.dimensionOptions.forEach((option) => {
        map[option.id] = option.name;
      });
      return map;
    });

    function summary(step: FunnelStep) {
      if (!step.conditions.length) {
        return translate('DataExplorer_FunnelNoCondition');
      }
      return step.conditions
        .map((condition) => `${names.value[condition.dimension] || condition.dimension} ${
          translate(`DataExplorer_FilterOperator_${condition.operator}`)} ${condition.value}`)
        .join(', ');
    }

    function isComplete(step: FunnelStep) {
      return step.name.trim() !== '' && step.conditions.length > 0
        && step.conditions.every(isFilterComplete);
    }

    return {
      editorOpen,
      types,
      summary,
      isComplete,
      breakdownRowOptions: BREAKDOWN_ROWS.map((rows) => ({
        key: String(rows),
        value: String(rows),
      })),
      translate,
    };
  },
});
</script>
