/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { translate } from 'CoreHome';
import {
  filterList,
  fromBase64Url,
  isFilterComplete,
  toBase64Url,
} from './explorationState';
import type {
  FunnelDefinition,
  FunnelState,
  FunnelStep,
  FunnelTimeUnit,
} from '../types';

export const FUNNEL_HASH_PARAM = 'funnel';

export const MIN_FUNNEL_STEPS = 2;
export const MAX_FUNNEL_STEPS = 10;
export const MAX_STEP_NAME_LENGTH = 100;
export const DEFAULT_BREAKDOWN_ROWS = 5;
export const BREAKDOWN_ROWS = [3, 5, 10, 15, 20];
export const TIME_UNITS: FunnelTimeUnit[] = ['seconds', 'minutes', 'hours', 'days'];

export function emptyStep(number: number): FunnelStep {
  return {
    name: translate('DataExplorer_FunnelStepDefaultName', String(number)),
    conditions: [],
    optional: false,
    follow: 'indirect',
    within: null,
  };
}

export function defaultFunnel(): FunnelState {
  return {
    steps: [emptyStep(1), emptyStep(2)],
    open: false,
    breakdown: null,
    breakdownRows: DEFAULT_BREAKDOWN_ROWS,
  };
}

function step(raw: unknown, index: number): FunnelStep {
  const value = (typeof raw === 'object' && raw !== null ? raw : {}) as Record<string, unknown>;
  const name = typeof value.name === 'string' ? value.name.slice(0, MAX_STEP_NAME_LENGTH) : '';
  let within: FunnelStep['within'] = null;
  if (index > 0 && typeof value.within === 'object' && value.within !== null) {
    const limit = value.within as Record<string, unknown>;
    const amount = parseInt(String(limit.value), 10);
    if (amount > 0 && TIME_UNITS.includes(limit.unit as FunnelTimeUnit)) {
      within = { value: amount, unit: limit.unit as FunnelTimeUnit };
    }
  }
  return {
    name,
    conditions: filterList(value.conditions, 'all'),
    optional: index > 0 && value.optional === true,
    follow: index > 0 && value.follow === 'direct' ? 'direct' : 'indirect',
    within,
  };
}

/**
 * Normalizes any value into a valid funnel: at least two steps and at most ten, the first one
 * never optional and following nothing, breakdown rows within their bounds.
 */
export function normalizeFunnel(raw: unknown): FunnelState {
  const value = (typeof raw === 'object' && raw !== null ? raw : {}) as Record<string, unknown>;
  const steps = (Array.isArray(value.steps) ? value.steps : [])
    .slice(0, MAX_FUNNEL_STEPS)
    .map((item, index) => step(item, index));
  while (steps.length < MIN_FUNNEL_STEPS) {
    steps.push(emptyStep(steps.length + 1));
  }
  const rows = parseInt(String(value.breakdownRows ?? value.breakdown_rows), 10);
  return {
    steps,
    open: value.open === true,
    breakdown: typeof value.breakdown === 'string' && value.breakdown !== ''
      ? value.breakdown
      : null,
    breakdownRows: rows > 0 ? Math.min(rows, 20) : DEFAULT_BREAKDOWN_ROWS,
  };
}

export function encodeFunnel(funnel: FunnelState): string {
  return toBase64Url(JSON.stringify(normalizeFunnel(funnel)));
}

export function decodeFunnel(hash: string): FunnelState {
  if (!hash) {
    return defaultFunnel();
  }
  try {
    return normalizeFunnel(JSON.parse(fromBase64Url(hash)));
  } catch (e) {
    return defaultFunnel();
  }
}

/**
 * The steps that can run: named, with at least one complete condition each.
 */
export function isFunnelRunnable(funnel: FunnelState): boolean {
  return funnel.steps.length >= MIN_FUNNEL_STEPS
    && funnel.steps.every((item) => item.name.trim() !== ''
      && item.conditions.length > 0
      && item.conditions.every(isFilterComplete))
    && !funnel.steps[funnel.steps.length - 1].optional;
}

export function toFunnelDefinition(funnel: FunnelState, segment: string): FunnelDefinition {
  const normalized = normalizeFunnel(funnel);
  return {
    type: 'funnel',
    technique: 'funnel',
    report_type: 'funnel',
    steps: normalized.steps.map((item) => ({ ...item, name: item.name.trim() })),
    open: normalized.open,
    breakdown: normalized.breakdown,
    breakdown_rows: normalized.breakdownRows,
    segment,
  };
}

export function fromFunnelDefinition(definition: unknown): FunnelState {
  return normalizeFunnel(definition);
}

/**
 * The dimensions a funnel reads, to list them in the variables.
 */
export function funnelDimensions(funnel: FunnelState): string[] {
  const ids: string[] = [];
  funnel.steps.forEach((item) => item.conditions.forEach((condition) => {
    if (!ids.includes(condition.dimension)) {
      ids.push(condition.dimension);
    }
  }));
  if (funnel.breakdown && !ids.includes(funnel.breakdown)) {
    ids.push(funnel.breakdown);
  }
  return ids;
}
