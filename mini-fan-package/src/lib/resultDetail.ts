import type { FavoriteSourceTypeApi } from '@/api/favorites'
import type { FavoriteRow, HistoryRow } from '@/types/dto'
import { favoriteContentDigest } from '@/lib/favoriteDigest'

export type ResultSourceType =
  | 'today_eat'
  | 'custom_wizard'
  | 'table_design'
  | 'fortune_cooking'
  | 'sauce_design'
  | 'gallery'

export interface ResultDetailPayload {
  kind: 'favorite' | 'history'
  record_id?: number
  source_type: ResultSourceType
  source_id: string
  title: string
  cuisine?: string | null
  ingredients?: string[]
  content: string
  request_payload?: unknown
  extra_payload?: unknown
  image_url?: string | null
  created_at?: string
}

const STORAGE_PREFIX = 'mp_result_detail_payload_'

export function normalizeSourceType(raw: unknown, requestPayload?: unknown): ResultSourceType {
  const mpSource = (requestPayload as Record<string, unknown> | null)?.source
  if (mpSource === 'mp-custom-wizard') {
    return 'custom_wizard'
  }

  if (typeof raw === 'string') {
    if (raw === 'custom_wizard') return 'custom_wizard'
    if (
      raw === 'today_eat' ||
      raw === 'table_design' ||
      raw === 'fortune_cooking' ||
      raw === 'sauce_design' ||
      raw === 'gallery'
    ) {
      return raw
    }
  }

  switch (mpSource) {
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
    default:
      return 'today_eat'
  }
}

/** 与 Laravel `FavoriteSourceType` 对齐，用于收藏读写 API */
export function favoriteApiSourceType(source: ResultSourceType): FavoriteSourceTypeApi {
  return source
}

export function toDetailPayloadFromFavorite(row: FavoriteRow): ResultDetailPayload {
  const content = row.recipe_content || ''
  const sourceType = normalizeSourceType(row.source_type, null)
  const sourceId = row.source_id || favoriteContentDigest(row.title || '', content)

  return {
    kind: 'favorite',
    record_id: row.id,
    source_type: sourceType,
    source_id: sourceId,
    title: row.title || '未命名',
    cuisine: row.cuisine ?? null,
    ingredients: row.ingredients ?? [],
    content,
    extra_payload: null,
    image_url: row.image_url ?? null,
    created_at: row.created_at,
  }
}

export function toDetailPayloadFromHistory(row: HistoryRow): ResultDetailPayload {
  const content = row.response_content || ''
  const sourceType = normalizeSourceType(row.source_type, row.request_payload)
  const sourceId = row.source_id || favoriteContentDigest(row.title || '', content)

  return {
    kind: 'history',
    record_id: row.id,
    source_type: sourceType,
    source_id: sourceId,
    title: row.title || '未命名',
    cuisine: row.cuisine ?? null,
    ingredients: row.ingredients ?? [],
    content,
    request_payload: row.request_payload,
    extra_payload: row.extra_payload,
    image_url: row.image_url ?? null,
    created_at: row.created_at,
  }
}

export function openResultDetail(payload: ResultDetailPayload) {
  const key = `${STORAGE_PREFIX}${Date.now()}_${Math.random().toString(36).slice(2, 10)}`
  uni.setStorageSync(key, payload)
  uni.navigateTo({
    url: `/pages/result-detail/index?key=${encodeURIComponent(key)}`,
  })
}

export function getResultDetailByKey(key: string): ResultDetailPayload | null {
  try {
    const data = uni.getStorageSync(key) as ResultDetailPayload | null
    return data ?? null
  } catch {
    return null
  }
}

export function sourceLabel(sourceType: ResultSourceType): string {
  switch (sourceType) {
    case 'today_eat':
      return '今日菜单'
    case 'custom_wizard':
      return '自由搭配'
    case 'table_design':
      return '家常好菜'
    case 'fortune_cooking':
      return '灵感厨房'
    case 'sauce_design':
      return '灵魂蘸料'
    case 'gallery':
      return '图鉴'
  }
}

export function sourcePagePath(sourceType: ResultSourceType): string {
  switch (sourceType) {
    case 'today_eat':
      return '/pages/today-eat/index'
    case 'custom_wizard':
      return '/pages/index/index'
    case 'table_design':
      return '/pages/table-menu/index'
    case 'fortune_cooking':
      return '/pages/fortune-cooking/index'
    case 'sauce_design':
      return '/pages/sauce-design/index'
    case 'gallery':
      return '/pages/gallery/index'
  }
}

