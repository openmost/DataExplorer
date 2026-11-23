<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerManage">
    <div class="dataExplorerManage__intro">{{ translate('DataExplorer_ManageIntro') }}</div>

    <Notification
      v-if="error"
      context="error"
      :noclear="true"
    >{{ error }}</Notification>
    <Notification
      v-if="notice"
      context="success"
      :noclear="true"
    >{{ notice }}</Notification>

    <div class="dataExplorerManage__search">
      <Field
        v-show="reports.length > 0"
        v-model="search"
        uicontrol="text"
        name="dataExplorerManageSearch"
        :title="translate('General_Search')"
      />
    </div>

    <div class="dataExplorerManage__tableScroller">
      <table v-content-table>
        <thead>
          <tr>
            <th class="first">{{ translate('General_Id') }}</th>
            <th>{{ translate('General_Name') }}</th>
            <th>{{ translate('DataExplorer_ManageColumnType') }}</th>
            <th>{{ translate('DataExplorer_ManageColumnLocation') }}</th>
            <th>{{ translate('DataExplorer_SaveSites') }}</th>
            <th>{{ translate('DataExplorer_ManageColumnAuthor') }}</th>
            <th>{{ translate('DataExplorer_ManageColumnUpdated') }}</th>
            <th class="dataExplorerManage__actions">{{ translate('General_Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="8">
              <ActivityIndicator :loading="true" />
            </td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="8">
              <br />
              {{ reports.length
                ? translate('DataExplorer_ManageNoMatch')
                : translate('DataExplorer_ManageEmpty') }}
              <br /><br />
            </td>
          </tr>
          <tr
            v-for="report in rows"
            :key="report.idreport"
          >
            <td class="first">{{ report.idreport }}</td>
            <td>{{ report.name }}</td>
            <td>{{ typeText(report) }}</td>
            <td>{{ locationText(report) }}</td>
            <td>{{ sitesText(report) }}</td>
            <td>{{ report.created_by || translate('DataExplorer_ManageAuthorDeleted') }}</td>
            <td>{{ report.updated_date || report.created_date }}</td>
            <td class="entityTable_ActionCell entityTable_ActionCell-3 dataExplorerManage__actions">
              <a
                class="table-action icon-show"
                :href="reportUrl(report)"
                :title="translate('DataExplorer_ManageView', report.name)"
                :aria-label="translate('DataExplorer_ManageView', report.name)"
              ></a>
              <a
                v-if="canOpen(report)"
                class="table-action icon-edit"
                :href="explorerUrl(report)"
                :title="translate('DataExplorer_ManageEdit', report.name)"
                :aria-label="translate('DataExplorer_ManageEdit', report.name)"
              ></a>
              <button
                v-if="report.editable"
                type="button"
                class="table-action icon-delete"
                :title="translate('DataExplorer_ManageDelete', report.name)"
                :aria-label="translate('DataExplorer_ManageDelete', report.name)"
                @click="askDelete(report)"
              ></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="tableActionBar">
      <a
        class="dataExplorerManage__create"
        :href="explorerUrl(null)"
      >
        <span class="icon-add" aria-hidden="true"></span>
        {{ translate('DataExplorer_NewExploration') }}
      </a>
    </div>

    <MatomoDialog
      v-model="confirmOpen"
      @yes="confirmDelete"
    >
      <div class="ui-confirm">
        <h2>{{ translate('DataExplorer_ManageDeleteConfirm', toDelete ? toDelete.name : '') }}</h2>
        <input
          role="yes"
          type="button"
          :value="translate('General_Yes')"
        />
        <input
          role="no"
          type="button"
          :value="translate('General_No')"
        />
      </div>
    </MatomoDialog>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue';
import {
  ActivityIndicator,
  AjaxHelper,
  ContentTable,
  Matomo,
  MatomoDialog,
  MatomoUrl,
  Notification,
  ReportingMenuStore,
  translate,
} from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import { EXPLORE_SUBCATEGORY, REPORT_HASH_PARAM } from '../Explorer/explorationState';
import type { ReportCategory as CategoryOption, SavedReport } from '../types';

const EXPLORE_CATEGORY = 'DataExplorer_DataExplorer';

/**
 * "Data Explorer > Manage explorations": the reports saved on this website, with links to their
 * page and to the explorer, and deletion for the users who can edit them.
 */
export default defineComponent({
  name: 'ReportsManager',
  components: {
    ActivityIndicator,
    Field,
    MatomoDialog,
    Notification,
  },
  directives: {
    ContentTable,
  },
  props: {
    isSuperUser: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const idSite = Number(Matomo.idSite);
    const reports = ref<SavedReport[]>([]);
    const categories = ref<CategoryOption[]>([]);
    const loading = ref(true);
    const error = ref('');
    const notice = ref('');
    const search = ref('');
    const confirmOpen = ref(false);
    const toDelete = ref<SavedReport|null>(null);

    function load(): Promise<void> {
      loading.value = true;
      return Promise.all([
        AjaxHelper.fetch<SavedReport[]>(
          { method: 'DataExplorer.getReports', idSite, filter_limit: -1 },
          { createErrorNotification: false },
        ),
        AjaxHelper.fetch<CategoryOption[]>(
          { method: 'DataExplorer.getCategories', idSite, filter_limit: -1 },
          { createErrorNotification: false },
        ).catch(() => [] as CategoryOption[]),
      ]).then(([loadedReports, loadedCategories]) => {
        reports.value = loadedReports;
        categories.value = loadedCategories;
      }).catch((e: Error) => {
        error.value = e.message;
      }).finally(() => {
        loading.value = false;
      });
    }

    const rows = computed(() => {
      const needle = search.value.trim().toLowerCase();
      if (!needle) {
        return reports.value;
      }
      return reports.value.filter((report) => report.name.toLowerCase().indexOf(needle) !== -1);
    });

    function typeText(report: SavedReport) {
      if (report.type === 'funnel') {
        return translate('DataExplorer_FunnelTab');
      }
      const technique = report.type === 'sql'
        ? translate('DataExplorer_SqlTab')
        : translate('DataExplorer_ExploreTab');
      return `${technique} > ${translate(`DataExplorer_ReportType_${report.report_type}`)}`;
    }

    function locationText(report: SavedReport) {
      const categoryId = report.category || EXPLORE_CATEGORY;
      const category = categories.value.find((item) => item.id === categoryId);
      const subcategory = category
        ? category.subcategories.find((item) => item.id === report.subcategory)
        : undefined;
      const categoryName = category ? category.name : categoryId;
      // a report saved without a page has a page of its own, named after it
      const pageName = report.subcategory === '' ? report.name : report.subcategory;
      return `${categoryName} > ${subcategory ? subcategory.name : pageName}`;
    }

    function sitesText(report: SavedReport) {
      if (report.placement.all) {
        return translate('DataExplorer_PlacementAll');
      }
      if (report.placement.sites.length === 1 && report.placement.sites[0] === idSite) {
        return translate('DataExplorer_PlacementThisSite');
      }
      return translate('DataExplorer_ManageSitesCount', String(report.placement.sites.length));
    }

    function pageUrl(params: Record<string, string>) {
      const query = MatomoUrl.stringify({
        idSite: String(idSite),
        period: String(MatomoUrl.parsed.value.period || Matomo.period),
        date: String(MatomoUrl.parsed.value.date || Matomo.currentDateString),
        ...params,
      });
      return `?${MatomoUrl.stringify(MatomoUrl.urlParsed.value)}#?${query}`;
    }

    function reportUrl(report: SavedReport) {
      return pageUrl({
        category: report.category || EXPLORE_CATEGORY,
        subcategory: report.subcategory,
      });
    }

    function explorerUrl(report: SavedReport|null) {
      const params: Record<string, string> = {
        category: EXPLORE_CATEGORY,
        subcategory: EXPLORE_SUBCATEGORY,
      };
      if (report) {
        params[REPORT_HASH_PARAM] = String(report.idreport);
      }
      return pageUrl(params);
    }

    function canOpen(report: SavedReport) {
      return report.editable && (report.type !== 'sql' || props.isSuperUser);
    }

    function askDelete(report: SavedReport) {
      notice.value = '';
      toDelete.value = report;
      confirmOpen.value = true;
    }

    function confirmDelete() {
      const report = toDelete.value;
      if (!report) {
        return;
      }
      AjaxHelper.post(
        { method: 'DataExplorer.deleteReport' },
        { idReport: report.idreport },
        { createErrorNotification: false },
      ).then(() => {
        notice.value = translate('DataExplorer_ManageDeleted', report.name);
        ReportingMenuStore.reloadMenuItems();
        return load();
      }).catch((e: Error) => {
        error.value = e.message;
      });
    }

    onMounted(() => {
      load();
    });

    return {
      reports,
      rows,
      loading,
      error,
      notice,
      search,
      confirmOpen,
      toDelete,
      typeText,
      locationText,
      sitesText,
      reportUrl,
      explorerUrl,
      canOpen,
      askDelete,
      confirmDelete,
    };
  },
});
</script>
