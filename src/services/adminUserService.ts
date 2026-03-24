import { supabase } from '../lib/supabase'

export type ProfileRole = 'user' | 'operator' | 'super_admin'

export async function getAllUsers() {
  const { data, error } = await supabase
    .from('user_profiles')
    .select('id,user_id,email,role,created_at')
    .order('created_at', { ascending: false })

  if (error) throw error
  return data
}

export async function updateUserRole(userId: string, role: ProfileRole) {
  const { error } = await supabase
    .from('user_profiles')
    .update({ role })
    .eq('user_id', userId)

  if (error) throw error
}
