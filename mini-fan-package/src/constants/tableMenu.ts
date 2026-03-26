/** 与 Web `TableDesign.vue` 选项 id 一致，便于 BFF 共用提示词映射 */

export const TABLE_MENU_TASTE_OPTIONS = [
  { id: 'spicy', name: '麻辣' },
  { id: 'sweet', name: '甜味' },
  { id: 'sour', name: '酸味' },
  { id: 'salty', name: '咸鲜' },
  { id: 'light', name: '清淡' },
  { id: 'rich', name: '浓郁' },
] as const

export const TABLE_MENU_CUISINE_STYLES = [
  { id: 'mixed', name: '混合菜系' },
  { id: 'chinese', name: '中式为主' },
  { id: 'western', name: '西式为主' },
  { id: 'japanese', name: '日式为主' },
] as const

export const TABLE_MENU_DINING_SCENES = [
  { id: 'family', name: '家庭聚餐' },
  { id: 'friends', name: '朋友聚会' },
  { id: 'romantic', name: '浪漫晚餐' },
  { id: 'business', name: '商务宴请' },
  { id: 'festival', name: '节日庆祝' },
  { id: 'casual', name: '日常用餐' },
] as const

export const TABLE_MENU_NUTRITION_OPTIONS = [
  { id: 'balanced', name: '营养均衡' },
  { id: 'protein', name: '高蛋白' },
  { id: 'vegetarian', name: '素食为主' },
  { id: 'low_fat', name: '低脂健康' },
  { id: 'comfort', name: '滋补养生' },
] as const

export const TABLE_MENU_DISH_COUNT_PRESETS = [2, 4, 6, 8] as const
