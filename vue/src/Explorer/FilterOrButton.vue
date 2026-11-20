<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerFilterOr">
    <button
      ref="button"
      type="button"
      class="dataExplorerFilterOr__button"
      :aria-expanded="menuOpen ? 'true' : 'false'"
      :aria-label="ariaLabel || translate('DataExplorer_FilterAddOrLabel')"
      @click="menuOpen = !menuOpen"
    >
      <span class="icon-add" aria-hidden="true"></span>
      <span>{{ label || translate('DataExplorer_FilterAddOr') }}</span>
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
      >{{ translate('DataExplorer_NoDimensions') }}</li>
    </ul>
  </div>
</template>

<script lang="ts">
import { defineComponent, PropType, ref } from 'vue';
import { FocusAnywhereButHere } from 'CoreHome';
import type { ZoneItem } from '../types';

/**
 * "+ Or" under a group of filters (the dimension picked is ORed with the others), or "+ And" for
 * a new group ANDed with them.
 */
export default defineComponent({
  name: 'FilterOrButton',
  props: {
    options: {
      type: Array as PropType<ZoneItem[]>,
      default: () => [],
    },
    // "Or" by default; "And" for the button that starts a new group
    label: {
      type: String,
      default: '',
    },
    ariaLabel: {
      type: String,
      default: '',
    },
  },
  emits: ['add'],
  directives: {
    FocusAnywhereButHere,
  },
  setup(props, { emit }) {
    const menuOpen = ref(false);
    const button = ref<HTMLElement|null>(null);

    function closeMenu(restoreFocus = false) {
      menuOpen.value = false;
      if (restoreFocus === true && button.value) {
        button.value.focus();
      }
    }

    function choose(id: string) {
      menuOpen.value = false;
      emit('add', id);
    }

    return {
      menuOpen,
      button,
      closeMenu,
      choose,
    };
  },
});
</script>
