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
