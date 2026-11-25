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
      <span class="dataExplorerSelect dataExplorerFilter__operator">
        <select
          v-model="draft.operator"
          class="dataExplorerControl browser-default"
          :aria-label="translate('DataExplorer_FilterOperatorLabel', dimensionName)"
        >
          <option
            v-for="option in operatorOptions"
            :key="option.key"
            :value="option.key"
          >{{ option.value }}</option>
        </select>
      </span>
      <ValueCombobox
        v-model="draft.value"
        class="dataExplorerFilter__value"
        :options="suggestions"
        :label="translate('DataExplorer_FilterValueLabel', dimensionName)"
        :toggle-label="translate('DataExplorer_FilterShowValues')"
        :placeholder="translate('DataExplorer_FilterValuePlaceholder')"
        :inputmode="isNumeric ? 'decimal' : 'text'"
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
  watch,
} from 'vue';
import { translate } from 'CoreHome';
import { FILTER_OPERATORS } from './explorationState';
import { suggestedValues } from './valueSuggestions';
import ValueCombobox from './ValueCombobox.vue';
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
    ValueCombobox,
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

    // the values of the dimension, offered while the condition is edited
    const suggestions = ref<string[]>([]);
    watch(editing, (open) => {
      if (open && !suggestions.value.length) {
        suggestedValues(props.filter.dimension).then((values) => {
          suggestions.value = values;
        });
      }
    }, { immediate: true });

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

    // a condition still open when its panel is applied keeps the value typed in it
    function commit() {
      if (editing.value && draft.value.trim()) {
        apply();
      }
    }

    return {
      uid,
      editing,
      draft,
      isNumeric,
      suggestions,
      operatorOptions: FILTER_OPERATORS.map((operator) => ({
        key: operator,
        value: operatorLabel(operator),
      })),
      operatorLabel,
      toggle,
      apply,
      cancel,
      commit,
    };
  },
});
</script>
