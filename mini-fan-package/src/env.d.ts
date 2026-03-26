/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_SUPABASE_URL: string
  readonly VITE_SUPABASE_ANON_KEY: string
  /** AI 代理 BFF 根地址，如 https://api.example.com */
  readonly VITE_API_BASE_URL: string
  /** 可选：远端小程序文案配置 JSON 完整 URL */
  readonly VITE_APP_CONFIG_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<object, object, unknown>
  export default component
}
