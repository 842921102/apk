import { API_BASE_URL, DEFAULT_HTTP_HEADERS, REQUEST_TIMEOUT_MS } from '@/constants'
import { getToken } from '@/composables/useAuth'

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE'

export interface HttpRequestOptions {
  /** 相对路径（如 /api/me/profile）或完整 https URL */
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

/**
 * 微信端 uni.request 默认 dataType=json 时，部分 4xx/5xx 的 JSON 体会解析失败，success 里 data 变成空，
 * 用户只能看到「无响应体」。用 text 自行解析可避免丢服务端 error 详情。
 */
function normalizeResponseBody(data: unknown): unknown {
  if (data == null || data === '') return null
  if (typeof data === 'object') return data
  const s = typeof data === 'string' ? data : String(data)
  const t = s.trim()
  if (!t) return null
  if ((t.startsWith('{') && t.endsWith('}')) || (t.startsWith('[') && t.endsWith(']'))) {
    try {
      return JSON.parse(t) as unknown
    } catch {
      /* 非严格 JSON，交给下方当纯文本摘要 */
    }
  }
  return s
}

function parseErrorMessage(data: unknown, fallback: string, statusCode: number): string {
  if (data == null || data === '') {
    if (statusCode >= 500) {
      return `${fallback}（无响应体，检查网关或 Laravel 是否可达）`
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
 * 统一请求 Laravel API
 * - 自动附加 Authorization: Bearer（微信登录后的 Laravel access_token）
 */
export function request<T = unknown>(options: HttpRequestOptions): Promise<T> {
  const base = API_BASE_URL.trim()
  if (!base) {
    return Promise.reject(new Error('未配置 API 根地址：请检查 mini-fan-package/config/env/index.ts 的 ENV_MODE 与对应环境的 baseUrl'))
  }

  const url = joinUrl(base, options.url)
  const token = getToken()
  const header: Record<string, string> = {
    ...DEFAULT_HTTP_HEADERS,
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...options.header,
  }
  if (token) {
    header.Authorization = `Bearer ${token}`
  }

  const method = options.method || 'GET'

  // 请求前诊断（base / path / 最终 URL）；全量打印便于排查 404；稳定后可删或改为 debugLog 条件
  console.log('[http:request]', {
    baseURL: base,
    url: options.url,
    fullUrl: url,
    method,
    data: options.data,
  })

  return new Promise((resolve, reject) => {
    uni.request({
      url,
      method: method as UniApp.RequestOptions['method'],
      data: options.data,
      header,
      timeout: REQUEST_TIMEOUT_MS,
      dataType: 'text',
      success: (res) => {
        const status = res.statusCode ?? 0
        const payload = normalizeResponseBody(res.data)
        if (status >= 200 && status < 300) {
          resolve(payload as T)
          return
        }
        let msg = parseErrorMessage(payload, `请求失败 (${status})`, status)
        if (payload == null && status >= 500) {
          const upUrl = joinUrl(base, '/up')
          msg += ` 可尝试用浏览器打开 ${upUrl} 检查 Laravel 是否存活。`
        }
        reject(new HttpError(msg, status, payload))
      },
      fail: (err) => {
        reject(new Error(err.errMsg || '网络请求失败'))
      },
    })
  })
}
