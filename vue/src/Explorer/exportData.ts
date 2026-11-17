/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import type { ResultColumn, ResultRow } from './resultTable';

export type ExportFormat = 'csv' | 'tsv';

export type Cell = string | number | null | undefined;

function csvCell(value: Cell): string {
  const text = value === null || value === undefined ? '' : String(value);
  return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function tsvCell(value: Cell): string {
  const text = value === null || value === undefined ? '' : String(value);
  return text.replace(/[\t\r\n]+/g, ' ');
}

/**
 * The header and the rows as CSV (RFC 4180, comma) or TSV (tab, no quoting: tabs and line breaks
 * inside a value become spaces). Numbers stay raw so spreadsheets read them as numbers.
 */
export function toDelimited(header: Cell[], body: Cell[][], format: ExportFormat): string {
  const cell = format === 'csv' ? csvCell : tsvCell;
  const separator = format === 'csv' ? ',' : '\t';
  return [header, ...body].map((line) => line.map(cell).join(separator)).join('\r\n');
}

/**
 * The result table as header and rows: the dimensions, then one column per metric, "metric | pivot
 * value" for the columns of a pivot.
 */
export function tableData(
  dimensionNames: string[],
  columns: ResultColumn[],
  rows: ResultRow[],
  metricNames: Record<string, string>,
  totalsLabel: string,
): { header: Cell[], body: Cell[][] } {
  const hasPivot = columns.some((column) => column.pivot !== null);
  const header: Cell[] = [
    ...dimensionNames,
    ...columns.map((column) => {
      const name = metricNames[column.metric] || column.metric;
      if (!hasPivot) {
        return name;
      }
      return `${name} | ${column.pivot === null ? totalsLabel : column.pivot}`;
    }),
  ];
  const body = rows.map((row) => [
    ...row.labels,
    ...columns.map((column) => row.values[column.key]),
  ]);
  return { header, body };
}

/**
 * A file name from the exploration title and the period, without characters file systems refuse.
 */
export function exportFileName(title: string, periodText: string, extension: string): string {
  const base = `${title} ${periodText}`
    .replace(/[/\\:*?"<>|]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  return `${base || 'export'}.${extension}`;
}

export function download(content: string, fileName: string, format: ExportFormat): void {
  // the byte order mark makes Excel read the file as UTF-8
  const blob = new Blob([String.fromCharCode(0xfeff), content], {
    type: format === 'csv' ? 'text/csv;charset=utf-8' : 'text/tab-separated-values;charset=utf-8',
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 0);
}
