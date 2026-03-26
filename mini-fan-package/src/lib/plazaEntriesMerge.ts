import { parseBooleanLike } from '@/config/defaultConfig'
import type { PlazaEntryConfig } from '@/types/plazaConfig'

/**
 * 将远端单条原始对象规范为 PlazaEntryConfig（字段别名：id→key，path→route，sortOrder→sort_order）
 */
export function normalizeRawPlazaEntry(
  raw: Record<string, unknown>,
): PlazaEntryConfig | null {
  const key =
    (typeof raw.key === 'string' && raw.key.trim()) ||
    (typeof raw.id === 'string' && raw.id.trim()) ||
    ''
  if (!key) return null

  const title =
    typeof raw.title === 'string' && raw.title.trim() !== ''
      ? raw.title.trim()
      : key

  const subtitle =
    typeof raw.subtitle === 'string' ? raw.subtitle : undefined

  const iconRaw =
    raw.icon ?? raw.icon_url ?? raw.iconUrl ?? raw.image
  const icon =
    typeof iconRaw === 'string' && iconRaw.trim() !== ''
      ? iconRaw.trim()
      : undefined

  const routeRaw =
    (typeof raw.route === 'string' && raw.route) ||
    (typeof raw.path === 'string' && raw.path) ||
    ''
  const route =
    typeof routeRaw === 'string'
      ? routeRaw.trim().startsWith('/')
        ? routeRaw.trim()
        : `/${routeRaw.trim()}`
      : ''

  const enabledRaw = parseBooleanLike(raw.enabled)
  const enabled = enabledRaw !== undefined ? enabledRaw : true

  const so =
    raw.sort_order ?? raw.sortOrder ?? raw.order ?? raw.sort
  let sort_order = 999
  if (typeof so === 'number' && Number.isFinite(so)) sort_order = so
  else if (typeof so === 'string' && /^\d+$/.test(so.trim()))
    sort_order = parseInt(so.trim(), 10)

  const cs = parseBooleanLike(raw.coming_soon ?? raw.comingSoon)
  const coming_soon = cs !== undefined ? cs : false

  return {
    key,
    title,
    subtitle,
    icon,
    route,
    enabled,
    sort_order,
    coming_soon,
  }
}

export function normalizePlazaEntriesList(raw: unknown[]): PlazaEntryConfig[] {
  const out: PlazaEntryConfig[] = []
  for (const item of raw) {
    if (!item || typeof item !== 'object' || Array.isArray(item)) continue
    const e = normalizeRawPlazaEntry(item as Record<string, unknown>)
    if (e) out.push(e)
  }
  return out
}

export function clonePlazaEntries(list: PlazaEntryConfig[]): PlazaEntryConfig[] {
  return list.map((e) => ({ ...e }))
}

/**
 * 远端列表按 key 覆盖默认项；未出现的 key 保留默认；远端新增 key 且含 title 则追加。
 * 结果按 sort_order 升序。
 */
export function mergePlazaWithDefaults(
  defaults: PlazaEntryConfig[],
  remoteList: PlazaEntryConfig[],
): PlazaEntryConfig[] {
  const map = new Map<string, PlazaEntryConfig>(
    defaults.map((d) => [d.key, { ...d }]),
  )

  for (const r of remoteList) {
    if (!r.key) continue
    const cur = map.get(r.key)
    if (cur) {
      map.set(r.key, {
        ...cur,
        title: r.title !== undefined && r.title !== '' ? r.title : cur.title,
        subtitle: r.subtitle !== undefined ? r.subtitle : cur.subtitle,
        icon: r.icon !== undefined ? r.icon : cur.icon,
        route:
          r.route !== undefined && r.route !== ''
            ? r.route
            : cur.route,
        enabled: r.enabled !== undefined ? r.enabled : cur.enabled,
        sort_order: Number.isFinite(r.sort_order) ? r.sort_order : cur.sort_order,
        coming_soon:
          r.coming_soon !== undefined ? r.coming_soon : cur.coming_soon,
        key: cur.key,
      })
    } else if (r.title) {
      map.set(r.key, {
        key: r.key,
        title: r.title,
        subtitle: r.subtitle,
        icon: r.icon,
        route: r.route || '',
        enabled: r.enabled !== undefined ? r.enabled : true,
        sort_order: Number.isFinite(r.sort_order) ? r.sort_order : 999,
        coming_soon: r.coming_soon ?? false,
      })
    }
  }

  return Array.from(map.values()).sort((a, b) => a.sort_order - b.sort_order)
}
