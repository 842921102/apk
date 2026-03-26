/**
 * 替换 `@supabase/node-fetch` 的 browser 入口（package.json 的 `browser` 字段）。
 * 官方 browser.js 通过 getGlobal() 只认 self / window / global；微信真机小程序 JS 环境通常没有这三者，
 * 会在模块加载时抛出 `unable to locate global object` → 整页白屏。开发者工具里常有 window，故易误判为「只有真机坏」。
 *
 * 此处不依赖上述全局名，在 `globalThis` 或降级空对象上安装 fetch/Headers 后，再按 node-fetch 的导出形状交给 supabase-js。
 */

/* eslint-disable @typescript-eslint/no-explicit-any */

function pickGlobalRoot(): Record<string, any> {
  const candidates: any[] = []
  try {
    if (typeof globalThis !== 'undefined') candidates.push(globalThis)
  } catch {
    /* ignore */
  }
  try {
    if (typeof global !== 'undefined') candidates.push(global)
  } catch {
    /* ignore */
  }
  try {
    if (typeof self !== 'undefined') candidates.push(self)
  } catch {
    /* ignore */
  }
  try {
    if (typeof window !== 'undefined') candidates.push(window)
  } catch {
    /* ignore */
  }
  for (const g of candidates) {
    if (g && typeof g === 'object') return g as Record<string, any>
  }
  return {}
}

function flattenHeaders(h: any): Record<string, string> {
  if (h == null) return {}
  if (typeof h.forEach === 'function') {
    const o: Record<string, string> = {}
    h.forEach((v: string, k: string) => {
      o[k] = v
    })
    return o
  }
  return h as Record<string, string>
}

function installWebFetchOn(root: Record<string, any>): void {
  if (typeof root.fetch === 'function') return

  function _req(
    url: string,
    method: string,
    header: Record<string, string>,
    data: unknown,
  ): Promise<any> {
    return new Promise((resolve, reject) => {
      /** 勿直接写 `uni` / `wx` 标识符：vite-plugin-uni 会把 `uni` 编译成对 vendor 的 require，与本 shim 形成环依赖，真机易白屏 */
      const gt = root as Record<string, any>
      const uniApi = gt.uni
      const wxApi = gt.wx
      const run =
        uniApi && typeof uniApi.request === 'function'
          ? (o: Record<string, unknown>) => {
              uniApi.request(o)
            }
          : wxApi && typeof wxApi.request === 'function'
            ? (o: Record<string, unknown>) => {
                wxApi.request(o)
              }
            : null
      if (!run) {
        reject(new Error('[wte-mp] 无 uni.request / wx.request'))
        return
      }
      run({
        url,
        method: method || 'GET',
        header: header || {},
        data,
        dataType: 'json',
        success(ret: { statusCode?: number; data?: unknown }) {
          const sc = ret.statusCode ?? 0
          const d = ret.data
          resolve({
            ok: sc >= 200 && sc < 300,
            status: sc,
            text() {
              return Promise.resolve(typeof d === 'string' ? d : JSON.stringify(d))
            },
            json() {
              return Promise.resolve(d)
            },
          })
        },
        fail(e: { errMsg?: string }) {
          reject(e)
        },
      })
    })
  }

  root.fetch = function (input: any, init?: any) {
    init = init || {}
    const u =
      typeof input === 'string' ? input : (input && (input as any).url) || ''
    const hdr = flattenHeaders(init.headers)
    return _req(
      u,
      ((init.method as string) || 'GET').toUpperCase(),
      hdr,
      init.body,
    )
  }

  if (typeof root.Headers !== 'function') {
    root.Headers = function Headers(this: any, init?: HeadersInit) {
      this._h = {}
      if (init && typeof init === 'object') {
        if (Array.isArray(init)) {
          for (let i = 0; i < init.length; i++) {
            const p = init[i] as [string, string]
            if (p && p.length >= 2)
              this._h[String(p[0]).toLowerCase()] = String(p[1])
          }
        } else {
          const o = init as Record<string, string>
          for (const k in o) {
            if (Object.prototype.hasOwnProperty.call(o, k))
              this._h[String(k).toLowerCase()] = String(o[k])
          }
        }
      }
    }
    const Hp = root.Headers.prototype
    Hp.get = function (k: string) {
      return this._h[String(k).toLowerCase()] || null
    }
    Hp.set = function (k: string, v: string) {
      this._h[String(k).toLowerCase()] = String(v)
    }
    Hp.has = function (k: string) {
      return Object.prototype.hasOwnProperty.call(
        this._h,
        String(k).toLowerCase(),
      )
    }
    Hp.append = function (k: string, v: string) {
      const key = String(k).toLowerCase()
      if (this._h[key] !== undefined) this._h[key] += ', ' + String(v)
      else this._h[key] = String(v)
    }
    Hp.delete = function (k: string) {
      delete this._h[String(k).toLowerCase()]
    }
    Hp.forEach = function (
      this: any,
      cb: (value: string, name: string, self: unknown) => void,
      thisArg?: unknown,
    ) {
      for (const k in this._h) {
        if (Object.prototype.hasOwnProperty.call(this._h, k)) {
          cb.call(thisArg, this._h[k], k, this)
        }
      }
    }
  }

  if (typeof root.Request !== 'function') {
    root.Request = function Request(u: string, i: any) {
      this.url = typeof u === 'string' ? u : ''
      this.i = i || {}
    }
  }
  if (typeof root.Response !== 'function') {
    root.Response = function Response(
      this: any,
      body: unknown,
      init?: { status?: number },
    ) {
      this._b = body
      this.status = (init && init.status) || 200
      this.ok = this.status >= 200 && this.status < 300
    }
    const Rp = root.Response.prototype
    Rp.text = function (this: any) {
      return Promise.resolve(this._b == null ? '' : String(this._b))
    }
    Rp.json = function (this: any) {
      try {
        const x = this._b
        return Promise.resolve(typeof x === 'string' ? JSON.parse(x) : x)
      } catch (e) {
        return Promise.reject(e)
      }
    }
  }
}

const root = pickGlobalRoot()
installWebFetchOn(root)

const boundFetch = (root.fetch as (...args: any[]) => any).bind(root)
export default boundFetch
export { boundFetch as fetch }
export const Headers = root.Headers
export const Request = root.Request
export const Response = root.Response
