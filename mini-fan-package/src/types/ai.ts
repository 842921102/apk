/** POST /api/me/today-eat 请求体（可扩展） */
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
  /** 由服务端根据用户授权与当日状态生成的上下文标签 */
  context_tags?: string[]
  /** 前端实时环境信号（城市/天气/温度），用于增强当次推荐上下文 */
  realtime_context?: {
    city?: string
    weather_text?: string
    weather_icon?: string
    temperature_text?: string
    temperature_c?: number
    location_authorized?: boolean
  }
}

/**
 * Laravel 返回：含推荐理由与食命文案；兼容仅含 title/content 的旧结果。
 */
export interface TodayEatResult {
  /** Laravel 首轮创建；用于「换一个推荐」续跑同一会话 */
  recommendation_session_id?: string
  /** 本条结果的来源：首轮 / 换一换 / 点选备选 */
  recommendation_source?: 'initial' | 'reroll' | 'alternative_selected'
  /** 写入 recommendation_records 后的主键 */
  recommendation_record_id?: number
  /** 与后台 dish_recipes 匹配成功时返回，用于「收藏这道菜」 */
  dish_recipe_id?: number | null
  /** 主推荐菜名（与 Laravel 对齐） */
  recommended_dish?: string
  /** 展示用标签 */
  tags?: string[]
  /** 有依据的推荐说明 */
  reason_text?: string
  /** 食命风格短句 */
  destiny_text?: string
  /** 备选菜品 */
  alternatives?: string[]
  /** 封面图（可选，有则展示） */
  cover_image?: string
  /** 菜谱名（兼容字段，通常等同于 recommended_dish） */
  title: string
  cuisine?: string
  ingredients?: string[]
  /** 菜谱正文 / Markdown / 纯文本摘要 */
  content: string
  history_saved?: boolean
}
