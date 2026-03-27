import { request } from '@/api/http'

export interface EatMemeRecordItem {
  id: number
  result_title?: string | null
  result_cuisine?: string | null
  result_ingredients?: string[] | null
  result_content?: string | null
  created_at?: string
  status: 'success' | 'failed'
}

export interface EatMemeListMeta {
  page: number
  perPage: number
  total: number
  lastPage: number
}

export async function fetchEatMemeRecordsPaged(
  page = 1,
  perPage = 15,
): Promise<{ items: EatMemeRecordItem[]; meta: EatMemeListMeta }> {
  const raw = await request<{
    data?: unknown
    meta?: { pagination?: Record<string, unknown> }
  }>({
    url: `/api/eat-meme?page=${page}&per_page=${perPage}`,
    method: 'GET',
  })
  const rows = Array.isArray(raw?.data) ? raw.data : []
  const p = raw?.meta?.pagination
  const meta: EatMemeListMeta = {
    page: typeof p?.page === 'number' ? p.page : page,
    perPage: typeof p?.per_page === 'number' ? p.per_page : perPage,
    total: typeof p?.total === 'number' ? p.total : rows.length,
    lastPage: typeof p?.last_page === 'number' ? p.last_page : 1,
  }
  return { items: rows as EatMemeRecordItem[], meta }
}

export async function fetchEatMemeRecords(page = 1, perPage = 10): Promise<EatMemeRecordItem[]> {
  const { items } = await fetchEatMemeRecordsPaged(page, perPage)
  return items
}

export async function deleteEatMemeRecord(id: number): Promise<void> {
  await request({
    url: `/api/eat-meme/${id}`,
    method: 'DELETE',
  })
}

