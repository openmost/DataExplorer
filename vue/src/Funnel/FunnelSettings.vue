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
      <div class="dataExplorerFunnelSettings__typeHelp">{{ funnel.open
        ? translate('DataExplorer_FunnelOpenHelp')
        : translate('DataExplorer_FunnelClosedHelp') }}</div>
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
          :class="{
            'is-incomplete': !isComplete(step),
            'is-dragged': dragged === index,
            'is-drop-target': over === index && dragged !== index,
          }"
          draggable="true"
          @dragstart="startDrag($event, index)"
          @dragend="dragged = null; over = null"
          @dragover.prevent="over = index"
          @drop.prevent="drop(index)"
        >
          <button
            type="button"
            class="dataExplorerFunnelSettings__stepButton"
            :title="translate('DataExplorer_FunnelEditSteps')"
            @click="editorOpen = true"
          >
            <span
              class="dataExplorerGrip"
              aria-hidden="true"
            ></span>
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
    <label
      v-if="funnel.breakdown"
      class="dataExplorerFunnelSettings__rows"
    >
      <span>{{ translate('DataExplorer_FunnelBreakdownRows') }}</span>
      <span class="dataExplorerSelect">
        <select
          class="browser-default"
          :value="String(funnel.breakdownRows)"
          @change="setBreakdownRows($event)"
        >
          <option
            v-for="option in breakdownRowOptions"
            :key="option.key"
            :value="option.key"
          >{{ option.value }}</option>
        </select>
      </span>
    </label>

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
  setup(props, { emit }) {
    const editorOpen = ref(false);

    // steps reordered by drag and drop: the first step is never optional and follows nothing,
    // the last one is never optional
    const dragged = ref<number|null>(null);
    const over = ref<number|null>(null);

    function startDrag(event: DragEvent, index: number) {
      dragged.value = index;
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(index));
      }
    }

    function drop(index: number) {
      const from = dragged.value;
      dragged.value = null;
      over.value = null;
      if (from === null || from === index) {
        return;
      }
      const steps = props.funnel.steps.map((step) => ({ ...step }));
      const [moved] = steps.splice(from, 1);
      steps.splice(index, 0, moved);
      steps[0] = {
        ...steps[0], optional: false, follow: 'indirect', within: null,
      };
      steps[steps.length - 1] = { ...steps[steps.length - 1], optional: false };
      emit('update', { ...props.funnel, steps });
    }

    function setBreakdownRows(event: Event) {
      emit('update', {
        ...props.funnel,
        breakdownRows: parseInt((event.target as HTMLSelectElement).value, 10),
      });
    }

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
      dragged,
      over,
      startDrag,
      drop,
      setBreakdownRows,
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
