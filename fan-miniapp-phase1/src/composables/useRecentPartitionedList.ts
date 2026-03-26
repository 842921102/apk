import { computed, type Ref, type ComputedRef } from 'vue'

/**
 * 「最近」区块：开启时前 `recentCount` 条为 recent，其余为 main；关闭时 main 为全量、recent 为空。
 * 与收藏/历史页 `show_recent_*` 行为一致。
 */
export function useRecentPartitionedList<T>(
  list: Ref<T[]>,
  showRecent: () => boolean,
  recentCount = 3,
): { recentList: ComputedRef<T[]>; mainList: ComputedRef<T[]> } {
  const recentList = computed(() => {
    if (!showRecent()) return []
    return list.value.slice(0, Math.min(recentCount, list.value.length))
  })
  const mainList = computed(() => {
    if (!showRecent()) return list.value
    return list.value.slice(recentCount)
  })
  return { recentList, mainList }
}
