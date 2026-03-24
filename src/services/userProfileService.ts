import { supabase } from '@/lib/supabase'

export async function getCurrentUserRole(): Promise<string | null> {
  const { data: userData, error: userError } = await supabase.auth.getUser()

  if (userError || !userData.user) {
    return null
  }

  const { data, error } = await supabase
    .from('user_profiles')
    .select('role')
    .eq('user_id', userData.user.id)
    .maybeSingle()

  if (error) {
    console.error('获取用户角色失败:', error)
    return null
  }

  return data?.role ?? null
}
