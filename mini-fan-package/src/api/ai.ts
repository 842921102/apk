import { request } from '@/api/http'
import type { TodayEatRequestBody, TodayEatResult } from '@/types/ai'

/**
 * 「今日菜单」：请求 Laravel `POST /api/me/today-eat*`（模型中心 + 推荐管线）。
 */
export async function requestTodayEat(body: TodayEatRequestBody): Promise<TodayEatResult> {
  const payload: Record<string, unknown> = {
    preferences: body.preferences,
    locale: body.locale ?? 'zh-CN',
  }
  if (body.context_tags?.length) {
    payload.context_tags = body.context_tags
  }
  if (body.realtime_context && typeof body.realtime_context === 'object') {
    payload.realtime_context = body.realtime_context
  }
  return request<TodayEatResult>({
    url: '/api/me/today-eat',
    method: 'POST',
    data: payload,
  })
}

export async function requestTodayEatReroll(body: {
  recommendation_session_id: string
  preferences: TodayEatRequestBody['preferences']
  locale?: string
  realtime_context?: TodayEatRequestBody['realtime_context']
}): Promise<TodayEatResult> {
  const payload: Record<string, unknown> = {
    recommendation_session_id: body.recommendation_session_id,
    preferences: body.preferences,
    locale: body.locale ?? 'zh-CN',
    realtime_context: body.realtime_context,
  }
  return request<TodayEatResult>({
    url: '/api/me/today-eat/reroll',
    method: 'POST',
    data: payload,
  })
}

export async function requestTodayEatSelectAlternative(body: {
  recommendation_session_id: string
  selected_dish: string
  preferences: TodayEatRequestBody['preferences']
  locale?: string
  realtime_context?: TodayEatRequestBody['realtime_context']
}): Promise<TodayEatResult> {
  const payload: Record<string, unknown> = {
    recommendation_session_id: body.recommendation_session_id,
    selected_dish: body.selected_dish,
    preferences: body.preferences,
    locale: body.locale ?? 'zh-CN',
    realtime_context: body.realtime_context,
  }
  return request<TodayEatResult>({
    url: '/api/me/today-eat/select-alternative',
    method: 'POST',
    data: payload,
  })
}

export async function requestRecipeImage(body: {
  prompt: string
  size?: string
}): Promise<{ url: string; raw?: unknown }> {
  return request<{ url: string; raw?: unknown }>({
    url: '/api/me/recipe-image',
    method: 'POST',
    data: {
      prompt: body.prompt,
      size: body.size ?? '1024x1024',
    } as Record<string, unknown>,
  })
}

export async function requestRecognizeIngredients(body: {
  image_base64: string
}): Promise<{ ingredients: string[]; raw?: unknown }> {
  return request<{ ingredients: string[]; raw?: unknown }>({
    url: '/api/me/ingredients-recognize',
    method: 'POST',
    data: {
      image_base64: body.image_base64,
    } as Record<string, unknown>,
  })
}
