import { request } from '@/api/http'
import type { FortuneRequestBody, FortuneResult, FortuneType } from '@/types/fortune'

function asRecord(v: unknown): Record<string, unknown> | null {
  if (!v || typeof v !== 'object' || Array.isArray(v)) return null
  return v as Record<string, unknown>
}

function normalizeSteps(raw: unknown): string[] {
  if (!Array.isArray(raw)) return []
  return raw.map((s) => {
    if (typeof s === 'string') return s
    const o = asRecord(s)
    if (o && typeof o.description === 'string') return o.description
    return String(s ?? '')
  }).filter(Boolean)
}

function coerceDifficulty(v: unknown): FortuneResult['difficulty'] {
  if (v === 'easy' || v === 'hard') return v
  return 'medium'
}

function coerceFortuneType(v: unknown, fallback: FortuneType): FortuneType {
  if (v === 'daily' || v === 'mood' || v === 'couple' || v === 'number') return v
  return fallback
}

function normalizeFortuneResult(raw: unknown, fallbackType: FortuneType): FortuneResult {
  const o = asRecord(raw)
  if (!o) {
    return {
      id: `fortune-${Date.now()}`,
      type: fallbackType,
      date: new Date().toISOString().slice(0, 10),
      dishName: '神秘料理',
      reason: '',
      luckyIndex: 7,
      description: '',
      tips: [],
      difficulty: 'medium',
      cookingTime: 30,
      mysticalMessage: '',
      ingredients: [],
      steps: [],
    }
  }

  let lucky = typeof o.luckyIndex === 'number' ? o.luckyIndex : Number(o.lucky_index)
  if (!Number.isFinite(lucky)) lucky = 7
  lucky = Math.min(10, Math.max(1, Math.round(lucky)))

  const tipsRaw = o.tips
  const tips = Array.isArray(tipsRaw) ? tipsRaw.filter((t): t is string => typeof t === 'string') : []

  const ingRaw = o.ingredients
  const ingredients = Array.isArray(ingRaw)
    ? ingRaw.filter((x): x is string => typeof x === 'string')
    : []

  const ct = typeof o.cookingTime === 'number' ? o.cookingTime : Number(o.cooking_time)
  const cookingTime = Number.isFinite(ct) && ct > 0 ? Math.round(ct) : 30

  return {
    id: typeof o.id === 'string' ? o.id : `fortune-${Date.now()}`,
    type: coerceFortuneType(o.type, fallbackType),
    date: typeof o.date === 'string' ? o.date : new Date().toISOString().slice(0, 10),
    dishName: String(o.dishName ?? o.dish_name ?? '神秘料理'),
    reason: String(o.reason ?? ''),
    luckyIndex: lucky,
    description: String(o.description ?? ''),
    tips,
    difficulty: coerceDifficulty(o.difficulty),
    cookingTime,
    mysticalMessage: String(o.mysticalMessage ?? o.mystical_message ?? ''),
    ingredients,
    steps: normalizeSteps(o.steps),
  }
}

/**
 * 解析 BFF：`{ result }` 或扁平字段，或 `data` / `result` 包一层
 */
export function normalizeFortuneApiResponse(
  raw: unknown,
  fallbackType: FortuneType,
): { result: FortuneResult; history_saved?: boolean } {
  let cur: unknown = raw
  for (let i = 0; i < 3; i++) {
    const o = asRecord(cur)
    if (!o) break

    const inner = o.result ?? o.data
    if (inner && typeof inner === 'object' && !Array.isArray(inner)) {
      const r = asRecord(inner)
      if (r && (r.dishName != null || r.dish_name != null)) {
        return {
          result: normalizeFortuneResult(inner, fallbackType),
          history_saved: typeof o.history_saved === 'boolean' ? o.history_saved : undefined,
        }
      }
    }

    if (o.dishName != null || o.dish_name != null) {
      return {
        result: normalizeFortuneResult(o, fallbackType),
        history_saved: typeof o.history_saved === 'boolean' ? o.history_saved : undefined,
      }
    }

    cur = o.data ?? o.result
  }

  return { result: normalizeFortuneResult(null, fallbackType) }
}

/**
 * 玄学厨房：POST /api/ai/fortune
 */
export async function requestFortune(
  body: FortuneRequestBody,
): Promise<{ result: FortuneResult; history_saved?: boolean }> {
  const payload: Record<string, unknown> = {
    fortune_type: body.fortune_type,
    locale: body.locale ?? 'zh-CN',
  }
  switch (body.fortune_type) {
    case 'daily':
      payload.daily = body.daily
      break
    case 'mood':
      payload.mood = body.mood
      break
    case 'couple':
      payload.couple = body.couple
      break
    case 'number':
      payload.number = body.number
      break
  }

  const raw = await request<unknown>({
    url: '/api/ai/fortune',
    method: 'POST',
    data: payload,
  })

  return normalizeFortuneApiResponse(raw, body.fortune_type)
}
