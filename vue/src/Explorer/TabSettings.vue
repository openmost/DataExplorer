<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerColumn dataExplorerSettings">
    <h3 class="dataExplorerColumn__title">{{ translate('DataExplorer_TabSettings') }}</h3>

    <div class="dataExplorerSettings__section">
      <h4 id="dataExplorerVisualizationTitle">{{ translate('DataExplorer_Visualization') }}</h4>
      <div
        class="dataExplorerVisualizations"
        role="group"
        aria-labelledby="dataExplorerVisualizationTitle"
      >
        <button
          v-for="option in visualizations"
          :key="option.id"
          type="button"
          class="dataExplorerIconButton dataExplorerVisualizations__button"
          :class="{ 'is-active': option.id === visualization }"
          :data-visualization="option.id"
          :aria-pressed="option.id === visualization ? 'true' : 'false'"
          :aria-label="option.label"
          :title="option.label"
          @click="$emit('setVisualization', option.id)"
        >
          <span :class="option.icon" aria-hidden="true"></span>
        </button>
      </div>
    </div>

    <div
      v-if="visualization === 'table'"
      class="dataExplorerSettings__section"
    >
      <h4 id="dataExplorerCellTypeTitle">{{ translate('DataExplorer_CellType') }}</h4>
      <div
        class="dataExplorerCellTypes"
        role="group"
        aria-labelledby="dataExplorerCellTypeTitle"
      >
        <button
          v-for="option in cellTypes"
          :key="option"
          type="button"
          class="dataExplorerCellTypes__button"
          :class="{ 'is-active': option === cellType }"
          :data-cell-type="option"
          :aria-pressed="option === cellType ? 'true' : 'false'"
          @click="$emit('setCellType', option)"
        >{{ translate(`DataExplorer_CellType_${option}`) }}</button>
      </div>
    </div>

    <DropZone
      zone="segment"
      accepts="segment"
      :title="translate('DataExplorer_SegmentComparison')"
      :items="segmentItems"
      :options="segmentOptions"
      :max="1"
      :placeholder="translate('DataExplorer_DropSegment')"
      :empty-menu-text="translate('DataExplorer_NoSegments')"
      @add="$emit('add', 'segment', $event)"
      @remove="$emit('remove', 'segment', $event)"
    />

    <DropZone
      zone="rows"
      accepts="dimension"
      :title="translate('DataExplorer_Rows')"
      :items="rowItems"
      :options="dimensionOptions"
      :max="limits.maxDimensions"
      :disabled="!hasRows"
      :disabled-reason="translate('DataExplorer_RowsNotUsedByVisualization')"
      :placeholder="translate('DataExplorer_DropDimension')"
      :empty-menu-text="translate('DataExplorer_NoDimensions')"
      @add="$emit('add', 'rows', $event)"
      @remove="$emit('remove', 'rows', $event)"
    />

    <DropZone
      zone="column"
      accepts="dimension"
      :title="translate('DataExplorer_Columns')"
      :items="columnItems"
      :options="dimensionOptions"
      :max="limits.maxColumns"
      :disabled="!hasRows"
      :disabled-reason="translate('DataExplorer_RowsNotUsedByVisualization')"
      :placeholder="translate('DataExplorer_DropDimension')"
      :empty-menu-text="translate('DataExplorer_NoDimensions')"
      @add="$emit('add', 'column', $event)"
      @remove="$emit('remove', 'column', $event)"
    />

    <DropZone
      zone="values"
      accepts="metric"
      :title="translate('DataExplorer_Values')"
      :items="valueItems"
      :options="metricOptions"
      :max="maxValues"
      :active-count="activeValues"
      :inactive-reason="inactiveValueReason"
      :placeholder="translate('DataExplorer_DropMetric')"
      :empty-menu-text="translate('DataExplorer_NoMetrics')"
      @add="$emit('add', 'values', $event)"
      @remove="$emit('remove', 'values', $event)"
    />
    <div
      v-if="inactiveValueReason && valueItems.length > 1"
      class="dataExplorerSettings__hint"
    >{{ inactiveValueReason }}</div>

    <DropZone
      zone="filters"
      accepts="dimension"
      :title="translate('DataExplorer_Filters')"
      :options="dimensionOptions"
      unlimited
      :placeholder="translate('DataExplorer_DropFilter')"
      :empty-menu-text="translate('DataExplorer_NoDimensions')"
      @add="$emit('add', 'filters', $event)"
    />
    <ul
      v-if="filters.length"
      class="dataExplorerFilters"
      :aria-label="translate('DataExplorer_Filters')"
    >
      <FilterRow
        v-for="(filter, index) in filters"
        :key="`${index}-${filter.filter.dimension}`"
        :filter="filter.filter"
        :dimension-name="filter.name"
        :reason="filter.reason"
        @update="$emit('updateFilter', index, $event)"
        @remove="$emit('removeFilter', index)"
      />
    </ul>
  </div>
</template>

<script lang="ts">
import { computed, defineComponent, PropType } from 'vue';
import { translate } from 'CoreHome';
import DropZone from './DropZone.vue';
import FilterRow from './FilterRow.vue';
import {
  CELL_TYPES,
  hasRowDimensions,
  VISUALIZATION_ICONS as ICONS,
  VISUALIZATIONS,
} from './explorationState';
import type {
  CatalogLimits,
  CellType,
  FilterView,
  Visualization,
  ZoneItem,
} from '../types';

/**
 * Middle column: visualization switcher and the drop zones of the exploration.
 */
export default defineComponent({
  name: 'TabSettings',
  components: {
    DropZone,
    FilterRow,
  },
  props: {
    visualization: {
      type: String as PropType<Visualization>,
      required: true,
    },
    cellType: {
      type: String as PropType<CellType>,
      default: 'plain',
    },
    limits: {
      type: Object as PropType<CatalogLimits>,
      required: true,
    },
    rowItems: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
    columnItems: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
    valueItems: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
    segmentItems: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
    filters: {
      type: Array as PropType<FilterView[]>,
      required: true,
    },
    dimensionOptions: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
    metricOptions: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
    segmentOptions: {
      type: Array as PropType<ZoneItem[]>,
      required: true,
    },
  },
  emits: ['setVisualization', 'setCellType', 'add', 'remove', 'updateFilter', 'removeFilter'],
  setup(props) {
    const visualizations = VISUALIZATIONS.map((id) => ({
      id,
      icon: ICONS[id],
      label: translate(`DataExplorer_Visualization_${id}`),
    }));

    const hasRows = computed(() => hasRowDimensions(props.visualization));

    // the pie and the KPI card show one metric: one more can't be added, those already there stay
    // (hatched) for the visualizations that show them all
    const singleValue = computed(() => props.visualization === 'pie' || props.visualization === 'kpicard');
    const maxValues = computed(() => (singleValue.value ? 1 : props.limits.maxMetrics));
    const activeValues = computed(() => (singleValue.value ? 1 : 0));
    const inactiveValueReason = computed(() => {
      if (props.visualization === 'pie') {
        return translate('DataExplorer_PieFirstMetricOnly');
      }
      return props.visualization === 'kpicard' ? translate('DataExplorer_KpiFirstMetricOnly') : '';
    });

    return {
      visualizations,
      cellTypes: CELL_TYPES,
      hasRows,
      maxValues,
      activeValues,
      inactiveValueReason,
    };
  },
});
</script>
