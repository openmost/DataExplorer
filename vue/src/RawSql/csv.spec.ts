/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { toCsv } from './csv';

describe('csv', () => {
  it('writes CSV safe for spreadsheets', () => {
    expect(toCsv(['a', 'b'], [['x,y', 'say "hi"'], ['=SUM(A1)', -3], [null, '@x']]))
      .toBe('a,b\r\n"x,y","say ""hi"""\r\n\'=SUM(A1),-3\r\n,\'@x');
  });
});
