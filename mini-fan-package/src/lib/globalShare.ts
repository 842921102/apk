/** 微信小程序全局分享：路径与标题默认值 */

export const GLOBAL_SHARE_TITLE = '饭否 — 今天吃什么'

const FALLBACK_PATH = '/pages/today-eat/index'

function currentPage(): Record<string, unknown> | undefined {
  if (typeof getCurrentPages !== 'function') return undefined
  const stack = getCurrentPages() as unknown[]
  if (!stack?.length) return undefined
  return stack[stack.length - 1] as Record<string, unknown>
}

/** 当前页完整 path（含 query），用于 onShareAppMessage.path */
export function buildMpSharePath(): string {
  const page = currentPage()
  if (!page) return FALLBACK_PATH

  let route =
    (page.route as string | undefined) ||
    (page.$page as { fullPath?: string } | undefined)?.fullPath?.split?.('?')?.[0] ||
    ''

  if (!route) return FALLBACK_PATH
  if (!route.startsWith('/')) route = `/${route}`

  const opts = (page.options || {}) as Record<string, string | undefined>
  const parts: string[] = []
  for (const key of Object.keys(opts)) {
    const v = opts[key]
    if (v != null && v !== '') {
      parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(v)}`)
    }
  }
  return parts.length ? `${route}?${parts.join('&')}` : route
}

/** 当前页 query 字符串（无 ?），用于 onShareTimeline.query */
export function buildMpShareQuery(): string {
  const page = currentPage()
  if (!page) return ''

  const opts = (page.options || {}) as Record<string, string | undefined>
  const parts: string[] = []
  for (const key of Object.keys(opts)) {
    const v = opts[key]
    if (v != null && v !== '') {
      parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(v)}`)
    }
  }
  return parts.join('&')
}

export function defaultShareAppMessage(): { title: string; path: string } {
  return {
    title: GLOBAL_SHARE_TITLE,
    path: buildMpSharePath(),
  }
}

export function defaultShareTimeline(): { title: string; query: string } {
  return {
    title: GLOBAL_SHARE_TITLE,
    query: buildMpShareQuery(),
  }
}
