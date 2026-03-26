/**
 * 功能广场入口：默认列表见 plazaEntriesDefault；运行时以 AppConfig.plaza_entries 为准（useAppConfig）
 */
import type { PlazaEntryConfig } from '@/types/plazaConfig'

export type { PlazaEntryConfig }
export type PlazaEntryItem = PlazaEntryConfig
export { PLAZA_ENTRIES_DEFAULT } from '@/config/plazaEntriesDefault'
