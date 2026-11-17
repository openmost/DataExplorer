/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import type {
  CatalogLimits,
  CellType,
  ExplorationState,
  Filter,
  FilterOperator,
  ReportType,
  VisualDefinition,
  Visualization,
} from '../types';

export const HASH_PARAM = 'exploration';
export const REPORT_HASH_PARAM = 'idReport';

export const VISUALIZATION_ICONS: Record<Visualization, string> = {
  table: 'icon-table',
  bar: 'icon-chart-bar',
  pie: 'icon-chart-pie',
  line: 'icon-chart-line',
  kpicard: 'icon-hits',
};

export const VISUALIZATIONS: Visualization[] = ['table', 'bar', 'pie', 'line', 'kpicard'];

export const CELL_TYPES: CellType[] = ['plain', 'bar', 'heatmap'];

export const FILTER_OPERATORS: FilterOperator[] = [
  'equals',
  'not_equals',
  'contains',
  'starts_with',
  'regex',
  'gt',
  'lt',
];

// a shared link is user input: bound every list so a crafted hash cannot blow up the page
const MAX_LIST_LENGTH = 100;

export function defaultState(): ExplorationState {
  return {
    technique: 'freeform',
    variables: { dimensions: [], metrics: [], segments: [] },
    rows: [],
    column: null,
    values: [],
    filters: [],
    visualization: 'table',
    segment: '',
    cellType: 'plain',
  };
}

export function reportTypeOf(visualization: Visualization): ReportType {
  if (visualization === 'line') {
    return 'evolution';
  }
  if (visualization === 'kpicard') {
    return 'kpicard';
  }
  return 'table';
}

export function hasRowDimensions(visualization: Visualization): boolean {
  return reportTypeOf(visualization) === 'table';
}

// UTF-8 bytes through percent-encoding: the Matomo 5 test runner has no TextEncoder
function toBase64Url(text: string): string {
  const binary = encodeURIComponent(text).replace(
    /%([0-9A-F]{2})/g,
    (match, hex: string) => String.fromCharCode(parseInt(hex, 16)),
  );
  return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function fromBase64Url(encoded: string): string {
  const base64 = encoded.replace(/-/g, '+').replace(/_/g, '/');
  const binary = window.atob(base64 + '='.repeat((4 - (base64.length % 4)) % 4));
  let percentEncoded = '';
  for (let i = 0; i < binary.length; i += 1) {
    percentEncoded += `%${binary.charCodeAt(i).toString(16).padStart(2, '0')}`;
  }
  return decodeURIComponent(percentEncoded);
}

function stringList(value: unknown): string[] {
  if (!Array.isArray(value)) {
    return [];
  }
  const result: string[] = [];
  value.forEach((item) => {
    if (typeof item === 'string' && item !== '' && !result.includes(item)) {
      result.push(item);
    }
  });
  return result.slice(0, MAX_LIST_LENGTH);
}

function filterList(value: unknown): Filter[] {
  if (!Array.isArray(value)) {
    return [];
  }
  const result: Filter[] = [];
  value.forEach((item) => {
    if (typeof item !== 'object' || item === null) {
      return;
    }
    const raw = item as Record<string, unknown>;
    if (typeof raw.dimension !== 'string' || raw.dimension === '') {
      return;
    }
    const operator = FILTER_OPERATORS.includes(raw.operator as FilterOperator)
      ? raw.operator as FilterOperator
      : 'equals';
    const filterValue = typeof raw.value === 'string' || typeof raw.value === 'number'
      ? String(raw.value)
      : '';
    result.push({ dimension: raw.dimension, operator, value: filterValue });
  });
  return result.slice(0, MAX_LIST_LENGTH);
}

function withVariables(state: ExplorationState): ExplorationState {
  const dimensions = [...state.variables.dimensions];
  const used = [
    ...state.rows,
    ...(state.column ? [state.column] : []),
    ...state.filters.map((filter) => filter.dimension),
  ];
  used.forEach((id) => {
    if (!dimensions.includes(id)) {
      dimensions.push(id);
    }
  });

  const metrics = [...state.variables.metrics];
  state.values.forEach((id) => {
    if (!metrics.includes(id)) {
      metrics.push(id);
    }
  });

  const segments = [...state.variables.segments];
  if (state.segment !== '' && !segments.includes(state.segment)) {
    segments.push(state.segment);
  }

  return { ...state, variables: { dimensions, metrics, segments } };
}

/**
 * Normalizes any value into a valid exploration: unknown keys dropped, lists deduplicated,
 * every dimension or metric in use also listed in the variables.
 */
export function normalize(raw: unknown): ExplorationState {
  const state = defaultState();
  if (typeof raw !== 'object' || raw === null) {
    return state;
  }
  const value = raw as Record<string, unknown>;
  const variables = (typeof value.variables === 'object' && value.variables !== null
    ? value.variables
    : {}) as Record<string, unknown>;

  state.variables = {
    dimensions: stringList(variables.dimensions),
    metrics: stringList(variables.metrics),
    segments: stringList(variables.segments),
  };
  state.rows = stringList(value.rows);
  state.column = typeof value.column === 'string' && value.column !== '' ? value.column : null;
  state.values = stringList(value.values);
  state.filters = filterList(value.filters);
  state.visualization = VISUALIZATIONS.includes(value.visualization as Visualization)
    ? value.visualization as Visualization
    : 'table';
  state.segment = typeof value.segment === 'string' ? value.segment : '';
  state.cellType = CELL_TYPES.includes(value.cellType as CellType)
    ? value.cellType as CellType
    : 'plain';

  return withVariables(state);
}

/**
 * A shared link may hold more row dimensions or metrics than this instance accepts: the extra ones
 * are dropped, the variables lists keep them.
 */
export function clampToLimits(state: ExplorationState, limits: CatalogLimits): ExplorationState {
  return {
    ...state,
    rows: state.rows.slice(0, limits.maxDimensions),
    values: state.values.slice(0, limits.maxMetrics),
  };
}

/**
 * Exploration as the value of the URL hash parameter (base64url of its JSON).
 */
export function encode(state: ExplorationState): string {
  return toBase64Url(JSON.stringify(normalize(state)));
}

/**
 * Exploration of a URL hash parameter value, the default exploration when it cannot be read.
 */
export function decode(hash: string): ExplorationState {
  if (!hash) {
    return defaultState();
  }
  try {
    return normalize(JSON.parse(fromBase64Url(hash)));
  } catch (e) {
    return defaultState();
  }
}

export function isFilterComplete(filter: Filter): boolean {
  return filter.value.trim() !== '';
}

/**
 * Visual definition sent to DataExplorer.runVisual and saved by addReport / updateReport.
 */
export function toDefinition(state: ExplorationState): VisualDefinition {
  const reportType = reportTypeOf(state.visualization);
  const isTable = reportType === 'table';

  return {
    technique: 'freeform',
    report_type: reportType,
    rows: isTable ? [...state.rows] : [],
    column: isTable ? state.column : null,
    metrics: reportType === 'kpicard' ? state.values.slice(0, 1) : [...state.values],
    filters: state.filters.filter(isFilterComplete).map((filter) => ({ ...filter })),
    segment: state.segment,
  };
}

/**
 * Exploration of a saved visual definition.
 */
export function fromDefinition(
  definition: Partial<VisualDefinition>,
  reportType: ReportType,
): ExplorationState {
  let visualization: Visualization = 'table';
  if (reportType === 'evolution') {
    visualization = 'line';
  } else if (reportType === 'kpicard') {
    visualization = 'kpicard';
  }

  return normalize({
    rows: definition.rows,
    column: definition.column,
    values: definition.metrics,
    filters: definition.filters,
    segment: definition.segment,
    visualization,
  });
}
