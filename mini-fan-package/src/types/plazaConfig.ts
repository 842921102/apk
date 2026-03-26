/**
 * 功能广场入口（与远端 / 后台列表字段对齐；小程序内统一用此结构）
 */
export interface PlazaEntryConfig {
  /** 稳定标识，用于与本地默认合并 */
  key: string
  title: string
  subtitle?: string
  /** 图标 URL，可选 */
  icon?: string
  /** 小程序页面路径，如 /pages/today-eat/index；占位项可为空 */
  route: string
  enabled: boolean
  sort_order: number
  /** true 时展示「敬请期待」，点击不跳转 */
  coming_soon?: boolean
}
