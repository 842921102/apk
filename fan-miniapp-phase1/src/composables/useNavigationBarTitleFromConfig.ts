import { watch, type Ref } from 'vue'
import type { AppConfig } from '@/config/defaultConfig'

/** 与 `pages.json` 默认文案对齐的可配置导航标题字段 */
export type AppConfigNavigationTitleKey = 'favorites_title' | 'histories_title'

/**
 * 配置加载后同步原生导航栏标题（失败静默）
 */
export function useNavigationBarTitleFromConfig(
  config: Ref<AppConfig>,
  key: AppConfigNavigationTitleKey,
) {
  watch(
    () => config.value[key],
    (t) => {
      if (typeof t !== 'string' || !t.trim()) return
      try {
        uni.setNavigationBarTitle({ title: t.trim() })
      } catch {
        /* ignore */
      }
    },
    { immediate: true },
  )
}
