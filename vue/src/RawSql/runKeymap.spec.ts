/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { runKeymap } from './runKeymap';

describe('runKeymap', () => {
  it('binds Mod-Enter to run with the highest precedence, before the default keymap', () => {
    const bindings: { key: string, run: () => boolean }[][] = [];
    const cm = {
      keymap: { of: (list: { key: string, run: () => boolean }[]) => { bindings.push(list); return 'keymap'; } },
      Prec: { highest: (extension: unknown) => ({ highest: extension }) },
    };
    const run = jest.fn();

    const extension = runKeymap(cm as never, run);

    expect(extension).toEqual({ highest: 'keymap' });
    expect(bindings[0].map((binding) => binding.key)).toEqual(['Mod-Enter']);
    expect(bindings[0][0].run()).toBe(true);
    expect(run).toHaveBeenCalledTimes(1);
  });
});
