/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { AjaxHelper, Matomo } from 'CoreHome';
import type { Catalog } from '../types';

// the values a filter or a funnel condition can pick from, as the segment editor suggests them:
// the segment of each dimension, then its suggested values, fetched once per dimension
const segmentByDimension: Record<string, string> = {};
const cache: Record<string, Promise<string[]>> = {};

export function registerSegments(catalog: Catalog): void {
  catalog.dimensions.forEach((group) => {
    group.items.forEach((item) => {
      if (item.segment) {
        segmentByDimension[item.id] = item.segment;
      }
    });
  });
}

export function suggestedValues(dimension: string): Promise<string[]> {
  const segmentName = segmentByDimension[dimension];
  if (!segmentName) {
    return Promise.resolve([]);
  }
  if (!cache[dimension]) {
    cache[dimension] = AjaxHelper.fetch<unknown>(
      {
        method: 'API.getSuggestedValuesForSegment',
        segmentName,
        segment: null,
        idSite: Matomo.idSite,
      },
      { createErrorNotification: false },
    ).then((values) => (Array.isArray(values)
      ? values.map((value) => String(value)).filter((value) => value !== '')
      : [])).catch(() => []);
  }
  return cache[dimension];
}
