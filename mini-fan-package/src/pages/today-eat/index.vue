<template>
  <view class="mp-page te te--home">
    <view class="te__topbar" :class="{ 'te__topbar--solid': phase !== 'idle' }">
      <view class="te__topbar-safe" />
      <view class="te__topbar-inner" :class="{ 'te__topbar-inner--idle': phase === 'idle' }" />
    </view>

    <view v-if="phase === 'idle'" class="te__idle-root">
      <view class="te__banner">
        <image class="te__banner-photo" src="/static/home/banner-hero-home.jpg" mode="aspectFill" />
        <view class="te__banner-shade" />
        <view
          class="te__banner-meteo"
          :class="{ 'te__banner-meteo--loading': bannerMeteoLoading }"
          :style="bannerMeteoLayoutStyle"
          @click="onBannerMeteoTap"
        >
          <text class="te__banner-meteo-city">{{ bannerCityName }}</text>
          <text class="te__banner-meteo-sep">·</text>
          <text class="te__banner-meteo-ico" aria-hidden="true">{{ bannerWeatherIcon }}</text>
          <text class="te__banner-meteo-w">{{ bannerWeatherText }}</text>
        </view>
        <view class="te__banner-hero" :style="bannerHeroLayoutStyle">
          <text class="te__banner-brand">饭否 · 今日灵感</text>
          <text class="te__banner-title">今天吃什么？</text>
          <text class="te__banner-sub">不用纠结，给你一个刚刚好的答案</text>
          <view class="te__banner-pill" @click="openTodayStatusSheet">
            <text class="te__banner-pill-txt">选今日状态</text>
          </view>
        </view>
      </view>

      <view class="te__home-main">
        <view class="te__tab-card">
          <view class="te__folder-head">
            <view class="te__folder-tabs">
              <view
                v-for="(panel, i) in HOME_TAB_PANELS"
                :key="panel.id"
                class="te__folder-tab"
                :class="{ 'te__folder-tab--on': homeTabIndex === i }"
                @click="homeTabIndex = i"
              >
                <text class="te__folder-tab-txt">{{ panel.title }}</text>
              </view>
            </view>
          </view>
          <view class="te__folder-body">
            <view
              :key="activeHomePanel.id"
              class="te__panel-body te__panel-body--tab te__tab-panel-surface"
              :class="{ 'te__tab-panel-surface--aux': activeHomePanel.chips?.length }"
            >
              <text class="te__bridge-title">{{ activeHomePanel.title }}</text>
              <view class="te__bridge-desc">
                <text class="te__bridge-desc-txt">{{ activeHomePanel.desc }}</text>
              </view>
              <button
                class="te__cta te__cta--eat te__cta--block"
                hover-class="te__cta--eat-pressed"
                :hover-start-time="0"
                :hover-stay-time="120"
                :disabled="activeHomePanel.id === 'eat' && generateInFlight"
                @click="onHomeTabPrimary(activeHomePanel.id)"
              >
                <text class="te__cta-txt">{{ homeTabPrimaryLabel(activeHomePanel) }}</text>
                <text class="te__cta-arrow">→</text>
              </button>
              <view v-if="activeHomePanel.chips?.length" class="te__home-tab-chips">
                <view v-for="(chip, ci) in activeHomePanel.chips" :key="ci" class="te__home-tab-chip">
                  <text class="te__home-tab-chip-txt">{{ chip }}</text>
                </view>
              </view>
              <text v-if="activeHomePanel.footerNote" class="te__home-tab-footer">{{ activeHomePanel.footerNote }}</text>
              <text v-if="activeHomePanel.hint" class="te__eat-hint">{{ activeHomePanel.hint }}</text>
              <text
                v-if="activeHomePanel.linkLabel"
                class="te__eat-skip"
                @click="onHomeTabLink(activeHomePanel.id)"
              >
                {{ activeHomePanel.linkLabel }}
              </text>
            </view>
          </view>
        </view>

        <view class="te__card te__shortcuts">
          <view class="te__short-grid">
            <view v-for="s in HOME_SHORTCUTS" :key="s.id" class="te__short-item" @click="onShortcut(s)">
              <view class="te__short-ico-wrap">
                <view v-if="s.id === 'custom'" class="te__glyph te__glyph--custom">
                  <view class="te__glyph-bar" />
                  <view class="te__glyph-bar te__glyph-bar--mid" />
                  <view class="te__glyph-bar te__glyph-bar--short" />
                </view>
                <view v-else-if="s.id === 'sauce'" class="te__glyph te__glyph--sauce">
                  <view class="te__glyph-cap" />
                  <view class="te__glyph-bottle" />
                </view>
                <view v-else-if="s.id === 'gallery'" class="te__glyph te__glyph--gallery">
                  <view class="te__glyph-frame" />
                  <view class="te__glyph-sun" />
                  <view class="te__glyph-hill" />
                </view>
                <view v-else class="te__glyph te__glyph--fav">
                  <view class="te__fav-circ te__fav-circ--l" />
                  <view class="te__fav-circ te__fav-circ--r" />
                  <view class="te__fav-v" />
                </view>
              </view>
              <text class="te__short-label">{{ s.label }}</text>
            </view>
          </view>
        </view>

        <view class="te__card te__hot" @click="goRecommendationHistory">
          <view class="te__hot-row">
            <text class="te__hot-title">热门推荐</text>
            <view class="te__hot-more">
              <text class="te__hot-more-txt">最近推荐记录</text>
              <text class="te__hot-more-chev"> ></text>
            </view>
          </view>
          <text class="te__hot-sub">看看你最近生成过的好味灵感</text>
        </view>

        <view class="te__taste-profile" @click="goTasteProfile">
          <text class="te__taste-profile-spark">✨</text>
          <view class="te__taste-profile-copy">
            <text class="te__taste-profile-title">完善口味画像</text>
            <text class="te__taste-profile-sub">推荐更懂你的偏好与忌口</text>
          </view>
          <text class="te__taste-profile-go">＞</text>
        </view>
      </view>
    </view>

    <!-- loading -->
    <view v-else-if="phase === 'loading'" class="te__phase-wrap">
      <view class="mp-card te__panel te__panel--state te__panel--loading">
        <view class="te__state-head">
          <text class="te__state-kicker">生成中</text>
          <text class="te__state-title">正在为你搭配方案</text>
        </view>
        <view class="mp-state-icon te__loading-icon">✨</view>
        <view class="te__progress-track">
          <view class="te__progress-fill" />
        </view>
        <text class="te__loading-hint">请稍候，服务端正在通过 AI 代理生成内容…</text>
      </view>
    </view>

    <!-- 错误 -->
    <view v-else-if="phase === 'error'" class="te__phase-wrap">
      <view class="mp-card te__panel te__panel--state te__panel--state-error">
        <view class="te__state-head">
          <text class="te__state-kicker te__state-kicker--danger">未成功</text>
          <text class="te__state-title">本次生成失败</text>
        </view>
        <view class="mp-state-icon mp-state-icon--danger te__err-icon">!</view>
        <view class="te__err-box">
          <text class="te__err-box-label">原因说明</text>
          <text class="te__err-msg">{{ errorMessage }}</text>
        </view>
        <view class="te__err-actions">
          <button v-if="needLogin" class="mp-btn-primary te__stack-btn" @click="goLogin">
            {{ config.common_empty_button_text }}
          </button>
          <button class="mp-btn-ghost te__stack-btn" @click="resetIdle">返回修改偏好</button>
        </view>
      </view>
    </view>

    <!-- 成功 -->
    <scroll-view
      v-else-if="phase === 'success' && result"
      class="te__scroll te__scroll--padded"
      scroll-y
    >
      <view class="mp-card te__result">
        <view class="te__result-hero">
          <text class="te__result-hero-k">推荐完成</text>
          <text class="te__result-hero-title">你的今日灵感已就绪</text>
          <text class="te__result-hero-sub">以下是依据你本次偏好生成的方案，可作为晚餐参考</text>
        </view>

        <view class="te__result-core">
          <view class="te__result-core-head">
            <view class="te__result-core-ico">
              <text class="te__result-core-emoji">🍽️</text>
            </view>
            <view class="te__result-core-titles">
              <text class="te__result-core-k">菜谱名称</text>
              <text class="te__result-title">{{ result.title }}</text>
            </view>
          </view>

          <text class="te__block-title">菜品信息</text>
          <view class="te__meta-grid">
            <view class="te__meta-cell">
              <text class="te__meta-label">菜系</text>
              <text class="te__meta-value">{{ result.cuisine || '—' }}</text>
            </view>
            <view class="te__meta-cell te__meta-cell--wide">
              <text class="te__meta-label">食材</text>
              <text class="te__meta-value">{{ ingredientsText }}</text>
            </view>
          </view>

          <view class="te__body">
            <text class="te__body-k">详细说明</text>
            <view class="te__body-sheet">
              <text class="te__body-text">{{ result.content }}</text>
            </view>
          </view>
        </view>

        <view v-if="historyNote" class="te__history-banner">
          <text class="te__history-note">{{ historyNote }}</text>
        </view>

        <view class="te__fav-row">
          <button class="mp-btn-ghost te__fav-btn" :disabled="favoriteLoading" @click="onToggleFavorite">
            <text>{{ isFavorited ? '取消收藏' : '加入收藏' }}</text>
          </button>
        </view>

        <view class="te__recent">
          <text class="te__recent-title">最近吃么么记录</text>
          <view v-if="recentLoading" class="te__recent-empty">加载中…</view>
          <view v-else-if="recentRecords.length === 0" class="te__recent-empty">暂无记录</view>
          <view v-else class="te__recent-list">
            <view v-for="row in recentRecords" :key="row.id" class="te__recent-item">
              <view class="te__recent-main" @click="onReplayRecord(row.id)">
                <text class="te__recent-name">{{ row.result_title || '未命名结果' }}</text>
                <text class="te__recent-meta">{{ row.result_cuisine || '—' }} · {{ row.created_at || '' }}</text>
              </view>
              <text class="te__recent-del" @click="onDeleteRecord(row.id)">删除</text>
            </view>
          </view>
        </view>
      </view>
      <button class="mp-btn-primary te__again te__again--hero" @click="resetIdle">
        <text class="te__again-txt">再生成一次</text>
        <text class="te__again-go">→</text>
      </button>
    </scroll-view>

    <view v-if="statusSheetOpen" class="te__sheet-mask" @click="onStatusMaskTap">
      <view class="te__sheet" @click.stop>
        <view class="te__sheet-head">
          <text class="te__sheet-title">今日状态</text>
          <text class="te__sheet-sub">标签描述今天的你；口味与忌口点选即可（多选）。</text>
        </view>

        <scroll-view scroll-y class="te__sheet-scroll" :show-scrollbar="false">
          <view class="te__sheet-block">
            <text class="te__sheet-k">心情</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_MOOD_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetMood === opt.value }"
                @click="toggleSheetMood(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view class="te__sheet-block">
            <text class="te__sheet-k">想吃的方向</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_WANT_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetWant === opt.value }"
                @click="toggleSheetWant(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view class="te__sheet-block">
            <text class="te__sheet-k">身体感受</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_BODY_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetBody === opt.value }"
                @click="toggleSheetBody(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view v-if="periodFeatureEnabled" class="te__sheet-block">
            <text class="te__sheet-k">特殊时期</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_PERIOD_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetPeriod === opt.value }"
                @click="toggleSheetPeriod(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>

          <view class="te__sheet-block">
            <text class="te__sheet-k">口味偏好</text>
            <view class="te__chip-row">
              <view
                v-for="opt in MEAL_FLAVOR_TAG_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetFlavorTags.includes(opt.value) }"
                @click="toggleSheetFlavorTag(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view class="te__sheet-block te__sheet-block--last">
            <text class="te__sheet-k">忌口 / 不吃</text>
            <view class="te__chip-row">
              <view
                v-for="opt in MEAL_TABOO_TAG_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetTabooTags.includes(opt.value) }"
                @click="toggleSheetTabooTag(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
        </scroll-view>

        <view class="te__sheet-foot">
          <button class="mp-btn-ghost te__sheet-foot-btn" @click="onSkipTodayStatus">
            <text>跳过，直接推荐</text>
          </button>
          <button class="mp-btn-primary te__sheet-foot-btn" @click="onConfirmTodayStatus">
            <text>开始推荐</text>
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { fetchHomeBannerAmbient } from '@/api/ambient'
import { requestTodayEat } from '@/api/ai'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import {
  insertRecipeHistoryFromTodayEat,
  isFavoriteRecipe,
  toggleFavoriteRecipe,
  BIZ_UNAUTHORIZED,
  BIZ_NEED_LARAVEL_AUTH,
  BIZ_NOT_CONFIGURED,
} from '@/api/biz'
import { fetchEatMemeRecords, deleteEatMemeRecord, type EatMemeRecordItem } from '@/api/eatMeme'
import { favoriteContentDigest } from '@/lib/favoriteDigest'
import type { TodayEatResult } from '@/types/ai'
import { fetchMeProfile, fetchMeDailyToday, putMeDailyToday } from '@/api/me'
import {
  DAILY_BODY_OPTIONS,
  DAILY_MOOD_OPTIONS,
  DAILY_PERIOD_OPTIONS,
  DAILY_WANT_OPTIONS,
  normalizeBodyFromApi,
  normalizeMoodFromApi,
  normalizePeriodFromApi,
  normalizeWantFromApi,
} from '@/constants/dailyStatus'
import {
  MEAL_FLAVOR_TAG_OPTIONS,
  MEAL_TABOO_TAG_OPTIONS,
  normalizeFlavorTagsFromApi,
  normalizeTabooTagsFromApi,
} from '@/constants/mealPreferenceTags'

type Phase = 'idle' | 'loading' | 'success' | 'error'

type HomeTabId = 'eat' | 'fortune' | 'table'

/**
 * 「吃什么」：主按钮 + 辅助说明 + 跳过链。
 * 「玄学厨房」「一桌好菜」：标题、说明、主按钮 + 展示用标签与底部辅助文案（无额外交互逻辑）。
 */
type HomeTabPanel = {
  id: HomeTabId
  title: string
  desc: string
  primaryLabel: string
  primaryLabelLoading?: string
  hint?: string
  linkLabel?: string
  /** 展示用轻标签（仅 fortune / table） */
  chips?: string[]
  /** 展示用底部说明（仅 fortune / table） */
  footerNote?: string
}

const HOME_TAB_PANELS: HomeTabPanel[] = [
  {
    id: 'eat',
    title: '吃什么',
    desc: '点选今日状态与口味忌口，再生成；也可以什么都不选。',
    primaryLabel: '给我一个今天的答案',
    primaryLabelLoading: '准备中…',
    hint: '不选也可以，我们会帮你决定',
    linkLabel: '跳过写入今日状态，直接生成',
  },
  {
    id: 'fortune',
    title: '玄学厨房',
    desc: '用一点随机与仪式感，换一道意想不到的菜。',
    primaryLabel: '进去逛逛',
    chips: ['今日随机', '不纠结选择', '仪式感做饭'],
    footerNote: '今天不想思考，就交给一点神秘灵感',
  },
  {
    id: 'table',
    title: '一桌好菜',
    desc: '搭配一桌菜，适合聚餐与家宴场景。',
    primaryLabel: '去搭配一桌',
    chips: ['家宴搭配', '聚餐推荐', '荤素汤组合'],
    footerNote: '从一道菜到一桌菜，帮你直接搭配好',
  },
]

function homeTabPrimaryLabel(panel: HomeTabPanel): string {
  if (panel.id === 'eat' && generateInFlight.value) {
    return panel.primaryLabelLoading ?? '准备中…'
  }
  return panel.primaryLabel
}

function onHomeTabPrimary(id: HomeTabId) {
  if (id === 'eat') {
    openTodayStatusSheet()
    return
  }
  if (id === 'fortune') {
    goFortuneCooking()
    return
  }
  goTableMenu()
}

function onHomeTabLink(id: HomeTabId) {
  if (id !== 'eat') return
  onSkipWriteGenerate()
}

type HomeShortcut = {
  id: 'custom' | 'sauce' | 'gallery' | 'fav'
  label: string
  go: () => void
}

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const phase = ref<Phase>('idle')
/** 防止连点重复请求 */
const generateInFlight = ref(false)
const result = ref<TodayEatResult | null>(null)
const errorMessage = ref('')
const needLogin = ref(false)
const historyNote = ref('')
const favoriteLoading = ref(false)
const isFavorited = ref(false)
const recentRecords = ref<EatMemeRecordItem[]>([])
const recentLoading = ref(false)

const periodFeatureEnabled = ref(false)
const dailyMood = ref('')
const dailyWant = ref('')
const dailyBody = ref('')
const dailyPeriod = ref('')
const dailyFlavorTags = ref<string[]>([])
const dailyTabooTags = ref<string[]>([])

const homeTabIndex = ref(0)
/** 当前首页 Tab 面板（仅用于模板展示与 :key 切换动效，业务仍以 homeTabIndex + HOME_TAB_PANELS 为准） */
const activeHomePanel = computed(() => HOME_TAB_PANELS[homeTabIndex.value])

const STORAGE_HOME_BANNER_CITY = 'home_banner_city'
const STORAGE_HOME_LOCATION_PROMPTED = 'home_location_prompted_v1'
const bannerMeteoLoading = ref(false)
const locationDenied = ref(false)
const bannerCityName = ref('深圳')
const bannerWeatherText = ref('晴')
const bannerWeatherIcon = ref('☀️')

const bannerMeteoTopPx = ref(48)
const bannerMeteoHeightPx = ref(32)
const bannerHeroTopPx = ref(96)

function syncBannerCapsuleAlign() {
  try {
    const sys = uni.getSystemInfoSync()
    const sb = Number(sys.statusBarHeight) || 20
    let meteoTop = sb + 6
    let meteoH = 32
    let heroTop = sb + 44 + 12

    try {
      const mb = uni.getMenuButtonBoundingClientRect()
      if (mb && typeof mb.top === 'number' && typeof mb.height === 'number' && mb.height > 0) {
        meteoTop = mb.top
        meteoH = mb.height
        heroTop = Math.round(mb.bottom + 12)
      }
    } catch {
      /* 非微信端 */
    }

    bannerMeteoTopPx.value = meteoTop
    bannerMeteoHeightPx.value = meteoH
    bannerHeroTopPx.value = heroTop
  } catch {
    /* 保持当前值 */
  }
}

const bannerMeteoLayoutStyle = computed(() => ({
  top: `${bannerMeteoTopPx.value}px`,
  height: `${bannerMeteoHeightPx.value}px`,
}))

const bannerHeroLayoutStyle = computed(() => ({
  top: `${bannerHeroTopPx.value}px`,
}))

async function refreshBannerAmbient(coords?: { latitude?: number; longitude?: number }) {
  bannerMeteoLoading.value = true
  try {
    const remote = await fetchHomeBannerAmbient(coords)
    bannerWeatherText.value = remote.weatherText
    bannerWeatherIcon.value = remote.weatherIcon
    const remoteCity = (remote.cityName || '').trim()
    let city = remoteCity
    try {
      const c = uni.getStorageSync(STORAGE_HOME_BANNER_CITY)
      // 仅在服务端未返回城市时才使用本地缓存兜底，避免覆盖定位结果
      if (!remoteCity && typeof c === 'string' && c.trim()) {
        city = c.trim()
      }
    } catch {
      /* ignore */
    }
    bannerCityName.value = city
  } finally {
    bannerMeteoLoading.value = false
  }
}

function isLocationScopeGranted(): Promise<boolean> {
  return new Promise((resolve) => {
    uni.getSetting({
      success: (res) => {
        resolve(Boolean(res.authSetting?.['scope.userLocation']))
      },
      fail: () => resolve(false),
    })
  })
}

function requestUserLocationAuthorize(): Promise<boolean> {
  return new Promise((resolve) => {
    uni.authorize({
      scope: 'scope.userLocation',
      success: () => resolve(true),
      fail: () => resolve(false),
    })
  })
}

function getUserLocationCoords(): Promise<{ latitude: number; longitude: number } | null> {
  return new Promise((resolve) => {
    uni.getLocation({
      type: 'gcj02',
      success: (res) => {
        const latitude = Number(res.latitude)
        const longitude = Number(res.longitude)
        if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
          resolve({ latitude, longitude })
          return
        }
        resolve(null)
      },
      fail: () => resolve(null),
    })
  })
}

async function ensureLocationPromptAndFetchAmbient() {
  let prompted = false
  try {
    prompted = Boolean(uni.getStorageSync(STORAGE_HOME_LOCATION_PROMPTED))
  } catch {
    prompted = false
  }

  const granted = await isLocationScopeGranted()
  if (granted) {
    locationDenied.value = false
    const coords = await getUserLocationCoords()
    await refreshBannerAmbient(coords ?? undefined)
    if (!prompted) {
      try {
        uni.setStorageSync(STORAGE_HOME_LOCATION_PROMPTED, 1)
      } catch {
        /* ignore */
      }
    }
    return
  }

  if (!prompted) {
    const modal = await new Promise<{ confirm: boolean }>((resolve) => {
      uni.showModal({
        title: '位置授权',
        content: '用于显示你所在城市和实时天气，不影响核心功能使用。',
        confirmText: '去授权',
        cancelText: '暂不',
        success: (res) => resolve({ confirm: Boolean(res.confirm) }),
        fail: () => resolve({ confirm: false }),
      })
    })
    try {
      uni.setStorageSync(STORAGE_HOME_LOCATION_PROMPTED, 1)
    } catch {
      /* ignore */
    }
    if (modal.confirm) {
      const ok = await requestUserLocationAuthorize()
      if (ok) {
        locationDenied.value = false
        const coords = await getUserLocationCoords()
        await refreshBannerAmbient(coords ?? undefined)
        return
      }
      locationDenied.value = true
    } else {
      locationDenied.value = true
    }
  }

  if (!granted) {
    locationDenied.value = true
  }
  await refreshBannerAmbient()
}

function onBannerMeteoTap() {
  if (!locationDenied.value) return
  uni.showModal({
    title: '开启位置权限',
    content: '开启后可显示你所在城市和实时天气。',
    confirmText: '去设置',
    cancelText: '取消',
    success: (res) => {
      if (!res.confirm) return
      uni.openSetting({
        success: (settingRes) => {
          if (settingRes.authSetting?.['scope.userLocation']) {
            locationDenied.value = false
            void ensureLocationPromptAndFetchAmbient()
          }
        },
      })
    },
  })
}

const statusSheetOpen = ref(false)
const sheetMood = ref('')
const sheetWant = ref('')
const sheetBody = ref('')
const sheetPeriod = ref('')
const sheetFlavorTags = ref<string[]>([])
const sheetTabooTags = ref<string[]>([])

function syncSheetFromDaily() {
  sheetMood.value = dailyMood.value
  sheetWant.value = dailyWant.value
  sheetBody.value = dailyBody.value
  sheetPeriod.value = dailyPeriod.value || 'none'
  sheetFlavorTags.value = [...dailyFlavorTags.value]
  sheetTabooTags.value = [...dailyTabooTags.value]
}

function toggleSheetFlavorTag(v: string) {
  const i = sheetFlavorTags.value.indexOf(v)
  if (i >= 0) {
    sheetFlavorTags.value = sheetFlavorTags.value.filter((x) => x !== v)
  } else {
    sheetFlavorTags.value = [...sheetFlavorTags.value, v]
  }
}

function toggleSheetTabooTag(v: string) {
  if (v === 'none') {
    sheetTabooTags.value = ['none']
    return
  }
  let next = sheetTabooTags.value.filter((x) => x !== 'none')
  const i = next.indexOf(v)
  if (i >= 0) {
    next = next.filter((x) => x !== v)
  } else {
    next = [...next, v]
  }
  sheetTabooTags.value = next
}

function applySheetToDaily() {
  dailyMood.value = sheetMood.value
  dailyWant.value = sheetWant.value
  dailyBody.value = sheetBody.value
  dailyPeriod.value = periodFeatureEnabled.value ? sheetPeriod.value || 'none' : ''
  dailyFlavorTags.value = [...sheetFlavorTags.value]
  dailyTabooTags.value = [...sheetTabooTags.value]
}

function flavorTagsToPreferenceTaste(tags: string[]): string | undefined {
  if (!tags.length) return undefined
  const labels = tags
    .map((k) => MEAL_FLAVOR_TAG_OPTIONS.find((o) => o.value === k)?.label)
    .filter((x): x is string => Boolean(x))
  return labels.length ? labels.join('、') : undefined
}

function tabooTagsToPreferenceAvoid(tags: string[]): string | undefined {
  if (!tags.length || tags.includes('none')) return undefined
  const labels = tags
    .filter((k) => k !== 'none')
    .map((k) => MEAL_TABOO_TAG_OPTIONS.find((o) => o.value === k)?.label)
    .filter((x): x is string => Boolean(x))
  return labels.length ? labels.join('、') : undefined
}

watch(periodFeatureEnabled, (ok) => {
  if (!ok) {
    dailyPeriod.value = ''
    sheetPeriod.value = 'none'
  }
})

function toggleSheetMood(v: string) {
  sheetMood.value = sheetMood.value === v ? '' : v
}

function toggleSheetWant(v: string) {
  sheetWant.value = sheetWant.value === v ? '' : v
}

function toggleSheetBody(v: string) {
  sheetBody.value = sheetBody.value === v ? '' : v
}

function toggleSheetPeriod(v: string) {
  sheetPeriod.value = sheetPeriod.value === v ? 'none' : v
}

function openTodayStatusSheet() {
  syncSheetFromDaily()
  statusSheetOpen.value = true
}

function onStatusMaskTap() {
  statusSheetOpen.value = false
}

function onSkipTodayStatus() {
  applySheetToDaily()
  statusSheetOpen.value = false
  void runGenerate(false)
}

function onConfirmTodayStatus() {
  applySheetToDaily()
  statusSheetOpen.value = false
  void runGenerate(true)
}

function onSkipWriteGenerate() {
  if (generateInFlight.value) return
  void runGenerate(false)
}

const HOME_SHORTCUTS: HomeShortcut[] = [
  {
    id: 'custom',
    label: '自定义',
    go: () => {
      uni.navigateTo({ url: '/pages/index/index' })
    },
  },
  {
    id: 'sauce',
    label: '酱料大师',
    go: () => {
      uni.navigateTo({ url: '/pages/sauce-design/index' })
    },
  },
  {
    id: 'gallery',
    label: '图鉴',
    go: () => {
      uni.navigateTo({ url: '/pages/gallery/index' })
    },
  },
  {
    id: 'fav',
    label: '收藏',
    go: () => {
      uni.navigateTo({ url: '/pages/recipe-favorites/index' })
    },
  },
]

function onShortcut(s: HomeShortcut) {
  s.go()
}

function goFortuneCooking() {
  uni.navigateTo({ url: '/pages/fortune-cooking/index' })
}

function goTableMenu() {
  uni.navigateTo({ url: '/pages/table-menu/index' })
}

function goRecommendationHistory() {
  uni.navigateTo({ url: '/pages/recommendation-history/index' })
}

function goTasteProfile() {
  uni.navigateTo({ url: '/pages/me/recommendation-preferences' })
}

const ingredientsText = computed(() => {
  const ing = result.value?.ingredients
  if (!ing?.length) return '—'
  return ing.join('、')
})

async function hydrateMeContext() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value) {
    periodFeatureEnabled.value = false
    return
  }
  try {
    const res = await fetchMeProfile()
    periodFeatureEnabled.value = Boolean(res.profile.period_feature_enabled)
    const t = res.today_status
    if (t) {
      dailyMood.value = normalizeMoodFromApi(t.mood)
      dailyWant.value = normalizeWantFromApi(t.wanted_food_style)
      dailyBody.value = normalizeBodyFromApi(t.body_state)
      dailyPeriod.value = periodFeatureEnabled.value
        ? normalizePeriodFromApi(t.period_status)
        : ''
      dailyFlavorTags.value = normalizeFlavorTagsFromApi(t.flavor_tags)
      dailyTabooTags.value = normalizeTabooTagsFromApi(t.taboo_tags)
    } else {
      dailyFlavorTags.value = []
      dailyTabooTags.value = []
    }
  } catch {
    /* 未登录或网络失败时忽略 */
  }
}

syncBannerCapsuleAlign()

onMounted(() => {
  nextTick(() => syncBannerCapsuleAlign())
})

onShow(() => {
  syncBannerCapsuleAlign()
  void ensureLocationPromptAndFetchAmbient()
  void hydrateMeContext()
  void loadRecentRecords()
})

function buildPreferences() {
  return {
    taste: flavorTagsToPreferenceTaste(dailyFlavorTags.value),
    avoid: tabooTagsToPreferenceAvoid(dailyTabooTags.value),
  }
}

async function getContextTagsForGenerate(saveDaily: boolean): Promise<string[] | undefined> {
  if (!isLoggedIn.value) return undefined
  if (saveDaily) {
    try {
      const dailyRes = await putMeDailyToday({
        mood: dailyMood.value || null,
        wanted_food_style: dailyWant.value || null,
        body_state: dailyBody.value || null,
        flavor_tags: dailyFlavorTags.value.length ? dailyFlavorTags.value : null,
        taboo_tags: dailyTabooTags.value.length ? dailyTabooTags.value : null,
        period_status: periodFeatureEnabled.value ? dailyPeriod.value || 'none' : 'none',
      })
      return dailyRes.recommendation_context_tags
    } catch (e) {
      console.warn('[today-eat] daily status / context tags skipped:', e)
      return undefined
    }
  }
  try {
    const d = await fetchMeDailyToday()
    return d.recommendation_context_tags
  } catch (e) {
    console.warn('[today-eat] fetch daily context tags skipped:', e)
    return undefined
  }
}

async function runGenerate(saveDaily: boolean) {
  if (generateInFlight.value || phase.value === 'loading') {
    return
  }
  generateInFlight.value = true
  needLogin.value = false
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
  phase.value = 'loading'
  errorMessage.value = ''

  await syncAuthFromSupabase()

  const preferences = buildPreferences()
  const contextTags = await getContextTagsForGenerate(saveDaily)

  try {
    const data = await requestTodayEat({
      preferences,
      locale: 'zh-CN',
      context_tags: contextTags,
    })

    if (!data || typeof data.content !== 'string' || !data.title) {
      throw new Error('接口返回格式异常')
    }

    result.value = {
      title: data.title,
      cuisine: data.cuisine,
      ingredients: data.ingredients,
      content: data.content,
      history_saved: data.history_saved,
    }

    await maybeSaveHistoryLocally(data, preferences)
    await syncFavoriteState()
    await loadRecentRecords()

    phase.value = 'success'
  } catch (e: unknown) {
    if (e instanceof HttpError && e.statusCode === 401) {
      needLogin.value = true
      errorMessage.value = '需要登录后才能生成，或登录已过期'
    } else if (e instanceof Error) {
      errorMessage.value = e.message || '请求失败'
    } else {
      errorMessage.value = '请求失败'
    }
    phase.value = 'error'
    result.value = null
  } finally {
    generateInFlight.value = false
  }
}

async function maybeSaveHistoryLocally(data: TodayEatResult, preferences: Record<string, unknown>) {
  historyNote.value = ''
  if (data.history_saved === true) {
    msg.toastSaveSuccess()
    return
  }
  if (!isLoggedIn.value) {
    historyNote.value =
      '未登录：本次未写入历史；登录后可在 BFF 支持自动写入或客户端补写'
    return
  }
  try {
    await insertRecipeHistoryFromTodayEat({
      title: data.title,
      cuisine: data.cuisine ?? null,
      ingredients: data.ingredients,
      response_content: data.content,
      request_payload: { preferences, source: 'mp-today-eat' },
    })
    msg.toastSaveSuccess()
  } catch (err: unknown) {
    const e = err as Error & { code?: string }
    if (e.code === BIZ_UNAUTHORIZED || e.message === BIZ_UNAUTHORIZED) {
      msg.toastSaveFailed('登录已过期')
    } else if (e.code === BIZ_NEED_LARAVEL_AUTH || e.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请使用微信一键登录后再试')
    } else if (e.code === BIZ_NOT_CONFIGURED || e.message === BIZ_NOT_CONFIGURED) {
      historyNote.value = '当前环境未启用历史写入配置，已跳过历史记录保存。'
    } else {
      msg.toastSaveFailed(e.message)
      console.error('[today-eat] history insert failed:', err)
    }
  }
}

async function syncFavoriteState() {
  if (!result.value?.title || !result.value?.content) return
  if (!isLoggedIn.value) {
    isFavorited.value = false
    return
  }
  try {
    const sid = favoriteContentDigest(result.value.title, result.value.content)
    isFavorited.value = await isFavoriteRecipe({
      source_type: 'today_eat',
      source_id: sid,
    })
  } catch {
    isFavorited.value = false
  }
}

async function onToggleFavorite() {
  if (!result.value?.title || !result.value?.content) return
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  if (favoriteLoading.value) return

  favoriteLoading.value = true
  try {
    const sid = favoriteContentDigest(result.value.title, result.value.content)
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'today_eat',
      source_id: sid,
      title: result.value.title,
      cuisine: result.value.cuisine ?? null,
      ingredients: result.value.ingredients ?? [],
      recipe_content: result.value.content,
      image_url: null,
    })
    isFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请先使用微信一键登录')
    } else if (err.code === BIZ_NOT_CONFIGURED || err.message === BIZ_NOT_CONFIGURED) {
      msg.toastSaveFailed('当前环境未开启收藏配置')
    } else {
      msg.toastSaveFailed(err.message || '收藏失败')
    }
  } finally {
    favoriteLoading.value = false
  }
}

function resetIdle() {
  phase.value = 'idle'
  result.value = null
  errorMessage.value = ''
  needLogin.value = false
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
  statusSheetOpen.value = false
  nextTick(() => syncBannerCapsuleAlign())
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/today-eat/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

async function loadRecentRecords() {
  recentLoading.value = true
  try {
    recentRecords.value = await fetchEatMemeRecords(1, 10)
  } catch {
    recentRecords.value = []
  } finally {
    recentLoading.value = false
  }
}

function onReplayRecord(id: number) {
  const row = recentRecords.value.find((x) => x.id === id)
  if (!row || !row.result_content || !row.result_title) return
  result.value = {
    title: row.result_title,
    cuisine: row.result_cuisine ?? undefined,
    ingredients: row.result_ingredients ?? [],
    content: row.result_content,
    history_saved: false,
  }
  phase.value = 'success'
}

async function onDeleteRecord(id: number) {
  try {
    await deleteEatMemeRecord(id)
    recentRecords.value = recentRecords.value.filter((x) => x.id !== id)
    uni.showToast({ title: '已删除', icon: 'success' })
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message || '删除失败')
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

$te-bg: #f5f6fa;
$te-title: #2d3436;
/* 与最终稿截图主色一致 */
$te-primary: #7b57e4;
$te-primary-soft: #b8a3f0;

.te--home {
  padding: 0 !important;
  padding-bottom: calc(32rpx + env(safe-area-inset-bottom)) !important;
  background: $te-bg !important;
}

$te-topbar-h: 88rpx;

.te__topbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 80;
  pointer-events: none;
}

.te__topbar-safe {
  width: 100%;
  height: env(safe-area-inset-top);
}

.te__topbar-inner {
  min-height: $te-topbar-h;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  background: transparent;
}

/* 首页 idle：无顶栏标题，不占额外高度，仅保留安全区 */
.te__topbar-inner--idle {
  min-height: 0;
  height: 0;
  overflow: hidden;
}

.te__topbar--solid .te__topbar-inner {
  min-height: $te-topbar-h;
  height: auto;
  overflow: visible;
  background: #ffffff;
  border-bottom: 1rpx solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2rpx 16rpx rgba(0, 0, 0, 0.04);
}

.te__idle-root {
  --te-banner-h: 58vh;
  padding-bottom: 8rpx;
}

.te__home-main {
  position: relative;
  z-index: 2;
  margin-top: calc(var(--te-banner-h) * -0.48 + 150rpx + 20px);
  padding: 0 16rpx;
}

.te__banner {
  position: relative;
  width: 100%;
  height: var(--te-banner-h);
  min-height: 460rpx;
  max-height: 680rpx;
  overflow: hidden;
}

.te__banner-photo {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: block;
}

.te__banner-shade {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  pointer-events: none;
  background:
    linear-gradient(
      to top,
      #f5f6fa 0%,
      #f5f6fa 18%,
      rgba(245, 246, 250, 0.92) 30%,
      rgba(245, 246, 250, 0.55) 44%,
      rgba(245, 246, 250, 0.12) 56%,
      rgba(255, 255, 255, 0) 64%
    ),
    linear-gradient(
      180deg,
      rgba(56, 44, 92, 0.58) 0%,
      rgba(108, 92, 231, 0.36) 14%,
      rgba(108, 92, 231, 0.14) 38%,
      rgba(255, 255, 255, 0) 78%
    ),
    linear-gradient(
      122deg,
      rgba(18, 14, 20, 0.92) 0%,
      rgba(32, 26, 30, 0.62) 38%,
      rgba(40, 34, 38, 0.28) 58%,
      rgba(255, 255, 255, 0) 90%
    ),
    linear-gradient(
      180deg,
      rgba(0, 0, 0, 0.32) 0%,
      rgba(0, 0, 0, 0.06) 35%,
      rgba(0, 0, 0, 0) 55%,
      rgba(0, 0, 0, 0.12) 100%
    );
}

.te__banner-hero {
  position: absolute;
  left: calc(32rpx + 5px);
  right: 120rpx;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.te__banner-meteo {
  position: absolute;
  left: calc(32rpx + 5px);
  right: 120rpx;
  z-index: 3;
  box-sizing: border-box;
  display: flex;
  flex-direction: row;
  align-items: center;
  flex-wrap: nowrap;
  gap: 0 10rpx;
  overflow: hidden;
}

.te__banner-meteo-city {
  font-size: 26rpx;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.92);
  text-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.35);
}

.te__banner-meteo-sep {
  font-size: 22rpx;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.45);
  text-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.35);
}

.te__banner-meteo-ico {
  font-size: 28rpx;
  line-height: 1;
}

.te__banner-meteo-w {
  font-size: 24rpx;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.88);
  text-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.35);
}

.te__banner-meteo--loading {
  opacity: 0.55;
}

.te__banner-brand {
  display: block;
  font-size: 22rpx;
  font-weight: 600;
  letter-spacing: 0.12em;
  color: rgba(255, 255, 255, 0.75);
  text-transform: uppercase;
}

.te__banner-title {
  display: block;
  margin-top: 16rpx;
  font-size: 48rpx;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.te__banner-sub {
  display: block;
  margin-top: 16rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: rgba(255, 255, 255, 0.88);
  max-width: 420rpx;
}

.te__banner-pill {
  align-self: flex-start;
  margin-top: 28rpx;
  padding: 12rpx 28rpx;
  border-radius: 999rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.55);
  background: rgba(255, 255, 255, 0.12);
  display: inline-flex;
  flex-direction: row;
  align-items: center;
  pointer-events: auto;
}

.te__banner-pill-txt {
  font-size: 24rpx;
  font-weight: 600;
  color: #fff;
}

/* 首页 Banner 下：治愈系文件夹 Tab — 选中项与白卡片顶缘连成一块白 */
.te__tab-card {
  margin-top: -12rpx;
  border-radius: 36rpx;
  padding: 12rpx;
  box-sizing: border-box;
  background: #eceef3;
  box-shadow: 0 12rpx 40rpx rgba(55, 40, 120, 0.08);
}

.te__folder-head {
  padding: 0;
  background: transparent;
}

.te__folder-tabs {
  display: flex;
  flex-direction: row;
  align-items: flex-end;
  gap: 8rpx;
  min-height: 92rpx;
}

/* 未选中：略矮的灰紫块，贴在行底，与轨道融合 */
.te__folder-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  align-self: flex-end;
  height: 72rpx;
  padding: 0 8rpx;
  box-sizing: border-box;
  border-radius: 20rpx;
  background: rgba(231, 233, 239, 0.92);
  transition:
    background 0.24s ease,
    box-shadow 0.24s ease,
    height 0.24s ease,
    border-radius 0.24s ease;
}

/* 选中：加高、仅上圆角，底边与白内容区同色相接 */
.te__folder-tab--on {
  align-self: stretch;
  min-height: 92rpx;
  border-radius: 22rpx 22rpx 0 0;
  background: #ffffff;
  box-shadow: none;
}

.te__folder-tab-txt {
  font-size: 32rpx;
  font-weight: 500;
  color: #5f6673;
  line-height: 1.25;
  text-align: center;
  transition:
    color 0.24s ease,
    font-weight 0.24s ease;
}

.te__folder-tab--on .te__folder-tab-txt {
  color: #1a1c21;
  font-weight: 700;
}

/* 顶缘与选中 Tab 底边贴合，消除拼缝；仅保留下圆角与外层卡片呼应 */
.te__folder-body {
  margin-top: -1rpx;
  background: #ffffff;
  border-radius: 0 0 24rpx 24rpx;
  padding: 48rpx 32rpx 32rpx;
  box-sizing: border-box;
}

.te__panel-body {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

/* 三个 Tab 共用：与「吃什么」同一套版式 */
.te__panel-body--tab {
  align-items: center;
  text-align: center;
}

/* 切换 Tab 时整块内容轻量入场，避免生硬跳变 */
.te__tab-panel-surface {
  min-height: 400rpx;
  box-sizing: border-box;
  animation: teHomeTabPaneIn 0.28s ease;
}

/* 玄学厨房 / 一桌好菜：内容更紧凑 */
.te__tab-panel-surface--aux .te__bridge-desc {
  margin-top: 20rpx;
}

.te__tab-panel-surface--aux .te__cta {
  margin-top: 32rpx;
}

@keyframes teHomeTabPaneIn {
  from {
    opacity: 0;
    transform: translate3d(16rpx, 0, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

.te__bridge-title {
  display: block;
  font-size: 52rpx;
  font-weight: 700;
  color: #1f2329;
  width: 100%;
  text-align: center;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.te__bridge-desc {
  margin-top: 24rpx;
  width: 100%;
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
  padding: 0 8rpx;
  box-sizing: border-box;
  text-align: center;
}

.te__bridge-desc-txt {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  font-size: 30rpx;
  line-height: 1.65;
  color: #6b7280;
  text-align: center;
}

.te__cta {
  margin-top: 44rpx;
  border: none;
  border-radius: 48rpx;
  height: 96rpx;
  padding: 0 36rpx;
  line-height: 1.2;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
}

.te__cta::after {
  border: none !important;
}

.te__cta--eat {
  background: linear-gradient(90deg, #8b5cf6 0%, #6d3df5 100%);
  color: #ffffff;
  box-shadow: 0 10rpx 28rpx rgba(109, 61, 245, 0.22);
}

.te__cta--eat[disabled] {
  opacity: 0.55;
}

.te__cta--eat-pressed {
  transform: scale(0.98);
  opacity: 0.92;
}

.te__cta--block {
  align-self: stretch;
  width: 100%;
  box-sizing: border-box;
}

.te__cta-txt {
  font-size: 36rpx;
  font-weight: 700;
}

.te__cta-arrow {
  font-size: 36rpx;
  font-weight: 700;
}

.te__eat-hint {
  display: block;
  margin-top: 28rpx;
  width: 100%;
  text-align: center;
  font-size: 28rpx;
  line-height: 1.55;
  color: #9ca3af;
}

.te__eat-skip {
  display: block;
  margin-top: 16rpx;
  width: 100%;
  text-align: center;
  font-size: 28rpx;
  line-height: 1.55;
  color: #7c5cf3;
  font-weight: 500;
  text-decoration: none;
}

/* 玄学厨房 / 一桌好菜：主按钮下轻标签 + 辅助说明（纯展示） */
.te__home-tab-chips {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 12rpx 16rpx;
  margin-top: 28rpx;
  width: 100%;
  padding: 0 8rpx;
  box-sizing: border-box;
}

.te__home-tab-chip {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  height: 44rpx;
  padding: 0 20rpx;
  box-sizing: border-box;
  border-radius: 999rpx;
  background: #f3eeff;
}

.te__home-tab-chip-txt {
  font-size: 24rpx;
  font-weight: 500;
  color: #7c5cf3;
  line-height: 1;
}

.te__home-tab-footer {
  display: block;
  margin-top: 20rpx;
  width: 100%;
  padding: 0 16rpx;
  box-sizing: border-box;
  text-align: center;
  font-size: 26rpx;
  line-height: 1.55;
  color: #9ca3af;
}

.te__card {
  margin-top: 24rpx;
  padding: 28rpx 24rpx;
  border-radius: 24rpx;
  background: #fff;
  box-shadow: 0 8rpx 28rpx rgba(0, 0, 0, 0.06);
}

.te__card.te__shortcuts {
  margin-top: 16rpx;
}

.te__short-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 20rpx;
}

.te__short-item {
  width: calc((100% - 60rpx) / 4);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14rpx;
}

/* 浅紫方底 + 紫标图标（与截图一致，非整块深紫渐变） */
.te__short-ico-wrap {
  width: 100rpx;
  height: 100rpx;
  border-radius: 24rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(165deg, #f5f0ff 0%, #ebe4ff 48%, #e2d9fc 100%);
  border: 1rpx solid rgba(123, 87, 228, 0.18);
  box-shadow: 0 6rpx 18rpx rgba(123, 87, 228, 0.12);
}

.te__glyph {
  width: 44rpx;
  height: 44rpx;
  position: relative;
  flex-shrink: 0;
}

/* 自定义：三横滑条 */
.te__glyph--custom {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 7rpx;
  width: 40rpx;
  height: 40rpx;
}

.te__glyph-bar {
  height: 6rpx;
  width: 100%;
  border-radius: 3rpx;
  background: linear-gradient(90deg, #966fec 0%, $te-primary 100%);
}

.te__glyph-bar--mid {
  width: 78%;
  align-self: flex-start;
}

.te__glyph-bar--short {
  width: 56%;
  align-self: flex-start;
}

/* 酱料：瓶盖 + 瓶身 */
.te__glyph--sauce {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  width: 36rpx;
  height: 42rpx;
}

.te__glyph-cap {
  width: 16rpx;
  height: 8rpx;
  border-radius: 4rpx 4rpx 0 0;
  background: linear-gradient(180deg, #b8a3f0, $te-primary);
  margin-bottom: 2rpx;
}

.te__glyph-bottle {
  width: 26rpx;
  height: 30rpx;
  border-radius: 8rpx 8rpx 10rpx 10rpx;
  background: linear-gradient(180deg, #a78eef 0%, $te-primary 55%, #6842cf 100%);
}

/* 图鉴：相框 + 简笔风景 */
.te__glyph--gallery {
  width: 40rpx;
  height: 40rpx;
}

.te__glyph-frame {
  position: absolute;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  border-radius: 8rpx;
  border: 3rpx solid $te-primary;
  box-sizing: border-box;
}

.te__glyph-sun {
  position: absolute;
  width: 8rpx;
  height: 8rpx;
  border-radius: 50%;
  background: #f6c15c;
  right: 8rpx;
  top: 8rpx;
}

.te__glyph-hill {
  position: absolute;
  left: 6rpx;
  bottom: 6rpx;
  width: 0;
  height: 0;
  border-left: 12rpx solid transparent;
  border-right: 12rpx solid transparent;
  border-bottom: 14rpx solid rgba(123, 87, 228, 0.55);
}

/* 收藏：心形 */
.te__glyph--fav {
  width: 42rpx;
  height: 38rpx;
}

.te__fav-circ {
  position: absolute;
  width: 20rpx;
  height: 20rpx;
  border-radius: 50%;
  background: linear-gradient(145deg, #966fec, $te-primary);
  top: 6rpx;
}

.te__fav-circ--l {
  left: 4rpx;
}

.te__fav-circ--r {
  right: 4rpx;
}

.te__fav-v {
  position: absolute;
  width: 20rpx;
  height: 20rpx;
  left: 11rpx;
  top: 14rpx;
  background: linear-gradient(145deg, #966fec, #6842cf);
  transform: rotate(45deg);
  border-radius: 4rpx;
}

.te__short-label {
  font-size: 22rpx;
  color: #636e72;
  font-weight: 600;
  text-align: center;
  line-height: 1.3;
}

.te__taste-profile {
  margin-top: 24rpx;
  padding: 28rpx 24rpx;
  border-radius: 24rpx;
  background: linear-gradient(120deg, rgba(123, 87, 228, 0.14) 0%, rgba(184, 163, 240, 0.22) 100%);
  border: 1rpx solid rgba(123, 87, 228, 0.2);
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 20rpx;
  box-shadow: 0 8rpx 24rpx rgba(123, 87, 228, 0.08);
}

.te__taste-profile-spark {
  font-size: 44rpx;
  line-height: 1;
  flex-shrink: 0;
}

.te__taste-profile-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.te__taste-profile-title {
  font-size: 30rpx;
  font-weight: 800;
  color: $te-primary;
}

.te__taste-profile-sub {
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.te__taste-profile-go {
  flex-shrink: 0;
  font-size: 32rpx;
  font-weight: 600;
  color: rgba(123, 87, 228, 0.55);
  line-height: 1;
  padding-left: 8rpx;
}

.te__hot-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
}

.te__hot-title {
  font-size: 30rpx;
  font-weight: 800;
  color: $te-title;
}

.te__hot-more {
  display: flex;
  flex-direction: row;
  align-items: center;
  font-size: 24rpx;
  color: $te-primary;
  font-weight: 600;
}

.te__hot-more-txt,
.te__hot-more-chev {
  font-size: 24rpx;
  color: $te-primary;
  font-weight: 600;
}

.te__hot-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
  line-height: 1.45;
}

.te__phase-wrap {
  padding: calc(env(safe-area-inset-top) + 88rpx + 24rpx) 24rpx 32rpx;
  box-sizing: border-box;
  min-height: 100vh;
  background: $te-bg;
}

.te__sheet-mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 500;
  background: rgba(17, 24, 39, 0.45);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.te__sheet {
  width: 100%;
  max-height: 82vh;
  border-radius: 28rpx 28rpx 0 0;
  background: #fff;
  box-shadow: 0 -8rpx 40rpx rgba(0, 0, 0, 0.12);
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}

.te__sheet-head {
  padding: 28rpx 28rpx 12rpx;
  border-bottom: 1rpx solid #f3f4f6;
}

.te__sheet-title {
  display: block;
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.te__sheet-sub {
  display: block;
  margin-top: 10rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.te__sheet-scroll {
  flex: 1;
  min-height: 200rpx;
  max-height: 56vh;
  padding: 8rpx 28rpx 16rpx;
  box-sizing: border-box;
}

.te__sheet-block {
  margin-top: 20rpx;
}

.te__sheet-block--last {
  margin-bottom: 12rpx;
}

.te__sheet-k {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: $mp-text-muted;
  margin-bottom: 14rpx;
}

.te__chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 14rpx;
}

.te__chip {
  padding: 14rpx 22rpx;
  border-radius: 999rpx;
  background: #f3f4f6;
  border: 1rpx solid #e5e7eb;
  font-size: 26rpx;
  color: #4b5563;
}

.te__chip--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
  color: $mp-accent;
  font-weight: 700;
}

.te__sheet-multi-hint {
  display: block;
  margin: -6rpx 0 14rpx;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}

.te__sheet-foot {
  display: flex;
  flex-direction: row;
  gap: 16rpx;
  padding: 20rpx 28rpx calc(24rpx + env(safe-area-inset-bottom));
  border-top: 1rpx solid #f3f4f6;
  background: #fff;
}

.te__sheet-foot-btn {
  flex: 1;
  margin: 0;
}

.te__fav-row {
  margin-top: 18rpx;
}

.te__fav-btn {
  width: 100%;
}

/* loading / error 态 */
.te__panel--state {
  position: relative;
  padding-top: 36rpx;
  padding-bottom: 40rpx;
  overflow: hidden;
}

.te__panel--loading {
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.1);
}

.te__panel--loading::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.te__panel--state-error {
  border-color: #fecaca;
  box-shadow: 0 8rpx 28rpx rgba(185, 28, 28, 0.08);
}

.te__panel--state-error::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #f87171 0%, #dc2626 100%);
}

.te__state-head {
  text-align: center;
  margin-bottom: 28rpx;
}

.te__state-kicker {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $mp-accent;
}

.te__state-kicker--danger {
  color: #b91c1c;
}

.te__state-title {
  display: block;
  margin-top: 10rpx;
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.te__loading-icon {
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 28rpx;
}

.te__progress-track {
  height: 12rpx;
  border-radius: 999rpx;
  background: #eceef2;
  overflow: hidden;
  margin: 0 24rpx 24rpx;
  box-shadow: inset 0 2rpx 6rpx rgba(0, 0, 0, 0.06);
}

.te__progress-fill {
  height: 100%;
  width: 55%;
  border-radius: 999rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 45%, #6743bf 100%);
}

.te__loading-hint {
  display: block;
  text-align: center;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  padding: 0 32rpx;
}

.te__err-icon {
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 24rpx;
}

.te__err-box {
  align-self: stretch;
  margin: 0 8rpx;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fef2f2;
  border: 1rpx solid #fecaca;
}

.te__err-box-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: #991b1b;
  letter-spacing: 0.06em;
  margin-bottom: 12rpx;
}

.te__err-msg {
  font-size: 26rpx;
  color: #7f1d1d;
  line-height: 1.55;
  word-break: break-word;
}

.te__err-actions {
  margin-top: 32rpx;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.te__stack-btn {
  width: 100%;
}

/* 结果区 */
.te__scroll {
  max-height: 100vh;
  box-sizing: border-box;
}

.te__scroll--padded {
  padding-top: calc(env(safe-area-inset-top) + 88rpx + 16rpx);
  padding-left: 24rpx;
  padding-right: 24rpx;
  padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
  max-height: 100vh;
  box-sizing: border-box;
}

.te__result {
  padding: 0;
  overflow: hidden;
}

.te__result-hero {
  padding: 36rpx 28rpx 32rpx;
  text-align: center;
  background: linear-gradient(180deg, rgba(243, 236, 255, 0.45) 0%, #fff 100%);
  border-bottom: 1rpx solid $mp-border;
}

.te__result-hero-k {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $mp-accent;
}

.te__result-hero-title {
  display: block;
  margin-top: 12rpx;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.te__result-hero-sub {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  padding: 0 16rpx;
}

.te__result-core {
  padding: 28rpx 28rpx 32rpx;
}

.te__block-title {
  display: block;
  margin-top: 28rpx;
  margin-bottom: 12rpx;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: $mp-text-muted;
}

.te__result-core-head {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 20rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #f3f4f6;
}

.te__result-core-ico {
  flex-shrink: 0;
  width: 72rpx;
  height: 72rpx;
  border-radius: 20rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.te__result-core-emoji {
  font-size: 40rpx;
  line-height: 1;
}

.te__result-core-titles {
  flex: 1;
  min-width: 0;
}

.te__result-core-k {
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
  letter-spacing: 0.06em;
}

.te__result-title {
  display: block;
  margin-top: 8rpx;
  font-size: 36rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.35;
  word-break: break-word;
}

.te__meta-grid {
  margin-top: 24rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16rpx;
}

.te__meta-cell {
  flex: 1;
  min-width: 200rpx;
  padding: 20rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.te__meta-cell--wide {
  flex: 1 1 100%;
  min-width: 0;
}

.te__meta-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
}

.te__meta-value {
  display: block;
  margin-top: 10rpx;
  font-size: 28rpx;
  color: #374151;
  line-height: 1.45;
  word-break: break-all;
}

.te__body {
  margin-top: 28rpx;
}

.te__body-k {
  font-size: 24rpx;
  font-weight: 800;
  color: $mp-accent;
  letter-spacing: 0.04em;
}

.te__body-sheet {
  margin-top: 12rpx;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.te__body-text {
  font-size: 28rpx;
  line-height: 1.65;
  color: #1f2937;
  white-space: pre-wrap;
  word-break: break-word;
}

.te__history-banner {
  padding: 20rpx 28rpx 28rpx;
  background: #fff;
}

.te__history-note {
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  padding: 16rpx 20rpx;
  border-radius: 12rpx;
  background: #f9fafb;
  border: 1rpx dashed #e5e7eb;
}

.te__again {
  margin-top: 24rpx;
  margin-bottom: 48rpx;
}

.te__again--hero {
  display: flex !important;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding-top: 30rpx !important;
  padding-bottom: 30rpx !important;
}

.te__again-txt {
  font-weight: 800;
}

.te__again-go {
  font-size: 32rpx;
  font-weight: 800;
}

.te__recent {
  margin: 4rpx 28rpx 22rpx;
  padding-top: 16rpx;
  border-top: 1rpx dashed $mp-border;
}

.te__recent-title {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.te__recent-empty {
  margin-top: 10rpx;
  font-size: 24rpx;
  color: $mp-text-muted;
}

.te__recent-list {
  margin-top: 10rpx;
  display: flex;
  flex-direction: column;
  gap: 10rpx;
}

.te__recent-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 14rpx;
  background: #f6f7fb;
}

.te__recent-main {
  display: flex;
  flex-direction: column;
  gap: 4rpx;
  min-width: 0;
  flex: 1;
}

.te__recent-name {
  font-size: 26rpx;
  color: $mp-text-primary;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.te__recent-meta {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.te__recent-del {
  font-size: 24rpx;
  color: #e55151;
}
</style>
