import { request } from '@/api/http'

export type UserFeedbackStatus = 'pending' | 'processing' | 'resolved' | 'rejected'

export interface UserFeedbackItem {
  id: number
  user_id: number
  title: string
  content: string
  contact?: string | null
  status: UserFeedbackStatus
  admin_remark?: string | null
  handled_at?: string | null
  created_at?: string | null
}

export interface UserFeedbackListMeta {
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export async function apiCreateUserFeedback(body: {
  title: string
  content: string
  contact?: string | null
}): Promise<{ data: UserFeedbackItem }> {
  return request<{ data: UserFeedbackItem }>({
    url: '/api/me/user-feedbacks',
    method: 'POST',
    data: body as Record<string, unknown>,
  })
}

export async function apiListMyUserFeedbacks(params?: {
  page?: number
  per_page?: number
}): Promise<{ data: UserFeedbackItem[]; meta: UserFeedbackListMeta }> {
  return request<{ data: UserFeedbackItem[]; meta: UserFeedbackListMeta }>({
    url: '/api/me/user-feedbacks',
    method: 'GET',
    data: params as Record<string, unknown>,
  })
}
