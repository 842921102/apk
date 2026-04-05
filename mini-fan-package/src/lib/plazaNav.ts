/** TabBar 页面路径 → switchTab，其余 navigateTo */
const TAB_ROUTES = new Set([
  '/pages/today-eat/index',
  '/pages/inspiration/index',
  '/pages/me/index',
])

/** 小程序已注册页面（用于避免远端 plaza_entries 下发未知 route 导致真机路由报错） */
const KNOWN_PAGE_ROUTES = new Set([
  '/pages/index/index',
  '/pages/today-eat/index',
  '/pages/plaza/index',
  '/pages/me/index',
  '/pages/login/index',
  '/pages/favorites/index',
  '/pages/histories/index',
  '/pages/table-menu/index',
  '/pages/fortune-cooking/index',
  '/pages/sauce-design/index',
  '/pages/gallery/index',
  '/pages/result-detail/index',
  '/pages/profile/index',
  '/pages/inspiration/index',
  '/pages/inspiration/publish',
  '/pages/inspiration/detail',
  '/pages/circle/index',
  '/pages/circle/publish',
  '/pages/circle/detail',
])

export function plazaNavTypeForRoute(route: string): 'switchTab' | 'navigateTo' {
  const r = route.startsWith('/') ? route : `/${route}`
  if (!r || r === '/') return 'navigateTo'
  return TAB_ROUTES.has(r) ? 'switchTab' : 'navigateTo'
}

export function isKnownAppPageRoute(route: string): boolean {
  const r = route.startsWith('/') ? route : `/${route}`
  return KNOWN_PAGE_ROUTES.has(r)
}
