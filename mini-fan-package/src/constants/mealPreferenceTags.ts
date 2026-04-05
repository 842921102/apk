/** 与 Laravel UserDailyStatusMvp::flavorTagKeys / tabooTagKeys 一致 */

export const MEAL_FLAVOR_TAG_OPTIONS = [
  { value: 'light', label: '清淡' },
  { value: 'spicy_hot', label: '香辣' },
  { value: 'mild_spicy', label: '微辣' },
  { value: 'sweet_sour', label: '酸甜' },
  { value: 'home_style', label: '家常' },
  { value: 'strong', label: '重口' },
  { value: 'soup', label: '汤水' },
] as const

export const MEAL_TABOO_TAG_OPTIONS = [
  { value: 'coriander', label: '香菜' },
  { value: 'alliums', label: '葱姜蒜' },
  { value: 'seafood', label: '海鲜' },
  { value: 'organ', label: '内脏' },
  { value: 'peanut', label: '花生' },
  { value: 'none', label: '暂无' },
] as const

const FLAVOR_SET = new Set(MEAL_FLAVOR_TAG_OPTIONS.map((o) => o.value))
const TABOO_SET = new Set(MEAL_TABOO_TAG_OPTIONS.map((o) => o.value))

export function normalizeFlavorTagsFromApi(raw: unknown): string[] {
  if (!Array.isArray(raw)) return []
  return raw.filter((x): x is string => typeof x === 'string' && FLAVOR_SET.has(x))
}

export function normalizeTabooTagsFromApi(raw: unknown): string[] {
  if (!Array.isArray(raw)) return []
  const list = raw.filter((x): x is string => typeof x === 'string' && TABOO_SET.has(x))
  if (list.includes('none')) return ['none']
  return list
}
