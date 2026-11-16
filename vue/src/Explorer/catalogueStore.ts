/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { reactive, readonly } from 'vue';
import type { VariableKind } from '../types';

// One catalogue panel per page: the three "+" buttons of the variables column drive this store and
// the single CataloguePanel instance, so two panels never stack.

interface CatalogueState {
  open: boolean;
  kind: VariableKind|null;
}

const state = reactive<CatalogueState>({ open: false, kind: null });

let returnFocusTarget: HTMLElement|null = null;

export const catalogueState = readonly(state);

export function openCatalogue(kind: VariableKind, trigger: HTMLElement|null): void {
  state.kind = kind;
  state.open = true;
  returnFocusTarget = trigger;
}

export function closeCatalogue(): void {
  state.open = false;
}

/**
 * The "+" button that opened the panel, when it is still in the page.
 */
export function getReturnFocusTarget(): HTMLElement|null {
  return returnFocusTarget && returnFocusTarget.isConnected ? returnFocusTarget : null;
}
