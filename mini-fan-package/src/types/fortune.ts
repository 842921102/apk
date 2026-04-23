/** 与 Web `FortuneCooking` / `aiService` 占卜接口对齐 */

export type FortuneType = 'daily' | 'mood' | 'couple' | 'number'

export interface FortuneDailyInput {
  zodiac: string
  animal: string
  date: string
}

export interface FortuneMoodInput {
  moods: string[]
  intensity: number
}

export interface FortunePersonInput {
  zodiac: string
  animal: string
  personality: string[]
}

export interface FortuneCoupleInput {
  user1: FortunePersonInput
  user2: FortunePersonInput
}

export interface FortuneNumberInput {
  number: number
  is_random: boolean
}

/**
 * 请求 Laravel：POST /api/me/fortune-cooking
 * 按 fortune_type 携带对应字段（camelCase 与 Web 一致，便于服务端复用提示词）
 */
export type FortuneRequestBody =
  | { fortune_type: 'daily'; daily: FortuneDailyInput; locale?: string }
  | { fortune_type: 'mood'; mood: FortuneMoodInput; locale?: string }
  | { fortune_type: 'couple'; couple: FortuneCoupleInput; locale?: string }
  | { fortune_type: 'number'; number: FortuneNumberInput; locale?: string }

export interface FortuneResult {
  id: string
  type: FortuneType
  date: string
  dishName: string
  reason: string
  luckyIndex: number
  description: string
  tips: string[]
  difficulty: 'easy' | 'medium' | 'hard'
  cookingTime: number
  mysticalMessage: string
  ingredients: string[]
  steps: string[]
}

export interface FortuneApiEnvelope {
  result: FortuneResult
  history_saved?: boolean
}
