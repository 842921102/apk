import { API_BASE_URL } from '@/constants'
import { getToken } from '@/composables/useAuth'

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE'

export interface HttpRequestOptions {
  /** 相对路径（如 /api/ai/today-eat）或完整 https URL */
  url: string
  method?: HttpMethod
  data?: Record<string, unknown>
  header?: Record<string, string>
}

export class HttpError extends Error {
  statusCode: number
  body?: unknown
  constructor(message: string, statusCode: number, body?: unknown) {
    super(message)
    this.name = 'HttpError'
    this.statusCode = statusCode
    this.body = body
  }
}

function joinUrl(base: string, path: string): string {
  const b = base.replace(/\/$/, '')
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const p = path.startsWith('/') ? path : `/${path}`
  return `${b}${p}`
}

function parseErrorMessage(data: unknown, fallback: string): string {
  if (data && typeof data === 'object') {
    const o = data as Record<string, unknown>
    const err = o.error
    if (err && typeof err === 'object') {
      const m = (err as Record<string, unknown>).message
      if (typeof m === 'string' && m) return m
    }
    if (typeof o.message === 'string' && o.message) return o.message
  }
  return fallback
}

/**
 * 统一请求：BFF / AI 代理
 * - 自动附加 Authorization: Bearer（有 Supabase access_token 时，便于 BFF 校验）
 * - 不直连第三方 AI，仅请求配置的 API_BASE_URL
 */
export function request<T = unknown>(options: HttpRequestOptions): Promise<T> {
  const base = API_BASE_URL.trim()
  if (!base) {
    return Promise.reject(new Error('未配置 VITE_API_BASE_URL'))
  }

  const url = joinUrl(base, options.url)
  const token = getToken()
  const header: Record<string, string> = {
    'Content-Type': 'application/json',
    ...options.header,
  }
  if (token) {
    header.Authorization = `Bearer ${token}`
  }

  const method = options.method || 'GET'

  return new Promise((resolve, reject) => {
    uni.request({
      url,
      method: method as UniApp.RequestOptions['method'],
      data: options.data,
      header,
      success: (res) => {
        const status = res.statusCode ?? 0
        if (status >= 200 && status < 300) {
          resolve(res.data as T)
          return
        }
        const msg = parseErrorMessage(res.data, `请求失败 (${status})`)
        reject(new HttpError(msg, status, res.data))
      },
      fail: (err) => {
        reject(new Error(err.errMsg || '网络请求失败'))
      },
    })
  })
}
