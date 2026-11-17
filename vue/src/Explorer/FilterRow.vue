<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <li
    class="dataExplorerFilter"
    :class="{ 'is-incompatible': !!reason }"
    :data-dimension="filter.dimension"
  >
    <div class="dataExplorerFilter__header">
      <span
        :id="`${uid}-name`"
        class="dataExplorerFilter__name"
        :title="reason"
      >
        <span
          v-if="reason"
          class="icon-warning"
          aria-hidden="true"
        ></span>
        {{ dimensionName }}
      </span>
      <button
        type="button"
        class="dataExplorerIconButton"
        :aria-label="translate('DataExplorer_RemoveFilter', dimensionName)"
        :title="translate('DataExplorer_RemoveFilter', dimensionName)"
        @click="$emit('remove')"
      >
        <span class="icon-close" aria-hidden="true"></span>
      </button>
    </div>
    <div
      v-if="reason"
      class="dataExplorerFilter__reason"
    >{{ reason }}</div>
    <div class="dataExplorerFilter__fields">
      <Field
        uicontrol="select"
        :name="`${uid}-operator`"
        class="dataExplorerFilter__operator"
        :full-width="true"
        :options="operatorOptions"
        :model-value="filter.operator"
        :ui-control-attributes="{
          'aria-label': translate('DataExplorer_FilterOperatorLabel', dimensionName),
        }"
        @update:model-value="update({ operator: $event })"
      />
      <Field
        uicontrol="text"
        :name="`${uid}-value`"
        class="dataExplorerFilter__value"
        :full-width="true"
        :placeholder="translate('DataExplorer_FilterValuePlaceholder')"
        :model-value="filter.value"
        :ui-control-attributes="{
          'aria-label': translate('DataExplorer_FilterValueLabel', dimensionName),
          inputmode: isNumeric ? 'decimal' : 'text',
        }"
        @update:model-value="update({ value: String($event) })"
      />
    </div>
  </li>
</template>

<script lang="ts">
import { computed, defineComponent, PropType } from 'vue';
import { translate } from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import { FILTER_OPERATORS } from './explorationState';
import type { Filter } from '../types';

let nextId = 0;

/**
 * One filter of the Filters zone: operator and value on a dimension. Applied as soon as it has a
 * value.
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

    const isNumeric = computed(() => ['gt', 'lt'].includes(props.filter.operator));

    function update(patch: Partial<Filter>) {
      emit('update', { ...props.filter, ...patch });
    }

    return {
      uid,
      operatorOptions: FILTER_OPERATORS.map((operator) => ({
        key: operator,
        value: translate(`DataExplorer_FilterOperator_${operator}`),
      })),
      isNumeric,
      update,
    };
  },
});
</script>
