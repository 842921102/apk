import { config } from '../../config/env'

/** 本地存储 key（与后续 BFF token 对齐时再统一） */
export const STORAGE_ACCESS_TOKEN = 'wte_mp_access_token'

/** 当前用户 JSON（AuthCurrentUser） */
export const STORAGE_CURRENT_USER = 'wte_mp_current_user'

/**
 * BFF / 统一 API 根地址（不要末尾 /）
 * 来源：`mini-fan-package/config/env/index.ts` 中的 `ENV_MODE`
 */
export const API_BASE_URL = config.baseUrl

/** 与 `request()` 一致的超时（毫秒） */
export const REQUEST_TIMEOUT_MS = config.requestTimeoutMs

/** 可选：远端静态 JSON 配置完整 URL（GET）；空字符串表示不走该路 */
export const APP_CONFIG_URL = config.appConfigUrl

/** `request()` 默认与业务层 header 合并 */
export const DEFAULT_HTTP_HEADERS = config.defaultHeaders
