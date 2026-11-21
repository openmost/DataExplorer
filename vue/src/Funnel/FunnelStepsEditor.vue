<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <SidePanel
    :model-value="modelValue"
    :title="translate('DataExplorer_FunnelEditSteps')"
    wide
    focus-selector=".dataExplorerFunnelEditor__name"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <ol class="dataExplorerFunnelEditor">
      <template
        v-for="(step, index) in draft"
        :key="step.key"
      >
        <li
          v-if="index > 0"
          class="dataExplorerFunnelEditor__link"
        >
          <select
            class="dataExplorerFunnelEditor__follow browser-default"
            :aria-label="translate('DataExplorer_FunnelFollowLabel', String(index + 1))"
            :value="step.follow"
            @change="setFollow(step, $event)"
          >
            <option
              v-for="option in followOptions"
              :key="option.key"
              :value="option.key"
            >{{ option.value }}</option>
          </select>
          <label class="dataExplorerFunnelEditor__within">
            <input
              type="checkbox"
              :checked="!!step.within"
              @change="toggleWithin(step)"
            />
            <span>{{ translate('DataExplorer_FunnelWithin') }}</span>
          </label>
          <template v-if="step.within">
            <input
              type="number"
              min="1"
              class="dataExplorerFunnelEditor__withinValue browser-default"
              :aria-label="translate('DataExplorer_FunnelWithinValue')"
              :value="step.within.value"
              @input="setWithinValue(step, $event)"
            />
            <select
              class="dataExplorerFunnelEditor__withinUnit browser-default"
              :aria-label="translate('DataExplorer_FunnelWithinUnit')"
              :value="step.within.unit"
              @change="setWithinUnit(step, $event)"
            >
              <option
                v-for="unit in timeUnits"
                :key="unit.key"
                :value="unit.key"
              >{{ unit.value }}</option>
            </select>
          </template>
        </li>
        <li class="dataExplorerFunnelEditor__step">
          <div class="dataExplorerFunnelEditor__head">
            <span class="dataExplorerFunnel__stepNumber">{{ index + 1 }}</span>
            <input
              v-model="step.name"
              type="text"
              class="dataExplorerFunnelEditor__name browser-default"
              :maxlength="maxNameLength"
              :aria-label="translate('DataExplorer_FunnelStepName', String(index + 1))"
            />
            <label
              v-if="index > 0 && index < draft.length - 1"
              class="dataExplorerFunnelEditor__optional"
            >
              <input
                type="checkbox"
                :checked="step.optional"
                @change="step.optional = !step.optional"
              />
              <span>{{ translate('DataExplorer_FunnelOptional') }}</span>
            </label>
            <div class="dataExplorerFunnelEditor__menu">
              <button
                type="button"
                class="dataExplorerIconButton"
                :aria-label="translate('DataExplorer_FunnelStepActions', String(index + 1))"
                :title="translate('DataExplorer_FunnelStepActions', String(index + 1))"
                :aria-expanded="menuFor === step.key ? 'true' : 'false'"
                @click="menuFor = menuFor === step.key ? null : step.key"
              >
                <span class="icon-more-verti" aria-hidden="true"></span>
              </button>
              <ul
                v-if="menuFor === step.key"
                class="dataExplorerFunnelEditor__menuList"
              >
                <li>
                  <button
                    type="button"
                    :disabled="draft.length >= maxSteps"
                    @click="copyStep(index)"
                  >{{ translate('DataExplorer_FunnelCopyStep') }}</button>
                </li>
                <li>
                  <button
                    type="button"
                    :disabled="draft.length >= maxSteps"
                    @click="insertStep(index)"
                  >{{ translate('DataExplorer_FunnelAddStepAbove') }}</button>
                </li>
                <li>
                  <button
                    type="button"
                    :disabled="draft.length >= maxSteps"
                    @click="insertStep(index + 1)"
                  >{{ translate('DataExplorer_FunnelAddStepBelow') }}</button>
                </li>
                <li>
                  <button
                    type="button"
                    :disabled="draft.length <= minSteps"
                    @click="removeStep(index)"
                  >{{ translate('DataExplorer_FunnelDeleteStep') }}</button>
                </li>
              </ul>
            </div>
          </div>

          <template
            v-for="(group, groupIndex) in groupsOf(step)"
            :key="groupIndex"
          >
            <div
              v-if="groupIndex > 0"
              class="dataExplorerFilterGroup__join"
              aria-hidden="true"
            >{{ translate('DataExplorer_FilterAnd') }}</div>
            <div class="dataExplorerFilterGroup">
              <ul class="dataExplorerFilters">
                <template
                  v-for="(item, position) in group"
                  :key="item.condition.key"
                >
                  <li
                    v-if="position > 0"
                    class="dataExplorerFilters__or"
                    aria-hidden="true"
                  >{{ translate('DataExplorer_FilterOr') }}</li>
                  <FilterRow
                    :filter="item.condition"
                    :dimension-name="nameOf(item.condition.dimension)"
                    @update="updateCondition(step, item.index, $event)"
                    @remove="removeCondition(step, item.index)"
                  />
                </template>
              </ul>
              <div class="dataExplorerFilterGroup__actions">
                <FilterOrButton
                  :options="dimensionOptions"
                  @add="addCondition(step, group[0].condition.group, $event)"
                />
              </div>
            </div>
          </template>
          <div class="dataExplorerFilterGroup__actions">
            <FilterOrButton
              :options="dimensionOptions"
              :label="step.conditions.length
                ? translate('DataExplorer_FilterAddAnd')
                : translate('DataExplorer_FunnelAddCondition')"
              :aria-label="translate('DataExplorer_FunnelAddConditionLabel', String(index + 1))"
              @add="addCondition(step, -1, $event)"
            />
          </div>
        </li>
      </template>
    </ol>
    <button
      type="button"
      class="btn-flat dataExplorerFunnelEditor__add"
      :disabled="draft.length >= maxSteps"
      @click="insertStep(draft.length)"
    >
      <span class="icon-add" aria-hidden="true"></span>
      {{ translate('DataExplorer_FunnelAddStep') }}
    </button>
    <p
      v-if="error"
      class="dataExplorerFunnelEditor__error"
      role="alert"
    >{{ error }}</p>

    <template #actions>
      <button
        type="button"
        class="btn-flat"
        @click="$emit('update:modelValue', false)"
      >{{ translate('General_Cancel') }}</button>
      <button
        type="button"
        class="btn"
        :disabled="!!error"
        @click="apply"
      >{{ translate('DataExplorer_FilterApply') }}</button>
    </template>
  </SidePanel>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  onBeforeUnmount,
  onMounted,
  PropType,
  ref,
  watch,
} from 'vue';
import { translate } from 'CoreHome';
import SidePanel from '../SidePanel/SidePanel.vue';
import FilterRow from '../Explorer/FilterRow.vue';
import FilterOrButton from '../Explorer/FilterOrButton.vue';
import { groupFilters } from '../Explorer/explorationState';
import {
  emptyStep,
  MAX_FUNNEL_STEPS,
  MAX_STEP_NAME_LENGTH,
  MIN_FUNNEL_STEPS,
  TIME_UNITS,
} from '../Explorer/funnelState';
import type {
  Filter,
  FunnelFollow,
  FunnelStep,
  FunnelTimeUnit,
  VariableView,
} from '../types';

type KeyedCondition = Filter & { key: number };
type DraftStep = Omit<FunnelStep, 'conditions'> & { key: number, conditions: KeyedCondition[] };

let nextKey = 0;
function key(): number {
  nextKey += 1;
  return nextKey;
}

/**
 * The steps of a funnel edited on a copy, applied at once: each step has a name, conditions on
 * the dimensions of the variables (OR inside a group, AND between groups, all on one action),
 * can be optional, and follows the previous step directly or not, within a time limit or not.
 */
export default defineComponent({
  name: 'FunnelStepsEditor',
  components: {
    FilterOrButton,
    FilterRow,
    SidePanel,
  },
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    steps: {
      type: Array as PropType<FunnelStep[]>,
      required: true,
    },
    dimensionOptions: {
      type: Array as PropType<VariableView[]>,
      default: () => [],
    },
  },
  emits: ['update:modelValue', 'apply'],
  setup(props, { emit }) {
    const draft = ref<DraftStep[]>([]);
    const menuFor = ref<number|null>(null);

    // the step menu closes on a click anywhere else
    function closeMenuOutside(event: MouseEvent) {
      if (menuFor.value !== null && !(event.target instanceof Element
        && event.target.closest('.dataExplorerFunnelEditor__menu'))) {
        menuFor.value = null;
      }
    }
    onMounted(() => document.addEventListener('mousedown', closeMenuOutside));
    onBeforeUnmount(() => document.removeEventListener('mousedown', closeMenuOutside));

    function toDraft(step: FunnelStep): DraftStep {
      return {
        ...step,
        within: step.within ? { ...step.within } : null,
        key: key(),
        conditions: step.conditions.map((condition) => ({ ...condition, key: key() })),
      };
    }

    watch(() => props.modelValue, (open) => {
      if (open) {
        draft.value = props.steps.map(toDraft);
        menuFor.value = null;
      }
    }, { immediate: true });

    const names = computed(() => {
      const map: Record<string, string> = {};
      props.dimensionOptions.forEach((option) => {
        map[option.id] = option.name;
      });
      return map;
    });

    function nameOf(id: string) {
      return names.value[id] || id;
    }

    function groupsOf(step: DraftStep) {
      const groups = new Map<number, { condition: KeyedCondition, index: number }[]>();
      step.conditions.forEach((condition, index) => {
        const group = groups.get(condition.group) || [];
        group.push({ condition, index });
        groups.set(condition.group, group);
      });
      return [...groups.values()];
    }

    function renumber(step: DraftStep) {
      const order = new Map<number, number>();
      step.conditions.forEach((condition) => {
        if (!order.has(condition.group)) {
          order.set(condition.group, order.size);
        }
        condition.group = order.get(condition.group) as number;
      });
    }

    // group -1 starts a new group (AND), any other is ORed with that group
    function addCondition(step: DraftStep, group: number, dimension: string) {
      const target = group < 0
        ? step.conditions.reduce((max, item) => Math.max(max, item.group + 1), 0)
        : group;
      const condition: KeyedCondition = {
        dimension, operator: 'equals', value: '', group: target, key: key(),
      };
      const last = step.conditions.map((item) => item.group).lastIndexOf(target);
      step.conditions.splice(last < 0 ? step.conditions.length : last + 1, 0, condition);
    }

    function updateCondition(step: DraftStep, index: number, filter: Filter) {
      Object.assign(step.conditions[index], filter);
    }

    function removeCondition(step: DraftStep, index: number) {
      step.conditions.splice(index, 1);
      renumber(step);
    }

    function insertStep(at: number) {
      menuFor.value = null;
      if (draft.value.length >= MAX_FUNNEL_STEPS) {
        return;
      }
      draft.value.splice(at, 0, toDraft(emptyStep(draft.value.length + 1)));
    }

    function copyStep(index: number) {
      menuFor.value = null;
      if (draft.value.length >= MAX_FUNNEL_STEPS) {
        return;
      }
      const source = draft.value[index];
      draft.value.splice(index + 1, 0, toDraft({
        ...source,
        name: translate('DataExplorer_FunnelStepCopyName', source.name)
          .slice(0, MAX_STEP_NAME_LENGTH),
      }));
    }

    function removeStep(index: number) {
      menuFor.value = null;
      if (draft.value.length > MIN_FUNNEL_STEPS) {
        draft.value.splice(index, 1);
      }
    }

    function toggleWithin(step: DraftStep) {
      step.within = step.within ? null : { value: 30, unit: 'minutes' };
    }

    function setWithinValue(step: DraftStep, event: Event) {
      if (step.within) {
        step.within.value = parseInt((event.target as HTMLInputElement).value, 10) || 0;
      }
    }

    function setFollow(step: DraftStep, event: Event) {
      step.follow = (event.target as HTMLSelectElement).value as FunnelFollow;
    }

    function setWithinUnit(step: DraftStep, event: Event) {
      if (step.within) {
        step.within.unit = (event.target as HTMLSelectElement).value as FunnelTimeUnit;
      }
    }

    const error = computed(() => {
      const unnamed = draft.value.findIndex((step) => step.name.trim() === '');
      if (unnamed >= 0) {
        return translate('DataExplorer_FunnelStepNameRequired', String(unnamed + 1));
      }
      const badLimit = draft.value.findIndex((step) => step.within && step.within.value < 1);
      if (badLimit >= 0) {
        return translate('DataExplorer_FunnelWithinInvalid', String(badLimit + 1));
      }
      return '';
    });

    function apply() {
      if (error.value) {
        return;
      }
      const last = draft.value.length - 1;
      emit('apply', draft.value.map((step, index): FunnelStep => ({
        name: step.name.trim(),
        conditions: groupFilters(step.conditions.map((condition) => ({
          dimension: condition.dimension,
          operator: condition.operator,
          value: condition.value,
          group: condition.group,
        }))),
        optional: index > 0 && index < last && step.optional,
        follow: index > 0 ? step.follow : 'indirect',
        within: index > 0 && step.within ? { ...step.within } : null,
      })));
      emit('update:modelValue', false);
    }

    return {
      draft,
      menuFor,
      nameOf,
      groupsOf,
      addCondition,
      updateCondition,
      removeCondition,
      insertStep,
      copyStep,
      removeStep,
      toggleWithin,
      setWithinValue,
      setWithinUnit,
      setFollow,
      error,
      apply,
      maxSteps: MAX_FUNNEL_STEPS,
      minSteps: MIN_FUNNEL_STEPS,
      maxNameLength: MAX_STEP_NAME_LENGTH,
      followOptions: [
        { key: 'indirect', value: translate('DataExplorer_FunnelFollowIndirect') },
        { key: 'direct', value: translate('DataExplorer_FunnelFollowDirect') },
      ],
      timeUnits: TIME_UNITS.map((unit) => ({
        key: unit,
        value: translate(`DataExplorer_FunnelUnit_${unit}`),
      })),
      translate,
    };
  },
});
</script>
