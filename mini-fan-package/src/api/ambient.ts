import { request } from '@/api/http'
import { API_BASE_URL } from '@/constants'
import type { HomeBannerAmbient } from '@/types/ambient'

/**
 * 对接：GET {VITE_API_BASE_URL}/api/miniapp/home-banner-ambient
 * JSON 根对象或包在 data / ambient / home_banner_ambient / homeBannerAmbient 下：
 * city_name | cityName，weather_text | weatherText，weather_icon_emoji | weatherIconEmoji，
 * 可选 weather_code：sunny | clear | cloudy | overcast | rain | drizzle | snow | fog | wind
 */
export const HOME_BANNER_AMBIENT_PATH = '/api/miniapp/home-banner-ambient'

export const MOCK_HOME_BANNER_AMBIENT: HomeBannerAmbient = {
  cityName: '深圳',
  weatherText: '晴',
  weatherIcon: '☀️',
}

const WEATHER_ICON_BY_CODE: Record<string, string> = {
  sunny: '☀️',
  clear: '☀️',
  cloudy: '☁️',
  overcast: '☁️',
  rain: '🌧️',
  drizzle: '🌦️',
  snow: '❄️',
  fog: '🌫️',
  wind: '💨',
}

function pickStr(o: Record<string, unknown>, ...keys: string[]): string | undefined {
  for (const k of keys) {
    const v = o[k]
    if (typeof v === 'string' && v.trim()) return v.trim()
  }
}

function unwrapPayload(raw: unknown): Record<string, unknown> | null {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return null
  const r = raw as Record<string, unknown>
  const inner = r.data ?? r.ambient ?? r.home_banner_ambient ?? r.homeBannerAmbient
  if (inner && typeof inner === 'object' && !Array.isArray(inner)) {
    return inner as Record<string, unknown>
  }
  return r
}

export function parseHomeBannerAmbient(raw: unknown): HomeBannerAmbient | null {
  const o = unwrapPayload(raw)
  if (!o) return null

  const cityName = pickStr(o, 'city_name', 'cityName', 'city')
  const weatherText = pickStr(o, 'weather_text', 'weatherText', 'condition', 'weather')
  const emoji = pickStr(o, 'weather_icon_emoji', 'weatherIconEmoji')
  const codeRaw = pickStr(o, 'weather_code', 'weatherCode')
  const code = codeRaw?.toLowerCase()
  const iconFromCode = code ? WEATHER_ICON_BY_CODE[code] : undefined
  const weatherIcon = emoji || iconFromCode

  if (!cityName && !weatherText && !weatherIcon) {
    return null
  }

  return {
    cityName: cityName || MOCK_HOME_BANNER_AMBIENT.cityName,
    weatherText: weatherText || MOCK_HOME_BANNER_AMBIENT.weatherText,
    weatherIcon: weatherIcon || MOCK_HOME_BANNER_AMBIENT.weatherIcon,
  }
}

/** 未配置 BFF、失败或未实现接口时返回 MOCK，不抛错 */
export async function fetchHomeBannerAmbient(params?: {
  latitude?: number
  longitude?: number
}): Promise<HomeBannerAmbient> {
  if (!API_BASE_URL.trim()) {
    return { ...MOCK_HOME_BANNER_AMBIENT }
  }
  const reqData: Record<string, unknown> = {}
  if (typeof params?.latitude === 'number' && Number.isFinite(params.latitude)) {
    reqData.latitude = params.latitude
  }
  if (typeof params?.longitude === 'number' && Number.isFinite(params.longitude)) {
    reqData.longitude = params.longitude
  }
  try {
    const raw = await request<unknown>({
      url: HOME_BANNER_AMBIENT_PATH,
      method: 'GET',
      data: reqData,
    })
    const parsed = parseHomeBannerAmbient(raw)
    if (parsed) return parsed
  } catch {
    /* 静默兜底 */
  }
  return { ...MOCK_HOME_BANNER_AMBIENT }
}
