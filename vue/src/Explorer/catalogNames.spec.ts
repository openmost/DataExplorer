/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { describe, expect, it } from 'vitest';
import { distinctNames } from './catalogNames';

const scopeLabel = (scope: string) => `scope ${scope}`;

describe('distinctNames', () => {
  it('keeps unique names as they are', () => {
    expect(distinctNames([
      { id: 'a', name: 'Country', scope: 'visit' },
      { id: 'b', name: 'Page URL', scope: 'action' },
    ], scopeLabel)).toEqual({ a: 'Country', b: 'Page URL' });
  });

  it('adds the scope to names used by several items', () => {
    expect(distinctNames([
      { id: 'Actions.VisitTotalActions', name: 'Actions', scope: 'visit' },
      { id: 'Actions.ActionType', name: 'Actions', scope: 'action' },
      { id: 'c', name: 'Country', scope: 'visit' },
    ], scopeLabel)).toEqual({
      'Actions.VisitTotalActions': 'Actions (scope visit)',
      'Actions.ActionType': 'Actions (scope action)',
      c: 'Country',
    });
  });

  it('adds the id when the name and the scope are the same', () => {
    expect(distinctNames([
      { id: 'One.Thing', name: 'Thing', scope: 'visit' },
      { id: 'Two.Thing', name: 'Thing', scope: 'visit' },
    ], scopeLabel)).toEqual({
      'One.Thing': 'Thing (scope visit, One.Thing)',
      'Two.Thing': 'Thing (scope visit, Two.Thing)',
    });
  });
});
