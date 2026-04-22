import type { MiniappEnvConfig } from './base'

/**
 * 生产环境（体验版 / 正式版上传前切换 ENV_MODE 为 prod）
 *
 * 当前正式主域名：fanf.yajianjs.com
 * 请确保该域名已在微信公众平台配置 request/uploadFile/downloadFile/socket 合法域名。
 */
const PROD_HOST = 'https://fanf.yajianjs.com'

export const prodConfig: Partial<MiniappEnvConfig> = {
  debugLog: false,
  baseUrl: PROD_HOST,
  uploadUrl: PROD_HOST,
  downloadUrl: PROD_HOST,
  wsUrl: 'wss://fanf.yajianjs.com',
  appConfigUrl: '',
}
