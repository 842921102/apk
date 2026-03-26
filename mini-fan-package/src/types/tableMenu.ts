/** POST /api/ai/table-menu — 与 Web TableDesign 配置对齐，供 BFF 转发 AI */

export interface TableMenuConfigPayload {
  dishCount: number
  flexibleCount: boolean
  tastes: string[]
  cuisineStyle: string
  diningScene: string
  nutritionFocus: string
  customRequirement: string
  customDishes: string[]
}

export interface TableMenuRequestBody {
  config: TableMenuConfigPayload
  locale?: string
}

export interface TableMenuDishItem {
  name: string
  description: string
  category: string
  tags: string[]
}

export interface TableMenuResponse {
  dishes: TableMenuDishItem[]
  /** BFF 已在服务端写入历史时为 true */
  history_saved?: boolean
}

/** POST /api/ai/table-dish-recipe — 单道菜详细菜谱 */

export interface TableDishRecipeRequestBody {
  dishName: string
  dishDescription: string
  category: string
  locale?: string
}

export interface TableDishRecipeStep {
  step: number
  description: string
  time?: number
  temperature?: string
}

export interface TableDishRecipeResponse {
  name: string
  cuisine?: string
  ingredients: string[]
  steps: TableDishRecipeStep[]
  cookingTime?: number
  difficulty?: string
  tips?: string[]
}
