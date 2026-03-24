import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

const hasValidSupabaseEnv =
  typeof supabaseUrl === 'string' &&
  supabaseUrl.length > 0 &&
  typeof supabaseAnonKey === 'string' &&
  supabaseAnonKey.length > 0

if (!hasValidSupabaseEnv) {
  console.error(
    '[Supabase] Missing VITE_SUPABASE_URL or VITE_SUPABASE_ANON_KEY. ' +
      'App will run in limited mode until env vars are provided.'
  )
}

// Avoid startup crash in packaged apps when env vars were not injected.
export const supabase = createClient(
  hasValidSupabaseEnv ? supabaseUrl : 'https://invalid.localhost',
  hasValidSupabaseEnv ? supabaseAnonKey : 'invalid-anon-key'
)