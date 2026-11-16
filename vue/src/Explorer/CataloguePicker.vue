<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div
    class="dataExplorerPicker"
    role="dialog"
    :aria-label="label"
    v-focus-anywhere-but-here="{ blur: onBlur }"
    @keydown.esc.stop.prevent="close"
  >
    <input
      ref="search"
      v-model="query"
      type="search"
      class="dataExplorerPicker__search browser-default"
      :placeholder="translate('DataExplorer_PickerSearch')"
      :aria-label="translate('DataExplorer_PickerSearch')"
    />
    <div class="dataExplorerPicker__list">
      <div
        v-for="group in filteredGroups"
        :key="group.category"
        class="dataExplorerPicker__group"
      >
        <div class="dataExplorerPicker__category">{{ group.category }}</div>
        <ul>
          <li
            v-for="item in group.items"
            :key="item.id"
          >
            <button
              type="button"
              class="dataExplorerPicker__item"
              :class="{ 'is-added': added.includes(item.id) }"
              :aria-pressed="added.includes(item.id) ? 'true' : 'false'"
              @click="toggle(item.id)"
            >
              <span
                class="dataExplorerPicker__check"
                :class="added.includes(item.id) ? 'icon-ok' : 'icon-add'"
                aria-hidden="true"
              ></span>
              {{ item.name }}
            </button>
          </li>
        </ul>
      </div>
      <p
        v-if="!filteredGroups.length"
        class="dataExplorerPicker__empty"
      >{{ translate('DataExplorer_PickerNoMatch') }}</p>
    </div>
    <div class="dataExplorerPicker__footer">
      <button
        type="button"
        class="btn btn-small"
        @click="close"
      >{{ translate('General_Close') }}</button>
    </div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  onMounted,
  PropType,
  ref,
} from 'vue';
import { FocusAnywhereButHere } from 'CoreHome';
import type { PickerGroup } from '../types';

/**
 * "+" panel of a variables list: searches the catalogue by name and category, adds or removes
 * variables of this exploration.
 */
export default defineComponent({
  name: 'CataloguePicker',
  props: {
    label: {
      type: String,
      required: true,
    },
    groups: {
      type: Array as PropType<PickerGroup[]>,
      required: true,
    },
    added: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
  },
  emits: ['add', 'remove', 'close'],
  directives: {
    FocusAnywhereButHere,
  },
  setup(props, { emit }) {
    const query = ref('');
    const search = ref<HTMLInputElement|null>(null);

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

    // closed by a click elsewhere: focus stays where the user put it
    function onBlur() {
      emit('close', false);
    }

    function close() {
      emit('close', true);
    }

    onMounted(() => {
      nextTick(() => {
        if (search.value) {
          search.value.focus();
        }
      });
    });

    return {
      query,
      search,
      filteredGroups,
      toggle,
      onBlur,
      close,
    };
  },
});
</script>
