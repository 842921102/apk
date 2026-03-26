/**
 * 收藏 / 历史：直连 Supabase（与 Web 相同表 `favorites`、`recipe_histories`）。
 * 调用列表/删除前请 `await syncAuthFromSupabase()`，保证 `useAuth` 与 `supabase.auth` 会话一致。
 * 请求携带的 JWT 由 supabase-js 处理；`useAuth.getToken()` 为同源 `access_token` 镜像，便于后续接 BFF 时在请求头复用。
 */
import { supabase, isSupabaseConfigured } from '@/lib/supabase'
import type { FavoriteRow, HistoryRow } from '@/types/dto'

export const BIZ_UNAUTHORIZED = 'UNAUTHORIZED'
export const BIZ_NOT_CONFIGURED = 'NOT_CONFIGURED'

function assertConfigured() {
  if (!isSupabaseConfigured()) {
    const e = new Error(BIZ_NOT_CONFIGURED)
    ;(e as Error & { code?: string }).code = BIZ_NOT_CONFIGURED
    throw e
  }
}

async function requireSessionUser() {
  assertConfigured()
  const { data, error } = await supabase.auth.getSession()
  if (error) throw error
  const session = data.session
  if (!session?.user) {
    const e = new Error(BIZ_UNAUTHORIZED)
    ;(e as Error & { code?: string }).code = BIZ_UNAUTHORIZED
    throw e
  }
  return session.user
}

export async function fetchFavorites(): Promise<FavoriteRow[]> {
  const user = await requireSessionUser()
  const { data, error } = await supabase
    .from('favorites')
    .select('id,user_id,title,cuisine,ingredients,recipe_content,image_url,created_at')
    .eq('user_id', user.id)
    .order('created_at', { ascending: false })

  if (error) throw error
  return (data ?? []) as FavoriteRow[]
}

export async function fetchHistories(): Promise<HistoryRow[]> {
  const user = await requireSessionUser()
  const { data, error } = await supabase
    .from('recipe_histories')
    .select('id,user_id,title,cuisine,ingredients,request_payload,response_content,image_url,created_at')
    .eq('user_id', user.id)
    .order('created_at', { ascending: false })

  if (error) throw error
  return (data ?? []) as HistoryRow[]
}

export async function deleteFavoriteById(id: number): Promise<void> {
  await requireSessionUser()
  const { error } = await supabase.from('favorites').delete().eq('id', id)
  if (error) throw error
}

export async function deleteHistoryById(id: number): Promise<void> {
  await requireSessionUser()
  const { error } = await supabase.from('recipe_histories').delete().eq('id', id)
  if (error) throw error
}

export async function getFavoritesCount(): Promise<number> {
  const user = await requireSessionUser()
  const { count, error } = await supabase
    .from('favorites')
    .select('*', { count: 'exact', head: true })
    .eq('user_id', user.id)

  if (error) throw error
  return count ?? 0
}

export async function getHistoriesCount(): Promise<number> {
  const user = await requireSessionUser()
  const { count, error } = await supabase
    .from('recipe_histories')
    .select('*', { count: 'exact', head: true })
    .eq('user_id', user.id)

  if (error) throw error
  return count ?? 0
}

export async function isFavoriteRecipe(payload: {
  title: string
  recipe_content: string
}): Promise<boolean> {
  const user = await requireSessionUser()

  const { data, error } = await supabase
    .from('favorites')
    .select('id')
    .eq('user_id', user.id)
    .eq('title', payload.title)
    .eq('recipe_content', payload.recipe_content)
    .limit(1)

  if (error) throw error
  return (data?.length ?? 0) > 0
}

/**
 * 收藏 / 取消收藏（toggle）
 * 唯一键：`user_id + title + recipe_content`
 */
export async function toggleFavoriteRecipe(payload: {
  title: string
  cuisine?: string | null
  ingredients?: string[]
  recipe_content: string
  image_url?: string | null
}): Promise<{ favorited: boolean; id?: number }> {
  const user = await requireSessionUser()

  const { data: existing, error: selErr } = await supabase
    .from('favorites')
    .select('id')
    .eq('user_id', user.id)
    .eq('title', payload.title)
    .eq('recipe_content', payload.recipe_content)
    .limit(1)

  if (selErr) throw selErr

  const existingId = (existing && existing[0] && typeof existing[0].id === 'number' ? existing[0].id : undefined) as
    | number
    | undefined

  if (existingId != null) {
    const { error: delErr } = await supabase.from('favorites').delete().eq('id', existingId)
    if (delErr) throw delErr
    return { favorited: false, id: existingId }
  }

  const { data: inserted, error: insErr } = await supabase
    .from('favorites')
    .insert({
      user_id: user.id,
      title: payload.title ?? null,
      cuisine: payload.cuisine ?? null,
      ingredients: payload.ingredients ?? [],
      recipe_content: payload.recipe_content ?? '',
      image_url: payload.image_url ?? null,
    })
    .select('id')
    .single()

  if (insErr) throw insErr

  const insertedId = inserted && typeof inserted.id === 'number' ? inserted.id : undefined
  return { favorited: true, id: insertedId }
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
  const user = await requireSessionUser()
  const { error } = await supabase.from('recipe_histories').insert({
    user_id: user.id,
    title: payload.title ?? null,
    cuisine: payload.cuisine ?? null,
    ingredients: payload.ingredients ?? [],
    request_payload: payload.request_payload ?? null,
    response_content: payload.response_content,
    image_url: null,
  })
  if (error) throw error
}
