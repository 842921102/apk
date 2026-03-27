import { request } from '@/api/http'
import type { TodayEatRequestBody, TodayEatResult } from '@/types/ai'

/**
 * 「吃什么」AI 代理：仅请求自有 BFF，不直连第三方模型。
 * BFF 实现：POST /api/ai/today-eat
 */
export async function requestTodayEat(body: TodayEatRequestBody): Promise<TodayEatResult> {
  return request<TodayEatResult>({
    url: '/api/ai/today-eat',
    method: 'POST',
    data: {
      preferences: body.preferences,
      locale: body.locale ?? 'zh-CN',
    } as Record<string, unknown>,
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
