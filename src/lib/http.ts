// Thin fetch wrapper around the Slim/PHP backend (see server/API_DOCUMENTATION.md).
// Every endpoint responds with the same envelope:
//   { success: boolean, message: string, data: T | null, errors?: Record<string, string[]> }
// This file owns: base URL resolution, attaching the bearer token, unwrapping
// that envelope into `data`, and transparently refreshing an expired access
// token once before giving up.

const BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080/api').replace(/\/+$/, '')

const ACCESS_TOKEN_KEY = 'skillswap.access_token'
const REFRESH_TOKEN_KEY = 'skillswap.refresh_token'

export class ApiError extends Error {
  status: number
  errors: Record<string, unknown> | null

  constructor(message: string, status: number, errors: Record<string, unknown> | null = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

interface Envelope<T> {
  success: boolean
  message: string
  data: T | null
  errors?: Record<string, unknown>
}

export const tokenStorage = {
  getAccessToken: (): string | null => localStorage.getItem(ACCESS_TOKEN_KEY),
  getRefreshToken: (): string | null => localStorage.getItem(REFRESH_TOKEN_KEY),
  setTokens(accessToken: string, refreshToken?: string) {
    localStorage.setItem(ACCESS_TOKEN_KEY, accessToken)
    if (refreshToken) localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken)
  },
  setAccessToken(accessToken: string) {
    localStorage.setItem(ACCESS_TOKEN_KEY, accessToken)
  },
  clear() {
    localStorage.removeItem(ACCESS_TOKEN_KEY)
    localStorage.removeItem(REFRESH_TOKEN_KEY)
  },
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'
  body?: unknown
  query?: Record<string, string | number | boolean | undefined | null>
  auth?: boolean // attach Authorization header (default true)
  // internal: prevents infinite refresh loops
  _isRetry?: boolean
}

function buildUrl(path: string, query?: RequestOptions['query']): string {
  const url = new URL(`${BASE_URL}${path.startsWith('/') ? path : `/${path}`}`)
  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== null && value !== '') {
        url.searchParams.set(key, String(value))
      }
    }
  }
  return url.toString()
}

let refreshPromise: Promise<string | null> | null = null

async function refreshAccessToken(): Promise<string | null> {
  const refreshToken = tokenStorage.getRefreshToken()
  if (!refreshToken) return null

  // De-dupe concurrent refreshes (e.g. several requests firing 401 at once).
  if (!refreshPromise) {
    refreshPromise = fetch(buildUrl('/auth/refresh'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: refreshToken }),
    })
      .then(async (res) => {
        const json = (await res.json()) as Envelope<{ access_token: string }>
        if (!res.ok || !json.success || !json.data) return null
        tokenStorage.setAccessToken(json.data.access_token)
        return json.data.access_token
      })
      .catch(() => null)
      .finally(() => {
        refreshPromise = null
      })
  }
  return refreshPromise
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, query, auth = true, _isRetry = false } = options

  const headers: Record<string, string> = { 'Content-Type': 'application/json' }
  if (auth) {
    const token = tokenStorage.getAccessToken()
    if (token) headers.Authorization = `Bearer ${token}`
  }

  let res: Response
  try {
    res = await fetch(buildUrl(path, query), {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError('Could not reach the server. Check your connection and try again.', 0)
  }

  // 204 / empty bodies still come back as JSON envelopes from this API, but
  // guard against a truly empty body just in case.
  const text = await res.text()
  const json: Envelope<T> = text ? JSON.parse(text) : { success: res.ok, message: '', data: null }

  if (res.status === 401 && auth && !_isRetry) {
    const newToken = await refreshAccessToken()
    if (newToken) {
      return request<T>(path, { ...options, _isRetry: true })
    }
    tokenStorage.clear()
    throw new ApiError('Your session has expired. Please log in again.', 401)
  }

  if (!res.ok || !json.success) {
    throw new ApiError(json.message || `Request failed (${res.status})`, res.status, json.errors ?? null)
  }

  return json.data as T
}

export const http = {
  get: <T>(path: string, query?: RequestOptions['query'], auth = true) =>
    request<T>(path, { method: 'GET', query, auth }),
  post: <T>(path: string, body?: unknown, auth = true) => request<T>(path, { method: 'POST', body, auth }),
  patch: <T>(path: string, body?: unknown, auth = true) => request<T>(path, { method: 'PATCH', body, auth }),
  delete: <T>(path: string, auth = true) => request<T>(path, { method: 'DELETE', auth }),
}
