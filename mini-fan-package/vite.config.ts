import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import uni from '@dcloudio/vite-plugin-uni'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// https://uniapp.dcloud.net.cn/collocation/vite-config.html
// 显式注入 VITE_*，避免 mp-weixin 产物里 import.meta.env 退化为 {} 导致运行异常。
// API / 静态配置 URL 以 `config/env` 为准；此处 VITE_API_BASE_URL / VITE_APP_CONFIG_URL 可为空（兼容旧 .env）。
export default defineConfig(({ mode }) => {
  // 只读 mini-fan-package 下的 .env*，避免仓库根目录 .env 里旧的 VITE_API_BASE_URL 覆盖本子工程配置
  const envDir = path.resolve(__dirname)
  const mergedEnv = loadEnv(mode, envDir, 'VITE_')
  return {
    envDir,
    plugins: [uni()],
    resolve: {
      alias: {
        /** 避免加载 @supabase/node-fetch/browser.js（真机无 window/global/self 会同步抛错） */
        '@supabase/node-fetch': path.resolve(__dirname, 'src/shims/supabase-node-fetch.ts'),
      },
    },
    define: {
      'import.meta.env.VITE_SUPABASE_URL': JSON.stringify(mergedEnv.VITE_SUPABASE_URL ?? ''),
      'import.meta.env.VITE_SUPABASE_ANON_KEY': JSON.stringify(mergedEnv.VITE_SUPABASE_ANON_KEY ?? ''),
      'import.meta.env.VITE_API_BASE_URL': JSON.stringify(mergedEnv.VITE_API_BASE_URL ?? ''),
      'import.meta.env.VITE_APP_CONFIG_URL': JSON.stringify(mergedEnv.VITE_APP_CONFIG_URL ?? ''),
    },
  }
})
