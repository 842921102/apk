import { request } from '@/api/http'
import type {
  RecommendationFeedbackReasonApi,
  RecommendationFeedbackTargetApi,
  RecommendationFeedbackTypeApi,
} from '@/constants/recommendationFeedback'
import type { RecommendationRecordDetail, RecommendationRecordListItem } from '@/types/recommendationHistory'

export interface RecommendationRecordsListMeta {
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export async function apiListRecommendationRecords(params?: {
  page?: number
  per_page?: number
}): Promise<{ data: RecommendationRecordListItem[]; meta: RecommendationRecordsListMeta }> {
  return request({
    url: '/api/me/recommendation-records',
    method: 'GET',
    data: params as Record<string, unknown>,
  })
}

export async function apiGetRecommendationRecord(id: number): Promise<{ data: RecommendationRecordDetail }> {
  return request({
    url: `/api/me/recommendation-records/${id}`,
    method: 'GET',
  })
}

export async function apiSetRecommendationRecordFavorite(
  id: number,
  favorited: boolean,
): Promise<{ data: { is_favorited: boolean } }> {
  return request({
    url: `/api/me/recommendation-records/${id}/favorite`,
    method: 'POST',
    data: { favorited },
  })
}

export async function apiSubmitRecommendationFeedback(
  recommendationRecordId: number,
  body: {
    feedback_type: RecommendationFeedbackTypeApi
    feedback_reason?: RecommendationFeedbackReasonApi | null
    feedback_target?: RecommendationFeedbackTargetApi | null
  },
): Promise<{ data: { ok: boolean } }> {
  return request({
    url: `/api/me/recommendation-records/${recommendationRecordId}/feedback`,
    method: 'POST',
    data: body as Record<string, unknown>,
  })
}
