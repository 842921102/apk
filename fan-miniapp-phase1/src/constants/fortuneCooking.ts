/**
 * 与 Web `src/config/fortune.ts` 的 id / 文案对齐（小程序独立副本，不引用 Web 源码）
 */
import type { FortuneType, FortuneResult } from '@/types/fortune'

export const FORTUNE_TYPE_CARDS: {
  id: FortuneType
  name: string
  desc: string
  emoji: string
}[] = [
  {
    id: 'daily',
    name: '今日运势菜',
    desc: '星座 + 生肖推荐幸运菜',
    emoji: '📅',
  },
  {
    id: 'mood',
    name: '心情料理',
    desc: '按心情与强度治愈系菜品',
    emoji: '💗',
  },
  {
    id: 'number',
    name: '幸运数字菜',
    desc: '数字占卜对应料理',
    emoji: '🔢',
  },
  {
    id: 'couple',
    name: '缘分配菜',
    desc: '双人星座默契合作菜',
    emoji: '💑',
  },
]

export const FORTUNE_ZODIACS: { id: string; name: string; symbol: string }[] = [
  { id: 'aries', name: '白羊座', symbol: '♈' },
  { id: 'taurus', name: '金牛座', symbol: '♉' },
  { id: 'gemini', name: '双子座', symbol: '♊' },
  { id: 'cancer', name: '巨蟹座', symbol: '♋' },
  { id: 'leo', name: '狮子座', symbol: '♌' },
  { id: 'virgo', name: '处女座', symbol: '♍' },
  { id: 'libra', name: '天秤座', symbol: '♎' },
  { id: 'scorpio', name: '天蝎座', symbol: '♏' },
  { id: 'sagittarius', name: '射手座', symbol: '♐' },
  { id: 'capricorn', name: '摩羯座', symbol: '♑' },
  { id: 'aquarius', name: '水瓶座', symbol: '♒' },
  { id: 'pisces', name: '双鱼座', symbol: '♓' },
]

export const FORTUNE_ANIMALS: { id: string; name: string; symbol: string }[] = [
  { id: 'rat', name: '鼠', symbol: '🐭' },
  { id: 'ox', name: '牛', symbol: '🐮' },
  { id: 'tiger', name: '虎', symbol: '🐯' },
  { id: 'rabbit', name: '兔', symbol: '🐰' },
  { id: 'dragon', name: '龙', symbol: '🐲' },
  { id: 'snake', name: '蛇', symbol: '🐍' },
  { id: 'horse', name: '马', symbol: '🐴' },
  { id: 'goat', name: '羊', symbol: '🐐' },
  { id: 'monkey', name: '猴', symbol: '🐵' },
  { id: 'rooster', name: '鸡', symbol: '🐓' },
  { id: 'dog', name: '狗', symbol: '🐕' },
  { id: 'pig', name: '猪', symbol: '🐷' },
]

export const FORTUNE_MOODS: { id: string; name: string; emoji: string }[] = [
  { id: 'happy', name: '开心', emoji: '😊' },
  { id: 'sad', name: '难过', emoji: '😢' },
  { id: 'anxious', name: '焦虑', emoji: '😰' },
  { id: 'tired', name: '疲惫', emoji: '😴' },
  { id: 'excited', name: '兴奋', emoji: '🤩' },
  { id: 'calm', name: '平静', emoji: '😌' },
  { id: 'angry', name: '愤怒', emoji: '😠' },
  { id: 'nostalgic', name: '思念', emoji: '🥺' },
]

/** 缘分配菜：性格标签（提交 id 列表，与 Web PersonInfo 一致） */
export const FORTUNE_PERSONALITY: { id: string; name: string }[] = [
  { id: 'outgoing', name: '外向' },
  { id: 'introverted', name: '内向' },
  { id: 'sensitive', name: '感性' },
  { id: 'rational', name: '理性' },
  { id: 'careful', name: '细心' },
  { id: 'casual', name: '随性' },
  { id: 'leader', name: '主导型' },
  { id: 'cooperative', name: '配合型' },
]

export const FORTUNE_INTENSITY_LABELS = ['很轻微', '轻微', '一般', '强烈', '非常强烈'] as const

export function fortuneTypeLabel(type: FortuneType): string {
  const m: Record<FortuneType, string> = {
    daily: '今日运势',
    mood: '心情料理',
    couple: '缘分配菜',
    number: '幸运数字',
  }
  return m[type] || '占卜'
}

export function difficultyLabel(d: FortuneResult['difficulty']): string {
  const m = { easy: '简单', medium: '中等', hard: '困难' }
  return m[d] || d
}
