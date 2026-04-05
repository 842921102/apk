/**
 * 个人中心主题色图标：使用本地 SVG（微信小程序对 image 的 data:image/svg+xml 支持不稳定，易出现空白）。
 */
export type MeThemedIconName =
  | 'settings'
  | 'order'
  | 'eat'
  | 'table'
  | 'fortune'
  | 'sauce'
  | 'help'
  | 'about'
  | 'agreement'
  | 'privacy'
  | 'chat'
  | 'chevronRight'

const ICON_SRC: Record<MeThemedIconName, string> = {
  settings: '/static/me-icons/settings.svg',
  order: '/static/me-icons/order.svg',
  eat: '/static/me-icons/eat.svg',
  table: '/static/me-icons/table.svg',
  fortune: '/static/me-icons/fortune.svg',
  sauce: '/static/me-icons/sauce.svg',
  help: '/static/me-icons/help.svg',
  about: '/static/me-icons/about.svg',
  agreement: '/static/me-icons/agreement.svg',
  privacy: '/static/me-icons/privacy.svg',
  chat: '/static/me-icons/chat.svg',
  chevronRight: '/static/me-icons/chevron-right.svg',
}

export function getMeThemedIconSrc(name: MeThemedIconName): string {
  return ICON_SRC[name]
}
