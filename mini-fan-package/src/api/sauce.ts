import { request } from '@/api/http'
import type { SaucePreference, SauceRecipe, SauceCategory, SauceStep, SauceStorageInfo } from '@/types/sauce'

function asRecord(v: unknown): Record<string, unknown> | null {
  if (!v || typeof v !== 'object' || Array.isArray(v)) return null
  return v as Record<string, unknown>
}

const SAUCE_CATEGORIES: SauceCategory[] = [
  'spicy',
  'garlic',
  'sweet',
  'complex',
  'regional',
  'fusion',
]

function coerceCategory(v: unknown): SauceCategory {
  if (typeof v === 'string' && SAUCE_CATEGORIES.includes(v as SauceCategory)) {
    return v as SauceCategory
  }
  return 'complex'
}

function coerceDifficulty(v: unknown): SauceRecipe['difficulty'] {
  if (v === 'easy' || v === 'hard') return v
  return 'medium'
}

function normalizeSteps(raw: unknown): SauceStep[] {
  if (!Array.isArray(raw)) return []
  return raw.map((s, i) => {
    if (typeof s === 'string') return { step: i + 1, description: s }
    const o = asRecord(s)
    if (!o) return { step: i + 1, description: '' }
    const step = typeof o.step === 'number' ? o.step : i + 1
    return {
      step,
      description: String(o.description ?? ''),
      time: typeof o.time === 'number' ? o.time : undefined,
      temperature: typeof o.temperature === 'string' ? o.temperature : undefined,
      technique: typeof o.technique === 'string' ? o.technique : undefined,
    }
  })
}

function normalizeStorage(raw: unknown): SauceStorageInfo {
  const o = asRecord(raw)
  if (!o) {
    return { method: '密封保存', duration: '—', temperature: '—' }
  }
  return {
    method: String(o.method ?? '密封保存'),
    duration: String(o.duration ?? '—'),
    temperature: String(o.temperature ?? '—'),
  }
}

export function normalizeSauceRecipe(raw: unknown, fallbackName: string): SauceRecipe {
  const o = asRecord(raw)
  if (!o) {
    return {
      id: `sauce-${Date.now()}`,
      name: fallbackName,
      category: 'complex',
      ingredients: [],
      steps: [],
      makingTime: 25,
      difficulty: 'medium',
      tips: [],
      storage: { method: '—', duration: '—', temperature: '—' },
      pairings: [],
      tags: [],
      description: '',
    }
  }

  const inner = asRecord(o.result ?? o.data) ?? o

  const ing = inner.ingredients
  const ingredients = Array.isArray(ing) ? ing.filter((x): x is string => typeof x === 'string') : []

  const tipsRaw = inner.tips
  const tips = Array.isArray(tipsRaw) ? tipsRaw.filter((t): t is string => typeof t === 'string') : []

  const pairRaw = inner.pairings
  const pairings = Array.isArray(pairRaw) ? pairRaw.filter((p): p is string => typeof p === 'string') : []

  const tagsRaw = inner.tags
  const tags = Array.isArray(tagsRaw) ? tagsRaw.filter((t): t is string => typeof t === 'string') : []

  const mt = inner.makingTime ?? inner.making_time
  const makingTime =
    typeof mt === 'number' && Number.isFinite(mt) ? Math.max(1, Math.round(mt)) : 25

  return {
    id: typeof inner.id === 'string' ? inner.id : `sauce-${Date.now()}`,
    name: String(inner.name ?? fallbackName),
    category: coerceCategory(inner.category),
    ingredients,
    steps: normalizeSteps(inner.steps),
    makingTime,
    difficulty: coerceDifficulty(inner.difficulty),
    tips,
    storage: normalizeStorage(inner.storage),
    pairings,
    tags,
    spiceLevel: typeof inner.spiceLevel === 'number' ? inner.spiceLevel : undefined,
    sweetLevel: typeof inner.sweetLevel === 'number' ? inner.sweetLevel : undefined,
    saltLevel: typeof inner.saltLevel === 'number' ? inner.saltLevel : undefined,
    sourLevel: typeof inner.sourLevel === 'number' ? inner.sourLevel : undefined,
    description: typeof inner.description === 'string' ? inner.description : undefined,
  }
}

/**
 * 解析推荐列表：`{ recommendations: string[] }` 或 `data.recommendations`
 */
export function normalizeSauceRecommendations(raw: unknown): string[] {
  let cur: unknown = raw
  for (let i = 0; i < 3; i++) {
    const o = asRecord(cur)
    if (!o) break
    const rec = o.recommendations
    if (Array.isArray(rec)) {
      return rec.map((x) => String(x).trim()).filter(Boolean)
    }
    cur = o.data ?? o.result
  }
  return []
}

/**
 * 智能推荐酱料名称：POST /api/me/sauce-recommend
 */
export async function requestSauceRecommend(
  preferences: SaucePreference,
  locale = 'zh-CN',
): Promise<string[]> {
  const raw = await request<unknown>({
    url: '/api/me/sauce-recommend',
    method: 'POST',
    data: {
      preferences,
      locale,
    } as Record<string, unknown>,
  })
  return normalizeSauceRecommendations(raw)
}

export interface SauceRecipeApiResult {
  recipe: SauceRecipe
  history_saved?: boolean
}

function unwrapRecipePayload(raw: unknown): unknown {
  let cur: unknown = raw
  for (let i = 0; i < 3; i++) {
    const o = asRecord(cur)
    if (!o) break
    if (o.recipe && typeof o.recipe === 'object') return o.recipe
    if (o.result && typeof o.result === 'object') return o.result
    if (o.name != null || o.ingredients != null) return o
    cur = o.data ?? o.result
  }
  return raw
}

/**
 * 按名称生成酱料配方：POST /api/me/sauce-recipe
 */
export async function requestSauceRecipe(
  sauceName: string,
  locale = 'zh-CN',
): Promise<SauceRecipeApiResult> {
  const raw = await request<unknown>({
    url: '/api/me/sauce-recipe',
    method: 'POST',
    data: {
      sauce_name: sauceName.trim(),
      locale,
    } as Record<string, unknown>,
  })
  const o = asRecord(raw)
  const inner = unwrapRecipePayload(raw)
  const recipe = normalizeSauceRecipe(inner, sauceName.trim())
  return {
    recipe,
    history_saved: o && typeof o.history_saved === 'boolean' ? o.history_saved : undefined,
  }
}
