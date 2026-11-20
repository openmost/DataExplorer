<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <li
    class="dataExplorerFilter"
    :class="{ 'is-incompatible': !!reason, 'is-editing': editing }"
    :data-dimension="filter.dimension"
  >
    <div class="dataExplorerFilter__header">
      <button
        type="button"
        class="dataExplorerFilter__summary"
        :title="reason || translate('DataExplorer_FilterEdit', dimensionName)"
        :aria-expanded="editing ? 'true' : 'false'"
        @click="toggle"
      >
        <span
          v-if="reason"
          class="icon-warning"
          aria-hidden="true"
        />
        <span class="dataExplorerFilter__name">{{ dimensionName }}</span>
        <span
          v-if="filter.value"
          class="dataExplorerFilter__condition"
        >{{ operatorLabel(filter.operator) }} {{ filter.value }}</span>
      </button>
      <button
        type="button"
        class="dataExplorerIconButton"
        :aria-label="translate('DataExplorer_RemoveFilter', dimensionName)"
        :title="translate('DataExplorer_RemoveFilter', dimensionName)"
        @click="$emit('remove')"
      >
        <span class="icon-close" aria-hidden="true" />
      </button>
    </div>
    <div
      v-if="reason"
      class="dataExplorerFilter__reason"
    >{{ reason }}</div>
    <div
      v-if="editing"
      class="dataExplorerFilter__fields"
      @keydown.enter.prevent="apply"
      @keydown.esc.stop.prevent="cancel"
    >
      <div class="dataExplorerFilter__label">{{ translate('DataExplorer_FilterConditions') }}</div>
      <Field
        uicontrol="select"
        :name="`${uid}-operator`"
        class="dataExplorerFilter__operator"
        :full-width="true"
        :options="operatorOptions"
        :model-value="draft.operator"
        :ui-control-attributes="{
          'aria-label': translate('DataExplorer_FilterOperatorLabel', dimensionName),
        }"
        @update:model-value="draft.operator = $event"
      />
      <Field
        uicontrol="text"
        :name="`${uid}-value`"
        class="dataExplorerFilter__value"
        :full-width="true"
        :placeholder="translate('DataExplorer_FilterValuePlaceholder')"
        :model-value="draft.value"
        :ui-control-attributes="{
          'aria-label': translate('DataExplorer_FilterValueLabel', dimensionName),
          inputmode: isNumeric ? 'decimal' : 'text',
        }"
        @update:model-value="draft.value = String($event)"
      />
      <div class="dataExplorerFilter__actions">
        <button
          type="button"
          class="btn-flat"
          @click="cancel"
        >{{ translate('General_Cancel') }}</button>
        <button
          type="button"
          class="btn"
          :disabled="!draft.value.trim()"
          @click="apply"
        >{{ translate('DataExplorer_FilterApply') }}</button>
      </div>
    </div>
  </li>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  reactive,
  ref,
} from 'vue';
import { translate } from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import { FILTER_OPERATORS } from './explorationState';
import type { Filter, FilterOperator } from '../types';

let nextId = 0;

/**
 * One filter of the Filters zone, like GA4: a summary line ("Device type is exactly Smartphone")
 * that opens the condition, applied with Apply, so the result does not run on every keystroke.
 * A new filter opens directly; cancelling it before any Apply removes it.
 */
export default defineComponent({
  name: 'FilterRow',
  components: {
    Field,
  },
  props: {
    filter: {
      type: Object as PropType<Filter>,
      required: true,
    },
    dimensionName: {
      type: String,
      required: true,
    },
    reason: {
      type: String,
      default: '',
    },
  },
  emits: ['update', 'remove'],
  setup(props, { emit }) {
    nextId += 1;
    const uid = `dataExplorerFilter${nextId}`;

    const editing = ref(props.filter.value.trim() === '');
    const draft = reactive({ operator: props.filter.operator, value: props.filter.value });

    const isNumeric = computed(() => ['gt', 'gte', 'lt', 'lte'].includes(draft.operator));

    function operatorLabel(operator: FilterOperator) {
      return translate(`DataExplorer_FilterOperator_${operator}`);
    }

    function toggle() {
      if (editing.value) {
        editing.value = false;
        return;
      }
      draft.operator = props.filter.operator;
      draft.value = props.filter.value;
      editing.value = true;
    }

    function apply() {
      if (!draft.value.trim()) {
        return;
      }
      emit('update', { ...props.filter, operator: draft.operator, value: draft.value });
      editing.value = false;
    }

    function cancel() {
      if (props.filter.value.trim() === '') {
        emit('remove');
        return;
      }
      draft.operator = props.filter.operator;
      draft.value = props.filter.value;
      editing.value = false;
    }

    return {
      uid,
      editing,
      draft,
      isNumeric,
      operatorOptions: FILTER_OPERATORS.map((operator) => ({
        key: operator,
        value: operatorLabel(operator),
      })),
      operatorLabel,
      toggle,
      apply,
      cancel,
    };
  },
});
</script>
