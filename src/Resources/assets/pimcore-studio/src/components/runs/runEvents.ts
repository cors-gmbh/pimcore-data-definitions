/**
 * Data Definitions Bundle - Pimcore Studio Plugin
 *
 * This source file is available under the Data Definitions Commercial License (DDCL).
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    DDCL
 */

import type { Run, RunType } from '../../types/runs'

export type RunUpdateEvent = 'changed' | 'progress' | 'deleted'

/**
 * Pushed by the backend via Mercure. "changed" carries the complete run, "progress" only counters,
 * "deleted" only id/type/definition.
 */
export interface RunUpdate {
  event: RunUpdateEvent
  run: Partial<Run> & { id: number, type: RunType }
}

/**
 * Tiny bus between the Mercure handler, the start dialog and the history / log views.
 */
const target = new EventTarget()

function on<T> (name: string, listener: (detail: T) => void): () => void {
  const handler = (event: Event): void => { listener((event as CustomEvent<T>).detail) }
  target.addEventListener(name, handler)
  return () => { target.removeEventListener(name, handler) }
}

export const runEvents = {
  emitStarted (run: Run): void {
    target.dispatchEvent(new CustomEvent<Run>('started', { detail: run }))
  },

  onStarted (listener: (run: Run) => void): () => void {
    return on('started', listener)
  },

  emitUpdate (update: RunUpdate): void {
    target.dispatchEvent(new CustomEvent<RunUpdate>('update', { detail: update }))
  },

  onUpdate (listener: (update: RunUpdate) => void): () => void {
    return on('update', listener)
  }
}
