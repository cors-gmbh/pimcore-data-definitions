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

import type { Run, RunStatus } from '../../types/runs'

export const STATUS_COLORS: Record<RunStatus, string> = {
  queued: 'default',
  running: 'processing',
  stopping: 'warning',
  finished: 'success',
  finished_with_errors: 'warning',
  failed: 'error',
  cancelled: 'default'
}

export const LEVEL_COLORS: Record<string, string> = {
  debug: 'default',
  info: 'blue',
  notice: 'cyan',
  warning: 'orange',
  error: 'red',
  critical: 'magenta',
  alert: 'magenta',
  emergency: 'magenta'
}

export function formatTimestamp (timestamp: number | null): string {
  if (timestamp === null) return '–'
  return new Date(timestamp * 1000).toLocaleString()
}

export function formatDuration (run: Run): string {
  if (run.startedAt === null) return '–'
  const end = run.finishedAt ?? Math.floor(Date.now() / 1000)
  let seconds = Math.max(0, end - run.startedAt)
  const hours = Math.floor(seconds / 3600)
  seconds -= hours * 3600
  const minutes = Math.floor(seconds / 60)
  seconds -= minutes * 60

  if (hours > 0) return `${hours}h ${minutes}m`
  if (minutes > 0) return `${minutes}m ${seconds}s`
  return `${seconds}s`
}

export function progressPercent (run: Run): number | null {
  if (run.total === null || run.total === 0) return null
  return Math.min(100, Math.round((run.processed / run.total) * 100))
}
