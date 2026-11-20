/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { describe, expect, it } from 'vitest';
import {
  clampToLimits,
  decode,
  DEFAULT_DIMENSIONS,
  DEFAULT_METRICS,
  defaultState,
  encode,
  fromDefinition,
  groupFilters,
  isFilterComplete,
  toDefinition,
} from './explorationState';
import type { ExplorationState, VisualDefinition } from '../types';

function exploration(): ExplorationState {
  return {
    technique: 'freeform',
    variables: {
      dimensions: ['UserCountry.Country', 'DevicesDetection.DeviceType', 'Actions.PageTitle'],
      metrics: ['nb_visits', 'nb_actions'],
      segments: ['browserCode==FF'],
    },
    rows: ['UserCountry.Country'],
    column: 'DevicesDetection.DeviceType',
    values: ['nb_visits', 'nb_actions'],
    filters: [{
      dimension: 'Actions.PageTitle', operator: 'contains', value: 'Élan "100%" & <b>', group: 0,
    }],
    tableView: 'hierarchical',
    visualization: 'bar',
    segment: 'browserCode==FF',
  };
}

describe('explorationState', () => {
  it('round trips an exploration through the URL hash value', () => {
    const state = exploration();
    const encoded = encode(state);

    expect(encoded).toMatch(/^[A-Za-z0-9_-]+$/);
    expect(decode(encoded)).toEqual(state);
  });

  it('returns the default exploration for an empty or unreadable hash', () => {
    expect(decode('')).toEqual(defaultState());
    expect(decode('%%%not base64')).toEqual(defaultState());
    expect(decode(encode(defaultState()).slice(0, 3))).toEqual(defaultState());
  });

  it('normalizes a crafted hash', () => {
    const crafted = {
      variables: { dimensions: 'x', metrics: [1, 'nb_visits', 'nb_visits'] },
      rows: ['UserCountry.Country', 42, ''],
      column: 7,
      values: ['nb_visits'],
      filters: [
        { dimension: 'UserCountry.Country', operator: 'drop table', value: 3 },
        { operator: 'equals', value: 'x' },
        'nope',
      ],
      visualization: 'treemap',
      extra: 'ignored',
    };
    const encoded = window.btoa(JSON.stringify(crafted))
      .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

    expect(decode(encoded)).toEqual({
      technique: 'freeform',
      variables: { dimensions: ['UserCountry.Country'], metrics: ['nb_visits'], segments: [] },
      rows: ['UserCountry.Country'],
      column: null,
      values: ['nb_visits'],
      filters: [{
        dimension: 'UserCountry.Country', operator: 'equals', value: '3', group: 0,
      }],
      visualization: 'table',
      segment: '',
      tableView: 'flat',
    });
  });

  it('lists every dimension and metric in use among the variables', () => {
    const state = decode(encode({
      ...defaultState(),
      rows: ['UserCountry.Country'],
      values: ['nb_visits'],
      segment: 'countryCode==fr',
    }));

    expect(state.variables).toEqual({
      dimensions: [...DEFAULT_DIMENSIONS, 'UserCountry.Country'],
      metrics: DEFAULT_METRICS,
      segments: ['countryCode==fr'],
    });
  });

  it('builds the definition of each visualization', () => {
    const state = exploration();

    expect(toDefinition(state)).toEqual({
      technique: 'freeform',
      report_type: 'table',
      rows: ['UserCountry.Country'],
      column: 'DevicesDetection.DeviceType',
      metrics: ['nb_visits', 'nb_actions'],
      filters: [{
        dimension: 'Actions.PageTitle', operator: 'contains', value: 'Élan "100%" & <b>', group: 0,
      }],
      table_view: 'hierarchical',
      segment: 'browserCode==FF',
    });

    const line = toDefinition({ ...state, visualization: 'line' });
    expect(line.report_type).toBe('evolution');
    expect(line.rows).toEqual([]);
    expect(line.column).toBeNull();

    const kpi = toDefinition({ ...state, visualization: 'kpicard' });
    expect(kpi.report_type).toBe('kpicard');
    expect(kpi.metrics).toEqual(['nb_visits']);
  });

  it('leaves out filters without a value', () => {
    const state = {
      ...exploration(),
      filters: [{
        dimension: 'Actions.PageTitle', operator: 'equals' as const, value: ' ', group: 0,
      }],
    };

    expect(toDefinition(state).filters).toEqual([]);
  });

  it('reopens a saved definition', () => {
    const definition = toDefinition({ ...exploration(), visualization: 'table' });

    const reopened = fromDefinition(definition, 'table');
    expect(reopened.rows).toEqual(definition.rows);
    expect(reopened.column).toBe(definition.column);
    expect(reopened.values).toEqual(definition.metrics);
    expect(reopened.filters).toEqual(definition.filters);
    expect(reopened.segment).toBe('browserCode==FF');
    expect(reopened.variables.dimensions).toEqual([
      'UserCountry.Country',
      'DevicesDetection.DeviceType',
      'Actions.PageTitle',
    ]);

    expect(fromDefinition({ metrics: ['nb_visits'] }, 'evolution').visualization).toBe('line');
    expect(fromDefinition({ metrics: ['nb_visits'] }, 'kpicard').visualization).toBe('kpicard');
  });

  it('keeps a decoded exploration within the limits of the catalogue', () => {
    const state = {
      ...defaultState(),
      rows: ['a', 'b', 'c', 'd'],
      values: ['m1', 'm2', 'm3'],
    };

    const clamped = clampToLimits(state, { maxDimensions: 2, maxMetrics: 2, maxColumns: 1 });
    expect(clamped.rows).toEqual(['a', 'b']);
    expect(clamped.values).toEqual(['m1', 'm2']);
    expect(clamped.variables.dimensions).toEqual(DEFAULT_DIMENSIONS);
  });

  it('sends a filter once it has a value, a blank value is not a filter yet', () => {
    expect(isFilterComplete({
      dimension: 'UserCountry.Country', operator: 'equals', value: 'France', group: 0,
    })).toBe(true);
    expect(isFilterComplete({
      dimension: 'UserCountry.Country', operator: 'equals', value: '  ', group: 0,
    })).toBe(false);

    const state = {
      ...exploration(),
      filters: [
        {
          dimension: 'DevicesDetection.DeviceType', operator: 'not_equals' as const, value: '', group: 0,
        },
        {
          dimension: 'UserCountry.Country', operator: 'equals' as const, value: 'France', group: 1,
        },
      ],
    };
    expect(toDefinition(state).filters).toEqual([
      {
        dimension: 'UserCountry.Country', operator: 'equals', value: 'France', group: 0,
      },
    ]);
  });

  it('keeps every filter operator and its value through the URL hash', () => {
    const operators = [
      'equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'not_starts_with',
      'ends_with', 'not_ends_with', 'regex', 'gt', 'gte', 'lt', 'lte',
    ] as const;
    const filters = operators.map((operator, index) => ({
      dimension: 'Actions.PageTitle',
      operator,
      value: `v${index} & "x"`,
      group: index,
    }));

    expect(decode(encode({ ...exploration(), filters })).filters).toEqual(filters);
  });

  it('turns an unknown filter operator into equals and drops a filter without dimension', () => {
    const decoded = fromDefinition({
      rows: ['UserCountry.Country'],
      metrics: ['nb_visits'],
      filters: [
        { dimension: 'UserCountry.Country', operator: 'like' as 'equals', value: 'fr' },
        { dimension: '', operator: 'equals', value: 'x' },
      ] as VisualDefinition['filters'],
    }, 'table');

    expect(decoded.filters).toEqual([{
      dimension: 'UserCountry.Country', operator: 'equals', value: 'fr', group: 0,
    }]);
  });

  it('reopens a saved table report in the chart it was saved with', () => {
    const definition = { rows: ['UserCountry.Country'], metrics: ['nb_visits'] };
    expect(fromDefinition({ ...definition, chart: 'pie' }, 'table').visualization).toBe('pie');
    expect(fromDefinition({ ...definition, chart: 'bar' }, 'table').visualization).toBe('bar');
    expect(fromDefinition(definition, 'table').visualization).toBe('table');
    expect(fromDefinition({ ...definition, chart: 'pie' }, 'evolution').visualization).toBe('line');
  });

  it('reads the filters of a link or a report saved before groups', () => {
    const filters = [
      { dimension: 'UserCountry.Country', operator: 'equals', value: 'fr' },
      { dimension: 'UserCountry.Country', operator: 'ends_with', value: 'many' },
    ];
    const groups = (match: unknown) => fromDefinition(
      { filters, filter_match: match } as Partial<VisualDefinition>,
      'table',
    ).filters.map((filter) => filter.group);

    expect(groups('any')).toEqual([0, 0]);
    expect(groups('all')).toEqual([0, 1]);
    expect(groups('xor')).toEqual([0, 1]);
    expect(groups(undefined)).toEqual([0, 1]);
    expect(toDefinition({ ...exploration(), filters: [] })).not.toHaveProperty('filter_match');
  });

  it('orders the filters by group and numbers the groups from 0', () => {
    const filter = (value: string, group: number) => ({
      dimension: 'UserCountry.Country', operator: 'equals' as const, value, group,
    });

    const grouped = groupFilters([filter('a', 5), filter('b', 2), filter('c', 5), filter('d', 2)]);
    expect(grouped.map((item) => item.value)).toEqual(['a', 'c', 'b', 'd']);
    expect(grouped.map((item) => item.group)).toEqual([0, 0, 1, 1]);

    const state = { ...exploration(), filters: [filter('x', 3), filter('y', 1), filter('z', 3)] };
    expect(decode(encode(state)).filters.map((item) => [item.value, item.group])).toEqual([
      ['x', 0], ['z', 0], ['y', 1],
    ]);
  });
});
