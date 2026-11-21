<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="dataExplorerFunnel">
    <p
      v-if="!steps.length"
      class="dataExplorerFunnel__empty"
    >{{ translate('CoreHome_ThereIsNoDataForThisReport') }}</p>
    <template v-else>
      <ul class="dataExplorerFunnel__summary">
        <li>
          <strong>{{ formatNumber(started) }}</strong>
          {{ translate('DataExplorer_FunnelPassesStarted') }}
        </li>
        <li>
          <strong>{{ formatNumber(completed) }}</strong>
          {{ translate('DataExplorer_FunnelPassesCompleted') }}
        </li>
        <li>
          <strong>{{ formatPercent(conversion) }}</strong>
          {{ translate('DataExplorer_FunnelConversionRate') }}
        </li>
      </ul>

      <ul
        v-if="series.length > 1"
        class="dataExplorerFunnel__legend"
      >
        <li
          v-for="(serie, index) in series"
          :key="serie"
        >
          <span
            class="dataExplorerFunnel__swatch"
            :style="{ background: colorOf(index) }"
          ></span>{{ serie }}
        </li>
      </ul>

      <ol
        class="dataExplorerFunnel__chart"
        :style="{ '--de-funnel-steps': steps.length }"
      >
        <li
          v-for="(step, index) in steps"
          :key="index"
          class="dataExplorerFunnel__step"
        >
          <div class="dataExplorerFunnel__stepHead">
            <span class="dataExplorerFunnel__stepNumber">{{ index + 1 }}</span>
            <span
              class="dataExplorerFunnel__stepName"
              :title="step.label"
            >{{ step.label }}</span>
          </div>
          <div class="dataExplorerFunnel__stepValue">
            <strong>{{ formatNumber(step.nb_entered) }}</strong>
            <span>{{ formatPercent(step.overall_rate) }}</span>
          </div>
          <div class="dataExplorerFunnel__bars">
            <div
              v-for="(bar, barIndex) in barsOf(step)"
              :key="barIndex"
              class="dataExplorerFunnel__barSlot"
              :title="bar.title"
            >
              <div
                class="dataExplorerFunnel__bar"
                :style="{ height: `${bar.height}%`, background: colorOf(barIndex) }"
              ></div>
            </div>
          </div>
          <div
            v-if="!isLast(index)"
            class="dataExplorerFunnel__drop"
          >
            <span class="dataExplorerFunnel__dropValue">
              {{ formatNumber(step.nb_abandoned) }}
            </span>
            <span>{{ translate('DataExplorer_FunnelAbandonedShort',
              formatPercent(step.abandon_rate)) }}</span>
          </div>
        </li>
      </ol>

      <table class="entityTable dataTable dataExplorerFunnel__table">
        <thead>
          <tr>
            <th class="label">{{ translate('DataExplorer_FunnelStep') }}</th>
            <th>{{ translate('DataExplorer_FunnelEntered') }}</th>
            <th>{{ translate('DataExplorer_FunnelStepRate') }}</th>
            <th>{{ translate('DataExplorer_FunnelAbandoned') }}</th>
            <th>{{ translate('DataExplorer_FunnelAbandonRate') }}</th>
            <th>{{ translate('DataExplorer_FunnelOverallRate') }}</th>
            <th>{{ translate('DataExplorer_FunnelAvgTime') }}</th>
          </tr>
        </thead>
        <tbody>
          <template
            v-for="(step, index) in steps"
            :key="index"
          >
            <tr class="dataExplorerFunnel__stepRow">
              <td class="label">{{ index + 1 }}. {{ step.label }}</td>
              <td>{{ formatNumber(step.nb_entered) }}</td>
              <td>{{ formatPercent(step.step_rate) }}</td>
              <td>{{ isLast(index) ? '' : formatNumber(step.nb_abandoned) }}</td>
              <td>{{ isLast(index) ? '' : formatPercent(step.abandon_rate) }}</td>
              <td>{{ formatPercent(step.overall_rate) }}</td>
              <td>{{ index > 0 ? formatTime(step.avg_time_to_step) : '' }}</td>
            </tr>
            <tr
              v-for="value in valuesOf(step)"
              :key="`${index}-${value.label}`"
              class="dataExplorerFunnel__valueRow"
            >
              <td class="label">{{ value.label }}</td>
              <td>{{ formatNumber(value.nb_entered) }}</td>
              <td>{{ formatPercent(value.step_rate) }}</td>
              <td>{{ isLast(index) ? '' : formatNumber(value.nb_abandoned) }}</td>
              <td>{{ isLast(index) ? '' : formatPercent(value.abandon_rate) }}</td>
              <td>{{ formatPercent(value.overall_rate) }}</td>
              <td>{{ index > 0 ? formatTime(value.avg_time_to_step) : '' }}</td>
            </tr>
          </template>
        </tbody>
      </table>
    </template>
  </div>
</template>

<script lang="ts">
import { computed, defineComponent, PropType } from 'vue';
import { NumberFormatter, translate } from 'CoreHome';
import type { FunnelStepResult } from '../types';

// the series colors of the core graphs
const COLORS = [
  '#5170AE', '#F3A010', '#CC3399', '#9933CC', '#80A033', '#246AD2', '#FD16EA', '#49C100',
];

/**
 * The chart and the table of a funnel: one bar per step, sized on the step that most passes
 * reached, one bar per breakdown value when the funnel has a breakdown.
 */
export default defineComponent({
  name: 'FunnelChart',
  props: {
    steps: {
      type: Array as PropType<FunnelStepResult[]>,
      default: () => [],
    },
  },
  setup(props) {
    const started = computed(() => props.steps.reduce(
      (sum, step) => sum + step.nb_started,
      0,
    ));
    const completed = computed(() => (props.steps.length
      ? props.steps[props.steps.length - 1].nb_entered
      : 0));
    const conversion = computed(() => (started.value > 0
      ? Math.round((completed.value * 1000) / started.value) / 10
      : 0));

    // the breakdown values in the order of the first step, those missing there after them
    const series = computed(() => {
      const names: string[] = [];
      props.steps.forEach((step) => {
        step.breakdown.forEach((value) => {
          if (!names.includes(value.label)) {
            names.push(value.label);
          }
        });
      });
      return names;
    });

    const scale = computed(() => {
      let max = 0;
      props.steps.forEach((step) => {
        if (series.value.length) {
          step.breakdown.forEach((value) => {
            max = Math.max(max, value.nb_entered);
          });
        } else {
          max = Math.max(max, step.nb_entered);
        }
      });
      return max;
    });

    function formatNumber(value: number) {
      return NumberFormatter.formatNumber(value, 0);
    }

    function formatPercent(value: number) {
      return NumberFormatter.formatPercent(value, 1);
    }

    // "45s", "2min 5s", "1h 20min"
    function formatTime(seconds: number) {
      const short = (key: string, value: number) => translate(key, String(value));
      if (seconds < 60) {
        return short('Intl_NSecondsShort', seconds);
      }
      if (seconds < 3600) {
        const minutes = Math.floor(seconds / 60);
        const rest = seconds % 60;
        return [short('Intl_NMinutesShort', minutes), rest ? short('Intl_NSecondsShort', rest) : '']
          .filter((part) => part !== '').join(' ');
      }
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      return [short('Intl_NHoursShort', hours), minutes ? short('Intl_NMinutesShort', minutes) : '']
        .filter((part) => part !== '').join(' ');
    }

    function height(value: number) {
      return scale.value > 0 ? Math.max(value > 0 ? 1 : 0, (value * 100) / scale.value) : 0;
    }

    function barsOf(step: FunnelStepResult) {
      if (!series.value.length) {
        return [{
          height: height(step.nb_entered),
          title: `${step.label}: ${formatNumber(step.nb_entered)}`,
        }];
      }
      return series.value.map((name) => {
        const value = step.breakdown.find((item) => item.label === name);
        const entered = value ? value.nb_entered : 0;
        return {
          height: height(entered),
          title: `${name}: ${formatNumber(entered)}`,
        };
      });
    }

    // the breakdown values of a step in the order of the legend
    function valuesOf(step: FunnelStepResult) {
      return series.value
        .map((name) => step.breakdown.find((item) => item.label === name))
        .filter((value) => !!value);
    }

    function isLast(index: number) {
      return index === props.steps.length - 1;
    }

    function colorOf(index: number) {
      return COLORS[index % COLORS.length];
    }

    return {
      started,
      completed,
      conversion,
      series,
      barsOf,
      colorOf,
      isLast,
      valuesOf,
      formatNumber,
      formatPercent,
      formatTime,
      translate,
    };
  },
});
</script>
