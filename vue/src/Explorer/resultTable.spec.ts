/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import {
  chartSlice,
  evolutionSeries,
  flattenRows,
  resultColumns,
  toCsv,
  totalValue,
} from './resultTable';

const pivotRows = [
  {
    label: 'France',
    value: 'fr',
    nb_visits: 3,
    nb_actions: 5,
    'nb_visits | Desktop': 2,
    'nb_actions | Desktop': 4,
    'nb_visits | Smartphone': 1,
    'nb_actions | Smartphone': 1,
    idsubdatatable: 4,
    subtable: [
      { label: 'Paris', nb_visits: 2, nb_actions: 3 },
      { label: 'Lyon', nb_visits: 1, nb_actions: 2 },
    ],
  },
  { label: 'Others', nb_visits: '7', nb_actions: 9 },
];

describe('resultTable', () => {
  it('lists the metric columns, totals first then each column value', () => {
    expect(resultColumns(pivotRows, ['nb_visits', 'nb_actions'])).toEqual([
      { key: 'nb_visits', metric: 'nb_visits', pivot: null },
      { key: 'nb_actions', metric: 'nb_actions', pivot: null },
      { key: 'nb_visits | Desktop', metric: 'nb_visits', pivot: 'Desktop' },
      { key: 'nb_actions | Desktop', metric: 'nb_actions', pivot: 'Desktop' },
      { key: 'nb_visits | Smartphone', metric: 'nb_visits', pivot: 'Smartphone' },
      { key: 'nb_actions | Smartphone', metric: 'nb_actions', pivot: 'Smartphone' },
    ]);
  });

  it('flattens nested rows into one line per combination', () => {
    const columns = resultColumns(pivotRows, ['nb_visits']).filter((column) => column.pivot === null);

    expect(flattenRows(pivotRows, 2, columns)).toEqual([
      { labels: ['France', 'Paris'], values: { nb_visits: 2 } },
      { labels: ['France', 'Lyon'], values: { nb_visits: 1 } },
      { labels: ['Others', ''], values: { nb_visits: 7 } },
    ]);
    expect(flattenRows(pivotRows, 1, columns)).toEqual([
      { labels: ['France'], values: { nb_visits: 3 } },
      { labels: ['Others'], values: { nb_visits: 7 } },
    ]);
  });

  it('keeps the largest rows for the charts and sums the rest', () => {
    const rows = [
      { label: 'a', nb_visits: 1 },
      { label: 'b', nb_visits: 5 },
      { label: 'c', nb_visits: 3 },
    ];

    expect(chartSlice(rows, 'nb_visits', 2, 'Others')).toEqual({
      labels: ['b', 'c', 'Others'],
      values: [5, 3, 1],
    });
  });

  it('reads an evolution keyed by period', () => {
    const result = {
      'Mar 1': { nb_visits: 2, nb_actions: 4 },
      'Mar 2': [],
      'Mar 3': [{ nb_visits: 1, nb_actions: 1 }],
    };

    expect(evolutionSeries(result, ['nb_visits', 'nb_actions'])).toEqual({
      labels: ['Mar 1', 'Mar 2', 'Mar 3'],
      series: [
        { metric: 'nb_visits', data: [2, 0, 1] },
        { metric: 'nb_actions', data: [4, 0, 1] },
      ],
    });
    expect(evolutionSeries([], ['nb_visits']).labels).toEqual([]);
  });

  it('reads the value of a table without row dimension', () => {
    expect(totalValue({ nb_visits: 6 }, 'nb_visits')).toBe(6);
    expect(totalValue([{ nb_visits: '6' }], 'nb_visits')).toBe(6);
    expect(totalValue([], 'nb_visits')).toBeNull();
  });

  it('writes CSV safe for spreadsheets', () => {
    expect(toCsv(['a', 'b'], [['x,y', 'say "hi"'], ['=SUM(A1)', -3], [null, '@x']]))
      .toBe('a,b\r\n"x,y","say ""hi"""\r\n\'=SUM(A1),-3\r\n,\'@x');
  });
});
