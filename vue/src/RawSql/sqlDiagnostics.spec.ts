/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { toDiagnostics } from './sqlDiagnostics';

const problem = (offsetUtf16: number|null, lengthUtf16: number, message: string) => ({
  offset: offsetUtf16,
  offsetUtf16,
  length: lengthUtf16,
  lengthUtf16,
  message,
});

describe('toDiagnostics', () => {
  it('underlines the offending token in UTF-16 units, errors and warnings', () => {
    const validation = {
      valid: false,
      errors: [problem(14, 4, 'This query is refused: the table "user" cannot be read.')],
      warnings: [problem(30, 9, 'The table log_visit is not filtered by idsite = {idsite}.')],
    };

    expect(toDiagnostics(validation, 50)).toEqual([
      { from: 14, to: 18, severity: 'error', message: 'This query is refused: the table "user" cannot be read.' },
      { from: 30, to: 39, severity: 'warning', message: 'The table log_visit is not filtered by idsite = {idsite}.' },
    ]);
  });

  it('covers the whole query when the problem has no offset', () => {
    const validation = { valid: false, errors: [problem(null, 0, 'too long')], warnings: [] };

    expect(toDiagnostics(validation, 12)).toEqual([{ from: 0, to: 12, severity: 'error', message: 'too long' }]);
  });

  it('keeps the range inside the document and at least one character wide', () => {
    const validation = { valid: false, errors: [problem(10, 0, 'near end'), problem(40, 5, 'stale')], warnings: [] };

    expect(toDiagnostics(validation, 11)).toEqual([
      { from: 10, to: 11, severity: 'error', message: 'near end' },
      { from: 11, to: 11, severity: 'error', message: 'stale' },
    ]);
  });
});
