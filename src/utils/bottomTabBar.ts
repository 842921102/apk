/** 底部 Tab 对应的顶层路由（与 BottomTabBar 一致） */
export const BOTTOM_TAB_PATHS = ['/', '/today-eat', '/plaza', '/me'] as const

export function shouldShowBottomTabBar(path: string): boolean {
  // 全站固定显示底部导航；后台仍保持无底栏，避免影响管理操作区
  return !path.startsWith('/admin')
}
