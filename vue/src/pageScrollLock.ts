/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

const PAGE_SCROLL_LOCK_CLASS = 'dataExplorer-panel-open';
const SCROLLBAR_WIDTH_VAR = '--dataexplorer-scrollbar-width';

// hiding the page scrollbar widens the page: its width is padded back so the columns do not move
export function lockPageScroll(lock: boolean): void {
  const root = document.documentElement;
  if (lock) {
    root.style.setProperty(SCROLLBAR_WIDTH_VAR, `${window.innerWidth - root.clientWidth}px`);
  } else {
    root.style.removeProperty(SCROLLBAR_WIDTH_VAR);
  }
  root.classList.toggle(PAGE_SCROLL_LOCK_CLASS, lock);
}
