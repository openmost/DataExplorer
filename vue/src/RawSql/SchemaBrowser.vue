<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerSchema">
    <h3 class="dataExplorerColumn__title">{{ translate('DataExplorer_SqlSchema') }}</h3>
    <div class="dataExplorerSchema__help">{{ translate('DataExplorer_SqlSchemaHelp') }}</div>
    <SearchInput
      v-model="query"
      class="dataExplorerSchema__search"
      :placeholder="translate('DataExplorer_SqlSchemaSearch')"
      :aria-label="translate('DataExplorer_SqlSchemaSearch')"
    />
    <ActivityIndicator :loading="loading" />
    <ul class="dataExplorerSchema__tables">
      <li
        v-for="table in filteredTables"
        :key="table.name"
      >
        <details :open="!!query">
          <summary>
            <span class="dataExplorerSchema__table">{{ table.name }}</span>
          </summary>
          <button
            type="button"
            class="dataExplorerSchema__insert"
            :aria-label="translate('DataExplorer_SqlInsertName', table.name)"
            @click="$emit('insert', table.name)"
          >{{ translate('DataExplorer_SqlInsertTable') }}</button>
          <ul class="dataExplorerSchema__columns">
            <li
              v-for="column in table.columns"
              :key="column"
            >
              <button
                type="button"
                class="dataExplorerSchema__column"
                :aria-label="translate('DataExplorer_SqlInsertName', column)"
                @click="$emit('insert', column)"
              >{{ column }}</button>
            </li>
          </ul>
        </details>
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  ref,
} from 'vue';
import { ActivityIndicator, SearchInput } from 'CoreHome';

/**
 * Tables raw SQL can read and their columns; a click inserts the name at the cursor.
 */
export default defineComponent({
  name: 'SchemaBrowser',
  components: {
    ActivityIndicator,
    SearchInput,
  },
  props: {
    schema: {
      type: Object as PropType<Record<string, string[]>>,
      default: () => ({}),
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['insert'],
  setup(props) {
    const query = ref('');

    const filteredTables = computed(() => {
      const needle = query.value.trim().toLowerCase();
      return Object.keys(props.schema)
        .map((name) => ({
          name,
          columns: needle && !name.toLowerCase().includes(needle)
            ? props.schema[name].filter((column) => column.toLowerCase().includes(needle))
            : props.schema[name],
        }))
        .filter((table) => !needle
          || table.columns.length > 0
          || table.name.toLowerCase().includes(needle));
    });

    return {
      query,
      filteredTables,
    };
  },
});
</script>
