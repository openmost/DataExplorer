/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { reactive } from 'vue';
import type { DragPayload } from '../types';

export const DRAG_MIME = 'application/x-dataexplorer-variable';

// browsers hide dataTransfer data until the drop: drop zones read the dragged kind from here to
// highlight while the pointer is over them
export const dragState = reactive<{ current: DragPayload|null }>({ current: null });

export function startDrag(event: DragEvent, payload: DragPayload): void {
  dragState.current = payload;
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData(DRAG_MIME, JSON.stringify(payload));
    event.dataTransfer.setData('text/plain', payload.id);
  }
}

export function endDrag(): void {
  dragState.current = null;
}

export function droppedPayload(event: DragEvent): DragPayload|null {
  const raw = event.dataTransfer ? event.dataTransfer.getData(DRAG_MIME) : '';
  if (raw) {
    try {
      const payload = JSON.parse(raw) as DragPayload;
      if (payload && typeof payload.id === 'string' && typeof payload.kind === 'string') {
        return payload;
      }
    } catch (e) {
      // not one of ours
    }
  }
  return dragState.current;
}
