/**
 * Laravel 收藏 API（经 BFF 透传时需配置 ADMIN_BACKEND_BASE_URL）
 */
import { request } from '@/api/http'

export type FavoriteSourceTypeApi =
  | 'today_eat'
  | 'table_design'
  | 'fortune_cooking'
  | 'sauce_design'
  | 'gallery'
  | 'recommendation_record'
  | 'recipe'

export interface FavoriteApiItem {
  id: number
  user_id: string
  source_type: string
  source_id: string | null
  title: string
  cuisine?: string | null
  ingredients?: string[] | null
  recipe_content: string
  extra_payload?: Record<string, unknown> | null
  created_at?: string | null
  updated_at?: string | null
}

export interface FavoritesListMeta {
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export async function apiListFavorites(params?: {
  keyword?: string
  source_type?: FavoriteSourceTypeApi
  page?: number
  per_page?: number
}): Promise<{ data: FavoriteApiItem[]; meta: FavoritesListMeta }> {
  return request<{ data: FavoriteApiItem[]; meta: FavoritesListMeta }>({
    url: '/api/favorites',
    method: 'GET',
    data: params as Record<string, unknown>,
  })
}

export async function apiCreateFavorite(body: {
  source_type: FavoriteSourceTypeApi
  source_id?: string | null
  title: string
  cuisine?: string | null
  ingredients?: string[]
  /** 菜谱收藏可为空串 */
  recipe_content?: string
  extra_payload?: Record<string, unknown> | null
}): Promise<{ data: FavoriteApiItem }> {
  return request<{ data: FavoriteApiItem }>({
    url: '/api/favorites',
    method: 'POST',
    data: body as Record<string, unknown>,
  })
}

export async function apiDeleteFavorite(id: number): Promise<{ data: { deleted: boolean } }> {
  return request<{ data: { deleted: boolean } }>({
    url: `/api/favorites/${id}`,
    method: 'DELETE',
  })
}

export async function apiCheckFavorite(
  source_type: FavoriteSourceTypeApi,
  source_id: string,
): Promise<{ data: { is_favorited: boolean; id?: number } }> {
  return request<{ data: { is_favorited: boolean; id?: number } }>({
    url: '/api/favorites/check',
    method: 'GET',
    data: { source_type, source_id },
  })
}
