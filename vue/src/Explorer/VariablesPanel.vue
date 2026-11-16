<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerColumn dataExplorerVariables">
    <h3 class="dataExplorerColumn__title">{{ translate('DataExplorer_Variables') }}</h3>
    <section
      v-for="section in sections"
      :key="section.kind"
      class="dataExplorerVariables__section"
      :data-kind="section.kind"
    >
      <div class="dataExplorerVariables__header">
        <h4 :id="`dataExplorerVariables-${section.kind}`">{{ section.title }}</h4>
        <button
          type="button"
          class="dataExplorerIconButton"
          :class="`dataExplorerVariables__add-${section.kind}`"
          :aria-label="section.addLabel"
          :title="section.addLabel"
          aria-haspopup="dialog"
          :aria-expanded="isOpen(section.kind) ? 'true' : 'false'"
          @click="open(section.kind, $event)"
        >
          <span class="icon-add" aria-hidden="true"></span>
        </button>
      </div>
      <ul
        class="dataExplorerVariables__list"
        :aria-labelledby="`dataExplorerVariables-${section.kind}`"
      >
        <li
          v-for="item in section.items"
          :key="item.id"
          class="dataExplorerVariable"
          :class="{ 'is-incompatible': !!item.reason, 'is-used': item.used }"
          :data-id="item.id"
          draggable="true"
          tabindex="0"
          :title="item.reason || section.useHint"
          :aria-description="item.reason || section.useHint"
          @dragstart="onDragStart($event, section.kind, item.id)"
          @dragend="onDragEnd"
          @dblclick="$emit('use', section.kind, item.id)"
          @keydown.enter.self.prevent="$emit('use', section.kind, item.id)"
          @keydown.space.self.prevent="$emit('use', section.kind, item.id)"
          @keydown.delete.self.prevent="$emit('removeVariable', section.kind, item.id)"
        >
          <span class="dataExplorerVariable__name">{{ item.name }}</span>
          <button
            type="button"
            class="dataExplorerIconButton dataExplorerVariable__remove"
            :aria-label="translate('DataExplorer_RemoveVariable', item.name)"
            :title="translate('DataExplorer_RemoveVariable', item.name)"
            @click="$emit('removeVariable', section.kind, item.id)"
          >
            <span class="icon-close" aria-hidden="true"></span>
          </button>
        </li>
        <li
          v-if="!section.items.length"
          class="dataExplorerVariables__empty"
        >{{ section.emptyText }}</li>
      </ul>
    </section>
    <CataloguePanel
      :title="activeSection ? activeSection.addLabel : ''"
      :groups="activeSection ? activeSection.groups : []"
      :added="activeSection ? activeSection.added : []"
      @add="activeSection && $emit('addVariable', activeSection.kind, $event)"
      @remove="activeSection && $emit('removeVariable', activeSection.kind, $event)"
    />
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
} from 'vue';
import { translate } from 'CoreHome';
import CataloguePanel from './CataloguePanel.vue';
import { catalogueState, openCatalogue } from './catalogueStore';
import { endDrag, startDrag } from './dragState';
import type { PickerGroup, VariableKind, VariableView } from '../types';

/**
 * Left column: the dimensions, metrics and segments added to this exploration, each list with a
 * "+" picker over the catalogue. Items are dragged onto the drop zones, or used with a double
 * click, Enter or Space.
 */
export default defineComponent({
  name: 'VariablesPanel',
  components: {
    CataloguePanel,
  },
  props: {
    dimensions: {
      type: Array as PropType<VariableView[]>,
      required: true,
    },
    metrics: {
      type: Array as PropType<VariableView[]>,
      required: true,
    },
    segments: {
      type: Array as PropType<VariableView[]>,
      required: true,
    },
    dimensionGroups: {
      type: Array as PropType<PickerGroup[]>,
      required: true,
    },
    metricGroups: {
      type: Array as PropType<PickerGroup[]>,
      required: true,
    },
    segmentGroups: {
      type: Array as PropType<PickerGroup[]>,
      required: true,
    },
  },
  emits: ['addVariable', 'removeVariable', 'use'],
  setup(props) {
    const sections = computed(() => [
      {
        kind: 'dimension' as VariableKind,
        title: translate('DataExplorer_Dimensions'),
        addLabel: translate('DataExplorer_AddDimensions'),
        emptyText: translate('DataExplorer_NoDimensions'),
        useHint: translate('DataExplorer_DimensionUseHint'),
        items: props.dimensions,
        groups: props.dimensionGroups,
        added: props.dimensions.map((item) => item.id),
      },
      {
        kind: 'metric' as VariableKind,
        title: translate('DataExplorer_Metrics'),
        addLabel: translate('DataExplorer_AddMetrics'),
        emptyText: translate('DataExplorer_NoMetrics'),
        useHint: translate('DataExplorer_MetricUseHint'),
        items: props.metrics,
        groups: props.metricGroups,
        added: props.metrics.map((item) => item.id),
      },
      {
        kind: 'segment' as VariableKind,
        title: translate('DataExplorer_Segments'),
        addLabel: translate('DataExplorer_AddSegments'),
        emptyText: translate('DataExplorer_NoSegments'),
        useHint: translate('DataExplorer_SegmentUseHint'),
        items: props.segments,
        groups: props.segmentGroups,
        added: props.segments.map((item) => item.id),
      },
    ]);

    const activeSection = computed(
      () => sections.value.find((section) => section.kind === catalogueState.kind) || null,
    );

    function isOpen(kind: VariableKind) {
      return catalogueState.open && catalogueState.kind === kind;
    }

    function open(kind: VariableKind, event: MouseEvent) {
      openCatalogue(kind, event.currentTarget as HTMLElement);
    }

    function onDragStart(event: DragEvent, kind: VariableKind, id: string) {
      startDrag(event, { kind, id });
    }

    return {
      sections,
      activeSection,
      isOpen,
      open,
      onDragStart,
      onDragEnd: endDrag,
    };
  },
});
</script>
