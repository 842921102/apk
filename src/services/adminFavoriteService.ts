import { supabase } from '../lib/supabase'

export async function getAllFavorites() {
  const { data, error } = await supabase
    .from('favorites')
    .select('*')
    .order('created_at', { ascending: false })

  if (error) throw error
  return data
}

export async function deleteAnyFavorite(id: number) {
  const { error } = await supabase
    .from('favorites')
    .delete()
    .eq('id', id)

  if (error) throw error
}
