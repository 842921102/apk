import type { MiniappEnvConfig } from './base'

/**
 * 测试 / 联调：当前与后台开发环境保持同域。
 * 若后续拆分独立测试域名，再改为对应 https / wss 地址。
 */
const TEST_API = 'https://fanf.yajianjs.com'

export const testConfig: Partial<MiniappEnvConfig> = {
  debugLog: true,
  baseUrl: TEST_API,
  uploadUrl: TEST_API,
  downloadUrl: TEST_API,
  wsUrl: 'wss://fanf.yajianjs.com',
  appConfigUrl: '',
}
