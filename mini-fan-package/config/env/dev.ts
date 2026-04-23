import type { MiniappEnvConfig } from './base'

/**
 * 本地开发：默认连本机 Laravel（`php artisan serve` 默认 http://127.0.0.1:8000）。
 *
 * 真机预览：请把下面地址改成电脑的局域网 IP（如 http://192.168.0.108:8000），
 * 并在微信开发者工具「详情 → 本地设置」勾选「不校验合法域名…」。
 */
const LOCAL_LARAVEL = 'http://127.0.0.1:8000'

export const devConfig: Partial<MiniappEnvConfig> = {
  debugLog: true,
  baseUrl: LOCAL_LARAVEL,
  uploadUrl: LOCAL_LARAVEL,
  downloadUrl: LOCAL_LARAVEL,
  wsUrl: '',
  appConfigUrl: '',
}
