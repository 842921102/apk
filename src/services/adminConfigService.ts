/**
 * 后台配置中心 — 数据结构与服务层（第一版）。
 * 当前使用 localStorage 持久化；后续可改为 Supabase 等远端存储，保持类型与函数签名不变即可。
 */

const STORAGE_SYSTEM = 'wte_admin_system_settings_v1'
const STORAGE_MODEL = 'wte_admin_model_config_v1'

export type SystemSettings = {
  site_name: string
  site_subtitle: string
  enable_gallery: boolean
  enable_fortune: boolean
}

export type ModelConfig = {
  model_name: string
  base_url: string
  api_key: string
  temperature: number
  timeout: number
}

export const defaultSystemSettings: SystemSettings = {
  site_name: '',
  site_subtitle: '',
  enable_gallery: true,
  enable_fortune: true
}

export const defaultModelConfig: ModelConfig = {
  model_name: '',
  base_url: '',
  api_key: '',
  temperature: 0.7,
  timeout: 60_000
}

function mergeSystem(partial: Partial<SystemSettings>): SystemSettings {
  return { ...defaultSystemSettings, ...partial }
}

export function loadSystemSettings(): SystemSettings {
  try {
    const raw = localStorage.getItem(STORAGE_SYSTEM)
    if (!raw) return { ...defaultSystemSettings }
    const parsed = JSON.parse(raw) as Partial<SystemSettings>
    return mergeSystem({
      site_name: typeof parsed.site_name === 'string' ? parsed.site_name : defaultSystemSettings.site_name,
      site_subtitle:
        typeof parsed.site_subtitle === 'string' ? parsed.site_subtitle : defaultSystemSettings.site_subtitle,
      enable_gallery:
        typeof parsed.enable_gallery === 'boolean' ? parsed.enable_gallery : defaultSystemSettings.enable_gallery,
      enable_fortune:
        typeof parsed.enable_fortune === 'boolean' ? parsed.enable_fortune : defaultSystemSettings.enable_fortune
    })
  } catch {
    return { ...defaultSystemSettings }
  }
}

export function saveSystemSettings(settings: SystemSettings): void {
  const payload: SystemSettings = {
    site_name: settings.site_name ?? '',
    site_subtitle: settings.site_subtitle ?? '',
    enable_gallery: Boolean(settings.enable_gallery),
    enable_fortune: Boolean(settings.enable_fortune)
  }
  localStorage.setItem(STORAGE_SYSTEM, JSON.stringify(payload))
}

export function loadModelConfig(): ModelConfig {
  try {
    const raw = localStorage.getItem(STORAGE_MODEL)
    if (!raw) return { ...defaultModelConfig }
    const parsed = JSON.parse(raw) as Partial<ModelConfig>
    const temperature =
      typeof parsed.temperature === 'number' && !Number.isNaN(parsed.temperature)
        ? Math.min(2, Math.max(0, parsed.temperature))
        : defaultModelConfig.temperature
    const timeout =
      typeof parsed.timeout === 'number' && !Number.isNaN(parsed.timeout) && parsed.timeout >= 1000
        ? Math.floor(parsed.timeout)
        : defaultModelConfig.timeout
    return {
      ...defaultModelConfig,
      model_name: typeof parsed.model_name === 'string' ? parsed.model_name : defaultModelConfig.model_name,
      base_url: typeof parsed.base_url === 'string' ? parsed.base_url : defaultModelConfig.base_url,
      api_key: typeof parsed.api_key === 'string' ? parsed.api_key : defaultModelConfig.api_key,
      temperature,
      timeout
    }
  } catch {
    return { ...defaultModelConfig }
  }
}

export function saveModelConfig(config: ModelConfig): void {
  const temperature =
    typeof config.temperature === 'number' && !Number.isNaN(config.temperature)
      ? Math.min(2, Math.max(0, config.temperature))
      : defaultModelConfig.temperature
  const timeout =
    typeof config.timeout === 'number' && !Number.isNaN(config.timeout) && config.timeout >= 1000
      ? Math.floor(config.timeout)
      : defaultModelConfig.timeout
  const payload: ModelConfig = {
    model_name: config.model_name ?? '',
    base_url: config.base_url ?? '',
    api_key: config.api_key ?? '',
    temperature,
    timeout
  }
  localStorage.setItem(STORAGE_MODEL, JSON.stringify(payload))
}
