/**
 * 小程序配置 — 读取链路收口（页面勿在此解析 JSON / 勿手写合并规则）
 *
 * ## 链路总览
 * 1. `defaultConfig.cloneAppConfigDefault()`：首屏与异常时的完整默认快照
 * 2. `fetchRemoteAppConfig()`：并行 `GET {API_BASE_URL}/api/miniapp/config` + 可选 `APP_CONFIG_URL`
 * 3. `configRemoteMap.parseRemoteConfigPayload(body)`：包装层剥离、分组展开、camelCase、plaza 数组 → `Partial<AppConfig>`
 * 4. `applyRemotePartialToAppConfig(base, partial)`：按 `APP_CONFIG_*_KEYS` 合并字符串/布尔；`plaza_entries` 走 `mergePlazaWithDefaults`（含 sort_order 排序）
 * 5. `getAppConfig()`：内存缓存 + 单飞请求，供 `useAppConfig` 与各页消费
 *
 * ## 职责边界
 * | 模块 | 职责 |
 * |------|------|
 * | `config/defaultConfig.ts` | 默认值、`AppConfig` 类型、字段清单、`pickAppConfigPartial`、`parseBooleanLike` |
 * | `api/configRemoteMap.ts` | 仅负责「远端形状 → Partial<AppConfig>」，不发起网络 |
 * | `lib/plazaEntriesMerge.ts` | 广场项别名、sort_order、`mergePlazaWithDefaults` |
 * | `composables/useAppConfig.ts` | 页面注入 `Ref<AppConfig>`，挂载后 hydrate |
 * | `composables/useNavigationBarTitleFromConfig` 等 | 导航栏标题、列表分区等 UI 辅助，不修改配置语义 |
 */
import { request } from '@/api/http'
import { API_BASE_URL, APP_CONFIG_URL } from '@/constants'
import { parseRemoteConfigPayload } from '@/api/configRemoteMap'
import {
  APP_CONFIG_BOOLEAN_KEYS,
  APP_CONFIG_DEFAULT,
  APP_CONFIG_STRING_KEYS,
  cloneAppConfigDefault,
  type AppConfig,
} from '@/config/defaultConfig'
import {
  clonePlazaEntries,
  mergePlazaWithDefaults,
} from '@/lib/plazaEntriesMerge'

/**
 * 将远端解析得到的 partial 合并进一份完整 base（不修改入参 base）
 */
function applyRemotePartialToAppConfig(
  base: AppConfig,
  patch: Partial<AppConfig>,
): AppConfig {
  const out: AppConfig = {
    ...base,
    plaza_entries: clonePlazaEntries(base.plaza_entries),
  }
  for (const k of APP_CONFIG_STRING_KEYS) {
    const v = patch[k]
    if (typeof v === 'string' && v.trim() !== '') {
      out[k] = v.trim()
    }
  }
  for (const k of APP_CONFIG_BOOLEAN_KEYS) {
    if (Object.prototype.hasOwnProperty.call(patch, k)) {
      const v = patch[k]
      if (typeof v === 'boolean') {
        out[k] = v
      }
    }
  }

  if (
    patch.plaza_entries &&
    Array.isArray(patch.plaza_entries) &&
    patch.plaza_entries.length > 0
  ) {
    out.plaza_entries = mergePlazaWithDefaults(
      clonePlazaEntries(APP_CONFIG_DEFAULT.plaza_entries),
      patch.plaza_entries,
    )
  }

  return out
}

/** 重新导出，供测试或类型外工具挑字段 */
export { pickAppConfigPartial } from '@/config/defaultConfig'

/** BFF：真实业务远端配置（失败返回 {}，不抛错） */
async function fetchBffMiniappConfig(): Promise<Partial<AppConfig>> {
  if (!API_BASE_URL.trim()) return {}
  try {
    const data = await request<unknown>({
      url: '/api/miniapp/config',
      method: 'GET',
    })
    return parseRemoteConfigPayload(data)
  } catch {
    return {}
  }
}

/** 静态 JSON URL（CDN / OSS 等，失败返回 {}） */
async function fetchStaticAppConfigUrl(): Promise<Partial<AppConfig>> {
  const url = APP_CONFIG_URL.trim()
  if (!url) return {}

  return new Promise((resolve) => {
    uni.request({
      url,
      method: 'GET',
      success: (res) => {
        const status = res.statusCode ?? 0
        if (status < 200 || status >= 300) {
          resolve({})
          return
        }
        resolve(parseRemoteConfigPayload(res.data))
      },
      fail: () => resolve({}),
    })
  })
}

/**
 * 并行拉取两路远端，BFF 结果覆盖静态 URL；任一路失败不影响另一路。
 */
export async function fetchRemoteAppConfig(): Promise<Partial<AppConfig>> {
  const [fromBff, fromStatic] = await Promise.all([
    fetchBffMiniappConfig(),
    fetchStaticAppConfigUrl(),
  ])
  return { ...fromStatic, ...fromBff }
}

/** 默认快照 + 远端 partial → 完整配置；永不抛错 */
export async function loadAppConfig(): Promise<AppConfig> {
  try {
    const remote = await fetchRemoteAppConfig()
    return applyRemotePartialToAppConfig(cloneAppConfigDefault(), remote)
  } catch {
    return cloneAppConfigDefault()
  }
}

let cache: AppConfig | null = null
let inflight: Promise<AppConfig> | null = null

/**
 * 带内存缓存（多 Tab 页共用一次远端请求）
 * @param forceRefresh 为 true 时清空缓存重新拉取
 */
export function getAppConfig(forceRefresh = false): Promise<AppConfig> {
  if (forceRefresh) {
    cache = null
    inflight = null
  }
  if (cache) return Promise.resolve(cache)
  if (inflight) return inflight

  inflight = loadAppConfig()
    .then((c) => {
      cache = c
      inflight = null
      return c
    })
    .catch(() => {
      inflight = null
      const fallback = cloneAppConfigDefault()
      cache = fallback
      return fallback
    })

  return inflight
}

export function invalidateAppConfigCache(): void {
  cache = null
  inflight = null
}
