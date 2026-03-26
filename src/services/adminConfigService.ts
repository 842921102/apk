/**
 * 后台配置中心 — 数据结构与服务层（第一版）。
 * 当前使用 localStorage 持久化；后续可改为 Supabase 等远端存储，保持类型与函数签名不变即可。
 */

const STORAGE_SYSTEM = 'wte_admin_system_settings_v1'
const STORAGE_MODEL = 'wte_admin_model_config_v1'
const STORAGE_FRONTEND = 'wte_admin_frontend_config_v1'

export type SystemSettings = {
  site_name: string
  site_subtitle: string
  enable_gallery: boolean
  enable_fortune: boolean
}

export type FrontendConfig = {
  app_name: string
  home_title: string
  home_subtitle: string
  home_banner_title: string
  home_banner_subtitle: string
  show_home_banner: boolean
  home_recommend_title: string
  home_recommend_subtitle: string
  show_home_recommend: boolean
  home_hot_title: string
  home_hot_subtitle: string
  show_home_hot: boolean
  plaza_title: string
  plaza_subtitle: string
  profile_welcome_text: string
  enable_fortune: boolean
  enable_gallery: boolean
  enable_sauce: boolean
  enable_table_design: boolean
  plaza_entries: PlazaEntryConfig[]
  profile_title: string
  profile_subtitle: string
  profile_guest_title: string
  profile_guest_subtitle: string
  show_profile_favorites: boolean
  show_profile_histories: boolean
  show_profile_settings: boolean
  show_profile_admin_entry: boolean
  profile_favorites_sort: number
  profile_histories_sort: number
  profile_settings_sort: number
  profile_admin_entry_sort: number
  profile_admin_tip: string
  favorites_title: string
  favorites_subtitle: string
  favorites_empty_title: string
  favorites_empty_subtitle: string
  favorites_empty_button_text: string
  histories_title: string
  histories_subtitle: string
  histories_empty_title: string
  histories_empty_subtitle: string
  histories_empty_button_text: string
  show_recent_favorites: boolean
  show_recent_histories: boolean
  login_prompt_title: string
  login_prompt_subtitle: string
  login_button_text: string
  toast_favorite_success: string
  toast_favorite_cancel: string
  toast_history_deleted: string
  toast_favorite_deleted: string
  toast_save_success: string
  toast_save_failed: string
  toast_load_failed: string
  common_empty_title: string
  common_empty_subtitle: string
  common_empty_button_text: string
}

export type PlazaEntryConfig = {
  key: string
  title: string
  subtitle: string
  icon: string
  route: string
  enabled: boolean
  sort_order: number
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

export const defaultFrontendConfig: FrontendConfig = {
  app_name: '饭否',
  home_title: '饭否',
  home_subtitle: '今天想吃点什么？说说冰箱里的食材，大师帮你配好这一餐',
  home_banner_title: '今晚吃什么，一键给你完整思路',
  home_banner_subtitle: '食材、菜系、步骤一次配齐，10 秒就能开做。',
  show_home_banner: true,
  home_recommend_title: '今日推荐',
  home_recommend_subtitle: '今晚就做这三道',
  show_home_recommend: true,
  home_hot_title: '热门玩法',
  home_hot_subtitle: '大家都在用',
  show_home_hot: true,
  plaza_title: '功能广场',
  plaza_subtitle: '选一站入口，把「今天玩什么」交给灵感 ✨',
  profile_welcome_text: '你好呀',
  enable_fortune: true,
  enable_gallery: true,
  enable_sauce: true,
  enable_table_design: true,
  profile_title: '开启你的美食生活',
  profile_subtitle: '你好呀',
  profile_guest_title: '登录体验完整功能',
  profile_guest_subtitle: '同步收藏、历史记录与个性化设置。',
  show_profile_favorites: true,
  show_profile_histories: true,
  show_profile_settings: true,
  show_profile_admin_entry: true,
  profile_favorites_sort: 10,
  profile_histories_sort: 20,
  profile_settings_sort: 30,
  profile_admin_entry_sort: 40,
  profile_admin_tip: '你拥有后台访问能力，可查看运营数据与配置项；前台体验仍与普通用户一致。',
  favorites_title: '我的收藏',
  favorites_subtitle: '喜欢的菜谱会保存在这里，随时可以回看和复刻。',
  favorites_empty_title: '暂无收藏',
  favorites_empty_subtitle: '在首页或生成结果里点亮爱心，菜谱会同步保存在这里，随时打开回顾。',
  favorites_empty_button_text: '去首页找菜谱',
  histories_title: '最近生成记录',
  histories_subtitle: '每一次生成都会保留在这里，方便你回看、复刻和优化。',
  histories_empty_title: '暂无历史记录',
  histories_empty_subtitle: '去首页添加食材并生成菜谱，每一次创作都会自动同步到这里，方便你回看与复用。',
  histories_empty_button_text: '去首页创作',
  show_recent_favorites: true,
  show_recent_histories: true,
  login_prompt_title: '登录体验完整功能',
  login_prompt_subtitle: '同步收藏、历史记录与个性化设置。',
  login_button_text: '去登录',
  toast_favorite_success: '已添加到收藏',
  toast_favorite_cancel: '已取消收藏',
  toast_history_deleted: '历史记录已删除',
  toast_favorite_deleted: '收藏已删除',
  toast_save_success: '保存成功',
  toast_save_failed: '保存失败，请稍后再试',
  toast_load_failed: '加载失败，请稍后重试',
  common_empty_title: '暂无内容',
  common_empty_subtitle: '当前还没有可展示的数据，稍后再来看看。',
  common_empty_button_text: '返回首页',
  plaza_entries: [
    {
      key: 'table_design',
      title: '满汉全席',
      subtitle: '一桌好菜，搭配用餐小剧本',
      icon: 'utensils',
      route: '/table-design',
      enabled: true,
      sort_order: 10
    },
    {
      key: 'sauce_design',
      title: '酱料大师',
      subtitle: '调味灵感与酱料设计',
      icon: 'droplet',
      route: '/sauce-design',
      enabled: true,
      sort_order: 20
    },
    {
      key: 'fortune_cooking',
      title: '玄学厨房',
      subtitle: '趣味运势与今日菜色',
      icon: 'moon',
      route: '/fortune-cooking',
      enabled: true,
      sort_order: 30
    },
    {
      key: 'gallery',
      title: '封神图鉴',
      subtitle: '美食图鉴随心浏览',
      icon: 'image',
      route: '/gallery',
      enabled: true,
      sort_order: 40
    },
    {
      key: 'how_to_cook',
      title: '菜谱教学',
      subtitle: '步骤清晰，跟做不慌',
      icon: 'bookOpen',
      route: '/how-to-cook',
      enabled: true,
      sort_order: 50
    }
  ]
}

function mergeSystem(partial: Partial<SystemSettings>): SystemSettings {
  return { ...defaultSystemSettings, ...partial }
}

function mergeFrontend(partial: Partial<FrontendConfig>): FrontendConfig {
  return { ...defaultFrontendConfig, ...partial }
}

function normalizePlazaEntries(entries: unknown): PlazaEntryConfig[] {
  const defaults = defaultFrontendConfig.plaza_entries
  if (!Array.isArray(entries)) return defaults.map(item => ({ ...item }))

  const byKey = new Map<string, Partial<PlazaEntryConfig>>()
  entries.forEach(item => {
    if (!item || typeof item !== 'object') return
    const row = item as Partial<PlazaEntryConfig>
    if (typeof row.key !== 'string' || !row.key.trim()) return
    byKey.set(row.key, row)
  })

  return defaults.map((base, index) => {
    const current = byKey.get(base.key) ?? {}
    return {
      key: base.key,
      title: typeof current.title === 'string' ? current.title : base.title,
      subtitle: typeof current.subtitle === 'string' ? current.subtitle : base.subtitle,
      icon: typeof current.icon === 'string' ? current.icon : base.icon,
      route: typeof current.route === 'string' ? current.route : base.route,
      enabled: typeof current.enabled === 'boolean' ? current.enabled : base.enabled,
      sort_order:
        typeof current.sort_order === 'number' && Number.isFinite(current.sort_order)
          ? Math.floor(current.sort_order)
          : base.sort_order ?? (index + 1) * 10
    }
  })
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

export function loadFrontendConfig(): FrontendConfig {
  try {
    const raw = localStorage.getItem(STORAGE_FRONTEND)
    if (!raw) return { ...defaultFrontendConfig }
    const parsed = JSON.parse(raw) as Partial<FrontendConfig>
    return mergeFrontend({
      app_name: typeof parsed.app_name === 'string' ? parsed.app_name : defaultFrontendConfig.app_name,
      home_title: typeof parsed.home_title === 'string' ? parsed.home_title : defaultFrontendConfig.home_title,
      home_subtitle:
        typeof parsed.home_subtitle === 'string' ? parsed.home_subtitle : defaultFrontendConfig.home_subtitle,
      home_banner_title:
        typeof parsed.home_banner_title === 'string'
          ? parsed.home_banner_title
          : defaultFrontendConfig.home_banner_title,
      home_banner_subtitle:
        typeof parsed.home_banner_subtitle === 'string'
          ? parsed.home_banner_subtitle
          : defaultFrontendConfig.home_banner_subtitle,
      show_home_banner:
        typeof parsed.show_home_banner === 'boolean'
          ? parsed.show_home_banner
          : defaultFrontendConfig.show_home_banner,
      home_recommend_title:
        typeof parsed.home_recommend_title === 'string'
          ? parsed.home_recommend_title
          : defaultFrontendConfig.home_recommend_title,
      home_recommend_subtitle:
        typeof parsed.home_recommend_subtitle === 'string'
          ? parsed.home_recommend_subtitle
          : defaultFrontendConfig.home_recommend_subtitle,
      show_home_recommend:
        typeof parsed.show_home_recommend === 'boolean'
          ? parsed.show_home_recommend
          : defaultFrontendConfig.show_home_recommend,
      home_hot_title:
        typeof parsed.home_hot_title === 'string' ? parsed.home_hot_title : defaultFrontendConfig.home_hot_title,
      home_hot_subtitle:
        typeof parsed.home_hot_subtitle === 'string'
          ? parsed.home_hot_subtitle
          : defaultFrontendConfig.home_hot_subtitle,
      show_home_hot:
        typeof parsed.show_home_hot === 'boolean' ? parsed.show_home_hot : defaultFrontendConfig.show_home_hot,
      plaza_title: typeof parsed.plaza_title === 'string' ? parsed.plaza_title : defaultFrontendConfig.plaza_title,
      plaza_subtitle:
        typeof parsed.plaza_subtitle === 'string' ? parsed.plaza_subtitle : defaultFrontendConfig.plaza_subtitle,
      profile_welcome_text:
        typeof parsed.profile_welcome_text === 'string'
          ? parsed.profile_welcome_text
          : defaultFrontendConfig.profile_welcome_text,
      enable_fortune:
        typeof parsed.enable_fortune === 'boolean' ? parsed.enable_fortune : defaultFrontendConfig.enable_fortune,
      enable_gallery:
        typeof parsed.enable_gallery === 'boolean' ? parsed.enable_gallery : defaultFrontendConfig.enable_gallery,
      enable_sauce:
        typeof parsed.enable_sauce === 'boolean' ? parsed.enable_sauce : defaultFrontendConfig.enable_sauce,
      enable_table_design:
        typeof parsed.enable_table_design === 'boolean'
          ? parsed.enable_table_design
          : defaultFrontendConfig.enable_table_design,
      plaza_entries: normalizePlazaEntries(parsed.plaza_entries),
      profile_title:
        typeof parsed.profile_title === 'string' ? parsed.profile_title : defaultFrontendConfig.profile_title,
      profile_subtitle:
        typeof parsed.profile_subtitle === 'string'
          ? parsed.profile_subtitle
          : defaultFrontendConfig.profile_subtitle,
      profile_guest_title:
        typeof parsed.profile_guest_title === 'string'
          ? parsed.profile_guest_title
          : defaultFrontendConfig.profile_guest_title,
      profile_guest_subtitle:
        typeof parsed.profile_guest_subtitle === 'string'
          ? parsed.profile_guest_subtitle
          : defaultFrontendConfig.profile_guest_subtitle,
      show_profile_favorites:
        typeof parsed.show_profile_favorites === 'boolean'
          ? parsed.show_profile_favorites
          : defaultFrontendConfig.show_profile_favorites,
      show_profile_histories:
        typeof parsed.show_profile_histories === 'boolean'
          ? parsed.show_profile_histories
          : defaultFrontendConfig.show_profile_histories,
      show_profile_settings:
        typeof parsed.show_profile_settings === 'boolean'
          ? parsed.show_profile_settings
          : defaultFrontendConfig.show_profile_settings,
      show_profile_admin_entry:
        typeof parsed.show_profile_admin_entry === 'boolean'
          ? parsed.show_profile_admin_entry
          : defaultFrontendConfig.show_profile_admin_entry,
      profile_favorites_sort:
        typeof parsed.profile_favorites_sort === 'number' && Number.isFinite(parsed.profile_favorites_sort)
          ? Math.floor(parsed.profile_favorites_sort)
          : defaultFrontendConfig.profile_favorites_sort,
      profile_histories_sort:
        typeof parsed.profile_histories_sort === 'number' && Number.isFinite(parsed.profile_histories_sort)
          ? Math.floor(parsed.profile_histories_sort)
          : defaultFrontendConfig.profile_histories_sort,
      profile_settings_sort:
        typeof parsed.profile_settings_sort === 'number' && Number.isFinite(parsed.profile_settings_sort)
          ? Math.floor(parsed.profile_settings_sort)
          : defaultFrontendConfig.profile_settings_sort,
      profile_admin_entry_sort:
        typeof parsed.profile_admin_entry_sort === 'number' && Number.isFinite(parsed.profile_admin_entry_sort)
          ? Math.floor(parsed.profile_admin_entry_sort)
          : defaultFrontendConfig.profile_admin_entry_sort,
      profile_admin_tip:
        typeof parsed.profile_admin_tip === 'string'
          ? parsed.profile_admin_tip
          : defaultFrontendConfig.profile_admin_tip,
      favorites_title:
        typeof parsed.favorites_title === 'string'
          ? parsed.favorites_title
          : defaultFrontendConfig.favorites_title,
      favorites_subtitle:
        typeof parsed.favorites_subtitle === 'string'
          ? parsed.favorites_subtitle
          : defaultFrontendConfig.favorites_subtitle,
      favorites_empty_title:
        typeof parsed.favorites_empty_title === 'string'
          ? parsed.favorites_empty_title
          : defaultFrontendConfig.favorites_empty_title,
      favorites_empty_subtitle:
        typeof parsed.favorites_empty_subtitle === 'string'
          ? parsed.favorites_empty_subtitle
          : defaultFrontendConfig.favorites_empty_subtitle,
      favorites_empty_button_text:
        typeof parsed.favorites_empty_button_text === 'string'
          ? parsed.favorites_empty_button_text
          : defaultFrontendConfig.favorites_empty_button_text,
      histories_title:
        typeof parsed.histories_title === 'string'
          ? parsed.histories_title
          : defaultFrontendConfig.histories_title,
      histories_subtitle:
        typeof parsed.histories_subtitle === 'string'
          ? parsed.histories_subtitle
          : defaultFrontendConfig.histories_subtitle,
      histories_empty_title:
        typeof parsed.histories_empty_title === 'string'
          ? parsed.histories_empty_title
          : defaultFrontendConfig.histories_empty_title,
      histories_empty_subtitle:
        typeof parsed.histories_empty_subtitle === 'string'
          ? parsed.histories_empty_subtitle
          : defaultFrontendConfig.histories_empty_subtitle,
      histories_empty_button_text:
        typeof parsed.histories_empty_button_text === 'string'
          ? parsed.histories_empty_button_text
          : defaultFrontendConfig.histories_empty_button_text,
      show_recent_favorites:
        typeof parsed.show_recent_favorites === 'boolean'
          ? parsed.show_recent_favorites
          : defaultFrontendConfig.show_recent_favorites,
      show_recent_histories:
        typeof parsed.show_recent_histories === 'boolean'
          ? parsed.show_recent_histories
          : defaultFrontendConfig.show_recent_histories,
      login_prompt_title:
        typeof parsed.login_prompt_title === 'string'
          ? parsed.login_prompt_title
          : defaultFrontendConfig.login_prompt_title,
      login_prompt_subtitle:
        typeof parsed.login_prompt_subtitle === 'string'
          ? parsed.login_prompt_subtitle
          : defaultFrontendConfig.login_prompt_subtitle,
      login_button_text:
        typeof parsed.login_button_text === 'string'
          ? parsed.login_button_text
          : defaultFrontendConfig.login_button_text,
      toast_favorite_success:
        typeof parsed.toast_favorite_success === 'string'
          ? parsed.toast_favorite_success
          : defaultFrontendConfig.toast_favorite_success,
      toast_favorite_cancel:
        typeof parsed.toast_favorite_cancel === 'string'
          ? parsed.toast_favorite_cancel
          : defaultFrontendConfig.toast_favorite_cancel,
      toast_history_deleted:
        typeof parsed.toast_history_deleted === 'string'
          ? parsed.toast_history_deleted
          : defaultFrontendConfig.toast_history_deleted,
      toast_favorite_deleted:
        typeof parsed.toast_favorite_deleted === 'string'
          ? parsed.toast_favorite_deleted
          : defaultFrontendConfig.toast_favorite_deleted,
      toast_save_success:
        typeof parsed.toast_save_success === 'string'
          ? parsed.toast_save_success
          : defaultFrontendConfig.toast_save_success,
      toast_save_failed:
        typeof parsed.toast_save_failed === 'string'
          ? parsed.toast_save_failed
          : defaultFrontendConfig.toast_save_failed,
      toast_load_failed:
        typeof parsed.toast_load_failed === 'string'
          ? parsed.toast_load_failed
          : defaultFrontendConfig.toast_load_failed,
      common_empty_title:
        typeof parsed.common_empty_title === 'string'
          ? parsed.common_empty_title
          : defaultFrontendConfig.common_empty_title,
      common_empty_subtitle:
        typeof parsed.common_empty_subtitle === 'string'
          ? parsed.common_empty_subtitle
          : defaultFrontendConfig.common_empty_subtitle,
      common_empty_button_text:
        typeof parsed.common_empty_button_text === 'string'
          ? parsed.common_empty_button_text
          : defaultFrontendConfig.common_empty_button_text
    })
  } catch {
    return { ...defaultFrontendConfig }
  }
}

export function saveFrontendConfig(config: FrontendConfig): void {
  const payload: FrontendConfig = {
    app_name: config.app_name ?? '',
    home_title: config.home_title ?? '',
    home_subtitle: config.home_subtitle ?? '',
    home_banner_title: config.home_banner_title ?? '',
    home_banner_subtitle: config.home_banner_subtitle ?? '',
    show_home_banner: Boolean(config.show_home_banner),
    home_recommend_title: config.home_recommend_title ?? '',
    home_recommend_subtitle: config.home_recommend_subtitle ?? '',
    show_home_recommend: Boolean(config.show_home_recommend),
    home_hot_title: config.home_hot_title ?? '',
    home_hot_subtitle: config.home_hot_subtitle ?? '',
    show_home_hot: Boolean(config.show_home_hot),
    plaza_title: config.plaza_title ?? '',
    plaza_subtitle: config.plaza_subtitle ?? '',
    profile_welcome_text: config.profile_welcome_text ?? '',
    enable_fortune: Boolean(config.enable_fortune),
    enable_gallery: Boolean(config.enable_gallery),
    enable_sauce: Boolean(config.enable_sauce),
    enable_table_design: Boolean(config.enable_table_design),
    plaza_entries: normalizePlazaEntries(config.plaza_entries).sort((a, b) => a.sort_order - b.sort_order),
    profile_title: config.profile_title ?? '',
    profile_subtitle: config.profile_subtitle ?? '',
    profile_guest_title: config.profile_guest_title ?? '',
    profile_guest_subtitle: config.profile_guest_subtitle ?? '',
    show_profile_favorites: Boolean(config.show_profile_favorites),
    show_profile_histories: Boolean(config.show_profile_histories),
    show_profile_settings: Boolean(config.show_profile_settings),
    show_profile_admin_entry: Boolean(config.show_profile_admin_entry),
    profile_favorites_sort:
      typeof config.profile_favorites_sort === 'number' && Number.isFinite(config.profile_favorites_sort)
        ? Math.floor(config.profile_favorites_sort)
        : defaultFrontendConfig.profile_favorites_sort,
    profile_histories_sort:
      typeof config.profile_histories_sort === 'number' && Number.isFinite(config.profile_histories_sort)
        ? Math.floor(config.profile_histories_sort)
        : defaultFrontendConfig.profile_histories_sort,
    profile_settings_sort:
      typeof config.profile_settings_sort === 'number' && Number.isFinite(config.profile_settings_sort)
        ? Math.floor(config.profile_settings_sort)
        : defaultFrontendConfig.profile_settings_sort,
    profile_admin_entry_sort:
      typeof config.profile_admin_entry_sort === 'number' && Number.isFinite(config.profile_admin_entry_sort)
        ? Math.floor(config.profile_admin_entry_sort)
        : defaultFrontendConfig.profile_admin_entry_sort,
    profile_admin_tip: config.profile_admin_tip ?? '',
    favorites_title: config.favorites_title ?? '',
    favorites_subtitle: config.favorites_subtitle ?? '',
    favorites_empty_title: config.favorites_empty_title ?? '',
    favorites_empty_subtitle: config.favorites_empty_subtitle ?? '',
    favorites_empty_button_text: config.favorites_empty_button_text ?? '',
    histories_title: config.histories_title ?? '',
    histories_subtitle: config.histories_subtitle ?? '',
    histories_empty_title: config.histories_empty_title ?? '',
    histories_empty_subtitle: config.histories_empty_subtitle ?? '',
    histories_empty_button_text: config.histories_empty_button_text ?? '',
    show_recent_favorites: Boolean(config.show_recent_favorites),
    show_recent_histories: Boolean(config.show_recent_histories),
    login_prompt_title: config.login_prompt_title ?? '',
    login_prompt_subtitle: config.login_prompt_subtitle ?? '',
    login_button_text: config.login_button_text ?? '',
    toast_favorite_success: config.toast_favorite_success ?? '',
    toast_favorite_cancel: config.toast_favorite_cancel ?? '',
    toast_history_deleted: config.toast_history_deleted ?? '',
    toast_favorite_deleted: config.toast_favorite_deleted ?? '',
    toast_save_success: config.toast_save_success ?? '',
    toast_save_failed: config.toast_save_failed ?? '',
    toast_load_failed: config.toast_load_failed ?? '',
    common_empty_title: config.common_empty_title ?? '',
    common_empty_subtitle: config.common_empty_subtitle ?? '',
    common_empty_button_text: config.common_empty_button_text ?? ''
  }
  localStorage.setItem(STORAGE_FRONTEND, JSON.stringify(payload))
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
