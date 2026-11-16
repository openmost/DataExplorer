/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import type { ApiRow } from '../types';

// pivot cells are "<metric id> | <column value>" columns, see VisualQueryRunner
export const PIVOT_SEPARATOR = ' | ';

export interface ResultColumn {
  key: string;
  metric: string;
  // column (pivot) value, null for the total of the metric
  pivot: string|null;
}

export interface ResultRow {
  labels: string[];
  values: Record<string, number>;
}

export interface ChartSlice {
  labels: string[];
  values: number[];
}

export interface EvolutionSeries {
  labels: string[];
  series: { metric: string, data: number[] }[];
}

function toNumber(value: unknown): number {
  if (typeof value === 'number') {
    return value;
  }
  if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
    return Number(value);
  }
  return 0;
}

/**
 * Metric columns of a runVisual table, in the server order: each metric, then each metric of
 * each column value. Other keys (label, row metadata, subtable ids) are left out.
 */
export function resultColumns(rows: ApiRow[], metrics: string[]): ResultColumn[] {
  const columns: ResultColumn[] = [];
  const seen = new Set<string>();

  const visit = (list: ApiRow[]) => {
    list.forEach((row) => {
      Object.keys(row).forEach((key) => {
        if (seen.has(key)) {
          return;
        }
        if (metrics.includes(key)) {
          seen.add(key);
          columns.push({ key, metric: key, pivot: null });
          return;
        }
        const separator = key.indexOf(PIVOT_SEPARATOR);
        if (separator > 0 && metrics.includes(key.substring(0, separator))) {
          seen.add(key);
          columns.push({
            key,
            metric: key.substring(0, separator),
            pivot: key.substring(separator + PIVOT_SEPARATOR.length),
          });
        }
      });
    });
  };
  visit(rows);

  // totals first, then the column values in their first-seen order, metrics in exploration order
  const pivots: (string|null)[] = [null];
  columns.forEach((column) => {
    if (!pivots.includes(column.pivot)) {
      pivots.push(column.pivot);
    }
  });
  const ordered: ResultColumn[] = [];
  pivots.forEach((pivot) => {
    metrics.forEach((metric) => {
      const found = columns.find((column) => column.pivot === pivot && column.metric === metric);
      if (found) {
        ordered.push(found);
      }
    });
  });
  return ordered;
}

/**
 * One line per combination of row dimension values (GA4 layout): nested subtables become the
 * following label columns. A row without subtable before the last level (the "Others" row of a
 * level) keeps empty labels for the levels below.
 */
export function flattenRows(rows: ApiRow[], depth: number, columns: ResultColumn[]): ResultRow[] {
  const result: ResultRow[] = [];

  const visit = (list: ApiRow[], parents: string[]) => {
    list.forEach((row) => {
      const labels = [...parents, typeof row.label === 'undefined' ? '' : String(row.label)];
      if (labels.length < depth && Array.isArray(row.subtable) && row.subtable.length) {
        visit(row.subtable, labels);
        return;
      }
      const values: Record<string, number> = {};
      columns.forEach((column) => {
        values[column.key] = toNumber(row[column.key]);
      });
      while (labels.length < depth) {
        labels.push('');
      }
      result.push({ labels, values });
    });
  };
  visit(rows, []);

  return result;
}

/**
 * First metric of the first row dimension for the bar and pie charts: the largest rows, the rest
 * summed into one "others" slice.
 */
export function chartSlice(
  rows: ApiRow[],
  metric: string,
  top: number,
  othersLabel: string,
): ChartSlice {
  const sorted = rows
    .map((row) => ({ label: String(row.label ?? ''), value: toNumber(row[metric]) }))
    .sort((a, b) => b.value - a.value);

  const kept = sorted.slice(0, top);
  const rest = sorted.slice(top);
  if (rest.length) {
    kept.push({ label: othersLabel, value: rest.reduce((sum, row) => sum + row.value, 0) });
  }

  return {
    labels: kept.map((row) => row.label),
    values: kept.map((row) => row.value),
  };
}

/**
 * One series per metric of an evolution result, keyed by period like the core API output of
 * several periods.
 */
export function evolutionSeries(result: unknown, metrics: string[]): EvolutionSeries {
  const labels: string[] = [];
  const series = metrics.map((metric) => ({ metric, data: [] as number[] }));

  if (typeof result !== 'object' || result === null || Array.isArray(result)) {
    return { labels, series };
  }

  Object.entries(result as Record<string, unknown>).forEach(([period, table]) => {
    labels.push(period);
    const row = (Array.isArray(table) ? table[0] : table) as Record<string, unknown>|undefined;
    series.forEach((line) => {
      line.data.push(toNumber(row ? row[line.metric] : 0));
    });
  });

  return { labels, series };
}

/**
 * Value of a metric in a table without row dimension (KPI card).
 */
export function totalValue(result: unknown, metric: string): number|null {
  const row = (Array.isArray(result) ? result[0] : result) as Record<string, unknown>|undefined;
  if (!row || typeof row !== 'object' || !(metric in row)) {
    return null;
  }
  return toNumber(row[metric]);
}

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
