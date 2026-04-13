<template>
  <view class="mp-page fav has-bottom-nav">
    <view v-if="!ready" class="mp-card fav__state">
      <text class="fav__muted">加载中…</text>
    </view>

    <view v-else-if="!isLoggedIn" class="mp-card fav__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔐</view>
        <text class="mp-empty__title">{{ config.common_empty_title }}</text>
        <text class="mp-empty__sub">{{ config.common_empty_subtitle }}</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="!hasApiBase" class="mp-card fav__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">⚙️</view>
        <text class="mp-empty__title">未配置接口地址</text>
        <text class="mp-empty__sub">请在项目中配置 VITE_API_BASE_URL（通常为 BFF 根地址，与微信登录一致）</text>
      </view>
    </view>

    <view v-else-if="isLoggedIn && !isLaravelSession" class="mp-card fav__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔗</view>
        <text class="mp-empty__title">请使用微信登录</text>
        <text class="mp-empty__sub">收藏已保存在饭否服务器，需通过微信一键登录后查看与同步</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="list.length === 0" class="mp-card fav__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">⭐</view>
        <text class="mp-empty__title">{{ config.favorites_empty_title }}</text>
        <text class="mp-empty__sub">{{ config.favorites_empty_subtitle }}</text>
        <button class="mp-btn-primary" @click="goTodayEat">{{ config.favorites_empty_button_text }}</button>
      </view>
    </view>

    <template v-else>
      <scroll-view class="fav__tabs-scroll" scroll-x :show-scrollbar="false">
        <view class="fav__tabs">
          <view
            v-for="tab in tabs"
            :key="tab.key"
            class="fav__tab"
            :class="{ 'fav__tab--active': activeTab === tab.key }"
            @click="activeTab = tab.key"
          >
            <text class="fav__tab-label">{{ tab.label }}</text>
            <text class="fav__tab-count">{{ tabCount(tab.key) }}</text>
          </view>
        </view>
      </scroll-view>

      <view v-if="filteredList.length === 0" class="mp-card fav__state fav__state--tab-empty">
        <view class="mp-empty">
          <view class="mp-empty__icon">📂</view>
          <text class="mp-empty__title">该分类暂无收藏</text>
          <text class="mp-empty__sub">换个分类看看，或去「今日菜单」生成并收藏内容。</text>
          <button class="mp-btn-primary" @click="goTodayEat">去今日菜单</button>
        </view>
      </view>

      <view v-else-if="recentList.length > 0" class="mp-card mp-card--accent-soft fav__recent">
        <view class="fav__recent-head">
          <text class="mp-kicker mp-kicker--accent">最近</text>
          <text class="fav__recent-title">最近收藏</text>
        </view>
        <text class="fav__recent-hint">最近收藏优先展示，方便快速回看。</text>
        <view
          v-for="(item, idx) in recentList"
          :key="'r' + item.id"
          class="fav__recent-row"
          :class="{ 'fav__recent-row--first': idx === 0 }"
          @click="openFavorite(item)"
        >
          <text class="fav__recent-line-title">{{ item.title || '未命名' }}</text>
          <text class="fav__recent-line-meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
        </view>
      </view>

      <scroll-view v-if="mainList.length > 0" class="fav__scroll" scroll-y>
        <view class="fav__list">
          <view v-for="item in mainList" :key="item.id" class="mp-card fav__row">
            <view class="fav__row-main" @click="openFavorite(item)">
              <view class="fav__row-ico">★</view>
              <view class="fav__row-copy">
                <text class="fav__row-title">{{ item.title || '未命名' }}</text>
                <text class="fav__row-meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
              </view>
            </view>
            <button class="mp-btn-danger-plain fav__del" @click.stop="onDelete(item)">删除</button>
          </view>
        </view>
      </scroll-view>

      <view
        v-else-if="config.show_recent_favorites && list.length > 0"
        class="mp-card mp-card--inset fav__hint-only"
      >
        <text class="fav__muted">当前条目已在「最近收藏」中全部展示</text>
      </view>
    </template>

    <MpIcoTabBar />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import MpIcoTabBar from '@/components/MpIcoTabBar.vue'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useNavigationBarTitleFromConfig } from '@/composables/useNavigationBarTitleFromConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import { API_BASE_URL } from '@/constants'
import { LARAVEL_ACCESS_TOKEN_PREFIX } from '@/composables/useAuth'
import {
  fetchFavorites,
  deleteFavoriteById,
  BIZ_UNAUTHORIZED,
  BIZ_NEED_LARAVEL_AUTH,
} from '@/api/biz'
import { normalizeSourceType, openResultDetail, toDetailPayloadFromFavorite } from '@/lib/resultDetail'
import { formatListTime } from '@/utils/dateFormat'
import type { FavoriteRow } from '@/types/dto'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn, accessToken } = useAuth()

useNavigationBarTitleFromConfig(config, 'favorites_title')

const ready = ref(false)
const list = ref<FavoriteRow[]>([])
type FavoriteTabKey =
  | 'all'
  | 'today_eat'
  | 'custom_wizard'
  | 'fortune_cooking'
  | 'table_design'
  | 'gallery'
  | 'sauce_design'
const activeTab = ref<FavoriteTabKey>('all')
const tabs: ReadonlyArray<{ key: FavoriteTabKey; label: string }> = [
  { key: 'all', label: '全部' },
  { key: 'today_eat', label: '今日菜单' },
  { key: 'custom_wizard', label: '自由搭配' },
  { key: 'fortune_cooking', label: '灵感厨房' },
  { key: 'table_design', label: '家常好菜' },
  { key: 'gallery', label: '图鉴' },
  { key: 'sauce_design', label: '灵魂蘸料' },
]
const hasApiBase = computed(() => Boolean(API_BASE_URL.trim()))
const isLaravelSession = computed(() => {
  const t = accessToken.value
  return typeof t === 'string' && t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)
})

function favoriteSourceType(item: FavoriteRow): Exclude<FavoriteTabKey, 'all'> {
  const t = normalizeSourceType(item.source_type, null)
  return t as Exclude<FavoriteTabKey, 'all'>
}

const filteredList = computed(() => {
  if (activeTab.value === 'all') return list.value
  return list.value.filter((item) => favoriteSourceType(item) === activeTab.value)
})

const recentList = computed(() => {
  if (!config.value.show_recent_favorites) return []
  return filteredList.value.slice(0, Math.min(3, filteredList.value.length))
})

const mainList = computed(() => {
  if (!config.value.show_recent_favorites) return filteredList.value
  return filteredList.value.slice(3)
})

function tabCount(tab: FavoriteTabKey): number {
  if (tab === 'all') return list.value.length
  let count = 0
  for (const item of list.value) {
    if (favoriteSourceType(item) === tab) count += 1
  }
  return count
}

async function load(fromPull = false) {
  if (!fromPull) {
    ready.value = false
  }
  await syncAuthFromSupabase()
  ready.value = true

  list.value = []
  if (!hasApiBase.value || !isLoggedIn.value) {
    uni.stopPullDownRefresh()
    return
  }
  if (!isLaravelSession.value) {
    uni.stopPullDownRefresh()
    return
  }

  try {
    list.value = await fetchFavorites()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_UNAUTHORIZED || err.message === BIZ_UNAUTHORIZED) {
      list.value = []
    } else if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      list.value = []
      msg.toastLoadFailed('请使用微信登录后查看收藏')
    } else {
      msg.toastLoadFailed(err.message)
    }
  } finally {
    uni.stopPullDownRefresh()
  }
}

onShow(() => {
  void load(false)
})

onPullDownRefresh(() => {
  void load(true)
})

function goLogin() {
  const redirect = encodeURIComponent('/pages/favorites/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

function goTodayEat() {
  uni.switchTab({ url: '/pages/today-eat/index' })
}

function openFavorite(item: FavoriteRow) {
  openResultDetail(toDetailPayloadFromFavorite(item))
}

function onDelete(item: FavoriteRow) {
  if (item.id == null) return
  uni.showModal({
    title: '删除收藏',
    content: '确定删除这条收藏吗？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await deleteFavoriteById(item.id as number)
        msg.toastFavoriteDeleted()
        await load()
      } catch (e: unknown) {
        const err = e as Error
        msg.toastSaveFailed(err.message)
      }
    },
  })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.fav {
  min-height: 100vh;
  padding: 24rpx 24rpx 0;
  box-sizing: border-box;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.fav__tabs-scroll {
  margin: 0 0 20rpx;
  white-space: nowrap;
}

.fav__tabs {
  display: inline-flex;
  flex-direction: row;
  gap: 14rpx;
  padding-right: 12rpx;
}

.fav__tab {
  min-width: 132rpx;
  padding: 14rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.92);
  border: 1rpx solid rgba(122, 87, 209, 0.14);
  box-shadow: 0 4rpx 14rpx rgba(122, 87, 209, 0.06);
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4rpx;
}

.fav__tab--active {
  background: linear-gradient(145deg, rgba(122, 87, 209, 0.18) 0%, #fff 78%);
  border-color: rgba(122, 87, 209, 0.4);
  box-shadow: 0 10rpx 20rpx rgba(122, 87, 209, 0.12);
}

.fav__tab-label {
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.2;
}

.fav__tab-count {
  font-size: 20rpx;
  color: $mp-text-muted;
  line-height: 1;
}

.fav__state {
  min-height: 320rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.fav__state--tab-empty {
  margin-bottom: 16rpx;
}

.fav__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
}

.fav__recent {
  margin-bottom: 24rpx;
  border-color: rgba(122, 87, 209, 0.25);
  background: linear-gradient(170deg, rgba(243, 236, 255, 0.88) 0%, #fff 58%);
}

.fav__recent-head {
  display: flex;
  flex-direction: column;
  gap: 6rpx;
}

.fav__recent-title {
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.fav__recent-hint {
  display: block;
  margin-top: 8rpx;
  margin-bottom: 8rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
  line-height: 1.4;
}

.fav__recent-row {
  margin-top: 16rpx;
  padding: 16rpx 14rpx 14rpx;
  border-top: 1rpx solid rgba(232, 221, 245, 0.72);
  border-radius: 16rpx;
}

.fav__recent-row--first {
  border-top-width: 0;
  padding-top: 12rpx;
  margin-top: 12rpx;
}

.fav__recent-line-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.fav__recent-line-meta {
  display: block;
  margin-top: 6rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.fav__scroll {
  max-height: calc(100vh - 120rpx);
}

.fav__list {
  display: flex;
  flex-direction: column;
  gap: 18rpx;
}

.fav__row {
  padding: 20rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  border-color: rgba(122, 87, 209, 0.2);
  background: #fff;
  box-shadow: 0 8rpx 24rpx rgba(122, 87, 209, 0.07);
}

.fav__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
}

.fav__row-ico {
  width: 52rpx;
  height: 52rpx;
  border-radius: 16rpx;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26rpx;
  color: $mp-accent;
  background: rgba(122, 87, 209, 0.1);
  border: 1rpx solid rgba(122, 87, 209, 0.22);
}

.fav__row-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.fav__row-title {
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
  word-break: break-word;
}

.fav__row-meta {
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.fav__del {
  margin: 0;
  flex-shrink: 0;
  align-self: center;
}

.fav__hint-only {
  padding: 28rpx 24rpx;
  text-align: center;
}
</style>
