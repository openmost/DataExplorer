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
      v-if="visualization === 'table' && rowItems.length > 1"
      class="dataExplorerSettings__section"
    >
      <div class="dataExplorerSettings__tableView">
        <h4 id="dataExplorerTableViewTitle">{{ translate('DataExplorer_TableView') }}</h4>
        <div
          class="dataExplorerSegmented"
          role="radiogroup"
          aria-labelledby="dataExplorerTableViewTitle"
        >
          <button
            v-for="view in tableViews"
            :key="view.id"
            type="button"
            role="radio"
            class="dataExplorerSegmented__button"
            :class="{ 'is-active': tableView === view.id }"
            :aria-checked="tableView === view.id ? 'true' : 'false'"
            @click="$emit('setTableView', view.id)"
          >{{ view.label }}</button>
        </div>
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
    >
      <template
        v-for="(group, groupIndex) in filterGroups"
        :key="groupIndex"
      >
      <div
        v-if="groupIndex > 0"
        class="dataExplorerFilterGroup__join"
        aria-hidden="true"
      >{{ translate('DataExplorer_FilterAnd') }}</div>
      <div
        class="dataExplorerFilterGroup"
        :class="{ 'is-drop-target': overGroup === group[0].filter.group }"
        @dragover="onGroupDragOver($event, group[0].filter.group)"
        @dragleave="overGroup = null"
        @drop="onGroupDrop($event, group[0].filter.group)"
      >
        <ul
          class="dataExplorerFilters"
          :aria-label="group.length > 1
            ? translate('DataExplorer_FilterGroupAnyLabel', groupIndex + 1)
            : translate('DataExplorer_FilterGroupLabel', groupIndex + 1)"
        >
          <template
            v-for="(view, position) in group"
            :key="filterKey(view.filter)"
          >
            <li
              v-if="position > 0"
              class="dataExplorerFilters__or"
              aria-hidden="true"
            >{{ translate('DataExplorer_FilterOr') }}</li>
            <FilterRow
              :filter="view.filter"
              :dimension-name="view.name"
              :reason="view.reason"
              draggable="true"
              :class="{ 'is-dragged': draggedIndex === view.index }"
              @dragstart="startFilterDrag($event, view.index)"
              @dragend="endFilterDrag"
              @update="$emit('updateFilter', view.index, $event)"
              @remove="$emit('removeFilter', view.index)"
            />
          </template>
        </ul>
        <div class="dataExplorerFilterGroup__actions">
          <FilterOrButton
            :options="dimensionOptions"
            @add="$emit('addFilterToGroup', group[0].filter.group, $event)"
          />
          <FilterOrButton
            v-if="groupIndex === filterGroups.length - 1"
            :options="dimensionOptions"
            :label="translate('DataExplorer_FilterAddAnd')"
            :aria-label="translate('DataExplorer_FilterAddAndLabel')"
            @add="$emit('add', 'filters', $event)"
          />
        </div>
      </div>
      </template>
      <div
        v-if="draggedIndex !== null"
        class="dataExplorerFilterGroup__newDrop"
        :class="{ 'is-drop-target': overGroup === NEW_GROUP }"
        @dragover="onGroupDragOver($event, NEW_GROUP)"
        @dragleave="overGroup = null"
        @drop="onGroupDrop($event, NEW_GROUP)"
      >{{ translate('DataExplorer_FilterDropNewGroup') }}</div>
      <p
        v-if="filterSummary"
        class="dataExplorerFilterSummary"
      >{{ filterSummary }}</p>
    </DropZone>
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
import DropZone from './DropZone.vue';
import FilterRow from './FilterRow.vue';
import FilterOrButton from './FilterOrButton.vue';
import {
  availableVisualizations,
  hasRowDimensions,
  VISUALIZATION_ICONS as ICONS,
} from './explorationState';
import type {
  CatalogLimits,
  Filter,
  TableView,
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
    FilterOrButton,
  },
  props: {
    visualization: {
      type: String as PropType<Visualization>,
      required: true,
    },
    treemap: {
      type: Boolean,
      default: false,
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
    tableView: {
      type: String as PropType<TableView>,
      default: 'flat',
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
  emits: [
    'setVisualization',
    'add',
    'remove',
    'updateFilter',
    'removeFilter',
    'addFilterToGroup',
    'moveFilter',
    'setTableView',
  ],
  setup(props, { emit }) {
    const visualizations = availableVisualizations(props.treemap).map((id) => ({
      id,
      icon: ICONS[id],
      label: translate(`DataExplorer_Visualization_${id}`),
    }));

    const hasRows = computed(() => hasRowDimensions(props.visualization));

    // the pie, the treemap and the KPI card show one metric: one more can't be added, those
    // already there stay (hatched) for the visualizations that show them all
    const singleValue = computed(() => ['pie', 'treemap', 'kpicard'].includes(props.visualization));
    const maxValues = computed(() => (singleValue.value ? 1 : props.limits.maxMetrics));
    const activeValues = computed(() => (singleValue.value ? 1 : 0));
    const inactiveValueReason = computed(() => {
      if (props.visualization === 'pie') {
        return translate('DataExplorer_PieFirstMetricOnly');
      }
      if (props.visualization === 'treemap') {
        return translate('DataExplorer_TreemapFirstMetricOnly');
      }
      return props.visualization === 'kpicard' ? translate('DataExplorer_KpiFirstMetricOnly') : '';
    });

    // the filters of a group are ORed, the groups ANDed
    const filterGroups = computed(() => {
      const groups = new Map<number, FilterView[]>();
      props.filters.forEach((view) => {
        const group = groups.get(view.filter.group) || [];
        group.push(view);
        groups.set(view.filter.group, group);
      });
      return [...groups.values()];
    });

    // "(Country is France or Belgium) and (Device type is Smartphone)", filters with a value only
    const filterSummary = computed(() => {
      const parts = filterGroups.value
        .map((group) => group.filter((view) => view.filter.value.trim() !== ''))
        .filter((group) => group.length > 0)
        .map((group) => {
          const text = group
            .map((view) => {
              const operator = translate(`DataExplorer_FilterOperator_${view.filter.operator}`);
              return `${view.name} ${operator} ${view.filter.value}`;
            })
            .join(` ${translate('DataExplorer_FilterOr')} `);
          return group.length > 1 ? `(${text})` : text;
        });
      return parts.length > 1 || (parts.length === 1 && parts[0].startsWith('('))
        ? parts.join(` ${translate('DataExplorer_FilterAnd')} `)
        : '';
    });

    // a filter dragged onto another group joins it (OR), onto the strip below the groups it starts
    // a new one (AND)
    const NEW_GROUP = -1;
    const FILTER_TYPE = 'application/x-dataexplorer-filter';
    const draggedIndex = ref<number|null>(null);
    const overGroup = ref<number|null>(null);

    function startFilterDrag(event: DragEvent, index: number) {
      draggedIndex.value = index;
      if (event.dataTransfer) {
        event.dataTransfer.setData(FILTER_TYPE, String(index));
        event.dataTransfer.effectAllowed = 'move';
      }
    }

    function endFilterDrag() {
      draggedIndex.value = null;
      overGroup.value = null;
    }

    function onGroupDragOver(event: DragEvent, group: number) {
      if (draggedIndex.value === null) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
      }
      overGroup.value = group;
    }

    function onGroupDrop(event: DragEvent, group: number) {
      if (draggedIndex.value === null) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      emit('moveFilter', draggedIndex.value, group);
      endFilterDrag();
    }

    // a filter keeps its row (and an open editor) when a filter is inserted or removed before it
    const keys = new WeakMap<Filter, number>();
    let nextKey = 0;
    function filterKey(filter: Filter): number {
      if (!keys.has(filter)) {
        nextKey += 1;
        keys.set(filter, nextKey);
      }
      return keys.get(filter) as number;
    }

    const tableViews = [
      { id: 'flat' as TableView, label: translate('DataExplorer_TableViewFlat') },
      { id: 'hierarchical' as TableView, label: translate('DataExplorer_TableViewHierarchical') },
    ];

    return {
      tableViews,
      filterGroups,
      filterKey,
      filterSummary,
      NEW_GROUP,
      draggedIndex,
      overGroup,
      startFilterDrag,
      endFilterDrag,
      onGroupDragOver,
      onGroupDrop,
      visualizations,
      hasRows,
      maxValues,
      activeValues,
      inactiveValueReason,
    };
  },
});
</script>
