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
          <view class="me__avatar-wrap">
            <text class="me__avatar">{{ avatarLetter }}</text>
          </view>
          <text class="me__profile-name">{{ displayPrimary }}</text>
          <text class="me__profile-id">账号 ID · {{ shortId }}</text>
          <view class="me__hero-rule" />
        </view>
      </view>

      <view class="me__sheet">
        <view class="mp-card me__user-card">
          <text class="me__rail">账户摘要</text>
          <text class="me__user-card-name">{{ displayPrimary }}</text>
          <text class="me__user-card-id">{{ shortId }}</text>
          <view
            v-if="config.profile_title || config.profile_subtitle"
            class="me__user-card-extra"
          >
            <view class="me__user-card-divider" />
            <text v-if="config.profile_title" class="me__profile-head">{{ config.profile_title }}</text>
            <text v-if="config.profile_subtitle" class="me__profile-sub">{{ config.profile_subtitle }}</text>
          </view>
        </view>

        <view v-if="configured" class="mp-card me__stats">
          <text class="me__rail">数据概览</text>
          <text class="me__stats-desc">以下为当前账号在小程序内已同步的条数（实时拉取）</text>
          <view class="me__stats-grid">
            <view class="me__stat-tile">
              <text class="me__stat-label">{{ statsFavoritesLabel }}</text>
              <text class="me__stat-num">{{ favDisplay }}</text>
            </view>
            <view class="me__stat-tile">
              <text class="me__stat-label">{{ statsHistoriesLabel }}</text>
              <text class="me__stat-num">{{ histDisplay }}</text>
            </view>
          </view>
        </view>

        <view class="me__menu-block">
          <view class="me__menu-head">
            <text class="me__rail">{{ menuSectionLabel }}</text>
            <text class="me__menu-h2">收藏与记录</text>
            <text class="me__menu-lead">点击进入对应列表页，支持下拉刷新</text>
          </view>
          <view class="me__tiles">
            <view
              v-for="item in menuEntries"
              :key="item.id"
              class="me__tile"
              @click="goMenu(item.path)"
            >
              <view class="me__tile-ico">
                <text class="me__tile-emoji">{{ menuIcon(item.id) }}</text>
              </view>
              <text class="me__tile-title">{{ item.title }}</text>
              <text v-if="item.subtitle" class="me__tile-sub">{{ item.subtitle }}</text>
            </view>
          </view>
        </view>

        <view class="me__logout-zone">
          <text class="me__rail me__rail--danger">账号与安全</text>
          <text class="me__logout-hint">退出后需重新登录才能继续同步收藏与历史</text>
          <button class="me__logout" @click="onLogoutTap">
            <text class="me__logout-txt">{{ logoutButtonLabel }}</text>
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAuth } from '@/composables/useAuth'
import { getFavoritesCount, getHistoriesCount, BIZ_UNAUTHORIZED } from '@/api/biz'
import { isSupabaseConfigured } from '@/lib/supabase'
import { ME_MENU_ENTRIES } from '@/config/meEntries'
import { useAppConfig } from '@/composables/useAppConfig'
import {
  LOGGED_STATS_FAVORITES,
  LOGGED_STATS_HISTORIES,
  LOGGED_MENU_SECTION_LABEL,
  LOGOUT_BUTTON,
  LOGOUT_CONFIRM_TITLE,
  LOGOUT_CONFIRM_CONTENT,
} from '@/config/meCopy'

const { config } = useAppConfig()

const {
  currentUser,
  isLoggedIn,
  syncAuthFromSupabase,
  logout,
} = useAuth()

const statsFavoritesLabel = LOGGED_STATS_FAVORITES
const statsHistoriesLabel = LOGGED_STATS_HISTORIES
const menuSectionLabel = LOGGED_MENU_SECTION_LABEL
const logoutButtonLabel = LOGOUT_BUTTON

const menuEntries = ME_MENU_ENTRIES

const configured = computed(() => isSupabaseConfigured())

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

onShow(() => {
  void refresh()
})

async function refresh() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value || !configured.value) {
    favCount.value = 0
    histCount.value = 0
    statsLoading.value = false
    return
  }
  statsLoading.value = true
  try {
    const [f, h] = await Promise.all([getFavoritesCount(), getHistoriesCount()])
    favCount.value = f
    histCount.value = h
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_UNAUTHORIZED || err.message === BIZ_UNAUTHORIZED) {
      favCount.value = 0
      histCount.value = 0
    }
  } finally {
    statsLoading.value = false
  }
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/me/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

function goMenu(path: string) {
  uni.navigateTo({ url: path })
}

function menuIcon(id: string) {
  if (id === 'favorites') return '⭐'
  if (id === 'histories') return '📜'
  return '·'
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

.me__tiles {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20rpx;
}

.me__tile {
  padding: 28rpx 22rpx 24rpx;
  border-radius: 22rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
  box-shadow: $mp-shadow-soft;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.me__tile-ico {
  width: 64rpx;
  height: 64rpx;
  border-radius: 18rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16rpx;
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

/* 退出：贴近 Web 浅红底 + 描边，识别度高 */
.me__logout-zone {
  padding-bottom: 8rpx;
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
</style>
