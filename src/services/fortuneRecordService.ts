import { supabase } from '@/lib/supabase'
import type { FortuneType } from '@/types'

const STORAGE_KEY = 'wte_fortune_records_v1'

export type FortuneRecord = {
  id: string
  title: string
  fortune_type: FortuneType | string
  result_content: string
  user_id: string
  user_email: string
  raw_result?: Record<string, unknown> | null
  created_at: string
}

const safeParse = (raw: string | null): FortuneRecord[] => {
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw) as unknown
    if (!Array.isArray(parsed)) return []
    return parsed
      .filter(item => item && typeof item === 'object')
      .map(item => item as FortuneRecord)
      .filter(item => typeof item.id === 'string' && typeof item.created_at === 'string')
  } catch {
    return []
  }
}

const loadLocal = (): FortuneRecord[] => safeParse(localStorage.getItem(STORAGE_KEY))
const saveLocal = (records: FortuneRecord[]) => localStorage.setItem(STORAGE_KEY, JSON.stringify(records))

export async function addFortuneRecord(input: {
  title: string
  fortune_type: FortuneType | string
  result_content: string
  raw_result?: Record<string, unknown>
}): Promise<void> {
  const { data: authData } = await supabase.auth.getUser()
  const user = authData.user
  if (!user) return
  const next: FortuneRecord = {
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    title: input.title.trim() || '未命名玄学结果',
    fortune_type: input.fortune_type,
    result_content: input.result_content || '',
    user_id: user.id,
    user_email: user.email || '',
    raw_result: input.raw_result ?? null,
    created_at: new Date().toISOString()
  }
  const list = loadLocal()
  list.unshift(next)
  saveLocal(list)
}

export function getAllFortuneRecords(): FortuneRecord[] {
  return loadLocal().sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
}

export function deleteFortuneRecord(id: string): void {
  saveLocal(loadLocal().filter(item => item.id !== id))
}
