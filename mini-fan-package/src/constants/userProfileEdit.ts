/** 推荐画像编辑页选项（与后端 user_profiles JSON 字段对齐，存中文或稳定英文值） */

export type ProfileChipOption = { id: string; label: string }

export const PROFILE_FLAVOR_OPTIONS: ProfileChipOption[] = [
  { id: '清淡', label: '清淡' },
  { id: '香辣', label: '香辣' },
  { id: '酸甜', label: '酸甜' },
  { id: '家常', label: '家常' },
  { id: '重口', label: '重口' },
  { id: '汤汤水水', label: '汤汤水水' },
  { id: '下饭型', label: '下饭型' },
  { id: '烧烤风味', label: '烧烤风味' },
]

/** 写入 cuisine_preferences：饮食类型 / 场景 */
export const PROFILE_DIET_TYPE_OPTIONS: ProfileChipOption[] = [
  { id: '家常口味', label: '家常' },
  { id: '减脂餐', label: '减脂' },
  { id: '健身餐', label: '健身' },
  { id: '养生滋补', label: '养生' },
  { id: '轻食简餐', label: '轻食' },
  { id: '快手简餐', label: '快手' },
]

export const PROFILE_DISLIKE_OPTIONS: ProfileChipOption[] = [
  { id: '牛肉', label: '牛肉' },
  { id: '羊肉', label: '羊肉' },
  { id: '海鲜', label: '海鲜' },
  { id: '香菜', label: '香菜' },
  { id: '辣椒', label: '辣椒' },
  { id: '葱姜蒜', label: '葱姜蒜' },
  { id: '内脏', label: '内脏' },
  { id: '暂无', label: '都不忌口' },
]

export const PROFILE_ALLERGY_OPTIONS: ProfileChipOption[] = [
  { id: '海鲜过敏', label: '海鲜过敏' },
  { id: '花生过敏', label: '花生过敏' },
  { id: '乳糖不耐', label: '乳糖不耐' },
  { id: '贝类敏感', label: '贝类敏感' },
  { id: '酒精敏感', label: '酒精敏感' },
  { id: '其他待补充', label: '其他' },
  { id: '暂无', label: '无过敏' },
]

export const PROFILE_RELIGIOUS_OPTIONS: ProfileChipOption[] = [
  { id: '无', label: '无' },
  { id: '清真', label: '清真' },
  { id: '素食（宗教）', label: '宗教素食' },
  { id: '佛教斋戒', label: '佛教斋戒' },
]

/** 单选 → diet_goal[0] + health_goal */
export const PROFILE_DIET_GOAL_SINGLE_OPTIONS: ProfileChipOption[] = [
  { id: '减脂', label: '减脂' },
  { id: '增肌', label: '增肌' },
  { id: '养胃', label: '养胃' },
  { id: '控糖', label: '控糖' },
  { id: '随便吃', label: '随便吃' },
]

/** 推荐理由风格：与后端 reason_style 产品线一致；玄学向同时建议开启食命 */
export const PROFILE_RECOMMENDATION_STYLE_OPTIONS = [
  { id: 'practical', label: '偏实用', desc: '直白、好执行' },
  { id: 'caring', label: '偏讲道理', desc: '温和、有共情' },
  { id: 'destiny_light', label: '偏玄学', desc: '轻松食命感' },
] as const

export type ProfileRecommendationStyleId = (typeof PROFILE_RECOMMENDATION_STYLE_OPTIONS)[number]['id']

export function heightCmRange(): string[] {
  const out: string[] = []
  for (let i = 120; i <= 220; i++) out.push(String(i))
  return out
}

export function cycleDaysRange(): string[] {
  const out: string[] = []
  for (let i = 21; i <= 40; i++) out.push(String(i))
  return out
}
