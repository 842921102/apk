<template>
  <view class="mp-page me">
    <!-- 未登录 -->
    <view v-if="!isLoggedIn" class="me__guest">
      <view class="mp-hero me__guest-hero">
        <view class="me__hero-inner">
          <text class="mp-hero__kicker mp-kicker--on-dark">个人中心</text>
          <text class="mp-hero__title me__hero-title">{{ config.profile_title || '开启你的美食生活' }}</text>
          <text class="mp-hero__sub me__hero-sub">
            {{ config.profile_subtitle || '登录后可同步收藏、历史与多端偏好' }}
          </text>
          <view class="me__hero-rule" />
        </view>
      </view>

      <view class="me__sheet me__sheet--guest">
        <view class="mp-card me__guest-card">
          <view class="me__guest-icon-wrap">
            <text class="me__guest-icon-emoji">✨</text>
          </view>
          <text class="me__rail me__rail--accent">当前状态</text>
          <text class="me__guest-title">{{ config.login_prompt_title }}</text>
          <text class="me__guest-sub">{{ config.login_prompt_subtitle }}</text>
          <button class="mp-btn-primary me__guest-btn" @click="goLogin">
            <text class="me__guest-btn-txt">{{ config.login_button_text }}</text>
            <text class="me__guest-btn-go">→</text>
          </button>
          <view class="me__guest-tip">
            <text class="me__guest-tip-title">未登录可先体验</text>
            <text class="me__guest-tip-line">· 在「吃什么」生成今日推荐</text>
            <text class="me__guest-tip-line">· 在「功能广场」浏览全部工具入口</text>
            <text class="me__guest-tip-line">· 登录后收藏与历史会云端同步</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 已登录 -->
    <view v-else class="me__logged">
      <view class="mp-hero me__profile-hero">
        <view class="me__hero-inner">
          <text class="mp-hero__kicker mp-kicker--on-dark">已登录</text>
          <view class="me__top-actions">
            <button class="me__top-action-btn" @click.stop="onSettingsTap">
              设置
            </button>
            <button class="me__top-action-btn" @click.stop="onFeedbackTap">
              反馈
            </button>
          </view>

          <view class="me__avatar-wrap" @click="goProfile">
            <text class="me__avatar">{{ avatarLetter }}</text>
          </view>
          <text class="me__profile-name" @click="goProfile">{{ displayPrimary }}</text>
          <text class="me__profile-id">账号 ID · {{ shortId }}</text>
          <view class="me__hero-rule" />
        </view>
      </view>

      <view class="me__sheet">
        <view class="mp-card me__user-card">
          <text class="me__rail">账号信息</text>
          <text class="me__user-card-name">{{ displayPrimary }}</text>
          <text class="me__user-card-id">用户ID：{{ shortId }}</text>
          <view
            v-if="config.profile_title || config.profile_subtitle"
            class="me__user-card-extra"
          >
            <view class="me__user-card-divider" />
            <text v-if="config.profile_title" class="me__profile-head">{{ config.profile_title }}</text>
            <text v-if="config.profile_subtitle" class="me__profile-sub">{{ config.profile_subtitle }}</text>
          </view>
        </view>

        <!-- 会员/身份卡片 -->
        <view class="mp-card me__membership">
          <view class="me__membership-head">
            <text class="me__rail">身份与权益</text>
            <button class="me__link-btn" @click="onMemberCenterTap">
              会员中心 ›
            </button>
          </view>
          <text class="me__membership-title">{{ memberLabel }}</text>
          <text class="me__membership-sub">{{ memberSubtitle }}</text>
          <view class="me__membership-badges">
            <view class="me__badge me__badge--accent">云端同步：收藏/历史</view>
            <view class="me__badge">统一回看详情</view>
          </view>
        </view>

        <!-- 我的资产区 -->
        <view v-if="configured" class="mp-card me__assets">
          <view class="me__assets-head">
            <text class="me__rail">我的资产</text>
            <text class="me__assets-sub">更像“资产区”，而不是简单按钮</text>
          </view>

          <view class="me__assets-grid">
            <view class="me__asset-tile" @click="goMenu('/pages/favorites/index')">
              <text class="me__asset-label">我的收藏</text>
              <text class="me__asset-num">{{ favDisplay }}</text>
              <text class="me__asset-link">查看明细 ›</text>
            </view>
            <view class="me__asset-tile" @click="goMenu('/pages/histories/index')">
              <text class="me__asset-label">我的历史</text>
              <text class="me__asset-num">{{ histDisplay }}</text>
              <text class="me__asset-link">查看明细 ›</text>
            </view>
          </view>

          <view class="me__recent">
            <view class="me__assets-head me__recent-head">
              <text class="me__rail me__rail--muted">最近生成</text>
              <button class="me__link-btn" @click="goMenu('/pages/histories/index')">
                全部 ›
              </button>
            </view>

            <view v-if="recentHistoriesLoading" class="me__recent-state">
              加载中…
            </view>
            <view v-else-if="recentHistories.length === 0" class="me__recent-state">
              暂无最近记录
            </view>
            <view v-else class="me__recent-list">
              <view
                v-for="h in recentHistories"
                :key="h.id"
                class="me__recent-item"
                @click="openHistoryDetail(h)"
              >
                <view class="me__recent-item-left">
                  <text class="me__recent-item-title">{{ h.title || '未命名' }}</text>
                  <text class="me__recent-item-meta">{{ h.cuisine || '—' }} · {{ formatListTime(h.created_at) }}</text>
                </view>
                <text class="me__recent-item-arrow">›</text>
              </view>
            </view>
          </view>
        </view>

        <view v-else class="mp-card me__assets me__assets--placeholder">
          <text class="me__rail">我的资产</text>
          <text class="me__assets-sub">请先完成微信一键登录后再查看云端数据。</text>
        </view>

        <!-- 我的记录区 -->
        <view class="mp-card me__features">
          <view class="me__features-head">
            <text class="me__rail">我的记录</text>
            <text class="me__features-sub">按类型快速回看详情</text>
          </view>

          <view class="me__features-grid">
            <view
              v-for="tile in recordTiles"
              :key="tile.id"
              class="me__feature-item"
              @click="onRecordTileTap(tile.type)"
            >
              <text class="me__feature-icon">{{ tile.icon }}</text>
              <text class="me__feature-title">{{ tile.title }}</text>
              <text v-if="latestByType[tile.type]?.title" class="me__feature-subtext">
                {{ latestByType[tile.type]?.title }}
              </text>
              <text v-else class="me__feature-subtext me__feature-subtext--muted">暂无记录</text>
            </view>
          </view>
        </view>

        <!-- 服务中心 -->
        <view class="mp-card me__service">
          <view class="me__support-head">
            <text class="me__rail">服务中心</text>
          </view>
          <view class="me__support-list">
            <view
              v-for="entry in serviceEntries"
              :key="entry.id"
              class="me__support-item"
              @click="onServiceTap(entry.id)"
            >
              <view class="me__support-left">
                <text class="me__support-icon">{{ entry.icon }}</text>
                <view>
                  <text class="me__support-title">{{ entry.title }}</text>
                  <text class="me__support-sub">{{ entry.subtitle }}</text>
                </view>
              </view>
              <text class="me__support-arrow">›</text>
            </view>
          </view>
        </view>

        <!-- 支持与设置区 -->
        <view class="mp-card me__support">
          <view class="me__support-head">
            <text class="me__rail">支持与设置</text>
          </view>
          <view class="me__support-list">
            <view
              v-for="entry in supportSettingsEntries"
              :key="entry.id"
              class="me__support-item"
              @click="onSupportSettingsTap(entry.id)"
            >
              <view class="me__support-left">
                <text class="me__support-icon">{{ entry.icon }}</text>
                <view>
                  <text class="me__support-title">{{ entry.title }}</text>
                  <text class="me__support-sub">{{ entry.subtitle }}</text>
                </view>
              </view>
              <text class="me__support-arrow">›</text>
            </view>
          </view>
        </view>

        <!-- 底部操作区 -->
        <view class="me__logout-zone mp-card">
          <text class="me__rail me__rail--danger">账号与安全</text>
          <text class="me__logout-hint">退出后需重新登录才能继续同步收藏与历史</text>
          <button class="me__logout" @click="onLogoutTap">
            <text class="me__logout-txt">{{ logoutButtonLabel }}</text>
          </button>
          <text class="me__version">版本号 v{{ appVersion }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAuth } from '@/composables/useAuth'
import { fetchHistories, getFavoritesCount, getHistoriesCount } from '@/api/biz'
import { isSupabaseConfigured } from '@/lib/supabase'
import { API_BASE_URL } from '@/constants'
import { useAppConfig } from '@/composables/useAppConfig'
import {
  LOGGED_STATS_FAVORITES,
  LOGGED_STATS_HISTORIES,
  LOGOUT_BUTTON,
  LOGOUT_CONFIRM_TITLE,
  LOGOUT_CONFIRM_CONTENT,
} from '@/config/meCopy'
import type { HistoryRow } from '@/types/dto'
import { formatListTime } from '@/utils/dateFormat'
import { openResultDetail, toDetailPayloadFromHistory, normalizeSourceType, type ResultSourceType } from '@/lib/resultDetail'

const { config } = useAppConfig()

const {
  currentUser,
  isLoggedIn,
  syncAuthFromSupabase,
  logout,
} = useAuth()

const statsFavoritesLabel = LOGGED_STATS_FAVORITES
const statsHistoriesLabel = LOGGED_STATS_HISTORIES
const logoutButtonLabel = LOGOUT_BUTTON
const appVersion = '1.0.0'

const memberLabel = ref('普通用户')
const memberSubtitle = ref('收藏与历史已接入云端，支持回看与再次使用（会员功能占位）')

const featureEntries = [
  { id: 'today_eat', title: '吃什么', icon: '🍽️', path: '/pages/today-eat/index' },
  { id: 'table_design', title: '一桌好菜', icon: '🥘', path: '/pages/table-menu/index' },
  { id: 'fortune_cooking', title: '玄学厨房', icon: '🔮', path: '/pages/fortune-cooking/index' },
  { id: 'sauce_design', title: '酱料大师', icon: '🧑‍🍳', path: '/pages/sauce-design/index' },
  { id: 'plaza', title: '功能广场', icon: '🧭', path: '/pages/plaza/index' },
  { id: 'favorites', title: '我的收藏', icon: '⭐', path: '/pages/favorites/index' },
  { id: 'histories', title: '我的历史', icon: '📜', path: '/pages/histories/index' },
  { id: 'settings', title: '设置', icon: '⚙️', path: '' },
  { id: 'about', title: '关于我们', icon: 'ℹ️', path: '' },
  { id: 'feedback', title: '意见反馈', icon: '💬', path: '' },
  { id: 'member_center', title: '会员中心', icon: '🎫', path: '' },
] as const

const supportEntries = [
  { id: 'help_center', title: '帮助中心', subtitle: '使用指引与常见问题', icon: '📘' },
  { id: 'user_agreement', title: '用户协议', subtitle: '占位内容', icon: '📄' },
  { id: 'privacy_policy', title: '隐私政策', subtitle: '占位内容', icon: '🔒' },
  { id: 'contact_customer', title: '联系客服', subtitle: '占位内容', icon: '🧑‍💻' },
] as const

// 记录区：从最近历史里按 source_type 聚合一条用于快速回看
const recordTiles = [
  { id: 'today_eat_record', type: 'today_eat' as const, title: '吃什么记录', icon: '🍽️' },
  { id: 'table_design_record', type: 'table_design' as const, title: '一桌好菜记录', icon: '🥘' },
  { id: 'fortune_cooking_record', type: 'fortune_cooking' as const, title: '玄学厨房记录', icon: '🔮' },
  { id: 'sauce_design_record', type: 'sauce_design' as const, title: '酱料大师记录', icon: '🧑‍🍳' },
] as const

// 服务中心，按国内小程序常见“服务/支持”结构摆放
const serviceEntries = [
  { id: 'menu_tutorial', title: '菜谱教学', subtitle: '入门指引与模板', icon: '📘' },
  { id: 'function_square', title: '功能广场', subtitle: '浏览全部工具入口', icon: '🧭' },
  { id: 'about_us', title: '关于我们', subtitle: '饭否小程序（占位）', icon: 'ℹ️' },
  { id: 'member_center', title: '会员中心', subtitle: '权益与订阅（占位）', icon: '🎫' },
] as const

// 支持与设置（帮助中心/协议/隐私/设置/反馈）
const supportSettingsEntries = [
  { id: 'help_center', title: '帮助中心', subtitle: '使用指引与常见问题', icon: '📘' },
  { id: 'user_agreement', title: '用户协议', subtitle: '占位内容', icon: '📄' },
  { id: 'privacy_policy', title: '隐私政策', subtitle: '占位内容', icon: '🔒' },
  { id: 'settings', title: '设置', subtitle: '账号与偏好（占位）', icon: '⚙️' },
  { id: 'feedback', title: '反馈/客服', subtitle: '意见与问题（占位）', icon: '💬' },
] as const

function inferHistorySourceType(h: HistoryRow): ResultSourceType {
  return normalizeSourceType(h.source_type, h.request_payload)
}

const latestByType = computed(() => {
  const out: Record<ResultSourceType, HistoryRow | null> = {
    today_eat: null,
    table_design: null,
    fortune_cooking: null,
    sauce_design: null,
    gallery: null,
  }

  for (const h of recentHistories.value) {
    const t = inferHistorySourceType(h)
    if (out[t] === null) out[t] = h
  }

  return out
})

/** 收藏可走 Laravel；历史仍依赖 Supabase 会话（可并行展示，各自失败则为 0） */
const configured = computed(() => isSupabaseConfigured() || Boolean(API_BASE_URL.trim()))

const favCount = ref(0)
const histCount = ref(0)
const statsLoading = ref(false)

const displayPrimary = computed(() => {
  const u = currentUser.value
  if (!u) return '用户'
  if (u.nickname?.trim()) return u.nickname.trim()
  return '用户'
})

const avatarLetter = computed(() => {
  const s = displayPrimary.value.trim()
  return s ? s.slice(0, 1) : '用'
})

const shortId = computed(() => {
  const id = currentUser.value?.id || ''
  if (!id) return '—'
  if (id.length <= 12) return id
  return `${id.slice(0, 6)}…${id.slice(-4)}`
})

const favDisplay = computed(() => {
  if (!configured.value) return '—'
  if (statsLoading.value) return '…'
  return String(favCount.value)
})

const histDisplay = computed(() => {
  if (!configured.value) return '—'
  if (statsLoading.value) return '…'
  return String(histCount.value)
})

const recentHistories = ref<HistoryRow[]>([])
const recentHistoriesLoading = ref(false)

onShow(() => {
  void refresh()
})

async function refresh() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value || !configured.value) {
    favCount.value = 0
    histCount.value = 0
    statsLoading.value = false
    recentHistories.value = []
    return
  }
  statsLoading.value = true
  recentHistoriesLoading.value = true
  try {
    const [fSettled, hSettled, recentSettled] = await Promise.allSettled([
      getFavoritesCount(),
      getHistoriesCount(),
      (async () => (await fetchHistories()).slice(0, 3))(),
    ])
    favCount.value = fSettled.status === 'fulfilled' ? fSettled.value : 0
    histCount.value = hSettled.status === 'fulfilled' ? hSettled.value : 0
    recentHistories.value = recentSettled.status === 'fulfilled' ? recentSettled.value : []
  } finally {
    statsLoading.value = false
    recentHistoriesLoading.value = false
  }
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/me/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

function goMenu(path: string) {
  uni.navigateTo({ url: path })
}

function goProfile() {
  uni.navigateTo({ url: '/pages/profile/index' })
}

function onSettingsTap() {
  uni.showModal({
    title: '设置',
    content: '设置功能暂未接入（占位）。',
    showCancel: false,
  })
}

function onFeedbackTap() {
  uni.showModal({
    title: '意见反馈',
    content: '反馈入口暂未接入（占位）。',
    showCancel: false,
  })
}

function onMemberCenterTap() {
  uni.showToast({ title: '会员中心占位', icon: 'none' })
}

function onRecordTileTap(type: ResultSourceType) {
  const h = latestByType.value[type]
  if (h) {
    openHistoryDetail(h)
    return
  }
  goMenu('/pages/histories/index')
}

function onServiceTap(id: (typeof serviceEntries)[number]['id']) {
  if (id === 'menu_tutorial' || id === 'function_square') {
    goMenu('/pages/plaza/index')
    return
  }
  if (id === 'about_us') {
    uni.showModal({
      title: '关于我们',
      content: '饭否小程序（体验版）\n主功能：生成、收藏、历史、回看。',
      showCancel: false,
    })
    return
  }
  if (id === 'member_center') return onMemberCenterTap()
}

function onSupportSettingsTap(id: (typeof supportSettingsEntries)[number]['id']) {
  if (id === 'help_center') {
    uni.showModal({
      title: '帮助中心',
      content: '占位内容：后续可接入 FAQ 或帮助页。',
      showCancel: false,
    })
    return
  }
  if (id === 'user_agreement') {
    uni.showModal({ title: '用户协议', content: '占位内容', showCancel: false })
    return
  }
  if (id === 'privacy_policy') {
    uni.showModal({ title: '隐私政策', content: '占位内容', showCancel: false })
    return
  }
  if (id === 'settings') return onSettingsTap()
  if (id === 'feedback') return onFeedbackTap()
}

function onFeatureTap(id: (typeof featureEntries)[number]['id']) {
  const entry = featureEntries.find((e) => e.id === id)
  if (!entry) return
  if (entry.path) {
    goMenu(entry.path)
    return
  }
  if (id === 'settings') return onSettingsTap()
  if (id === 'about') {
    uni.showModal({
      title: '关于我们',
      content: '饭否小程序（体验版）\n主功能：生成、收藏、历史、回看。\n',
      showCancel: false,
    })
    return
  }
  if (id === 'feedback') return onFeedbackTap()
  if (id === 'member_center') return onMemberCenterTap()
  if (id === 'histories') return goMenu('/pages/histories/index')
  if (id === 'favorites') return goMenu('/pages/favorites/index')
}

function onSupportTap(id: (typeof supportEntries)[number]['id']) {
  const titleMap: Record<string, string> = {
    help_center: '帮助中心',
    user_agreement: '用户协议',
    privacy_policy: '隐私政策',
    contact_customer: '联系客服',
  }
  uni.showModal({
    title: titleMap[id] ?? '服务与支持',
    content: '占位内容：后续可接入页面或富文本展示。',
    showCancel: false,
  })
}

function openHistoryDetail(h: HistoryRow) {
  if (!h) return
  if (h.id == null) return
  openResultDetail(toDetailPayloadFromHistory(h))
}

function onLogoutTap() {
  uni.showModal({
    title: LOGOUT_CONFIRM_TITLE,
    content: LOGOUT_CONFIRM_CONTENT,
    confirmText: '退出',
    cancelText: '取消',
    success: (res) => {
      if (res.confirm) void doLogout()
    },
  })
}

async function doLogout() {
  await logout()
  favCount.value = 0
  histCount.value = 0
  uni.showToast({ title: '已退出登录', icon: 'none' })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.me__hero-inner {
  text-align: center;
}

.me__hero-title {
  max-width: 620rpx;
  margin-left: auto;
  margin-right: auto;
}

.me__hero-sub {
  max-width: 580rpx;
  margin-left: auto;
  margin-right: auto;
}

.me__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

/* 白卡片层叠：贴近 Web -mt 主卡片 */
.me__sheet {
  position: relative;
  z-index: 2;
  margin-top: -36rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.me__sheet--guest {
  margin-top: -36rpx;
}

.me__rail {
  display: block;
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: $mp-text-muted;
  margin-bottom: 12rpx;
}

.me__rail--accent {
  color: $mp-accent;
}

.me__rail--danger {
  color: #b91c1c;
}

/* —— 未登录 —— */
.me__guest {
  display: flex;
  flex-direction: column;
}

.me__guest-hero {
  margin-bottom: 0;
}

.me__guest-card {
  padding: 36rpx 28rpx 32rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.1);
}

.me__guest-icon-wrap {
  width: 112rpx;
  height: 112rpx;
  border-radius: 28rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8rpx;
  box-shadow: 0 4rpx 16rpx rgba(122, 87, 209, 0.08);
}

.me__guest-icon-emoji {
  font-size: 52rpx;
  line-height: 1;
}

.me__guest-title {
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__guest-sub {
  margin-top: 12rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
  padding: 0 8rpx;
}

.me__guest-btn {
  margin-top: 36rpx;
  width: 100%;
  max-width: 480rpx;
  display: flex !important;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  padding-top: 30rpx !important;
  padding-bottom: 30rpx !important;
  box-shadow: 0 16rpx 48rpx rgba(122, 87, 209, 0.35);
}

.me__guest-btn-txt {
  font-weight: 800;
  font-size: 30rpx;
}

.me__guest-btn-go {
  font-size: 32rpx;
  font-weight: 800;
}

.me__guest-tip {
  margin-top: 28rpx;
  align-self: stretch;
  text-align: left;
  padding: 22rpx 24rpx;
  border-radius: 18rpx;
  background: rgba(243, 236, 255, 0.55);
  border: 1rpx solid $mp-ring-accent;
}

.me__guest-tip-title {
  display: block;
  font-size: 24rpx;
  font-weight: 800;
  color: $mp-accent;
  margin-bottom: 12rpx;
}

.me__guest-tip-line {
  display: block;
  font-size: 24rpx;
  line-height: 1.55;
  color: #5a3ba8;
}

/* —— 已登录头图 —— */
.me__logged {
  display: flex;
  flex-direction: column;
}

.me__profile-hero {
  padding-top: 32rpx;
  padding-bottom: 48rpx;
}

.me__avatar-wrap {
  width: 120rpx;
  height: 120rpx;
  border-radius: 32rpx;
  background: rgba(255, 255, 255, 0.22);
  border: 2rpx solid rgba(255, 255, 255, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 20rpx auto 16rpx;
}

.me__avatar {
  font-size: 48rpx;
  font-weight: 800;
  color: #fff;
}

.me__profile-name {
  font-size: 36rpx;
  font-weight: 800;
  color: #fff;
}

.me__profile-id {
  margin-top: 10rpx;
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.88);
  max-width: 90%;
  word-break: break-all;
}

/* 用户信息白卡片 */
.me__user-card {
  padding: 28rpx 24rpx 30rpx;
}

.me__user-card-name {
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__user-card-id {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
  word-break: break-all;
}

.me__user-card-extra {
  margin-top: 8rpx;
}

.me__user-card-divider {
  height: 1rpx;
  background: #f3f4f6;
  margin: 20rpx 0 16rpx;
}

.me__profile-head {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.me__profile-sub {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

/* 数据统计 */
.me__stats {
  padding: 28rpx 24rpx 30rpx;
}

.me__stats-desc {
  display: block;
  margin-top: 4rpx;
  margin-bottom: 20rpx;
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.me__stats-grid {
  display: flex;
  flex-direction: row;
  gap: 20rpx;
}

.me__stat-tile {
  flex: 1;
  padding: 24rpx 20rpx;
  border-radius: 18rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.me__stat-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
}

.me__stat-num {
  display: block;
  margin-top: 12rpx;
  font-size: 44rpx;
  font-weight: 800;
  color: $mp-accent;
  line-height: 1;
}

/* 宫格入口 */
.me__menu-block {
  margin-top: 0;
  padding: 28rpx 24rpx;
}

.me__menu-head {
  margin-bottom: 16rpx;
  padding-left: 4rpx;
}

.me__menu-h2 {
  display: block;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__menu-lead {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.me__quick-grid {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.me__quick-item {
  padding: 22rpx 20rpx;
  border-radius: 18rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.me__quick-left {
  display: flex;
  align-items: center;
  gap: 14rpx;
  min-width: 0;
}

.me__quick-right {
  display: flex;
  align-items: center;
  gap: 10rpx;
}

.me__quick-count {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-accent;
}

.me__quick-arrow {
  font-size: 30rpx;
  color: $mp-text-muted;
}

.me__tile-ico {
  width: 56rpx;
  height: 56rpx;
  border-radius: 16rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0;
}

.me__tile-emoji {
  font-size: 32rpx;
  line-height: 1;
}

.me__tile-title {
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__tile-sub {
  margin-top: 8rpx;
  font-size: 22rpx;
  line-height: 1.4;
  color: $mp-text-secondary;
}

.me__tools {
  padding: 28rpx 24rpx;
}

.me__tool-row {
  padding: 18rpx 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1rpx solid #f3f4f6;
}

.me__tool-row:first-of-type {
  border-top: none;
}

.me__tool-left {
  display: flex;
  align-items: center;
  gap: 14rpx;
}

.me__tool-icon {
  font-size: 30rpx;
  width: 42rpx;
  text-align: center;
}

.me__tool-title {
  display: block;
  font-size: 27rpx;
  color: $mp-text-primary;
  font-weight: 600;
}

.me__tool-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.me__tool-arrow {
  font-size: 30rpx;
  color: $mp-text-muted;
}

/* 退出：贴近 Web 浅红底 + 描边，识别度高 */
.me__logout-zone {
  padding: 28rpx 24rpx 20rpx;
}

.me__logout-hint {
  display: block;
  margin-top: 4rpx;
  margin-bottom: 20rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.me__logout {
  width: 100%;
  padding: 28rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: #d64556 !important;
  background: #fff5f5 !important;
  border: 2rpx solid #fde2e4 !important;
  border-radius: 22rpx;
}

.me__logout::after {
  border: none !important;
}

.me__logout-txt {
  font-weight: 800;
}

/* —— 个人中心新版结构（顶部快捷 / 会员 / 资产 / 功能 / 支持） —— */
.me__profile-hero {
  position: relative;
}

.me__top-actions {
  position: absolute;
  top: 16rpx;
  right: 24rpx;
  display: flex;
  flex-direction: row;
  gap: 12rpx;
}

.me__top-action-btn {
  font-size: 22rpx;
  font-weight: 800;
  color: rgba(255, 255, 255, 0.92);
  background: rgba(255, 255, 255, 0.18);
  border: 1rpx solid rgba(255, 255, 255, 0.32);
  padding: 10rpx 16rpx;
  border-radius: 18rpx;
}

.me__link-btn {
  border: none;
  background: none;
  padding: 0;
  margin: 0;
  color: $mp-accent;
  font-size: 22rpx;
  font-weight: 900;
}

.me__badge {
  display: inline-block;
  padding: 10rpx 14rpx;
  border-radius: 999rpx;
  background: #f3f4f6;
  border: 1rpx solid #e5e7eb;
  font-size: 22rpx;
  color: $mp-text-secondary;
  margin-right: 12rpx;
  margin-bottom: 10rpx;
}

.me__badge--accent {
  background: rgba(122, 87, 209, 0.12);
  border-color: rgba(122, 87, 209, 0.22);
  color: $mp-accent;
}

.me__membership,
.me__assets,
.me__features,
.me__support,
.me__service {
  padding: 28rpx 24rpx;
}

.me__membership-head {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.me__membership-title {
  display: block;
  margin-top: 6rpx;
  font-size: 34rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__membership-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.me__membership-badges {
  margin-top: 16rpx;
}

.me__assets-head {
  display: flex;
  flex-direction: row;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16rpx;
}

.me__assets-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  line-height: 1.4;
  color: $mp-text-secondary;
}

.me__assets-grid {
  margin-top: 12rpx;
  display: flex;
  flex-direction: row;
  gap: 20rpx;
}

.me__asset-tile {
  flex: 1;
  padding: 22rpx 18rpx;
  border-radius: 18rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.me__asset-label {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__asset-num {
  display: block;
  margin-top: 12rpx;
  font-size: 46rpx;
  font-weight: 950;
  color: $mp-accent;
  line-height: 1;
}

.me__asset-link {
  display: block;
  margin-top: 12rpx;
  font-size: 22rpx;
  font-weight: 850;
  color: $mp-accent;
}

.me__recent {
  margin-top: 22rpx;
}

.me__rail--muted {
  color: $mp-text-muted;
}

.me__recent-head {
  margin-bottom: 14rpx;
}

.me__recent-state {
  padding: 18rpx 0;
  text-align: center;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.me__recent-list {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.me__recent-item {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
  padding: 16rpx 14rpx;
  border-radius: 18rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.me__recent-item-left {
  min-width: 0;
  flex: 1;
}

.me__recent-item-title {
  display: block;
  font-size: 26rpx;
  font-weight: 900;
  color: $mp-text-primary;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.me__recent-item-meta {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.me__recent-item-arrow {
  font-size: 30rpx;
  font-weight: 950;
  color: $mp-text-muted;
}

.me__features-head {
  display: flex;
  flex-direction: row;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16rpx;
}

.me__features-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
}

.me__features-grid {
  margin-top: 18rpx;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16rpx;
}

.me__feature-item {
  padding: 22rpx 18rpx;
  border-radius: 18rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.me__feature-icon {
  font-size: 36rpx;
  line-height: 1;
}

.me__feature-title {
  font-size: 24rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__feature-subtext {
  display: block;
  margin-top: 2rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
}

.me__feature-subtext--muted {
  color: $mp-text-muted;
}

.me__support-head {
  display: block;
  margin-bottom: 6rpx;
}

.me__support-list {
  margin-top: 12rpx;
}

.me__support-item {
  padding: 18rpx 0;
  border-top: 1rpx solid #f3f4f6;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.me__support-item:first-child {
  border-top: none;
}

.me__support-left {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 14rpx;
  min-width: 0;
  flex: 1;
}

.me__support-icon {
  font-size: 32rpx;
  width: 50rpx;
  text-align: center;
}

.me__support-title {
  display: block;
  font-size: 24rpx;
  font-weight: 900;
  color: $mp-text-primary;
  word-break: break-all;
}

.me__support-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 20rpx;
  color: $mp-text-secondary;
  word-break: break-all;
}

.me__support-arrow {
  font-size: 30rpx;
  font-weight: 950;
  color: $mp-text-muted;
}

.me__version {
  display: block;
  margin-top: 18rpx;
  font-size: 20rpx;
  color: $mp-text-muted;
  text-align: center;
}

</style>
