/** POST /api/ai/today-eat 请求体（与 BFF 约定，可扩展） */
export interface TodayEatPreferences {
  /** 口味偏好，如「清淡」「辣」 */
  taste?: string
  /** 忌口或不想吃的食材 */
  avoid?: string
  /** 用餐人数 */
  people?: number
}

export interface TodayEatRequestBody {
  preferences: TodayEatPreferences
  locale?: string
}

/**
 * BFF 返回结构（最小可用）
 * history_saved: BFF 已在服务端写入 recipe_histories 时为 true；否则小程序可在登录态下补写
 */
export interface TodayEatResult {
  title: string
  cuisine?: string
  ingredients?: string[]
  /** 菜谱正文 / Markdown / 纯文本摘要 */
  content: string
  history_saved?: boolean
}
