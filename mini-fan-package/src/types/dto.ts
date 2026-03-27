/**
 * 与现有 Supabase 表字段对齐的占位类型（后续接 BFF 再细化）
 * Web: favorites / recipe_histories
 */

export interface FavoriteRow {
  id?: number
  user_id?: string
  /** Laravel favorites.source_type */
  source_type?: string
  source_id?: string | null
  title: string
  cuisine?: string | null
  ingredients?: string[]
  recipe_content: string
  image_url?: string | null
  created_at?: string
}

export interface HistoryRow {
  id?: number
  user_id?: string
  source_type?: string
  source_id?: string | null
  title?: string | null
  cuisine?: string | null
  ingredients?: string[]
  request_payload?: unknown
  extra_payload?: unknown
  response_content: string
  image_url?: string | null
  created_at?: string
}

export interface UserProfileRow {
  id?: string
  user_id?: string
  email?: string | null
  role?: string
  created_at?: string
}
