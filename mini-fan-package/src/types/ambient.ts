/** 首页 Banner 左上角：城市 + 天气（GET /api/miniapp/home-banner-ambient） */
export interface HomeBannerAmbient {
  cityName: string
  weatherText: string
  /** 展示用 emoji，如 ☀️ */
  weatherIcon: string
  /** 展示用温度，如 26°C */
  temperatureText?: string
}
