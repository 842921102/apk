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

export async function fetchEatMemeRecords(page = 1, perPage = 10): Promise<EatMemeRecordItem[]> {
  const raw = await request<{ data?: unknown }>({
    url: `/api/eat-meme?page=${page}&per_page=${perPage}`,
    method: 'GET',
  })
  const rows = Array.isArray(raw?.data) ? raw.data : []
  return rows as EatMemeRecordItem[]
}

export async function deleteEatMemeRecord(id: number): Promise<void> {
  await request({
    url: `/api/eat-meme/${id}`,
    method: 'DELETE',
  })
}

