<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    class="dataExplorerDropZone"
    :class="{
      'is-over': isOver,
      'is-accepting': isAccepting,
      'is-full': isFull,
      'is-disabled': disabled,
    }"
    :data-zone="zone"
    :data-kind="accepts"
    @dragover="onDragOver"
    @dragenter="onDragOver"
    @dragleave="isOver = false"
    @drop="onDrop"
  >
    <div class="dataExplorerDropZone__header">
      <h4 :id="headingId">{{ title }}</h4>
      <span
        v-if="max > 1"
        class="dataExplorerDropZone__count"
      >{{ translate('DataExplorer_ZoneCount', items.length, max) }}</span>
    </div>
    <div
      v-if="disabled && disabledReason"
      class="dataExplorerDropZone__hint"
    >{{ disabledReason }}</div>
    <ul
      class="dataExplorerDropZone__chips"
      :aria-labelledby="headingId"
    >
      <li
        v-for="(item, index) in items"
        :key="item.id"
        ref="chips"
        class="dataExplorerChip"
        :class="{ 'is-incompatible': !!item.reason }"
        :data-id="item.id"
        tabindex="0"
        :title="item.reason || translate('DataExplorer_ChipRemoveHint')"
        @keydown.delete.self.prevent="remove(item.id, index)"
      >
        <span
          v-if="item.reason"
          class="icon-warning"
          aria-hidden="true"
        ></span>
        <span class="dataExplorerChip__name">{{ item.name }}</span>
        <span
          v-if="item.reason"
          class="dataExplorerVisuallyHidden"
        >{{ item.reason }}</span>
        <button
          type="button"
          class="dataExplorerIconButton dataExplorerChip__remove"
          :aria-label="translate('DataExplorer_RemoveFromZone', item.name, title)"
          :title="translate('DataExplorer_RemoveFromZone', item.name, title)"
          @click="remove(item.id, index)"
        >
          <span class="icon-close" aria-hidden="true"></span>
        </button>
      </li>
    </ul>
    <div
      v-if="!isFull && !disabled"
      class="dataExplorerDropZone__slot"
    >
      <button
        ref="slotButton"
        type="button"
        class="dataExplorerDropZone__select"
        :aria-expanded="menuOpen ? 'true' : 'false'"
        @click="menuOpen = !menuOpen"
      >
        <span
          class="icon-add"
          aria-hidden="true"
        ></span>
        <span>{{ placeholder }}</span>
      </button>
      <ul
        v-if="menuOpen"
        class="dataExplorerDropZone__menu"
        v-focus-anywhere-but-here="{ blur: closeMenu }"
        @keydown.esc.stop.prevent="closeMenu(true)"
      >
        <li
          v-for="option in options"
          :key="option.id"
        >
          <button
            type="button"
            :disabled="!!option.reason"
            :title="option.reason"
            @click="choose(option.id)"
          >{{ option.name }}</button>
        </li>
        <li
          v-if="!options.length"
          class="dataExplorerDropZone__menuEmpty"
        >{{ emptyMenuText }}</li>
      </ul>
    </div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  PropType,
  ref,
} from 'vue';
import { FocusAnywhereButHere } from 'CoreHome';
import { dragState, droppedPayload, endDrag } from './dragState';
import type { VariableKind, ZoneItem } from '../types';

/**
 * A drop zone of the tab settings (Rows, Columns, Values, Filters, Segment comparison): accepts
 * variables of one kind dragged from the variables column, or picked with the "select" button,
 * shows them as chips removable with their button or with Delete / Backspace.
 */
export default defineComponent({
  name: 'DropZone',
  props: {
    zone: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    accepts: {
      type: String as PropType<VariableKind>,
      required: true,
    },
    items: {
      type: Array as PropType<ZoneItem[]>,
      default: () => [],
    },
    // variables of the accepted kind that can be picked with the select button
    options: {
      type: Array as PropType<ZoneItem[]>,
      default: () => [],
    },
    max: {
      type: Number,
      default: 0,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    disabledReason: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      required: true,
    },
    emptyMenuText: {
      type: String,
      required: true,
    },
    // a zone that lists its items elsewhere (filters) keeps accepting
    unlimited: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['add', 'remove'],
  directives: {
    FocusAnywhereButHere,
  },
  setup(props, { emit }) {
    const isOver = ref(false);
    const menuOpen = ref(false);
    const chips = ref<HTMLElement[]>([]);
    const slotButton = ref<HTMLElement|null>(null);
    const headingId = computed(() => `dataExplorerZone-${props.zone}`);

    const isFull = computed(() => !props.unlimited
      && props.max > 0
      && props.items.length >= props.max);

    const isAccepting = computed(() => !!dragState.current
      && dragState.current.kind === props.accepts
      && !props.disabled
      && !isFull.value);

    function onDragOver(event: DragEvent) {
      if (!isAccepting.value) {
        return;
      }
      event.preventDefault();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'copy';
      }
      isOver.value = true;
    }

    function onDrop(event: DragEvent) {
      isOver.value = false;
      const payload = droppedPayload(event);
      endDrag();
      if (!payload || payload.kind !== props.accepts || props.disabled || isFull.value) {
        return;
      }
      event.preventDefault();
      emit('add', payload.id);
    }

    function focusAfterRemoval(index: number) {
      nextTick(() => {
        const remaining = chips.value.filter((chip) => !!chip && chip.isConnected);
        const next = remaining[Math.min(index, remaining.length - 1)];
        if (next) {
          next.focus();
        } else if (slotButton.value) {
          slotButton.value.focus();
        }
      });
    }

    function remove(id: string, index: number) {
      emit('remove', id);
      focusAfterRemoval(index);
    }

    function closeMenu(restoreFocus = false) {
      menuOpen.value = false;
      if (restoreFocus === true && slotButton.value) {
        slotButton.value.focus();
      }
    }

    function choose(id: string) {
      menuOpen.value = false;
      emit('add', id);
    }

    return {
      isOver,
      menuOpen,
      chips,
      slotButton,
      headingId,
      isFull,
      isAccepting,
      onDragOver,
      onDrop,
      remove,
      closeMenu,
      choose,
    };
  },
});
</script>
