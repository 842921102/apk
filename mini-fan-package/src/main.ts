/** 须最先执行：修复微信小程序里 new URL(https://*.supabase.co) 抛错 → Supabase 初始化崩溃白屏 */
import 'url-polyfill'

import { createSSRApp } from 'vue'
import App from './App.vue'
import { defaultShareAppMessage, defaultShareTimeline } from '@/lib/globalShare'

export function createApp() {
  const app = createSSRApp(App)
  app.mixin({
    onShareAppMessage: defaultShareAppMessage,
    onShareTimeline: defaultShareTimeline,
  })
  return { app }
}
