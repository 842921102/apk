import { computed, type Ref, type ComputedRef } from 'vue'
import type { AppConfig } from '@/config/defaultConfig'
import type { PlazaEntryConfig } from '@/types/plazaConfig'

/** 功能广场：仅展示 enabled 的入口（排序已在配置合并阶段完成） */
export function useVisiblePlazaEntries(
  config: Ref<AppConfig>,
): ComputedRef<PlazaEntryConfig[]> {
  return computed(() => config.value.plaza_entries.filter((e) => e.enabled))
}
