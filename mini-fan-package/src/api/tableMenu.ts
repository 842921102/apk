import { request } from '@/api/http'
import type {
  TableMenuRequestBody,
  TableMenuResponse,
  TableMenuDishItem,
  TableDishRecipeRequestBody,
  TableDishRecipeResponse,
} from '@/types/tableMenu'

function asRecord(v: unknown): Record<string, unknown> | null {
  if (!v || typeof v !== 'object' || Array.isArray(v)) return null
  return v as Record<string, unknown>
}

/**
 * 解析 BFF 返回：兼容 `{ dishes: [...] }` 或误包一层 `data`
 */
export function normalizeTableMenuResponse(raw: unknown): TableMenuResponse {
  let cur: unknown = raw
  for (let i = 0; i < 3; i++) {
    const o = asRecord(cur)
    if (!o) break
    const dishes = o.dishes
    if (Array.isArray(dishes)) {
      const list: TableMenuDishItem[] = []
      for (const item of dishes) {
        const d = asRecord(item)
        if (!d) continue
        const name = typeof d.name === 'string' ? d.name.trim() : ''
        if (!name) continue
        list.push({
          name,
          description: typeof d.description === 'string' ? d.description : '',
          category: typeof d.category === 'string' ? d.category : '',
          tags: Array.isArray(d.tags)
            ? d.tags.filter((t): t is string => typeof t === 'string')
            : [],
        })
      }
      return {
        dishes: list,
        history_saved: typeof o.history_saved === 'boolean' ? o.history_saved : undefined,
      }
    }
    cur = o.data ?? o.result
  }
  return { dishes: [] }
}

function normalizeDishRecipeResponse(raw: unknown): TableDishRecipeResponse {
  const o = asRecord(raw)
  if (!o) {
    return {
      name: '',
      ingredients: [],
      steps: [],
    }
  }
  const inner = asRecord(o.data ?? o.result) ?? o
  const stepsRaw = inner.steps
  const steps: TableDishRecipeResponse['steps'] = []
  if (Array.isArray(stepsRaw)) {
    for (const s of stepsRaw) {
      const r = asRecord(s)
      if (!r) continue
      const step = typeof r.step === 'number' ? r.step : parseInt(String(r.step), 10) || steps.length + 1
      const desc = typeof r.description === 'string' ? r.description : ''
      steps.push({
        step,
        description: desc,
        time: typeof r.time === 'number' ? r.time : undefined,
        temperature: typeof r.temperature === 'string' ? r.temperature : undefined,
      })
    }
  }
  const ing = inner.ingredients
  return {
    name: typeof inner.name === 'string' ? inner.name : '',
    cuisine: typeof inner.cuisine === 'string' ? inner.cuisine : undefined,
    ingredients: Array.isArray(ing) ? ing.filter((x): x is string => typeof x === 'string') : [],
    steps,
    cookingTime: typeof inner.cookingTime === 'number' ? inner.cookingTime : undefined,
    difficulty: typeof inner.difficulty === 'string' ? inner.difficulty : undefined,
    tips: Array.isArray(inner.tips)
      ? inner.tips.filter((t): t is string => typeof t === 'string')
      : undefined,
  }
}

/**
 * 满汉全席 / 一桌好菜菜单生成：仅请求自有 BFF。
 * 约定：POST /api/ai/table-menu，body: `{ config, locale? }`
 */
export async function requestTableMenu(body: TableMenuRequestBody): Promise<TableMenuResponse> {
  const raw = await request<unknown>({
    url: '/api/ai/table-menu',
    method: 'POST',
    data: {
      config: body.config,
      locale: body.locale ?? 'zh-CN',
    } as Record<string, unknown>,
  })
  return normalizeTableMenuResponse(raw)
}

/**
 * 单道菜菜谱：POST /api/ai/table-dish-recipe
 */
export async function requestTableDishRecipe(
  body: TableDishRecipeRequestBody,
): Promise<TableDishRecipeResponse> {
  const raw = await request<unknown>({
    url: '/api/ai/table-dish-recipe',
    method: 'POST',
    data: {
      dish_name: body.dishName,
      dish_description: body.dishDescription,
      category: body.category,
      locale: body.locale ?? 'zh-CN',
    } as Record<string, unknown>,
  })
  return normalizeDishRecipeResponse(raw)
}
