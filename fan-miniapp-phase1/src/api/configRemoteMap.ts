/**
 * 远端 JSON → `Partial<AppConfig>`（仅解析与映射，不发起网络、不做缓存）
 *
 * 合并与默认值兜底见 `api/config.ts` 中 `applyRemotePartialToAppConfig` / `getAppConfig`。
 *
 * 支持形态：
 * 1. 扁平 snake_case / camelCase（含 showHomeBanner → show_home_banner）
 * 2. 包装层（最多剥 3 层）：{ data } | { config } | { miniapp } | { result }
 * 3. 分组对象：
 *    - home: { title, subtitle }
 *    - home_banner | homeBanner: { title, subtitle, show }
 *    - home_recommend | homeRecommend: { title, subtitle, show }
 *    - home_hot | homeHot: { title, subtitle, show }
 *    - plaza / profile / profile_guest | profileGuest | guest（同前）
 *    - favorites / favorite：title, subtitle, empty_title, empty_subtitle, empty_button_text, show_recent
 *    - histories / history：同上，show_recent → show_recent_histories
 *    - login / login_prompt | loginPrompt：prompt_title | title, prompt_subtitle | subtitle, button_text
 *    - toasts | toast：favorite_success, favorite_cancel, history_deleted, favorite_deleted, save_success, save_failed, load_failed
 *    - common_empty | commonEmpty：title, subtitle, button_text
 */
import {
  pickAppConfigPartial,
  parseBooleanLike,
  type AppConfig,
} from '@/config/defaultConfig'
import { normalizePlazaEntriesList } from '@/lib/plazaEntriesMerge'

const CAMEL_STRING_TO_APP: [string, keyof AppConfig][] = [
  ['homeTitle', 'home_title'],
  ['homeSubtitle', 'home_subtitle'],
  ['homeBannerTitle', 'home_banner_title'],
  ['homeBannerSubtitle', 'home_banner_subtitle'],
  ['homeRecommendTitle', 'home_recommend_title'],
  ['homeRecommendSubtitle', 'home_recommend_subtitle'],
  ['homeHotTitle', 'home_hot_title'],
  ['homeHotSubtitle', 'home_hot_subtitle'],
  ['plazaTitle', 'plaza_title'],
  ['plazaSubtitle', 'plaza_subtitle'],
  ['profileTitle', 'profile_title'],
  ['profileSubtitle', 'profile_subtitle'],
  ['profileGuestTitle', 'profile_guest_title'],
  ['profileGuestSubtitle', 'profile_guest_subtitle'],
  ['favoritesTitle', 'favorites_title'],
  ['favoritesSubtitle', 'favorites_subtitle'],
  ['favoritesEmptyTitle', 'favorites_empty_title'],
  ['favoritesEmptySubtitle', 'favorites_empty_subtitle'],
  ['favoritesEmptyButtonText', 'favorites_empty_button_text'],
  ['historiesTitle', 'histories_title'],
  ['historiesSubtitle', 'histories_subtitle'],
  ['historiesEmptyTitle', 'histories_empty_title'],
  ['historiesEmptySubtitle', 'histories_empty_subtitle'],
  ['historiesEmptyButtonText', 'histories_empty_button_text'],
  ['loginPromptTitle', 'login_prompt_title'],
  ['loginPromptSubtitle', 'login_prompt_subtitle'],
  ['loginButtonText', 'login_button_text'],
  ['toastFavoriteSuccess', 'toast_favorite_success'],
  ['toastFavoriteCancel', 'toast_favorite_cancel'],
  ['toastHistoryDeleted', 'toast_history_deleted'],
  ['toastFavoriteDeleted', 'toast_favorite_deleted'],
  ['toastSaveSuccess', 'toast_save_success'],
  ['toastSaveFailed', 'toast_save_failed'],
  ['toastLoadFailed', 'toast_load_failed'],
  ['commonEmptyTitle', 'common_empty_title'],
  ['commonEmptySubtitle', 'common_empty_subtitle'],
  ['commonEmptyButtonText', 'common_empty_button_text'],
]

const CAMEL_BOOL_TO_APP: [string, keyof AppConfig][] = [
  ['showHomeBanner', 'show_home_banner'],
  ['showHomeRecommend', 'show_home_recommend'],
  ['showHomeHot', 'show_home_hot'],
  ['showRecentFavorites', 'show_recent_favorites'],
  ['showRecentHistories', 'show_recent_histories'],
]

function unwrapRemoteRoot(data: unknown): Record<string, unknown> | null {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return null
  let cur: unknown = data
  for (let depth = 0; depth < 3; depth++) {
    if (!cur || typeof cur !== 'object' || Array.isArray(cur)) break
    const o = cur as Record<string, unknown>
    const next = o.data ?? o.config ?? o.miniapp ?? o.result
    if (next && typeof next === 'object' && !Array.isArray(next)) {
      cur = next
      continue
    }
    break
  }
  return cur as Record<string, unknown>
}

function mergeHomeBlock(
  add: Record<string, unknown>,
  node: unknown,
  titleKey: keyof AppConfig,
  subtitleKey: keyof AppConfig,
  showKey: keyof AppConfig,
) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return
  const o = node as Record<string, unknown>
  if (typeof o.title === 'string') add[titleKey] = o.title
  if (typeof o.subtitle === 'string') add[subtitleKey] = o.subtitle
  const sh = parseBooleanLike(o.show ?? o.visible ?? o.enabled)
  if (sh !== undefined) add[showKey] = sh
}

function extractGroupedFields(u: Record<string, unknown>): Record<string, unknown> {
  const add: Record<string, unknown> = {}

  const home = u.home
  if (home && typeof home === 'object' && !Array.isArray(home)) {
    const h = home as Record<string, unknown>
    if (typeof h.title === 'string') add.home_title = h.title
    if (typeof h.subtitle === 'string') add.home_subtitle = h.subtitle
  }

  mergeHomeBlock(
    add,
    u.home_banner ?? u.homeBanner,
    'home_banner_title',
    'home_banner_subtitle',
    'show_home_banner',
  )
  mergeHomeBlock(
    add,
    u.home_recommend ?? u.homeRecommend,
    'home_recommend_title',
    'home_recommend_subtitle',
    'show_home_recommend',
  )
  mergeHomeBlock(
    add,
    u.home_hot ?? u.homeHot,
    'home_hot_title',
    'home_hot_subtitle',
    'show_home_hot',
  )

  const plaza = u.plaza
  if (plaza && typeof plaza === 'object' && !Array.isArray(plaza)) {
    const p = plaza as Record<string, unknown>
    if (typeof p.title === 'string') add.plaza_title = p.title
    if (typeof p.subtitle === 'string') add.plaza_subtitle = p.subtitle
  }

  const profile = u.profile
  if (profile && typeof profile === 'object' && !Array.isArray(profile)) {
    const p = profile as Record<string, unknown>
    if (typeof p.title === 'string') add.profile_title = p.title
    if (typeof p.subtitle === 'string') add.profile_subtitle = p.subtitle
  }

  const guest =
    u.profile_guest ?? u.profileGuest ?? u.guest
  if (guest && typeof guest === 'object' && !Array.isArray(guest)) {
    const g = guest as Record<string, unknown>
    if (typeof g.title === 'string') add.profile_guest_title = g.title
    if (typeof g.subtitle === 'string') add.profile_guest_subtitle = g.subtitle
  }

  mergeFavoritesLike(
    add,
    u.favorites ?? u.favorite,
    'favorites',
  )
  mergeFavoritesLike(
    add,
    u.histories ?? u.history,
    'histories',
  )

  mergeLoginPromptBlock(add, u.login_prompt ?? u.loginPrompt)
  mergeLoginPromptBlock(add, u.login)

  mergeToastsBlock(add, u.toasts ?? u.toast)
  mergeCommonEmptyBlock(add, u.common_empty ?? u.commonEmpty)

  return add
}

function mergeLoginPromptBlock(add: Record<string, unknown>, node: unknown) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return
  const o = node as Record<string, unknown>
  const title =
    o.title ?? o.prompt_title ?? o.promptTitle ?? o.login_prompt_title
  if (typeof title === 'string') add.login_prompt_title = title
  const sub =
    o.subtitle ??
    o.prompt_subtitle ??
    o.promptSubtitle ??
    o.login_prompt_subtitle
  if (typeof sub === 'string') add.login_prompt_subtitle = sub
  const btn = o.button_text ?? o.buttonText ?? o.login_button_text
  if (typeof btn === 'string') add.login_button_text = btn
}

function mergeToastsBlock(add: Record<string, unknown>, node: unknown) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return
  const o = node as Record<string, unknown>
  const map: [string, keyof AppConfig][] = [
    ['favorite_success', 'toast_favorite_success'],
    ['favorite_cancel', 'toast_favorite_cancel'],
    ['history_deleted', 'toast_history_deleted'],
    ['favorite_deleted', 'toast_favorite_deleted'],
    ['save_success', 'toast_save_success'],
    ['save_failed', 'toast_save_failed'],
    ['load_failed', 'toast_load_failed'],
  ]
  for (const [snake, appKey] of map) {
    const camel =
      snake.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase())
    const v = o[snake] ?? o[camel]
    if (typeof v === 'string') add[appKey] = v
  }
}

function mergeCommonEmptyBlock(add: Record<string, unknown>, node: unknown) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return
  const o = node as Record<string, unknown>
  if (typeof o.title === 'string') add.common_empty_title = o.title
  if (typeof o.subtitle === 'string') add.common_empty_subtitle = o.subtitle
  const b = o.button_text ?? o.buttonText
  if (typeof b === 'string') add.common_empty_button_text = b
}

function mergeFavoritesLike(
  add: Record<string, unknown>,
  node: unknown,
  kind: 'favorites' | 'histories',
) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return
  const o = node as Record<string, unknown>
  const p = (a: string, b: string) => (kind === 'favorites' ? a : b)

  if (typeof o.title === 'string')
    add[p('favorites_title', 'histories_title')] = o.title
  if (typeof o.subtitle === 'string')
    add[p('favorites_subtitle', 'histories_subtitle')] = o.subtitle

  const et =
    o.empty_title ?? o.emptyTitle ?? o.empty_heading ?? o.emptyHeading
  if (typeof et === 'string')
    add[p('favorites_empty_title', 'histories_empty_title')] = et

  const es = o.empty_subtitle ?? o.emptySubtitle
  if (typeof es === 'string')
    add[p('favorites_empty_subtitle', 'histories_empty_subtitle')] = es

  const eb =
    o.empty_button_text ??
    o.emptyButtonText ??
    o.empty_button ??
    o.emptyButton
  if (typeof eb === 'string')
    add[p('favorites_empty_button_text', 'histories_empty_button_text')] = eb

  const sr = parseBooleanLike(o.show_recent ?? o.showRecent)
  if (sr !== undefined) {
    add[
      kind === 'favorites'
        ? 'show_recent_favorites'
        : 'show_recent_histories'
    ] = sr
  }
}

/** 根级 plaza_entries / plaza.entries / items 等 */
function extractPlazaEntriesArray(
  u: Record<string, unknown>,
): unknown[] | undefined {
  if (Array.isArray(u.plaza_entries)) return u.plaza_entries
  if (Array.isArray(u.plazaEntries)) return u.plazaEntries
  const plaza = u.plaza
  if (plaza && typeof plaza === 'object' && !Array.isArray(plaza)) {
    const p = plaza as Record<string, unknown>
    if (Array.isArray(p.entries)) return p.entries
    if (Array.isArray(p.items)) return p.items
    if (Array.isArray(p.list)) return p.list
  }
  return undefined
}

function applyCamelCaseFromRoot(
  u: Record<string, unknown>,
  target: Record<string, unknown>,
) {
  for (const [camel, key] of CAMEL_STRING_TO_APP) {
    const v = u[camel]
    if (typeof v === 'string' && v.trim() !== '') {
      target[key] = v
    }
  }
  for (const [camel, key] of CAMEL_BOOL_TO_APP) {
    const b = parseBooleanLike(u[camel])
    if (b !== undefined) target[key] = b
  }
}

/**
 * 将 BFF / 静态 URL 返回的 body 解析为 AppConfig 的部分字段
 */
export function parseRemoteConfigPayload(data: unknown): Partial<AppConfig> {
  const u = unwrapRemoteRoot(data)
  if (!u) return {}

  const grouped = extractGroupedFields(u)
  const flat: Record<string, unknown> = { ...u, ...grouped }
  applyCamelCaseFromRoot(u, flat)

  const partial: Partial<AppConfig> = pickAppConfigPartial(flat)

  const rawPlaza = extractPlazaEntriesArray(u)
  if (rawPlaza && rawPlaza.length > 0) {
    const normalized = normalizePlazaEntriesList(rawPlaza)
    if (normalized.length > 0) {
      partial.plaza_entries = normalized
    }
  }

  return partial
}
