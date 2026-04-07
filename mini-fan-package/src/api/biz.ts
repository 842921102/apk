/**
 * 收藏：Laravel `favorites` 表 + Bearer `laravel_access_*`（微信登录）。
 * 历史：Laravel `recipe_histories`（经 BFF 透传）。
 */
import type { FavoriteRow, HistoryRow } from '@/types/dto'
import { getToken, LARAVEL_ACCESS_TOKEN_PREFIX } from '@/composables/useAuth'
import { favoriteContentDigest } from '@/lib/favoriteDigest'
import {
  apiListFavorites,
  apiCreateFavorite,
  apiDeleteFavorite,
  apiCheckFavorite,
  type FavoriteSourceTypeApi,
  type FavoriteApiItem,
} from '@/api/favorites'
import { apiCreateHistory, apiDeleteHistory, apiListHistories } from '@/api/histories'

export const BIZ_UNAUTHORIZED = 'UNAUTHORIZED'
export const BIZ_NOT_CONFIGURED = 'NOT_CONFIGURED'
/** 当前账号不是微信→Laravel 登录态，无法读写后端收藏 */
export const BIZ_NEED_LARAVEL_AUTH = 'NEED_LARAVEL_AUTH'

function assertLaravelFavoriteSession() {
  const t = getToken()
  if (!t || !t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)) {
    const e = new Error(BIZ_NEED_LARAVEL_AUTH)
    ;(e as Error & { code?: string }).code = BIZ_NEED_LARAVEL_AUTH
    throw e
  }
}

function mapApiToFavoriteRow(it: FavoriteApiItem): FavoriteRow {
  const extra =
    it.extra_payload && typeof it.extra_payload === 'object'
      ? (it.extra_payload as Record<string, unknown>)
      : {}
  const imageUrl =
    typeof extra.cover_image === 'string'
      ? extra.cover_image
      : typeof extra.image_url === 'string'
        ? extra.image_url
        : null

  const tagList = Array.isArray(extra.tags)
    ? extra.tags.filter((x): x is string => typeof x === 'string' && x.trim())
    : []

  return {
    id: it.id,
    user_id: it.user_id,
    source_type: it.source_type,
    source_id: it.source_id ?? undefined,
    title: it.title,
    cuisine: it.cuisine ?? null,
    ingredients: it.ingredients ?? undefined,
    recipe_content: it.recipe_content,
    image_url: imageUrl,
    tags: tagList.length ? tagList : undefined,
    created_at: it.created_at ?? undefined,
  }
}

export async function fetchFavorites(): Promise<FavoriteRow[]> {
  assertLaravelFavoriteSession()
  const { data, meta } = await apiListFavorites({ per_page: 100, page: 1 })
  void meta
  return data.map(mapApiToFavoriteRow)
}

export async function fetchRecipeFavorites(): Promise<FavoriteRow[]> {
  assertLaravelFavoriteSession()
  const { data, meta } = await apiListFavorites({ per_page: 100, page: 1, source_type: 'recipe' })
  void meta
  return data.map(mapApiToFavoriteRow)
}

export async function deleteFavoriteById(id: number): Promise<void> {
  assertLaravelFavoriteSession()
  await apiDeleteFavorite(id)
}

export async function getFavoritesCount(): Promise<number> {
  assertLaravelFavoriteSession()
  const { meta } = await apiListFavorites({ per_page: 1, page: 1 })
  return meta.pagination.total
}

export async function getRecipeFavoritesCount(): Promise<number> {
  assertLaravelFavoriteSession()
  const { meta } = await apiListFavorites({ per_page: 1, page: 1, source_type: 'recipe' })
  return meta.pagination.total
}

export async function isFavoriteRecipe(payload: {
  source_type: FavoriteSourceTypeApi
  source_id: string
}): Promise<boolean> {
  assertLaravelFavoriteSession()
  const { data } = await apiCheckFavorite(payload.source_type, payload.source_id)
  return data.is_favorited
}

/**
 * 收藏 / 取消收藏（toggle）；后端以 user_id + source_type + source_id 去重。
 */
export async function toggleFavoriteRecipe(payload: {
  source_type: FavoriteSourceTypeApi
  source_id: string
  title: string
  cuisine?: string | null
  ingredients?: string[]
  recipe_content?: string
  image_url?: string | null
  extra_payload?: Record<string, unknown> | null
}): Promise<{ favorited: boolean; id?: number }> {
  assertLaravelFavoriteSession()

  const extra = { ...payload.extra_payload }
  if (payload.image_url) {
    extra.image_url = payload.image_url
    extra.cover_image = payload.image_url
  }

  const { data: checked } = await apiCheckFavorite(payload.source_type, payload.source_id)
  if (checked.is_favorited && checked.id != null) {
    await apiDeleteFavorite(checked.id)
    return { favorited: false, id: checked.id }
  }

  const created = await apiCreateFavorite({
    source_type: payload.source_type,
    source_id: payload.source_id,
    title: payload.title,
    cuisine: payload.cuisine,
    ingredients: payload.ingredients ?? [],
    recipe_content: payload.recipe_content ?? '',
    extra_payload: Object.keys(extra).length ? extra : null,
  })

  return { favorited: true, id: created.data.id }
}

function mapMpSourceToHistorySourceType(source: unknown) {
  if (typeof source !== 'string') return 'today_eat'
  switch (source) {
    case 'mp-today-eat':
      return 'today_eat'
    case 'mp-table-menu':
      return 'table_design'
    case 'mp-fortune-cooking':
      return 'fortune_cooking'
    case 'mp-sauce-design':
      return 'sauce_design'
    case 'mp-gallery':
      return 'gallery'
    case 'mp-custom-wizard':
      return 'today_eat'
    default:
      return 'today_eat'
  }
}

function mapApiToHistoryRow(it: import('@/api/histories').HistoryApiItem): HistoryRow {
  return {
    id: it.id,
    user_id: it.user_id,
    source_type: it.source_type,
    source_id: it.source_id ?? null,
    title: it.title ?? null,
    cuisine: it.cuisine ?? null,
    ingredients: it.ingredients ?? undefined,
    request_payload: it.request_payload ?? undefined,
    response_content: it.response_content,
    extra_payload: it.extra_payload ?? undefined,
    image_url: null,
    created_at: it.created_at ?? undefined,
  }
}

export async function fetchHistories(): Promise<HistoryRow[]> {
  assertLaravelFavoriteSession()
  const { data } = await apiListHistories({ per_page: 100, page: 1 })
  return data.map(mapApiToHistoryRow)
}

export async function deleteHistoryById(id: number): Promise<void> {
  assertLaravelFavoriteSession()
  await apiDeleteHistory(id)
}

export async function getHistoriesCount(): Promise<number> {
  assertLaravelFavoriteSession()
  const { meta } = await apiListHistories({ per_page: 1, page: 1 })
  return meta.pagination.total
}

/**
 * BFF 未写入历史时的兜底：由小程序在登录态下写入 `recipe_histories`（与 Web 字段对齐）。
 * 优先仍应由 BFF 在生成成功后写库；若接口返回 `history_saved: true` 则不应再调用本函数。
 */
export async function insertRecipeHistoryFromTodayEat(payload: {
  title: string
  cuisine?: string | null
  ingredients?: string[]
  response_content: string
  request_payload?: Record<string, unknown>
}): Promise<void> {
  assertLaravelFavoriteSession()

  const sourceType = mapMpSourceToHistorySourceType(payload.request_payload?.source)
  const sourceId = favoriteContentDigest(payload.title ?? '', payload.response_content ?? '')

  await apiCreateHistory({
    source_type: sourceType,
    source_id: sourceId,
    title: payload.title,
    cuisine: payload.cuisine ?? null,
    ingredients: payload.ingredients ?? [],
    request_payload: payload.request_payload ?? null,
    response_content: payload.response_content,
    extra_payload: null,
  })
}
