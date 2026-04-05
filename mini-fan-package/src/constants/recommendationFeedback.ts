/** 与 Laravel RecommendationFeedbackType 一致 */
export type RecommendationFeedbackTypeApi =
  | 'want_to_eat'
  | 'not_today'
  | 'change_direction'
  | 'reason_accurate'
  | 'not_suitable'

/** 与 Laravel RecommendationFeedbackReason 一致 */
export type RecommendationFeedbackReasonApi =
  | 'too_greasy'
  | 'too_complex'
  | 'wrong_flavor'
  | 'already_ate_recently'
  | 'not_fit_goal'
  | 'not_fit_today_status'

/** 与 Laravel RecommendationFeedbackTarget 一致 */
export type RecommendationFeedbackTargetApi = 'result' | 'reason_text' | 'destiny_text'

export interface RecommendationFeedbackQuickOption {
  type: RecommendationFeedbackTypeApi
  label: string
  /** 需要二级原因弹层 */
  needsSheet: boolean
}

export const RECOMMENDATION_FEEDBACK_QUICK_OPTIONS: RecommendationFeedbackQuickOption[] = [
  { type: 'want_to_eat', label: '想吃', needsSheet: false },
  { type: 'not_today', label: '今天不想吃', needsSheet: true },
  { type: 'change_direction', label: '换个方向', needsSheet: false },
  { type: 'reason_accurate', label: '理由挺准', needsSheet: false },
  { type: 'not_suitable', label: '这个不适合我', needsSheet: true },
]

export const RECOMMENDATION_FEEDBACK_REASON_OPTIONS: { value: RecommendationFeedbackReasonApi; label: string }[] = [
  { value: 'too_greasy', label: '太油了' },
  { value: 'too_complex', label: '太麻烦了' },
  { value: 'wrong_flavor', label: '今天不想吃这个口味' },
  { value: 'already_ate_recently', label: '最近吃过了' },
  { value: 'not_fit_goal', label: '不符合我的目标' },
  { value: 'not_fit_today_status', label: '现在状态不适合' },
]
