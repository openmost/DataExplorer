<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    class="dataExplorerReportPicker"
    v-focus-anywhere-but-here="{ blur: close }"
    @keydown.esc.stop.prevent="close(true)"
  >
    <button
      ref="trigger"
      type="button"
      class="dataExplorerReportPicker__trigger"
      :aria-label="label"
      :aria-expanded="open ? 'true' : 'false'"
      @click="toggle"
    >
      <span class="dataExplorerReportPicker__current">{{ currentName }}</span>
      <span class="icon-chevron-down" aria-hidden="true" />
    </button>
    <div
      v-if="open"
      class="dataExplorerReportPicker__panel"
      @keydown.down.prevent="move(1)"
      @keydown.up.prevent="move(-1)"
      @keydown.enter.prevent="pickActive"
    >
      <SearchInput
        ref="search"
        v-model="query"
        :placeholder="translate('DataExplorer_ReportPickerSearch')"
        :aria-label="translate('DataExplorer_ReportPickerSearch')"
      />
      <div
        :id="`${uid}-list`"
        ref="list"
        class="dataExplorerReportPicker__list"
        role="listbox"
        :aria-label="label"
      >
        <div
          :id="optionId('')"
          role="option"
          class="dataExplorerReportPicker__item"
          :class="{ 'is-selected': modelValue === '', 'is-active': activeKey === '' }"
          :aria-selected="modelValue === '' ? 'true' : 'false'"
          tabindex="-1"
          @mousedown.prevent
          @mousemove="activeKey = ''"
          @click="pick('')"
          @keydown.enter.prevent="pick('')"
        >{{ newLabel }}</div>
        <div
          v-for="(group, index) in visibleGroups"
          :key="index"
          role="group"
          :aria-label="group.label || undefined"
          class="dataExplorerReportPicker__group"
        >
          <div
            v-if="group.label"
            class="dataExplorerReportPicker__groupLabel"
            aria-hidden="true"
          >{{ group.label }}</div>
          <div
            v-for="item in group.items"
            :id="optionId(item.key)"
            :key="item.key"
            role="option"
            class="dataExplorerReportPicker__item"
            :class="{ 'is-selected': modelValue === item.key, 'is-active': activeKey === item.key }"
            :aria-selected="modelValue === item.key ? 'true' : 'false'"
            tabindex="-1"
            @mousedown.prevent
            @mousemove="activeKey = item.key"
            @click="pick(item.key)"
            @keydown.enter.prevent="pick(item.key)"
          >
            <span class="dataExplorerReportPicker__name">{{ item.name }}</span>
            <span
              v-if="item.place"
              class="dataExplorerReportPicker__place"
            >{{ item.place }}</span>
          </div>
        </div>
        <div
          v-if="query.trim() !== '' && !visibleGroups.length"
          class="dataExplorerReportPicker__empty"
        >{{ translate('DataExplorer_ManageNoMatch') }}</div>
      </div>
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
  watch,
} from 'vue';
import { FocusAnywhereButHere, SearchInput } from 'CoreHome';
import type { ReportPickerGroup } from '../types';

let nextId = 0;

/**
 * The report the explorer opens: a new exploration, then the groups (saved explorations, custom
 * reports), filtered by a search on the name.
 */
export default defineComponent({
  name: 'ReportPicker',
  components: {
    SearchInput,
  },
  directives: {
    FocusAnywhereButHere,
  },
  props: {
    modelValue: {
      type: String,
      default: '',
    },
    groups: {
      type: Array as PropType<ReportPickerGroup[]>,
      default: () => [],
    },
    newLabel: {
      type: String,
      required: true,
    },
    label: {
      type: String,
      required: true,
    },
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const open = ref(false);
    const query = ref('');
    const trigger = ref<HTMLElement|null>(null);
    const search = ref<{ $el: HTMLElement }|null>(null);
    const list = ref<HTMLElement|null>(null);
    // the option the arrow keys are on: the focus stays in the search, like a combobox
    const activeKey = ref<string|null>(null);
    nextId += 1;
    const uid = `dataExplorerReportPicker${nextId}`;
    const optionId = (key: string) => `${uid}-${key === '' ? 'new' : key}`;

    const currentName = computed(() => {
      const found = props.groups
        .flatMap((group) => group.items)
        .find((item) => item.key === props.modelValue);
      return found ? found.name : props.newLabel;
    });

    const visibleGroups = computed(() => {
      const needle = query.value.trim().toLowerCase();
      return props.groups
        .map((group) => ({
          label: group.label,
          items: needle === ''
            ? group.items
            : group.items.filter((item) => item.name.toLowerCase().includes(needle)),
        }))
        .filter((group) => group.items.length > 0);
    });

    const optionKeys = computed(() => [
      '',
      ...visibleGroups.value.flatMap((group) => group.items.map((item) => item.key)),
    ]);

    function searchInput(): HTMLInputElement|null {
      return search.value ? search.value.$el.querySelector('input') : null;
    }

    watch(activeKey, (key) => {
      nextTick(() => {
        const input = searchInput();
        if (input) {
          input.setAttribute('role', 'combobox');
          input.setAttribute('aria-controls', `${uid}-list`);
          input.setAttribute('aria-expanded', 'true');
          if (key === null) {
            input.removeAttribute('aria-activedescendant');
          } else {
            input.setAttribute('aria-activedescendant', optionId(key));
          }
        }
        const element = key === null ? null : document.getElementById(optionId(key));
        if (element) {
          element.scrollIntoView({ block: 'nearest' });
        }
      });
    });

    watch(query, () => {
      activeKey.value = null;
    });

    function move(step: number) {
      const keys = optionKeys.value;
      const position = activeKey.value === null ? -1 : keys.indexOf(activeKey.value);
      let next = (position + step + keys.length) % keys.length;
      if (position < 0) {
        next = step > 0 ? 0 : keys.length - 1;
      }
      activeKey.value = keys[next];
    }

    function close(restoreFocus = false) {
      if (!open.value) {
        return;
      }
      open.value = false;
      query.value = '';
      activeKey.value = null;
      if (restoreFocus === true && trigger.value) {
        trigger.value.focus();
      }
    }

    function toggle() {
      if (open.value) {
        close();
        return;
      }
      open.value = true;
      activeKey.value = props.modelValue;
      nextTick(() => {
        const input = searchInput();
        if (input) {
          input.focus();
        }
      });
    }

    function pick(key: string) {
      close(true);
      emit('update:modelValue', key);
    }

    function pickActive() {
      if (activeKey.value !== null && optionKeys.value.includes(activeKey.value)) {
        pick(activeKey.value);
      }
    }

    return {
      open,
      query,
      trigger,
      search,
      list,
      uid,
      activeKey,
      optionId,
      move,
      pickActive,
      currentName,
      visibleGroups,
      close,
      toggle,
      pick,
    };
  },
});
</script>
