<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <Teleport to="body">
    <Transition name="dataExplorerPanelBackdrop">
      <div
        v-if="modelValue"
        class="dataExplorerPanelBackdrop"
        @click="close"
      ></div>
    </Transition>
    <Transition
      name="dataExplorerPanel"
      @after-leave="onAfterLeave"
    >
      <section
        v-if="modelValue"
        ref="panel"
        class="dataExplorerPanel dataExplorerFormPanel"
        :class="{ 'is-wide': wide }"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        @keydown.esc.stop.prevent="close"
        @keydown.tab="trapFocus"
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
            <span
              class="icon-close"
              aria-hidden="true"
            ></span>
          </button>
        </header>
        <div class="dataExplorerPanel__body dataExplorerFormPanel__body">
          <slot />
        </div>
        <footer
          v-if="$slots.actions"
          class="dataExplorerFormPanel__actions"
        >
          <slot name="actions" />
        </footer>
      </section>
    </Transition>
  </Teleport>
</template>

<script lang="ts">
import {
  defineComponent,
  nextTick,
  onBeforeUnmount,
  ref,
  watch,
} from 'vue';
import { translate } from 'CoreHome';
import { lockPageScroll } from '../pageScrollLock';

let panelCount = 0;

const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

/**
 * A form panel sliding in from the right edge over the page, like the catalogue panel, the
 * explorer staying visible on its left. Modal: focus stays inside, Escape and the backdrop close
 * it, focus goes back to the element that opened it.
 */
export default defineComponent({
  name: 'SidePanel',
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    // room for a form of several columns, like the funnel steps
    wide: {
      type: Boolean,
      default: false,
    },
    // selector of the element focused on open, the first focusable element otherwise
    focusSelector: {
      type: String,
      default: '',
    },
  },
  emits: ['update:modelValue', 'closed'],
  setup(props, { emit }) {
    panelCount += 1;
    const titleId = `dataExplorerFormPanelTitle${panelCount}`;
    const panel = ref<HTMLElement|null>(null);
    let opener: HTMLElement|null = null;

    function focusables(): HTMLElement[] {
      if (!panel.value) {
        return [];
      }
      return Array.from(panel.value.querySelectorAll<HTMLElement>(FOCUSABLE))
        .filter((element) => element.offsetParent !== null);
    }

    watch(() => props.modelValue, (open, wasOpen) => {
      // a closed panel never unlocks the scroll another panel locked
      if (open || wasOpen) {
        lockPageScroll(open);
      }
      if (!open) {
        return;
      }
      opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
      nextTick(() => {
        const preferred = props.focusSelector && panel.value
          ? panel.value.querySelector<HTMLElement>(props.focusSelector)
          : null;
        const target = preferred || focusables()[0];
        if (target) {
          target.focus();
        }
      });
    }, { immediate: true });

    onBeforeUnmount(() => {
      if (props.modelValue) {
        lockPageScroll(false);
      }
    });

    function close() {
      emit('update:modelValue', false);
    }

    function trapFocus(event: KeyboardEvent) {
      const list = focusables();
      if (!list.length) {
        return;
      }
      const first = list[0];
      const last = list[list.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function onAfterLeave() {
      if (opener && document.body.contains(opener)) {
        opener.focus();
      }
      opener = null;
      emit('closed');
    }

    return {
      titleId,
      panel,
      close,
      trapFocus,
      onAfterLeave,
      translate,
    };
  },
});
</script>
