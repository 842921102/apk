import { supabase } from '@/lib/supabase'
import type { SauceRecipe } from '@/types'

const STORAGE_KEY = 'wte_sauce_records_v1'

export type SauceRecord = {
  id: string
  title: string
  category: string
  content: string
  user_id: string
  user_email: string
  recipe?: SauceRecipe | null
  created_at: string
}

const safeParse = (raw: string | null): SauceRecord[] => {
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw) as unknown
    if (!Array.isArray(parsed)) return []
    return parsed
      .filter(item => item && typeof item === 'object')
      .map(item => item as SauceRecord)
      .filter(item => typeof item.id === 'string' && typeof item.created_at === 'string')
  } catch {
    return []
  }
}

const loadLocal = (): SauceRecord[] => safeParse(localStorage.getItem(STORAGE_KEY))
const saveLocal = (records: SauceRecord[]) => localStorage.setItem(STORAGE_KEY, JSON.stringify(records))

export async function addSauceRecord(recipe: SauceRecipe): Promise<void> {
  const { data: authData } = await supabase.auth.getUser()
  const user = authData.user
  if (!user) return

  const title = (recipe.name || '未命名酱料').trim()
  const content = `${recipe.description || ''}\n\n${Array.isArray(recipe.steps) ? recipe.steps.map((s, i) => `${i + 1}. ${s}`).join('\n') : ''}`.trim()
  const next: SauceRecord = {
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    title,
    category: recipe.category || 'unknown',
    content,
    user_id: user.id,
    user_email: user.email || '',
    recipe,
    created_at: new Date().toISOString()
  }
  const list = loadLocal()
  list.unshift(next)
  saveLocal(list)
}

export function getAllSauceRecords(): SauceRecord[] {
  return loadLocal().sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
}

export function deleteSauceRecord(id: string): void {
  saveLocal(loadLocal().filter(item => item.id !== id))
}
