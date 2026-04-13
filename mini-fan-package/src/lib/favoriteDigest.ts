/**
 * 为「今日菜单 / 灵感厨房 / 灵魂蘸料 / 家常好菜」等生成稳定 source_id（FNV-1a 32-bit）。
 * 图鉴等已有业务主键的页面请直接用图片 id，勿用本函数。
 */
export function favoriteContentDigest(title: string, recipeContent: string): string {
  const s = `${title}\n${recipeContent}`
  let h = 2166136261 >>> 0
  for (let i = 0; i < s.length; i++) {
    h ^= s.charCodeAt(i)
    h = Math.imul(h, 16777619) >>> 0
  }
  return 'd' + h.toString(16).padStart(8, '0')
}
