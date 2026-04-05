/**
 * 首次问卷本地草稿（不上传、不提交），便于断点续填与后续步骤扩展。
 */
import { CURRENT_ONBOARDING_VERSION } from '@/constants/onboarding'

export const ONBOARDING_DRAFT_STORAGE_KEY = 'wte_onboarding_draft_v1'

export type OnboardingScreenKey =
  | 'welcome'
  | 'flavor'
  | 'taboo'
  | 'goals'
  | 'vitals'
  | 'lifestyle'
  | 'recommend_settings'
  | 'summary'

export type CookingFrequencyKey = 'often' | 'sometimes' | 'rarely' | 'takeout'
export type FamilySizeKey = 'single' | 'couple' | 'family3' | 'family5'
export type OnboardingGender = 'male' | 'female' | 'undisclosed' | 'unknown'

export interface OnboardingDraftV1 {
  v: 1
  /** 当前停留的引导屏 */
  screen: OnboardingScreenKey
  /** 首次问卷是否已完成（本地闭环） */
  onboarding_completed: boolean
  /** 用户本次选择「跳过」：放行进入首页，后续可通过版本迁移再次门禁 */
  onboarding_skipped: boolean
  /** 与 `constants/onboarding` 中 CURRENT 对齐，便于发版再次提醒 */
  onboarding_version: number
  /** 口味偏好（多选文案，与选项 label 一致） */
  flavorPreferences: string[]
  /** 不喜欢吃的食材 */
  dislike_ingredients: string[]
  /** 过敏 / 必须避免 */
  allergy_ingredients: string[]
  /** 饮食目标（多选） */
  diet_goals: string[]
  /** 身高 cm */
  height_cm: number | null
  /** 体重 kg */
  weight_kg: number | null
  /** 目标体重 kg，可选 */
  target_weight_kg: number | null
  /** 做饭频率：often | sometimes | rarely | takeout */
  cooking_frequency: string
  /** 生活习惯标签（多选，中文文案） */
  lifestyle_tags: string[]
  /** 用餐场景：single | couple | family3 | family5 */
  family_size: string
  /** 食命推荐；null 表示本步尚未选择 */
  destiny_mode_enabled: boolean | null
  /** 特殊状态贴心推荐；null 表示尚未选择 */
  period_feature_enabled: boolean | null
  /** 是否接受商品推荐；null 表示尚未选择 */
  accepts_product_recommendation: boolean | null
  /** 生日 YYYY-MM-DD，可选 */
  birthday: string
  /** 性别（基础资料，不参与推荐逻辑自动绑定） */
  gender: OnboardingGender
  updatedAt: number
}

const COOKING_FREQ_ALLOWED = new Set<string>(['often', 'sometimes', 'rarely', 'takeout'])
const FAMILY_SIZE_ALLOWED = new Set<string>(['single', 'couple', 'family3', 'family5'])
const GENDER_ALLOWED = new Set<OnboardingGender>(['male', 'female', 'undisclosed', 'unknown'])

const defaultDraft = (): OnboardingDraftV1 => ({
  v: 1,
  screen: 'welcome',
  onboarding_completed: false,
  onboarding_skipped: false,
  onboarding_version: CURRENT_ONBOARDING_VERSION,
  flavorPreferences: [],
  dislike_ingredients: [],
  allergy_ingredients: [],
  diet_goals: [],
  height_cm: null,
  weight_kg: null,
  target_weight_kg: null,
  cooking_frequency: '',
  lifestyle_tags: [],
  family_size: '',
  destiny_mode_enabled: null,
  period_feature_enabled: null,
  accepts_product_recommendation: null,
  birthday: '',
  gender: 'unknown',
  updatedAt: Date.now(),
})

function parseScreen(s: unknown): OnboardingScreenKey {
  if (s === 'pending') return 'summary'
  if (
    s === 'flavor' ||
    s === 'taboo' ||
    s === 'goals' ||
    s === 'vitals' ||
    s === 'lifestyle' ||
    s === 'recommend_settings' ||
    s === 'summary'
  )
    return s
  return 'welcome'
}

function parseStringList(v: unknown): string[] {
  if (!Array.isArray(v)) return []
  return v.map((x) => String(x)).filter(Boolean)
}

function parseNullableNumber(v: unknown): number | null {
  if (v === null || v === undefined || v === '') return null
  if (typeof v === 'number' && Number.isFinite(v)) return v
  const n = Number(v)
  return Number.isFinite(n) ? n : null
}

function parseBoolOpt(v: unknown): boolean | null {
  if (v === true || v === false) return v
  return null
}

function parseCookingFrequency(v: unknown): string {
  const s = typeof v === 'string' ? v : ''
  return COOKING_FREQ_ALLOWED.has(s) ? s : ''
}

function parseFamilySize(v: unknown): string {
  const s = typeof v === 'string' ? v : ''
  return FAMILY_SIZE_ALLOWED.has(s) ? s : ''
}

function parseGender(v: unknown): OnboardingGender {
  const s = typeof v === 'string' ? v : ''
  return GENDER_ALLOWED.has(s as OnboardingGender) ? (s as OnboardingGender) : 'unknown'
}

function parseBirthday(v: unknown): string {
  return typeof v === 'string' ? v.trim() : ''
}

function parseCompleted(v: unknown): boolean {
  return v === true
}

function parseSkipped(v: unknown): boolean {
  return v === true
}

function parseVersion(v: unknown): number {
  if (typeof v === 'number' && Number.isFinite(v) && v >= 1) return Math.floor(v)
  return CURRENT_ONBOARDING_VERSION
}

export function readOnboardingDraft(): OnboardingDraftV1 {
  try {
    const raw = uni.getStorageSync(ONBOARDING_DRAFT_STORAGE_KEY)
    if (!raw || typeof raw !== 'string') return defaultDraft()
    const p = JSON.parse(raw) as Partial<OnboardingDraftV1>
    if (p.v !== 1) return defaultDraft()
    return {
      v: 1,
      screen: parseScreen(p.screen),
      onboarding_completed: parseCompleted(p.onboarding_completed),
      onboarding_skipped: parseSkipped(p.onboarding_skipped),
      onboarding_version: parseVersion(p.onboarding_version),
      flavorPreferences: parseStringList(p.flavorPreferences),
      dislike_ingredients: parseStringList(p.dislike_ingredients),
      allergy_ingredients: parseStringList(p.allergy_ingredients),
      diet_goals: parseStringList(p.diet_goals),
      height_cm: parseNullableNumber(p.height_cm),
      weight_kg: parseNullableNumber(p.weight_kg),
      target_weight_kg: parseNullableNumber(p.target_weight_kg),
      cooking_frequency: parseCookingFrequency(p.cooking_frequency),
      lifestyle_tags: parseStringList(p.lifestyle_tags),
      family_size: parseFamilySize(p.family_size),
      destiny_mode_enabled: parseBoolOpt(p.destiny_mode_enabled),
      period_feature_enabled: parseBoolOpt(p.period_feature_enabled),
      accepts_product_recommendation: parseBoolOpt(p.accepts_product_recommendation),
      birthday: parseBirthday(p.birthday),
      gender: parseGender(p.gender),
      updatedAt: typeof p.updatedAt === 'number' ? p.updatedAt : Date.now(),
    }
  } catch {
    return defaultDraft()
  }
}

export function writeOnboardingDraft(patch: Partial<Omit<OnboardingDraftV1, 'v'>>): OnboardingDraftV1 {
  const cur = readOnboardingDraft()
  const next: OnboardingDraftV1 = {
    v: 1,
    screen: patch.screen ?? cur.screen,
    onboarding_completed:
      patch.onboarding_completed !== undefined ? patch.onboarding_completed : cur.onboarding_completed,
    onboarding_skipped:
      patch.onboarding_skipped !== undefined ? patch.onboarding_skipped : cur.onboarding_skipped,
    onboarding_version:
      patch.onboarding_version !== undefined ? patch.onboarding_version : cur.onboarding_version,
    flavorPreferences: patch.flavorPreferences ?? cur.flavorPreferences,
    dislike_ingredients: patch.dislike_ingredients ?? cur.dislike_ingredients,
    allergy_ingredients: patch.allergy_ingredients ?? cur.allergy_ingredients,
    diet_goals: patch.diet_goals ?? cur.diet_goals,
    height_cm: patch.height_cm !== undefined ? patch.height_cm : cur.height_cm,
    weight_kg: patch.weight_kg !== undefined ? patch.weight_kg : cur.weight_kg,
    target_weight_kg: patch.target_weight_kg !== undefined ? patch.target_weight_kg : cur.target_weight_kg,
    cooking_frequency:
      patch.cooking_frequency !== undefined ? patch.cooking_frequency : cur.cooking_frequency,
    lifestyle_tags: patch.lifestyle_tags ?? cur.lifestyle_tags,
    family_size: patch.family_size !== undefined ? patch.family_size : cur.family_size,
    destiny_mode_enabled:
      patch.destiny_mode_enabled !== undefined ? patch.destiny_mode_enabled : cur.destiny_mode_enabled,
    period_feature_enabled:
      patch.period_feature_enabled !== undefined
        ? patch.period_feature_enabled
        : cur.period_feature_enabled,
    accepts_product_recommendation:
      patch.accepts_product_recommendation !== undefined
        ? patch.accepts_product_recommendation
        : cur.accepts_product_recommendation,
    birthday: patch.birthday !== undefined ? patch.birthday : cur.birthday,
    gender: patch.gender !== undefined ? patch.gender : cur.gender,
    updatedAt: Date.now(),
  }
  try {
    uni.setStorageSync(ONBOARDING_DRAFT_STORAGE_KEY, JSON.stringify(next))
  } catch {
    /* ignore quota / simulator */
  }
  return next
}
