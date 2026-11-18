/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

/**
 * CSV of a result (RFC 4180 quoting). Cells that a spreadsheet would run as a formula are
 * prefixed with a quote.
 */
export function toCsv(header: string[], lines: (string|number|null)[][]): string {
  const cell = (value: string|number|null) => {
    let text = value === null ? '' : String(value);
    if (/^[=+\-@\t\r]/.test(text) && typeof value !== 'number') {
      text = `'${text}`;
    }
    return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
  };

  return [header, ...lines].map((line) => line.map(cell).join(',')).join('\r\n');
}
