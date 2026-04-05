import { request } from '@/api/http'
import type {
  MeProfileResponse,
  OnboardingProfileSubmitPayload,
  UserDailyStatusDto,
  UserProfileDto,
} from '@/types/profile'

export async function fetchMeProfile(): Promise<MeProfileResponse> {
  return request<MeProfileResponse>({
    url: '/api/me/profile',
    method: 'GET',
  })
}

/** 与 GET /api/me/profile 等价，路径为 /api/user/profile */
export async function fetchUserProfile(): Promise<MeProfileResponse> {
  return request<MeProfileResponse>({
    url: '/api/user/profile',
    method: 'GET',
  })
}

export async function putMeProfile(
  body: Partial<
    Pick<
      UserProfileDto,
      | 'birthday'
      | 'gender'
      | 'height_cm'
      | 'weight_kg'
      | 'target_weight_kg'
      | 'flavor_preferences'
      | 'taboo_ingredients'
      | 'diet_preferences'
      | 'diet_goal'
      | 'cuisine_preferences'
      | 'dislike_ingredients'
      | 'allergy_ingredients'
      | 'taboo_ingredients'
      | 'cooking_frequency'
      | 'meal_pattern'
      | 'family_size'
      | 'lifestyle_tags'
      | 'religious_restrictions'
      | 'period_tracking'
      | 'health_goal'
      | 'recommendation_style'
      | 'destiny_mode_enabled'
      | 'period_feature_enabled'
      | 'accepts_product_recommendation'
      | 'onboarding_version'
    >
  > & { complete_onboarding?: boolean },
): Promise<MeProfileResponse> {
  return request<MeProfileResponse>({
    url: '/api/me/profile',
    method: 'PUT',
    data: body as Record<string, unknown>,
  })
}

/** PUT /api/user/profile，与 putMeProfile 等价 */
export async function putUserProfile(
  body: Parameters<typeof putMeProfile>[0],
): Promise<MeProfileResponse> {
  return request<MeProfileResponse>({
    url: '/api/user/profile',
    method: 'PUT',
    data: body as Record<string, unknown>,
  })
}

export async function postUserProfileOnboarding(
  body: OnboardingProfileSubmitPayload,
): Promise<MeProfileResponse> {
  return request<MeProfileResponse>({
    /** 与 GET/PUT /api/me/profile 同前缀，便于只透传 /api/me/* 的 BFF */
    url: '/api/me/profile/onboarding',
    method: 'POST',
    data: body as Record<string, unknown>,
  })
}

/** GET：当日写入的今日状态 + 当前推荐上下文标签（不写库） */
export async function fetchMeDailyToday(): Promise<{
  today_status: UserDailyStatusDto | null
  recommendation_context_tags: string[]
}> {
  return request({
    url: '/api/me/daily-status/today',
    method: 'GET',
  })
}

export async function putMeDailyToday(body: {
  mood?: string | null
  appetite_state?: string | null
  body_state?: string | null
  wanted_food_style?: string | null
  flavor_tags?: string[] | null
  taboo_tags?: string[] | null
  period_status?: string | null
  note?: string | null
}): Promise<{ today_status: UserDailyStatusDto; recommendation_context_tags: string[] }> {
  return request({
    url: '/api/me/daily-status/today',
    method: 'PUT',
    data: body as Record<string, unknown>,
  })
}
