/** GET /api/me/profile、/api/user/profile 等接口中的 profile 块 */
export interface UserProfileDto {
  user_id: number
  birthday: string | null
  gender: 'unknown' | 'male' | 'female' | 'undisclosed'
  height_cm: number | null
  weight_kg: number | null
  target_weight_kg: number | null
  flavor_preferences: string[]
  cuisine_preferences: string[]
  dislike_ingredients: string[]
  allergy_ingredients: string[]
  taboo_ingredients: string[]
  /** 历史字段：与饮食类型相关；问卷以 diet_goal 为准 */
  diet_preferences: string[]
  diet_goal: string[]
  health_goal: string | null
  cooking_frequency: string | null
  meal_pattern: string | null
  family_size: string | null
  lifestyle_tags: string[]
  religious_restrictions?: string[]
  period_tracking?: {
    last_period_start?: string | null
    cycle_days?: number | null
  } | null
  recommendation_style: string | null
  destiny_mode_enabled: boolean
  period_feature_enabled: boolean
  accepts_product_recommendation: boolean
  onboarding_completed_at: string | null
  onboarding_version: number | null
}

/** POST /api/me/profile/onboarding（亦保留服务端别名 /api/user/profile/onboarding） */
export interface OnboardingProfileSubmitPayload {
  birthday: string | null
  gender: 'unknown' | 'male' | 'female' | 'undisclosed'
  height_cm: number
  weight_kg: number
  target_weight_kg: number | null
  diet_goal: string[]
  flavor_preferences: string[]
  cuisine_preferences: string[]
  dislike_ingredients: string[]
  allergy_ingredients: string[]
  taboo_ingredients: string[]
  cooking_frequency: string
  meal_pattern: string | null
  family_size: string
  lifestyle_tags: string[]
  recommendation_style: string | null
  destiny_mode_enabled: boolean
  period_feature_enabled: boolean
  accepts_product_recommendation: boolean
  onboarding_version: number
}

export interface UserDailyStatusDto {
  status_date: string
  mood: string | null
  appetite_state: string | null
  body_state: string | null
  wanted_food_style: string | null
  /** 今日口味多选，与后端 user_daily_statuses.flavor_tags 一致 */
  flavor_tags?: string[]
  /** 今日忌口多选；含 none 表示暂无 */
  taboo_tags?: string[]
  period_status: string
  note: string | null
}

export interface MeProfileResponse {
  profile: UserProfileDto
  today_status: UserDailyStatusDto | null
  recommendation_context_tags: string[]
  needs_onboarding: boolean
  /** 与 Laravel `users.name` 一致；默认可能为「微信用户」 */
  nickname?: string
  /** 赞助用户：个人中心展示「赞助用户」标签；与 sponsor_until、支付 sponsor_love 联动 */
  is_sponsor?: boolean
  /** 爱心赞助当前有效期结束时间（ISO8601）；未赞助或非有效期则无 */
  sponsor_until?: string | null
}
