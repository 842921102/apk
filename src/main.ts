import { createApp } from 'vue'
import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import { Capacitor } from '@capacitor/core'
import App from './App.vue'
import Home from './views/Home.vue'
import About from './views/About.vue'
import TodayEat from './views/TodayEat.vue'
import TableDesign from './views/TableDesign.vue'
import Favorites from './views/Favorites.vue'
import Gallery from './views/Gallery.vue'
import HowToCook from './views/HowToCook.vue'
import SauceDesign from './views/SauceDesign.vue'
import FortuneCooking from './views/FortuneCooking.vue'
import SettingsDemo from './views/SettingsDemo.vue'
import Login from './views/Login.vue'
import Histories from './views/Histories.vue'
import Me from './views/Me.vue'
import Plaza from './views/Plaza.vue'
import Collect from './views/Collect.vue'
import { autoRefreshEnvSettings } from './utils/envWatcher'
import { supabase } from './lib/supabase'
import { getCurrentUserRole } from './services/userProfileService'
import { canAccessAdmin } from './lib/appRoles'
import { showAppToast } from './utils/showAppToast'
import './style.css'

const routes = [
  {
    path: '/',
    component: Home
  },
  {
    path: '/login',
    component: Login
  },
  {
    path: '/about',
    component: About
  },
  {
    path: '/today-eat',
    component: TodayEat
  },
  {
    path: '/table-design',
    component: TableDesign
  },
  {
    path: '/plaza',
    component: Plaza
  },
  {
    path: '/collect',
    component: Collect,
    meta: { requiresAuth: true }
  },
  {
    path: '/favorites',
    component: Favorites,
    meta: { requiresAuth: true }
  },
  {
    path: '/me',
    component: Me
  },
  {
    path: '/histories',
    component: Histories,
    meta: { requiresAuth: true }
  },
  {
    path: '/gallery',
    component: Gallery
  },
  {
    path: '/how-to-cook',
    component: HowToCook
  },
  {
    path: '/sauce-design',
    component: SauceDesign
  },
  {
    path: '/fortune-cooking',
    component: FortuneCooking
  },
  {
    path: '/settings',
    component: SettingsDemo
  },
  {
    path: '/settings-demo',
    redirect: '/settings'
  },
  {
    path: '/admin',
    name: 'Admin',
    component: () => import('@/views/Admin.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: Capacitor.isNativePlatform() ? createWebHashHistory() : createWebHistory(),
  routes
})

const PAGE_TITLE_MAP: Record<string, string> = {
  '/': '饭否',
  '/today-eat': '今天吃什么',
  '/plaza': '功能广场',
  '/table-design': '一桌好菜',
  '/sauce-design': '酱料设计',
  '/how-to-cook': '菜谱教学',
  '/fortune-cooking': '玄学厨房',
  '/gallery': '封神图鉴',
  '/collect': '我的收藏',
  '/favorites': '我的收藏',
  '/histories': '历史记录',
  '/me': '我的',
  '/settings': '系统设置',
  '/login': '登录 / 注册',
  '/about': '关于我们',
  '/admin': '管理后台'
}

// 登录拦截
router.beforeEach(async (to, _from, next) => {
  const requiresAuth = to.matched.some(record => record.meta?.requiresAuth)

  if (!requiresAuth) {
    next()
    return
  }

  try {
    const { data, error } = await supabase.auth.getUser()
    const loginRedirect = { path: '/login', query: { redirect: to.fullPath, reason: 'auth' } }

    if (error) {
      console.error('获取用户信息失败:', error)
      next(loginRedirect)
      return
    }

    if (!data.user) {
      next(loginRedirect)
      return
    }

    if (to.path === '/admin') {
      const role = await getCurrentUserRole()
      if (!canAccessAdmin(role)) {
        showAppToast('当前账号暂无后台权限', 'warning')
        next('/')
        return
      }
    }

    next()
  } catch (error) {
    console.error('路由守卫异常:', error)
    next({ path: '/login', query: { redirect: to.fullPath, reason: 'auth' } })
  }
})

router.afterEach(to => {
  const pageTitle = PAGE_TITLE_MAP[to.path] || '饭否'
  document.title = `${pageTitle} - 饭否`
})

const app = createApp(App)

app.use(router)

autoRefreshEnvSettings()

app.mount('#app')