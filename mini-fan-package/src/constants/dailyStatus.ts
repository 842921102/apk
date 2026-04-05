/** 与后端 UserDailyStatusMvp / user_daily_statuses 一致 */

export const DAILY_MOOD_OPTIONS = [
  { value: 'tired', label: '疲惫' },
  { value: 'happy', label: '开心' },
  { value: 'calm', label: '平静' },
  { value: 'stressed', label: '紧绷' },
  { value: 'low', label: '低落' },
] as const

export const DAILY_WANT_OPTIONS = [
  { value: 'hot', label: '热乎' },
  { value: 'light', label: '清爽' },
  { value: 'comforting', label: '治愈' },
  { value: 'spicy', label: '微辣' },
  { value: 'quick', label: '要快' },
] as const

export const DAILY_BODY_OPTIONS = [
  { value: 'normal', label: '如常' },
  { value: 'low_appetite', label: '没胃口' },
  { value: 'want_warm_food', label: '想暖胃' },
  { value: 'greasy_tired', label: '吃腻了' },
  { value: 'need_energy', label: '要能量' },
] as const

export const DAILY_PERIOD_OPTIONS = [
  { value: 'none', label: '不涉及' },
  { value: 'premenstrual', label: '经前期' },
  { value: 'menstrual', label: '经期' },
  { value: 'postmenstrual', label: '经后期' },
  { value: 'unknown', label: '不确定' },
] as const

const MOOD_SET = new Set(DAILY_MOOD_OPTIONS.map((o) => o.value))
const WANT_SET = new Set(DAILY_WANT_OPTIONS.map((o) => o.value))
const BODY_SET = new Set(DAILY_BODY_OPTIONS.map((o) => o.value))
const PERIOD_SET = new Set(DAILY_PERIOD_OPTIONS.map((o) => o.value))

/** 历史小程序选项 → MVP */
export function normalizeMoodFromApi(raw: string | null | undefined): string {
  if (raw == null || raw === '') return ''
  const legacy: Record<string, string> = { anxious: 'stressed' }
  const v = legacy[raw] ?? raw
  return MOOD_SET.has(v) ? v : ''
}

export function normalizeWantFromApi(raw: string | null | undefined): string {
  if (raw == null || raw === '') return ''
  const legacy: Record<string, string> = { warm: 'hot', indulgent: 'comforting' }
  const v = legacy[raw] ?? raw
  return WANT_SET.has(v) ? v : ''
}

export function normalizeBodyFromApi(raw: string | null | undefined): string {
  if (raw == null || raw === '') return ''
  const legacy: Record<string, string> = {
    tired: 'need_energy',
    ok: 'normal',
    unwell: 'low_appetite',
    weak_appetite: 'low_appetite',
    cold_stomach: 'want_warm_food',
    greasy: 'greasy_tired',
  }
  const v = legacy[raw] ?? raw
  return BODY_SET.has(v) ? v : ''
}

export function normalizePeriodFromApi(raw: string | null | undefined): string {
  if (raw == null || raw === '') return 'none'
  return PERIOD_SET.has(raw) ? raw : 'none'
}
