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

function stringifyDetail(detail: unknown): string {
  if (detail == null) return ''
  if (typeof detail === 'string') return detail.trim()
  if (typeof detail === 'object') {
    try {
      return JSON.stringify(detail).slice(0, 200)
    } catch {
      return ''
    }
  }
  return String(detail).trim()
}

function parseErrorMessage(data: unknown, fallback: string, statusCode: number): string {
  if (data == null || data === '') {
    if (statusCode >= 500) {
      return `${fallback}（无响应体，检查 BFF/网关/Laravel 是否可达）`
    }
    return fallback
  }

  // 网关常返回 HTML 纯文本，uni 的 data 为 string，导致原先只能看到「请求失败 (502)」
  if (typeof data === 'string') {
    const s = data.trim()
    if ((s.startsWith('{') || s.startsWith('[')) && s.length > 1) {
      try {
        return parseErrorMessage(JSON.parse(s) as unknown, fallback, statusCode)
      } catch {
        /* 非 JSON 字符串，继续走下方摘要 */
      }
    }
    const snippet = s.replace(/\s+/g, ' ').slice(0, 180)
    if (snippet) {
      return `${fallback} · ${snippet}`
    }
  }

  if (data && typeof data === 'object' && !Array.isArray(data)) {
    const o = data as Record<string, unknown>
    if (Object.keys(o).length === 0 && statusCode >= 500) {
      return `${fallback}（响应体为空，多为网关或上游未启动）`
    }
  }

  if (data && typeof data === 'object') {
    const o = data as Record<string, unknown>
    const err = o.error
    if (err && typeof err === 'object') {
      const er = err as Record<string, unknown>
      const m = er.message
      const detailStr = stringifyDetail(er.detail)
      const hintStr = stringifyDetail(er.hint)
      if (typeof m === 'string' && m) {
        if (m === 'not_found' && typeof er.path === 'string') {
          return `无此接口：${String(er.method ?? '')} ${er.path}`.trim()
        }
        if (detailStr) {
          const core = `${m}: ${detailStr}`.slice(0, 200)
          return hintStr ? `${core} · ${hintStr.slice(0, 100)}` : core.slice(0, 240)
        }
        if (hintStr) {
          return `${m} · ${hintStr}`.slice(0, 240)
        }
        return m
      }
      if (detailStr) {
        return detailStr.slice(0, 240)
      }
    }
    if (typeof o.message === 'string' && o.message) return o.message
    // Laravel 校验错误：{ message, errors: { field: [msg] } }
    const errors = o.errors
    if (errors && typeof errors === 'object') {
      const firstKey = Object.keys(errors as Record<string, unknown>)[0]
      if (firstKey) {
        const arr = (errors as Record<string, unknown>)[firstKey]
        if (Array.isArray(arr) && typeof arr[0] === 'string' && arr[0]) {
          return arr[0]
        }
      }
    }
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
        const msg = parseErrorMessage(res.data, `请求失败 (${status})`, status)
        reject(new HttpError(msg, status, res.data))
      },
      fail: (err) => {
        reject(new Error(err.errMsg || '网络请求失败'))
      },
    })
  })
}
