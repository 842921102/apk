import {
  ENV_MODE,
  config,
  baseUrl,
  uploadUrl,
  downloadUrl,
  wsUrl,
  staticUrl,
  isProd,
} from '../../config/env'

export function getEnvMode() {
  return ENV_MODE
}

export function getBaseUrl() {
  return baseUrl.trim()
}

export function getUploadUrl() {
  return uploadUrl.trim()
}

export function getDownloadUrl() {
  return downloadUrl.trim()
}

export function getWsUrl() {
  return wsUrl.trim()
}

export function isProductionEnv() {
  return isProd
}

/** 简短说明，便于页面或日志展示 */
export function getEnvSummary(): string {
  return `[${ENV_MODE}] API=${baseUrl || '(未配置)'}`
}

function joinBase(base: string, path: string): string {
  const b = base.replace(/\/$/, '')
  if (!b) return path
  const p = path.startsWith('/') ? path : `/${path}`
  return `${b}${p}`
}

/** 上传、下载接入时拼接完整 URL（相对路径） */
export function resolveUploadUrl(path: string): string {
  return joinBase(uploadUrl.trim(), path)
}

export function resolveDownloadUrl(path: string): string {
  return joinBase(downloadUrl.trim(), path)
}

/**
 * 启动时打印当前环境（生产环境仅一行关键信息，避免刷屏）
 */
export function logMiniappEnvOnLaunch(): void {
  const ver = config.appVersionLabel
  if (isProd && !config.debugLog) {
    console.log(`[mini-fan] env=${ENV_MODE} baseUrl=${baseUrl} version=${ver}`)
    return
  }
  console.log('[mini-fan] 当前环境:', ENV_MODE)
  console.log('[mini-fan] baseUrl:', baseUrl)
  console.log('[mini-fan] uploadUrl:', uploadUrl)
  console.log('[mini-fan] downloadUrl:', downloadUrl)
  if (wsUrl.trim()) console.log('[mini-fan] wsUrl:', wsUrl)
  if (staticUrl.trim()) console.log('[mini-fan] staticUrl:', staticUrl)
  console.log('[mini-fan] version:', ver)
}
