<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerSetup">
    <ContentBlock :content-title="translate('DataExplorer_SetupTitle')">
      <div class="dataExplorerSetup__text">{{ translate('DataExplorer_SetupIntro') }}</div>
      <Notification
        :context="notificationContext"
        :noclear="true"
      >{{ status.message }}</Notification>
      <pre
        v-if="status.state === 'missing_grants' && missingGrantLines.length"
        class="dataExplorerSetup__code"
      >{{ missingGrantLines.join('\n') }}</pre>
    </ContentBlock>

    <ContentBlock :content-title="translate('DataExplorer_SetupCreateUserTitle')">
      <div class="dataExplorerSetup__text">{{ translate('DataExplorer_SetupCreateUserHelp') }}</div>
      <div
        v-if="isAnyHost"
        class="dataExplorerSetup__text"
      >{{ translate('DataExplorer_SetupAnyHostHelp') }}</div>
      <pre
        v-if="hasTables"
        class="dataExplorerSetup__code"
      >{{ sqlSnippet }}</pre>
      <Notification
        v-else
        context="warning"
        :noclear="true"
      >{{ translate('DataExplorer_SetupNoTables') }}</Notification>
    </ContentBlock>

    <ContentBlock :content-title="translate('DataExplorer_SetupConfigTitle')">
      <div class="dataExplorerSetup__text">{{ translate('DataExplorer_SetupConfigHelp') }}</div>
      <pre class="dataExplorerSetup__code">{{ iniBlock }}</pre>
    </ContentBlock>
  </div>
</template>

<script lang="ts">
import { computed, defineComponent, PropType } from 'vue';
import { ContentBlock, Notification } from 'CoreHome';

interface SetupStatus {
  state: string;
  message: string;
}

const CONTEXTS: Record<string, string> = {
  ready: 'success',
  not_configured: 'info',
  missing_grants: 'warning',
};

/**
 * Admin page of the read-only database user that raw SQL runs with: its status, the SQL that
 * creates it and the config.ini.php block that declares it.
 */
export default defineComponent({
  name: 'ReadOnlySetup',
  components: {
    ContentBlock,
    Notification,
  },
  props: {
    status: {
      type: Object as PropType<SetupStatus>,
      required: true,
    },
    sqlSnippet: {
      type: String,
      default: '',
    },
    iniBlock: {
      type: String,
      default: '',
    },
    missingGrantLines: {
      type: Array as PropType<string[]>,
      default: () => [],
    },
    isAnyHost: {
      type: Boolean,
      default: false,
    },
    hasTables: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const notificationContext = computed(() => CONTEXTS[props.status.state] || 'error');

    return {
      notificationContext,
    };
  },
});
</script>
