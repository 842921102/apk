/**
 * 各环境共用的类型与默认值（具体域名在 dev / test / prod 中覆盖）
 */

export type EnvMode = 'dev' | 'test' | 'prod'

export interface MiniappEnvConfig {
  appName: string
  /** 展示用版本标识（与 package.json 一致即可手动维护） */
  appVersionLabel: string
  requestTimeoutMs: number
  /** 是否在控制台打印较详细的环境与调试信息 */
  debugLog: boolean
  defaultHeaders: Record<string, string>
  /** Laravel API 根地址，无末尾 / */
  baseUrl: string
  /** 上传域名；与 API 同域时与 baseUrl 相同 */
  uploadUrl: string
  /** 下载域名；与 API 同域时与 baseUrl 相同 */
  downloadUrl: string
  /** WebSocket；未使用可留空字符串 */
  wsUrl: string
  /** 可选：静态资源 CDN 前缀，无则留空 */
  staticAssetBase: string
  /** 可选：远端静态 JSON（GET）；留空则仅走 Laravel GET /api/miniapp/config */
  appConfigUrl: string
}

export const baseDefaults: MiniappEnvConfig = {
  appName: '吃什么',
  appVersionLabel: '1.0.0',
  requestTimeoutMs: 60_000,
  debugLog: true,
  defaultHeaders: {
    'X-Miniapp-Client': 'mini-fan-package',
  },
  baseUrl: '',
  uploadUrl: '',
  downloadUrl: '',
  wsUrl: '',
  staticAssetBase: '',
  appConfigUrl: '',
}
