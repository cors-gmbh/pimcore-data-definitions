/**
 * Run history types (RunController)
 */

export type RunType = 'import' | 'export'

export type RunStatus = 'queued' | 'running' | 'stopping' | 'finished' | 'finished_with_errors' | 'failed' | 'cancelled'

export type RunTrigger = 'gui' | 'cli' | 'api' | 'rerun'

export interface Run {
  id: number
  type: RunType
  definition: number
  definitionName: string | null
  status: RunStatus
  active: boolean
  trigger: RunTrigger
  userId: number | null
  userName: string | null
  params: Record<string, unknown>
  total: number | null
  processed: number
  createdCount: number
  updatedCount: number
  skippedCount: number
  errorCount: number
  logCount: number
  message: string | null
  hostname: string | null
  pid: number | null
  createdAt: number
  startedAt: number | null
  finishedAt: number | null
}

export type RunLogLevel = 'debug' | 'info' | 'notice' | 'warning' | 'error' | 'critical' | 'alert' | 'emergency'

export interface RunLogEntry {
  id: number
  level: RunLogLevel
  message: string
  context: Record<string, unknown> | null
  rowIndex: number | null
  createdAt: number
}

export type ParamFieldType = 'text' | 'textarea' | 'number' | 'boolean' | 'select' | 'multiselect' | 'date' | 'asset' | 'object' | 'json'

export interface ParamField {
  name: string
  type: ParamFieldType
  label: string
  required: boolean
  default: unknown
  description: string | null
  options: Array<{ value: string | number, label: string }>
  group: string | null
}

export interface Paged<T> {
  total: number
  data: T[]
}
