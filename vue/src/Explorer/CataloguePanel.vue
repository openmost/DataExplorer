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
          <SearchInput
            ref="search"
            v-model="query"
            :placeholder="translate('DataExplorer_PickerSearch')"
            :aria-label="translate('DataExplorer_PickerSearch')"
          />
          <div
            class="dataExplorerPanel__count"
            aria-live="polite"
          >{{ translate('DataExplorer_PanelAddedCount', added.length) }}</div>
        </div>
        <div
          ref="body"
          class="dataExplorerPanel__body"
          @keydown="onBodyKeydown"
        >
          <section
            v-for="(group, groupIndex) in filteredGroups"
            :key="group.category"
            class="dataExplorerPanel__group"
          >
            <h3 class="dataExplorerPanel__category">
              <button
                type="button"
                class="dataExplorerPanel__categoryToggle"
                data-nav="true"
                :aria-expanded="isExpanded(group.category) ? 'true' : 'false'"
                :aria-controls="`dataExplorerPanelGroup-${groupIndex}`"
                @click="toggleCategory(group.category)"
              >
                <span class="dataExplorerPanel__categoryName">{{ group.category }}</span>
                <span class="dataExplorerPanel__categoryCount">{{ group.items.length }}</span>
                <span
                  class="dataExplorerPanel__chevron icon-chevron-down"
                  aria-hidden="true"
                ></span>
              </button>
            </h3>
            <ul
              v-show="isExpanded(group.category)"
              :id="`dataExplorerPanelGroup-${groupIndex}`"
            >
              <li
                v-for="item in group.items"
                :key="item.id"
              >
                <button
                  type="button"
                  class="dataExplorerPanel__item"
                  :class="{ 'is-added': added.includes(item.id), 'is-incompatible': !!item.reason }"
                  :data-id="item.id"
                  data-nav="true"
                  :title="item.reason || undefined"
                  :aria-pressed="added.includes(item.id) ? 'true' : 'false'"
                  :aria-describedby="item.reason ? reasonId(item.id) : undefined"
                  @click="toggle(item.id)"
                >
                  <span class="dataExplorerPanel__name">{{ item.name }}</span>
                  <span
                    v-if="added.includes(item.id)"
                    class="dataExplorerPanel__check icon-ok"
                    aria-hidden="true"
                  ></span>
                  <span
                    v-if="item.reason"
                    :id="reasonId(item.id)"
                    class="dataExplorerVisuallyHidden"
                  >{{ item.reason }}</span>
                </button>
              </li>
            </ul>
          </section>
          <div
            v-if="!filteredGroups.length"
            class="dataExplorerPanel__empty"
          >{{ translate('DataExplorer_PickerNoMatch') }}</div>
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
  reactive,
  ref,
  watch,
} from 'vue';
import { SearchInput } from 'CoreHome';
import type { PickerGroup } from '../types';
import { catalogueState, closeCatalogue, getReturnFocusTarget } from './catalogueStore';

const FOCUSABLE = [
  'button:not([disabled])',
  'input:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

const PAGE_SCROLL_LOCK_CLASS = 'dataExplorer-panel-open';
const SCROLLBAR_WIDTH_VAR = '--dataexplorer-scrollbar-width';

// hiding the page scrollbar widens the page: its width is padded back so the columns do not move
function lockPageScroll(lock: boolean) {
  const root = document.documentElement;
  if (lock) {
    root.style.setProperty(SCROLLBAR_WIDTH_VAR, `${window.innerWidth - root.clientWidth}px`);
  } else {
    root.style.removeProperty(SCROLLBAR_WIDTH_VAR);
  }
  root.classList.toggle(PAGE_SCROLL_LOCK_CLASS, lock);
}

/**
 * Side panel of the "+" buttons: slides over the page from the right, searches the catalogue by
 * name and category, adds or removes variables without closing. Escape, the close button or a
 * click on the backdrop close it and give the focus back to the "+" button.
 */
export default defineComponent({
  name: 'CataloguePanel',
  components: {
    SearchInput,
  },
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
    const body = ref<HTMLElement|null>(null);
    const search = ref<{ $el: HTMLElement }|null>(null);
    const query = ref('');
    const titleId = 'dataExplorerPanelTitle';
    const expanded = reactive<Record<string, boolean>>({});

    const isOpen = computed(() => catalogueState.open);

    // a search shows every category with a match, opened
    function isExpanded(category: string): boolean {
      return query.value.trim() !== '' || !!expanded[category];
    }

    function toggleCategory(category: string) {
      expanded[category] = !isExpanded(category);
    }

    // on open, only the categories already used in the exploration are expanded
    function resetExpanded() {
      Object.keys(expanded).forEach((category) => {
        delete expanded[category];
      });
      props.groups.forEach((group) => {
        if (group.items.some((item) => props.added.includes(item.id))) {
          expanded[group.category] = true;
        }
      });
    }

    function reasonId(id: string): string {
      return `dataExplorerPanelReason-${id.replace(/[^\w-]/g, '_')}`;
    }

    // arrow keys move between the category headers and the visible items
    function onBodyKeydown(event: KeyboardEvent) {
      const keys = ['ArrowDown', 'ArrowUp', 'Home', 'End'];
      if (keys.indexOf(event.key) === -1 || !body.value) {
        return;
      }
      const rows = Array.from(body.value.querySelectorAll<HTMLElement>('[data-nav="true"]'))
        .filter((row) => row.offsetParent !== null);
      if (!rows.length) {
        return;
      }
      event.preventDefault();
      const current = rows.indexOf(document.activeElement as HTMLElement);
      let next = 0;
      if (event.key === 'End') {
        next = rows.length - 1;
      } else if (event.key === 'ArrowDown') {
        next = current === -1 ? 0 : Math.min(current + 1, rows.length - 1);
      } else if (event.key === 'ArrowUp') {
        next = current <= 0 ? 0 : current - 1;
      }
      rows[next].focus();
    }

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
      lockPageScroll(open);
      if (open) {
        query.value = '';
        resetExpanded();
        document.addEventListener('keydown', onDocumentKeydown);
        nextTick(() => {
          const input = search.value ? search.value.$el.querySelector('input') : null;
          if (input) {
            input.focus();
          }
        });
        return;
      }
      document.removeEventListener('keydown', onDocumentKeydown);
      restoreFocus();
    });

    onBeforeUnmount(() => {
      document.removeEventListener('keydown', onDocumentKeydown);
      lockPageScroll(false);
      closeCatalogue();
    });

    return {
      panel,
      body,
      search,
      query,
      titleId,
      isOpen,
      filteredGroups,
      isExpanded,
      toggleCategory,
      reasonId,
      onBodyKeydown,
      toggle,
      close,
      onPanelKeydown,
    };
  },
});
</script>
