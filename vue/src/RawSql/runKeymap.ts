/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import type { Extension, KeyBinding, Prec as PrecType } from './codeMirrorModules';

interface KeymapModules {
  keymap: { of(bindings: readonly KeyBinding[]): Extension };
  Prec: typeof PrecType;
}

/**
 * Ctrl or Cmd + Enter runs the query. The default keymap of basicSetup binds the same keys to
 * insertBlankLine, so this binding needs the highest precedence.
 */
export function runKeymap(cm: KeymapModules, run: () => void): Extension {
  return cm.Prec.highest(cm.keymap.of([{
    key: 'Mod-Enter',
    run: () => {
      run();
      return true;
    },
  }]));
}
