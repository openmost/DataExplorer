<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    class="dataExplorerCombobox"
    :class="{ 'is-open': isOpen, 'has-options': options.length > 0 }"
  >
    <input
      ref="input"
      type="text"
      class="dataExplorerControl dataExplorerCombobox__input browser-default"
      role="combobox"
      autocomplete="off"
      aria-autocomplete="list"
      :aria-expanded="isOpen ? 'true' : 'false'"
      :aria-controls="listId"
      :aria-activedescendant="activeIndex >= 0 ? optionId(activeIndex) : undefined"
      :aria-label="label"
      :placeholder="placeholder"
      :inputmode="inputmode"
      :value="modelValue"
      @input="onInput"
      @click="open(false)"
      @keydown="onKeydown"
      @blur="close"
    />
    <button
      v-if="options.length"
      type="button"
      tabindex="-1"
      class="dataExplorerCombobox__toggle"
      :aria-label="toggleLabel"
      :title="toggleLabel"
      @mousedown.prevent="toggle"
    >
      <span class="icon-chevron-down" aria-hidden="true" />
    </button>
    <ul
      v-show="isOpen"
      :id="listId"
      ref="list"
      class="dataExplorerCombobox__list"
      role="listbox"
      :aria-label="label"
    >
      <li
        v-for="(option, index) in visibleOptions"
        :id="optionId(index)"
        :key="option"
        role="option"
        class="dataExplorerCombobox__option"
        :class="{ 'is-active': index === activeIndex, 'is-selected': option === modelValue }"
        :aria-selected="option === modelValue ? 'true' : 'false'"
        @mousedown.prevent="pick(option)"
        @mousemove="activeIndex = index"
      >{{ option }}</li>
    </ul>
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

let nextId = 0;

const MAX_VISIBLE_OPTIONS = 200;

/**
 * A text field with its list of suggested values, searchable as you type. The value stays free:
 * operators like "contains" or a regular expression take text that is not in the list.
 */
export default defineComponent({
  name: 'ValueCombobox',
  props: {
    modelValue: {
      type: String,
      default: '',
    },
    options: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
    label: {
      type: String,
      required: true,
    },
    toggleLabel: {
      type: String,
      required: true,
    },
    placeholder: {
      type: String,
      default: '',
    },
    inputmode: {
      type: String as PropType<'text' | 'decimal'>,
      default: 'text',
    },
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    nextId += 1;
    const listId = `dataExplorerCombobox${nextId}`;

    const input = ref<HTMLInputElement | null>(null);
    const list = ref<HTMLElement | null>(null);
    const isOpen = ref(false);
    const activeIndex = ref(-1);
    // the text typed since the list opened; empty shows every value, like a select
    const search = ref('');

    const visibleOptions = computed(() => {
      const needle = search.value.trim().toLowerCase();
      const matches = needle
        ? props.options.filter((option) => option.toLowerCase().includes(needle))
        : props.options;
      return matches.slice(0, MAX_VISIBLE_OPTIONS);
    });

    function optionId(index: number) {
      return `${listId}-${index}`;
    }

    function scrollToActive() {
      nextTick(() => {
        const element = list.value?.children[activeIndex.value] as HTMLElement | undefined;
        if (element) {
          element.scrollIntoView({ block: 'nearest' });
        }
      });
    }

    function open(filtered: boolean) {
      if (!props.options.length) {
        return;
      }
      if (!filtered) {
        search.value = '';
      }
      isOpen.value = true;
      activeIndex.value = visibleOptions.value.indexOf(props.modelValue);
      scrollToActive();
    }

    function close() {
      isOpen.value = false;
      activeIndex.value = -1;
    }

    function toggle() {
      if (isOpen.value) {
        close();
      } else {
        open(false);
      }
      if (input.value) {
        input.value.focus();
      }
    }

    function pick(option: string) {
      emit('update:modelValue', option);
      close();
    }

    function onInput(event: Event) {
      const { value } = event.target as HTMLInputElement;
      emit('update:modelValue', value);
      search.value = value;
      open(true);
      activeIndex.value = -1;
    }

    function move(step: number) {
      if (!isOpen.value) {
        open(false);
        return;
      }
      const count = visibleOptions.value.length;
      if (!count) {
        return;
      }
      activeIndex.value = (activeIndex.value + step + count) % count;
      scrollToActive();
    }

    // Enter and Escape stop here only while the list is open,
    // otherwise they apply or cancel the condition
    function onKeydown(event: KeyboardEvent) {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
      } else if (event.key === 'Enter' && isOpen.value && activeIndex.value >= 0) {
        event.preventDefault();
        event.stopPropagation();
        pick(visibleOptions.value[activeIndex.value]);
      } else if (event.key === 'Escape' && isOpen.value) {
        event.preventDefault();
        event.stopPropagation();
        close();
      } else if (event.key === 'Tab') {
        close();
      }
    }

    return {
      input,
      list,
      listId,
      isOpen,
      activeIndex,
      visibleOptions,
      optionId,
      open,
      close,
      toggle,
      pick,
      onInput,
      onKeydown,
    };
  },
});
</script>
