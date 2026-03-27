import { request } from '@/api/http'

export interface FeatureDataRecordItem {
  id: number
  feature_type: string
  status: string
  title?: string | null
  sub_type?: string | null
  result_summary?: string | null
  result_payload?: {
    cuisine?: string | null
    ingredients?: string[] | null
  } | null
  input_payload?: Record<string, unknown> | null
  created_at?: string
}

export interface FeatureDataListMeta {
  page: number
  perPage: number
  total: number
  lastPage: number
}

export async function fetchFeatureDataRecordsPaged(params: {
  featureType: string
  status?: string
  page?: number
  perPage?: number
}): Promise<{ items: FeatureDataRecordItem[]; meta: FeatureDataListMeta }> {
  const { featureType, status = 'success', page = 1, perPage = 20 } = params
  const q = new URLSearchParams({
    feature_type: featureType,
    page: String(page),
    per_page: String(perPage),
  })
  if (status) q.set('status', status)

  const raw = await request<{
    data?: unknown
    meta?: { pagination?: Record<string, unknown> }
  }>({
    url: `/api/feature-data?${q.toString()}`,
    method: 'GET',
  })

  const rows = Array.isArray(raw?.data) ? raw.data : []
  const p = raw?.meta?.pagination
  const meta: FeatureDataListMeta = {
    page: typeof p?.page === 'number' ? p.page : page,
    perPage: typeof p?.per_page === 'number' ? p.per_page : perPage,
    total: typeof p?.total === 'number' ? p.total : rows.length,
    lastPage: typeof p?.last_page === 'number' ? p.last_page : 1,
  }

  return { items: rows as FeatureDataRecordItem[], meta }
}
