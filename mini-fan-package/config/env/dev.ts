import type { MiniappEnvConfig } from './base'

/**
 * 本地开发：默认连本机 BFF（bff-server 默认端口 8800，与仓库 bff-server 一致）。
 *
 * 真机预览：请把下面地址改成电脑的局域网 IP（如 http://192.168.0.108:8800），
 * 并在微信开发者工具「详情 → 本地设置」勾选「不校验合法域名…」。
 */
const LOCAL_BFF = 'http://127.0.0.1:8800'

export const devConfig: Partial<MiniappEnvConfig> = {
  debugLog: true,
  baseUrl: LOCAL_BFF,
  uploadUrl: LOCAL_BFF,
  downloadUrl: LOCAL_BFF,
  wsUrl: '',
  appConfigUrl: '',
}
