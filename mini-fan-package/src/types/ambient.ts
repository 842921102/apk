/** 首页 Banner 左上角：城市 + 天气（BFF GET /api/miniapp/home-banner-ambient） */
export interface HomeBannerAmbient {
  cityName: string
  weatherText: string
  /** 展示用 emoji，如 ☀️ */
  weatherIcon: string
}
