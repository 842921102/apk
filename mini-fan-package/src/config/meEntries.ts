/**
 * 「我的」页已登录菜单（后续可接后台配置替换数组）
 */
export interface MeMenuEntry {
  id: string
  title: string
  subtitle?: string
  path: string
}

export const ME_MENU_ENTRIES: MeMenuEntry[] = [
  {
    id: 'favorites',
    title: '我的收藏',
    subtitle: '查看已保存的菜谱',
    path: '/pages/favorites/index',
  },
  {
    id: 'histories',
    title: '我的历史',
    subtitle: '最近生成记录',
    path: '/pages/histories/index',
  },
]
