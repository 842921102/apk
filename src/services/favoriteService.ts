import { supabase } from '../lib/supabase'

export async function addFavorite(recipe: {
  title: string
  cuisine?: string
  ingredients?: string[]
  recipe_content: string
  image_url?: string
}) {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { data, error } = await supabase.from('favorites').insert({
    user_id: user.id,
    title: recipe.title,
    cuisine: recipe.cuisine ?? null,
    ingredients: recipe.ingredients ?? [],
    recipe_content: recipe.recipe_content,
    image_url: recipe.image_url ?? null
  })

  if (error) throw error
  return data
}

export async function getFavorites() {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { data, error } = await supabase
    .from('favorites')
    .select('*')
    .eq('user_id', user.id)
    .order('created_at', { ascending: false })

  if (error) throw error
  return data
}

export async function deleteFavorite(id: number) {
  const { error } = await supabase
    .from('favorites')
    .delete()
    .eq('id', id)

  if (error) throw error
}

export async function clearFavorites() {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { error } = await supabase
    .from('favorites')
    .delete()
    .eq('user_id', user.id)

  if (error) throw error
}

export async function isFavoriteByTitle(title: string) {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { data, error } = await supabase
    .from('favorites')
    .select('id,title')
    .eq('user_id', user.id)
    .eq('title', title)
    .maybeSingle()

  if (error) throw error
  return data
}

export async function deleteFavoriteByTitle(title: string) {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { error } = await supabase
    .from('favorites')
    .delete()
    .eq('user_id', user.id)
    .eq('title', title)

  if (error) throw error
}