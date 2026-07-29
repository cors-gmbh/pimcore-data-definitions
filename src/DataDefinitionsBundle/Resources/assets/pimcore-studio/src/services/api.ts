/**
 * DataDefinitions API Service
 *
 * Talks to the existing classic-admin resource controllers
 * (/admin/data_definitions/...). Those routes are session-authenticated and
 * CSRF-protected; CoreShop's BodyListener decodes JSON request bodies, so we
 * can post application/json directly. A dedicated Studio Backend API layer
 * can replace this later without touching the components (only buildUrl and
 * the fetch helpers would change).
 */

import type { ImportDefinition, ExportDefinition, DefinitionConfig, ColumnResponse } from '../types/definitions'

interface EntityWithId {
  id?: number
}

const ADMIN_BASE = '/admin/data_definitions'

/**
 * Pimcore's classic-admin firewall requires the x-pimcore-csrf-token header
 * on mutating admin routes. Classic's Ext.Ajax injects it automatically;
 * Studio plugins calling /admin endpoints fetch it themselves. The token
 * endpoint (/admin/login/csrf-token) is session-bound and public.
 */
let cachedCsrfToken: string | null = null

async function adminCsrfToken (): Promise<string> {
  if (cachedCsrfToken !== null) {
    return cachedCsrfToken
  }

  const response = await fetch('/admin/login/csrf-token', { credentials: 'include' })
  const result: { csrfToken?: string } = await response.json()

  cachedCsrfToken = result.csrfToken ?? ''

  return cachedCsrfToken
}

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
  const token = await adminCsrfToken()

  return await fetch(url, {
    method,
    credentials: 'include',
    headers: {
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      'x-pimcore-csrf-token': token
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
    return `${ADMIN_BASE}/import_definitions${route}`
  }

  /**
   * Import a definition from JSON file
   */
  async import (definitionId: number, file: File): Promise<ImportDefinition> {
    const formData = new FormData()
    formData.append('Filedata', file)
    formData.append('id', String(definitionId))

    const token = await adminCsrfToken()
    const response = await fetch(this.buildUrl('/import'), {
      method: 'POST',
      credentials: 'include',
      // no explicit Content-Type — the browser sets the multipart boundary
      headers: { 'x-pimcore-csrf-token': token },
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
    return `${ADMIN_BASE}/export_definitions${route}`
  }
}

/**
 * API Instances
 */
export const importDefinitionApi = new ImportDefinitionApi()
export const exportDefinitionApi = new ExportDefinitionApi()
