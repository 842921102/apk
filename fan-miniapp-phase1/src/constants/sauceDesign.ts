/**
 * 与 Web `src/config/sauces.ts` 对齐（小程序独立副本）
 */
import type { SauceCategory, SauceRecipe } from '@/types/sauce'

export const SAUCE_USE_CASE_OPTIONS = [
  { id: 'noodles', name: '拌面', icon: '🍜' },
  { id: 'dipping', name: '蘸菜', icon: '🥢' },
  { id: 'cooking', name: '炒菜', icon: '🍳' },
  { id: 'bbq', name: '烧烤', icon: '🔥' },
  { id: 'hotpot', name: '火锅', icon: '🍲' },
] as const

export const SAUCE_CATEGORY_LABELS: Record<SauceCategory, string> = {
  spicy: '辣味酱料',
  garlic: '蒜香酱料',
  sweet: '甜味酱料',
  complex: '复合调味酱',
  regional: '地方特色酱',
  fusion: '创新融合酱',
}

export function sauceCategoryLabel(c: string): string {
  if (c in SAUCE_CATEGORY_LABELS) return SAUCE_CATEGORY_LABELS[c as SauceCategory]
  return '其他酱料'
}

export function sauceDifficultyLabel(d: SauceRecipe['difficulty']): string {
  const m = { easy: '简单', medium: '中等', hard: '困难' }
  return m[d] || d
}
