/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_SUPABASE_URL: string
  readonly VITE_SUPABASE_ANON_KEY: string
  /**
   * 已由 `config/env` 接管；Vite 仍可能注入空字符串以保持兼容。
   * 业务侧请使用 `@/constants` 的 `API_BASE_URL`。
   */
  readonly VITE_API_BASE_URL: string
  /**
   * 已由 `config/env` 中 `appConfigUrl` 接管；此处可为空。
   * 业务侧请使用 `@/constants` 的 `APP_CONFIG_URL`。
   */
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
