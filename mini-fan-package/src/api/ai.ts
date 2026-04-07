import { request } from '@/api/http'
import type { TodayEatRequestBody, TodayEatResult } from '@/types/ai'

/**
 * 「吃什么」AI 代理：仅请求自有 BFF，不直连第三方模型。
 * BFF 实现：POST /api/ai/today-eat
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
    url: '/api/ai/today-eat',
    method: 'POST',
    data: payload,
  })
}

/** 同一会话内换一道（需首轮返回的 recommendation_session_id，且已登录走 Laravel） */
export async function requestTodayEatReroll(body: {
  recommendation_session_id: string
  preferences: TodayEatRequestBody['preferences']
  locale?: string
  realtime_context?: TodayEatRequestBody['realtime_context']
}): Promise<TodayEatResult> {
  return request<TodayEatResult>({
    url: '/api/ai/today-eat/reroll',
    method: 'POST',
    data: {
      recommendation_session_id: body.recommendation_session_id,
      preferences: body.preferences,
      locale: body.locale ?? 'zh-CN',
      realtime_context: body.realtime_context,
    },
  })
}

/** 用户点选备选菜作为今日主菜（重新生成完整解释与备选列表） */
export async function requestTodayEatSelectAlternative(body: {
  recommendation_session_id: string
  selected_dish: string
  preferences: TodayEatRequestBody['preferences']
  locale?: string
  realtime_context?: TodayEatRequestBody['realtime_context']
}): Promise<TodayEatResult> {
  return request<TodayEatResult>({
    url: '/api/ai/today-eat/select-alternative',
    method: 'POST',
    data: {
      recommendation_session_id: body.recommendation_session_id,
      selected_dish: body.selected_dish,
      preferences: body.preferences,
      locale: body.locale ?? 'zh-CN',
      realtime_context: body.realtime_context,
    },
  })
}

export async function requestRecipeImage(body: {
  prompt: string
  size?: string
}): Promise<{ url: string; raw?: unknown }> {
  return request<{ url: string; raw?: unknown }>({
    url: '/api/ai/recipe-image',
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
    url: '/api/ai/ingredients-recognize',
    method: 'POST',
    data: {
      image_base64: body.image_base64,
    } as Record<string, unknown>,
  })
}
