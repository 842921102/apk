import { supabase } from '@/lib/supabase'

const STORAGE_KEY = 'wte_table_menu_records_v1'

export type TableMenuDish = {
  name: string
  description?: string
  category?: string
  tags?: string[]
}

export type TableMenuRecord = {
  id: string
  title: string
  user_id: string
  user_email: string
  dishes_count: number
  menu_content: TableMenuDish[]
  config_snapshot?: Record<string, unknown> | null
  created_at: string
}

const safeParse = (raw: string | null): TableMenuRecord[] => {
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw) as unknown
    if (!Array.isArray(parsed)) return []
    return parsed
      .filter(item => item && typeof item === 'object')
      .map(item => item as TableMenuRecord)
      .filter(item => typeof item.id === 'string' && typeof item.created_at === 'string')
  } catch {
    return []
  }
}

const loadLocalRecords = (): TableMenuRecord[] => {
  return safeParse(localStorage.getItem(STORAGE_KEY))
}

const saveLocalRecords = (records: TableMenuRecord[]) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(records))
}

export async function addTableMenuRecord(input: {
  title: string
  menu_content: TableMenuDish[]
  config_snapshot?: Record<string, unknown>
}): Promise<void> {
  const { data: authData } = await supabase.auth.getUser()
  const user = authData.user
  if (!user) return

  const next: TableMenuRecord = {
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    title: input.title.trim() || '未命名菜单',
    user_id: user.id,
    user_email: user.email || '',
    dishes_count: Array.isArray(input.menu_content) ? input.menu_content.length : 0,
    menu_content: Array.isArray(input.menu_content) ? input.menu_content : [],
    config_snapshot: input.config_snapshot ?? null,
    created_at: new Date().toISOString()
  }
  const list = loadLocalRecords()
  list.unshift(next)
  saveLocalRecords(list)
}

export function getAllTableMenuRecords(): TableMenuRecord[] {
  return loadLocalRecords().sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
}

export function deleteTableMenuRecord(id: string): void {
  const next = loadLocalRecords().filter(item => item.id !== id)
  saveLocalRecords(next)
}
