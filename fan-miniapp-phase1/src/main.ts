/** 须最先执行：修复微信小程序里 new URL(https://*.supabase.co) 抛错 → Supabase 初始化崩溃白屏 */
import 'url-polyfill'

import { createSSRApp } from 'vue'
import App from './App.vue'

export function createApp() {
  const app = createSSRApp(App)
  return { app }
}
