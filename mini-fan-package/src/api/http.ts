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

/**
 * 微信端 uni.request 默认 dataType=json 时，部分 4xx/5xx 的 JSON 体会解析失败，success 里 data 变成空，
 * 用户只能看到「无响应体」。用 text 自行解析可避免丢 BFF 返回的 error 详情。
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

/** 微信返回的 header 键名大小写不统一 */
function headerValue(header: unknown, name: string): string {
  if (!header || typeof header !== 'object') return ''
  const want = name.toLowerCase()
  const o = header as Record<string, unknown>
  for (const key of Object.keys(o)) {
    if (key.toLowerCase() === want) {
      const v = o[key]
      return v == null ? '' : String(v).trim()
    }
  }
  return ''
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
      dataType: 'text',
      success: (res) => {
        const status = res.statusCode ?? 0
        const payload = normalizeResponseBody(res.data)
        if (status >= 200 && status < 300) {
          resolve(payload as T)
          return
        }
        let msg = parseErrorMessage(payload, `请求失败 (${status})`, status)
        // 502 等且正文为空：多为网关/错端口；用 BFF 自定义头区分是否命中本仓库 Node
        if (payload == null && status >= 500) {
          const fromBff = headerValue(res.header, 'x-wte-bff')
          const depsUrl = joinUrl(base, '/health/deps')
          msg += fromBff
            ? ' BFF 已响应但正文为空，请在电脑查看 bff-server 终端里 [bff] /api/auth/wechat 的报错。'
            : ` 未命中本仓库 BFF（无 X-Wte-Bff 头）：用浏览器打开 ${depsUrl} 应得到 JSON；若打不开或不是 JSON，说明小程序配置的 API 地址上未运行本仓库 bff-server（请在 bff-server 目录 npm start，端口与 VITE_API_BASE_URL 一致）。`
        }
        reject(new HttpError(msg, status, payload))
      },
      fail: (err) => {
        reject(new Error(err.errMsg || '网络请求失败'))
      },
    })
  })
}
