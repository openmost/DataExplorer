/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import {
  clampToLimits,
  decode,
  defaultState,
  encode,
  fromDefinition,
  isFilterComplete,
  toDefinition,
} from './explorationState';
import type { ExplorationState } from '../types';

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
    filters: [{ dimension: 'Actions.PageTitle', operator: 'contains', value: 'Élan "100%" & <b>' }],
    visualization: 'bar',
    segment: 'browserCode==FF',
    cellType: 'heatmap',
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
      filters: [{ dimension: 'UserCountry.Country', operator: 'equals', value: '3' }],
      visualization: 'table',
      segment: '',
      cellType: 'plain',
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
      dimensions: ['UserCountry.Country'],
      metrics: ['nb_visits'],
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
      filters: [{ dimension: 'Actions.PageTitle', operator: 'contains', value: 'Élan "100%" & <b>' }],
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
    const state = { ...exploration(), filters: [{ dimension: 'Actions.PageTitle', operator: 'equals' as const, value: ' ' }] };

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
      cellType: 'bar' as const,
    };

    const clamped = clampToLimits(state, { maxDimensions: 2, maxMetrics: 2, maxColumns: 1 });
    expect(clamped.rows).toEqual(['a', 'b']);
    expect(clamped.values).toEqual(['m1', 'm2']);
    expect(clamped.variables.dimensions).toEqual([]);
    expect(clamped.cellType).toBe('bar');
  });

  it('defaults an unknown cell type to plain', () => {
    expect(decode(encode({ ...defaultState(), cellType: 'sparkles' as 'plain' })).cellType).toBe('plain');
  });

  it('sends a filter once it has a value, a blank value is not a filter yet', () => {
    expect(isFilterComplete({ dimension: 'UserCountry.Country', operator: 'equals', value: 'France' })).toBe(true);
    expect(isFilterComplete({ dimension: 'UserCountry.Country', operator: 'equals', value: '  ' })).toBe(false);

    const state = {
      ...exploration(),
      filters: [
        { dimension: 'UserCountry.Country', operator: 'equals' as const, value: 'France' },
        { dimension: 'DevicesDetection.DeviceType', operator: 'not_equals' as const, value: '' },
      ],
    };
    expect(toDefinition(state).filters).toEqual([
      { dimension: 'UserCountry.Country', operator: 'equals', value: 'France' },
    ]);
  });

  it('keeps every filter operator and its value through the URL hash', () => {
    const operators = ['equals', 'not_equals', 'contains', 'starts_with', 'regex', 'gt', 'lt'] as const;
    const filters = operators.map((operator, index) => ({
      dimension: 'Actions.PageTitle',
      operator,
      value: `v${index} & "x"`,
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
      ],
    }, 'table');

    expect(decoded.filters).toEqual([{ dimension: 'UserCountry.Country', operator: 'equals', value: 'fr' }]);
  });

  it('reopens a saved table report in the chart it was saved with', () => {
    const definition = { rows: ['UserCountry.Country'], metrics: ['nb_visits'] };
    expect(fromDefinition({ ...definition, chart: 'pie' }, 'table').visualization).toBe('pie');
    expect(fromDefinition({ ...definition, chart: 'bar' }, 'table').visualization).toBe('bar');
    expect(fromDefinition(definition, 'table').visualization).toBe('table');
    expect(fromDefinition({ ...definition, chart: 'pie' }, 'evolution').visualization).toBe('line');
  });
});
