/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { exportFileName, tableData, toDelimited } from './exportData';

describe('DataExplorer/exportData', () => {
  it('quotes CSV values holding a comma, a quote or a line break', () => {
    expect(toDelimited(['Page', 'Visits'], [['a,b', 3], ['say "hi"', 2], ['x\ny', 1]], 'csv'))
      .toBe('Page,Visits\r\n"a,b",3\r\n"say ""hi""",2\r\n"x\ny",1');
  });

  it('turns tabs and line breaks of TSV values into spaces', () => {
    expect(toDelimited(['Page', 'Visits'], [['a\tb', 3], ['x\r\ny', null]], 'tsv'))
      .toBe('Page\tVisits\r\na b\t3\r\nx y\t');
  });

  it('names the pivot columns after their metric and value', () => {
    const { header, body } = tableData(
      ['Country'],
      [
        { key: 'm0', metric: 'nb_visits', pivot: null },
        { key: 'm0p0', metric: 'nb_visits', pivot: 'Desktop' },
      ],
      [{ labels: ['France'], values: { m0: 3, m0p0: 2 } }],
      { nb_visits: 'Visits' },
      'Total',
    );
    expect(header).toEqual(['Country', 'Visits | Total', 'Visits | Desktop']);
    expect(body).toEqual([['France', 3, 2]]);
  });

  it('builds a file name without the characters file systems refuse', () => {
    expect(exportFileName('Pages / titles', '2026-11-16', 'csv')).toBe('Pages titles 2026-11-16.csv');
  });
});
