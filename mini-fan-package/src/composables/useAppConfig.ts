import { ref, onMounted, type Ref } from 'vue'
import { cloneAppConfigDefault, type AppConfig } from '@/config/defaultConfig'
import { getAppConfig } from '@/api/config'

/**
 * 页面统一读取应用配置：首屏为 `cloneAppConfigDefault()`，挂载后 `getAppConfig()` 合并远端并更新。
 */
export function useAppConfig(): {
  config: Ref<AppConfig>
  hydrate: () => Promise<void>
  refresh: () => Promise<void>
} {
  const config = ref<AppConfig>(cloneAppConfigDefault())

  async function hydrate() {
    const c = await getAppConfig()
    config.value = c
  }

  async function refresh() {
    const c = await getAppConfig(true)
    config.value = c
  }

  onMounted(() => {
    void hydrate()
  })

  return { config, hydrate, refresh }
}
