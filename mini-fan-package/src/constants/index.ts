/** 本地存储 key（与后续 BFF token 对齐时再统一） */
export const STORAGE_ACCESS_TOKEN = 'wte_mp_access_token'

/** 当前用户 JSON（AuthCurrentUser） */
export const STORAGE_CURRENT_USER = 'wte_mp_current_user'

/** AI 代理 / BFF 根地址，如 https://api.example.com（不要末尾 /） */
// 注意：这里不要用可选链（`?.`），否则 Vite define 注入的
// `import.meta.env.VITE_API_BASE_URL` 可能无法被静态替换，导致运行时退化为空字符串。
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || ''
