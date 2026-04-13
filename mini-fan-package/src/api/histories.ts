/**
 * Laravel 历史 API（经 BFF 透传）
 * 依赖：Bearer `laravel_access_*`（微信一键登录后由后端签发）
 */
import { request } from '@/api/http'

export type HistorySourceTypeApi =
  | 'today_eat'
  | 'custom_wizard'
  | 'table_design'
  | 'fortune_cooking'
  | 'sauce_design'
  | 'gallery'

export interface HistoryApiItem {
  id: number
  user_id: string
  source_type: string
  source_id: string | null
  title: string
  cuisine?: string | null
  ingredients?: string[] | null
  request_payload?: Record<string, unknown> | null
  response_content: string
  extra_payload?: Record<string, unknown> | null
  created_at?: string | null
  updated_at?: string | null
}

export interface HistoriesListMeta {
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export async function apiListHistories(params?: {
  keyword?: string
  source_type?: HistorySourceTypeApi
  page?: number
  per_page?: number
}): Promise<{ data: HistoryApiItem[]; meta: HistoriesListMeta }> {
  return request<{ data: HistoryApiItem[]; meta: HistoriesListMeta }>({
    url: '/api/histories',
    method: 'GET',
    data: params as Record<string, unknown>,
  })
}

export async function apiCreateHistory(body: {
  source_type: HistorySourceTypeApi
  source_id?: string | null
  title: string
  cuisine?: string | null
  ingredients?: string[]
  request_payload?: Record<string, unknown> | null
  response_content: string
  extra_payload?: Record<string, unknown> | null
}): Promise<{ data: HistoryApiItem }> {
  return request<{ data: HistoryApiItem }>({
    url: '/api/histories',
    method: 'POST',
    data: body as Record<string, unknown>,
  })
}

export async function apiDeleteHistory(id: number): Promise<{ data: { deleted: boolean } }> {
  return request<{ data: { deleted: boolean } }>({
    url: `/api/histories/${id}`,
    method: 'DELETE',
  })
}

