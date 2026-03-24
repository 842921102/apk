import { supabase } from '../lib/supabase'

export async function getAllHistories() {
  const { data, error } = await supabase
    .from('recipe_histories')
    .select('*')
    .order('created_at', { ascending: false })

  if (error) throw error
  return data
}

export async function deleteAnyHistory(id: number) {
  const { error } = await supabase
    .from('recipe_histories')
    .delete()
    .eq('id', id)

  if (error) throw error
}
