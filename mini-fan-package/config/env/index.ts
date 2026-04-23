import type { EnvMode, MiniappEnvConfig } from './base'
import { baseDefaults } from './base'
import { devConfig } from './dev'
import { testConfig } from './test'
import { prodConfig } from './prod'

// =============================================================================
// ⚠️ 唯一环境开关：上传微信「体验版 / 正式版」前务必改为 'prod' 并重新编译上传
//
//   export const ENV_MODE: EnvMode = 'dev'   // 日常本地开发
//   export const ENV_MODE: EnvMode = 'test'  // 联调 / 测试服
//   export const ENV_MODE: EnvMode = 'prod'  // 体验版、正式版
// =============================================================================
/** 使用 `as EnvMode` 避免 TS 将字面量收窄，保证 isTest / isProd 等判断类型正确 */
export const ENV_MODE = 'prod' as EnvMode

const packs: Record<EnvMode, Partial<MiniappEnvConfig>> = {
  dev: devConfig,
  test: testConfig,
  prod: prodConfig,
}

function mergeConfig(mode: EnvMode): MiniappEnvConfig {
  return { ...baseDefaults, ...packs[mode] }
}

export const config = mergeConfig(ENV_MODE)

export const baseUrl = config.baseUrl
export const uploadUrl = config.uploadUrl
export const downloadUrl = config.downloadUrl
export const wsUrl = config.wsUrl
export const staticUrl = config.staticAssetBase

export const isDev = ENV_MODE === 'dev'
export const isTest = ENV_MODE === 'test'
export const isProd = ENV_MODE === 'prod'
