export type RecommendationSourceApi = 'initial' | 'reroll' | 'alternative_selected'

/** 推荐记录关联的做法详情（后台结构化 + 快照兜底） */
export interface RecipeDetailPayload {
  source: string
  title: string
  description?: string | null
  ingredients: string[]
  seasonings: string[]
  steps: Array<{ step_no: number; content: string }>
  cooking_time?: string | null
  difficulty?: string | null
  tips: string[]
  legacy_recipe_content?: string | null
  is_actionable: boolean
  display_tags: string[]
  cuisine?: string | null
}

/** 与后端 ReasonTextStyle 一致；旧数据可能为其它字符串 */
export type ReasonTextStyleApi = 'practical' | 'caring' | 'goal_oriented' | 'scene_based'

/** 与后端 DestinyCopyStyle 一致 */
export type DestinyCopyStyleApi = 'healing' | 'daily' | 'ritual' | 'destiny_light'

export interface RecommendationRecordListItem {
  id: number
  recommended_dish: string
  tags: string[]
  reason_summary: string
  recommendation_date: string
  recommendation_source: RecommendationSourceApi | string
  is_favorited: boolean
  created_at?: string | null
}

export interface RecommendationRecordDetail {
  id: number
  session_id: string
  recommendation_source: RecommendationSourceApi | string
  recommendation_date: string
  meal_type: string
  recommended_dish: string
  dish_recipe_id?: number | null
  tags: string[]
  reason_text: string
  destiny_text?: string | null
  destiny_style?: DestinyCopyStyleApi | string | null
  reason_style?: ReasonTextStyleApi | string | null
  alternatives: string[]
  cuisine?: string | null
  ingredients: string[]
  recipe_content: string
  recipe_detail?: RecipeDetailPayload | null
  weather_snapshot?: Record<string, unknown> | null
  festival_snapshot?: Record<string, unknown> | null
  user_profile_snapshot?: Record<string, unknown> | null
  daily_status_snapshot?: Record<string, unknown> | null
  is_favorited: boolean
  created_at?: string | null
  updated_at?: string | null
}
