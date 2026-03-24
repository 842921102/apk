import { supabase } from '../lib/supabase'

export async function addHistory(record: {
  title?: string
  cuisine?: string
  ingredients?: string[]
  request_payload?: any
  response_content: string
  image_url?: string
}) {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { data, error } = await supabase.from('recipe_histories').insert({
    user_id: user.id,
    title: record.title ?? null,
    cuisine: record.cuisine ?? null,
    ingredients: record.ingredients ?? [],
    request_payload: record.request_payload ?? null,
    response_content: record.response_content,
    image_url: record.image_url ?? null,
  })

  if (error) throw error
  return data
}

export async function getHistories() {
  const { data: userRes } = await supabase.auth.getUser()
  const user = userRes.user
  if (!user) throw new Error('请先登录')

  const { data, error } = await supabase
    .from('recipe_histories')
    .select('*')
    .eq('user_id', user.id)
    .order('created_at', { ascending: false })

  if (error) throw error
  return data
}

export async function deleteHistory(id: number) {
  const { error } = await supabase
    .from('recipe_histories')
    .delete()
    .eq('id', id)

  if (error) throw error
}