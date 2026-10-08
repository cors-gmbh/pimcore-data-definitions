/**
 * DataDefinitions API Service
 *
 * Talks to the CoreShop resource controllers under the Studio API prefix
 * (/pimcore-studio/api/data_definitions/...). The routes sit behind the Studio
 * API firewall and are authenticated by the Studio session, so `credentials:
 * 'include'` is all that is needed. CoreShop's BodyListener decodes JSON
 * request bodies, so we can post application/json directly.
 */

import type { ImportDefinition, ExportDefinition, DefinitionConfig, ColumnResponse } from '../types/definitions'
import type { Paged, ParamField, Run, RunLogEntry, RunStatus, RunType } from '../types/runs'

interface EntityWithId {
  id?: number
}

const API_BASE = '/pimcore-studio/api/data_definitions'
const IMPORT_RULES_BASE = '/pimcore-studio/api/data-definitions/import-rules'
const RUNS_BASE = '/pimcore-studio/api/data-definitions/runs'

async function failWith (action: string, response: Response): Promise<never> {
  let detail = ''
  try {
    detail = (await response.text()).slice(0, 300)
  } catch {
    // response body unreadable — status alone will have to do
  }
  throw new Error(`${action} failed (HTTP ${response.status})${detail !== '' ? `: ${detail}` : ''}`)
}

/**
 * The CoreShop resource controllers answer failures with HTTP 200 and
 * { success: false, message } (e.g. form validation errors on save) —
 * surface those as errors instead of handing the envelope to the UI.
 */
function unwrap<R> (data: any): R {
  if (data !== null && typeof data === 'object' && data.success === false) {
    throw new Error(typeof data.message === 'string' && data.message !== '' ? data.message : 'Request rejected by the server')
  }

  return (data?.data ?? data) as R
}

async function adminGet (url: string): Promise<Response> {
  return await fetch(url, {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include'
  })
}

async function adminSend (url: string, method: 'POST' | 'DELETE', body?: unknown): Promise<Response> {
  return await fetch(url, {
    method,
    credentials: 'include',
    headers: {
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {})
    },
    ...(body !== undefined ? { body: JSON.stringify(body) } : {})
  })
}

/**
 * Base API class for entity operations
 */
abstract class BaseEntityApi<T extends EntityWithId> {
  protected abstract buildUrl (route: string): string

  async list (): Promise<T[]> {
    const response = await adminGet(this.buildUrl('/list'))
    if (!response.ok) await failWith('Loading definition list', response)
    return unwrap(await response.json())
  }

  async get (id: number): Promise<T> {
    const response = await adminGet(this.buildUrl(`/get?id=${id}`))
    if (!response.ok) await failWith('Loading definition', response)
    return unwrap(await response.json())
  }

  async add (entity: Partial<T>): Promise<T> {
    const response = await adminSend(this.buildUrl('/add'), 'POST', entity)
    if (!response.ok) await failWith('Creating definition', response)
    return unwrap(await response.json())
  }

  async save (entity: T): Promise<T> {
    const response = await adminSend(this.buildUrl('/save'), 'POST', entity)
    if (!response.ok) await failWith('Saving definition', response)
    return unwrap(await response.json())
  }

  async delete (id: number): Promise<void> {
    const response = await adminSend(this.buildUrl(`/delete?id=${id}`), 'DELETE')
    if (!response.ok) await failWith('Deleting definition', response)
  }

  async getConfig (): Promise<DefinitionConfig> {
    const response = await adminGet(this.buildUrl('/get-config'))
    if (!response.ok) await failWith('Loading configuration', response)
    return await response.json()
  }

  async getColumns (definitionId: number): Promise<ColumnResponse> {
    const response = await adminGet(this.buildUrl(`/get-columns?id=${definitionId}`))
    if (!response.ok) await failWith('Loading columns', response)
    return await response.json()
  }

  async export (definitionId: number): Promise<Blob> {
    // the vendor route is GET-only
    const response = await adminGet(this.buildUrl(`/export?id=${definitionId}`))
    if (!response.ok) await failWith('Exporting definition', response)
    return await response.blob()
  }

  async duplicate (definitionId: number, name: string): Promise<T> {
    const response = await adminSend(this.buildUrl('/duplicate'), 'POST', { id: definitionId, name })
    if (!response.ok) await failWith('Duplicating definition', response)
    return unwrap(await response.json())
  }
}

/**
 * Import Definition API
 */
export class ImportDefinitionApi extends BaseEntityApi<ImportDefinition> {
  protected buildUrl (route: string): string {
    return `${API_BASE}/import_definitions${route}`
  }

  /**
   * Import a definition from JSON file
   */
  async import (definitionId: number, file: File): Promise<ImportDefinition> {
    const formData = new FormData()
    formData.append('Filedata', file)
    formData.append('id', String(definitionId))

    const response = await fetch(this.buildUrl('/import'), {
      method: 'POST',
      credentials: 'include',
      // no explicit Content-Type — the browser sets the multipart boundary
      body: formData
    })
    if (!response.ok) await failWith('Importing definition', response)
    return unwrap(await response.json())
  }
}

/**
 * Export Definition API
 */
export class ExportDefinitionApi extends BaseEntityApi<ExportDefinition> {
  protected buildUrl (route: string): string {
    return `${API_BASE}/export_definitions${route}`
  }
}

/**
 * Import rules of the import_rule interpreter: XLSX round trip
 * (ImportRuleController::exportAction / importAction).
 */
export const importRuleApi = {
  async exportRules (rules: unknown[]): Promise<Blob> {
    const response = await fetch(`${IMPORT_RULES_BASE}/export`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ rules: JSON.stringify(rules) })
    })
    if (!response.ok) await failWith('Exporting import rules', response)
    return await response.blob()
  },

  async importRules<R> (file: File): Promise<R[]> {
    const formData = new FormData()
    formData.append('file', file)

    const response = await fetch(`${IMPORT_RULES_BASE}/import`, {
      method: 'POST',
      credentials: 'include',
      body: formData
    })
    if (!response.ok) await failWith('Importing import rules', response)
    const data: { success?: boolean, rules?: R[], message?: string } = await response.json()
    if (data.success !== true || !Array.isArray(data.rules)) {
      throw new Error(typeof data.message === 'string' && data.message !== '' ? data.message : 'Import rejected by the server')
    }
    return data.rules
  }
}

/**
 * The run endpoints answer errors with a proper HTTP status and a Symfony error body.
 */
async function runJson<R> (action: string, response: Response): Promise<R> {
  if (!response.ok) {
    let detail = ''
    try {
      const body = await response.json()
      detail = typeof body?.detail === 'string' ? body.detail : (typeof body?.message === 'string' ? body.message : '')
    } catch {
      // no JSON body
    }
    throw new Error(`${action} failed (HTTP ${response.status})${detail !== '' ? `: ${detail}` : ''}`)
  }

  return await response.json()
}

function query (params: Record<string, string | number | undefined | null>): string {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      search.append(key, String(value))
    }
  })
  const result = search.toString()
  return result !== '' ? `?${result}` : ''
}

/**
 * Import/export runs: start, history, logs (RunController)
 */
export const runApi = {
  async paramsSchema (type: RunType, definition: number): Promise<ParamField[]> {
    const response = await adminGet(`${RUNS_BASE}/params-schema${query({ type, definition })}`)
    const data = await runJson<{ fields: ParamField[] }>('Loading run parameters', response)
    return data.fields
  },

  async start (type: RunType, definition: number, params: Record<string, unknown>, async = true): Promise<Run> {
    const response = await adminSend(`${RUNS_BASE}/start`, 'POST', { type, definition, params, async })
    return (await runJson<{ run: Run }>('Starting run', response)).run
  },

  async upload (type: RunType, file: File): Promise<string> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', type)

    const response = await fetch(`${RUNS_BASE}/upload`, {
      method: 'POST',
      credentials: 'include',
      body: formData
    })
    return (await runJson<{ asset: string }>('Uploading file', response)).asset
  },

  async list (filter: { type?: RunType, definition?: number, status?: RunStatus[], limit?: number, offset?: number }): Promise<Paged<Run>> {
    const response = await adminGet(`${RUNS_BASE}${query({
      type: filter.type,
      definition: filter.definition,
      status: filter.status?.join(','),
      limit: filter.limit,
      offset: filter.offset
    })}`)
    return await runJson<Paged<Run>>('Loading runs', response)
  },

  async get (id: number): Promise<Run> {
    const response = await adminGet(`${RUNS_BASE}/${id}`)
    return (await runJson<{ run: Run }>('Loading run', response)).run
  },

  async logs (id: number, filter: { level?: string[], search?: string, limit?: number, offset?: number }): Promise<Paged<RunLogEntry>> {
    const response = await adminGet(`${RUNS_BASE}/${id}/logs${query({
      level: filter.level?.join(','),
      search: filter.search,
      limit: filter.limit,
      offset: filter.offset
    })}`)
    return await runJson<Paged<RunLogEntry>>('Loading run log', response)
  },

  async stop (id: number): Promise<Run> {
    const response = await adminSend(`${RUNS_BASE}/${id}/stop`, 'POST')
    return (await runJson<{ run: Run }>('Stopping run', response)).run
  },

  async rerun (id: number, async = true): Promise<Run> {
    const response = await adminSend(`${RUNS_BASE}/${id}/rerun`, 'POST', { async })
    return (await runJson<{ run: Run }>('Re-running', response)).run
  },

  async delete (id: number): Promise<void> {
    const response = await adminSend(`${RUNS_BASE}/${id}`, 'DELETE')
    await runJson('Deleting run', response)
  }
}

/**
 * API Instances
 */
export const importDefinitionApi = new ImportDefinitionApi()
export const exportDefinitionApi = new ExportDefinitionApi()
