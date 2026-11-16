<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <Teleport to="body">
    <Transition name="dataExplorerPanelBackdrop">
      <div
        v-show="isOpen"
        class="dataExplorerPanelBackdrop"
        @click="close"
      ></div>
    </Transition>
    <Transition name="dataExplorerPanel">
      <section
        v-show="isOpen"
        ref="panel"
        class="dataExplorerPanel"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        @keydown="onPanelKeydown"
      >
        <header class="dataExplorerPanel__header">
          <h2
            :id="titleId"
            class="dataExplorerPanel__title"
          >{{ title }}</h2>
          <button
            type="button"
            class="dataExplorerIconButton dataExplorerPanel__close"
            :aria-label="translate('General_Close')"
            :title="translate('General_Close')"
            @click="close"
          >
            <span class="icon-close" aria-hidden="true"></span>
          </button>
        </header>
        <div class="dataExplorerPanel__search">
          <input
            ref="search"
            v-model="query"
            type="search"
            class="browser-default"
            :placeholder="translate('DataExplorer_PickerSearch')"
            :aria-label="translate('DataExplorer_PickerSearch')"
          />
          <p
            class="dataExplorerPanel__count"
            aria-live="polite"
          >{{ translate('DataExplorer_PanelAddedCount', added.length) }}</p>
        </div>
        <div class="dataExplorerPanel__body">
          <section
            v-for="group in filteredGroups"
            :key="group.category"
            class="dataExplorerPanel__group"
          >
            <h3 class="dataExplorerPanel__category">{{ group.category }}</h3>
            <ul>
              <li
                v-for="item in group.items"
                :key="item.id"
              >
                <button
                  type="button"
                  class="dataExplorerPanel__item"
                  :class="{ 'is-added': added.includes(item.id) }"
                  :data-id="item.id"
                  :aria-pressed="added.includes(item.id) ? 'true' : 'false'"
                  @click="toggle(item.id)"
                >
                  <span
                    class="dataExplorerPanel__check"
                    :class="added.includes(item.id) ? 'icon-ok' : 'icon-add'"
                    aria-hidden="true"
                  ></span>
                  <span class="dataExplorerPanel__name">{{ item.name }}</span>
                </button>
              </li>
            </ul>
          </section>
          <p
            v-if="!filteredGroups.length"
            class="dataExplorerPanel__empty"
          >{{ translate('DataExplorer_PickerNoMatch') }}</p>
        </div>
      </section>
    </Transition>
  </Teleport>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  onBeforeUnmount,
  PropType,
  ref,
  watch,
} from 'vue';
import type { PickerGroup } from '../types';
import { catalogueState, closeCatalogue, getReturnFocusTarget } from './catalogueStore';

const FOCUSABLE = [
  'button:not([disabled])',
  'input:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

const PAGE_SCROLL_LOCK_CLASS = 'dataExplorer-panel-open';

/**
 * Side panel of the "+" buttons: slides over the page from the right, searches the catalogue by
 * name and category, adds or removes variables without closing. Escape, the close button or a
 * click on the backdrop close it and give the focus back to the "+" button.
 */
export default defineComponent({
  name: 'CataloguePanel',
  props: {
    title: {
      type: String,
      default: '',
    },
    groups: {
      type: Array as PropType<PickerGroup[]>,
      default: () => [],
    },
    added: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
  },
  emits: ['add', 'remove'],
  setup(props, { emit }) {
    const panel = ref<HTMLElement|null>(null);
    const search = ref<HTMLInputElement|null>(null);
    const query = ref('');
    const titleId = 'dataExplorerPanelTitle';

    const isOpen = computed(() => catalogueState.open);

    const filteredGroups = computed(() => {
      const needle = query.value.trim().toLowerCase();
      if (!needle) {
        return props.groups;
      }
      return props.groups
        .map((group) => {
          if (group.category.toLowerCase().includes(needle)) {
            return group;
          }
          return {
            category: group.category,
            items: group.items.filter((item) => item.name.toLowerCase().includes(needle)),
          };
        })
        .filter((group) => group.items.length > 0);
    });

    function toggle(id: string) {
      emit(props.added.includes(id) ? 'remove' : 'add', id);
    }

    function close() {
      closeCatalogue();
    }

    function onDocumentKeydown(event: KeyboardEvent) {
      if (event.key === 'Escape' && !event.defaultPrevented) {
        event.preventDefault();
        close();
      }
    }

    // keeps the Tab focus inside the panel
    function onPanelKeydown(event: KeyboardEvent) {
      if (event.key !== 'Tab' || !panel.value) {
        return;
      }
      const focusable = Array.from(panel.value.querySelectorAll<HTMLElement>(FOCUSABLE));
      if (!focusable.length) {
        return;
      }
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function restoreFocus() {
      const active = document.activeElement;
      // a click elsewhere in the page already moved the focus, leave it there
      if (active && active !== document.body && panel.value && !panel.value.contains(active)) {
        return;
      }
      const target = getReturnFocusTarget();
      if (target) {
        target.focus();
      }
    }

    watch(isOpen, (open) => {
      document.documentElement.classList.toggle(PAGE_SCROLL_LOCK_CLASS, open);
      if (open) {
        query.value = '';
        document.addEventListener('keydown', onDocumentKeydown);
        nextTick(() => {
          if (search.value) {
            search.value.focus();
          }
        });
        return;
      }
      document.removeEventListener('keydown', onDocumentKeydown);
      restoreFocus();
    });

    onBeforeUnmount(() => {
      document.removeEventListener('keydown', onDocumentKeydown);
      document.documentElement.classList.remove(PAGE_SCROLL_LOCK_CLASS);
      closeCatalogue();
    });

    return {
      panel,
      search,
      query,
      titleId,
      isOpen,
      filteredGroups,
      toggle,
      close,
      onPanelKeydown,
    };
  },
});
</script>
