<template>
  <view class="mp-page fav has-bottom-nav">
    <view
      v-if="ready && isLoggedIn && configured && config.favorites_subtitle"
      class="mp-card mp-card--inset fav__lead"
    >
      <text class="mp-kicker mp-kicker--accent">说明</text>
      <text class="fav__lead-text">{{ config.favorites_subtitle }}</text>
    </view>

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

    <view v-else-if="!configured" class="mp-card fav__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">⚙️</view>
        <text class="mp-empty__title">未配置 Supabase</text>
        <text class="mp-empty__sub">请在 fan-miniapp-phase1 配置 VITE_SUPABASE_URL / VITE_SUPABASE_ANON_KEY</text>
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
      <view v-if="recentList.length > 0" class="mp-card mp-card--accent-soft fav__recent">
        <view class="fav__recent-head">
          <text class="mp-kicker mp-kicker--accent">最近</text>
          <text class="fav__recent-title">最近收藏</text>
        </view>
        <text class="fav__recent-hint">可在远端配置 show_recent_favorites 关闭本区块</text>
        <view
          v-for="(item, idx) in recentList"
          :key="'r' + item.id"
          class="fav__recent-row"
          :class="{ 'fav__recent-row--first': idx === 0 }"
        >
          <text class="fav__recent-line-title">{{ item.title || '未命名' }}</text>
          <text class="fav__recent-line-meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
        </view>
      </view>

      <scroll-view v-if="mainList.length > 0" class="fav__scroll" scroll-y>
        <view class="mp-list-shell">
          <view v-for="item in mainList" :key="item.id" class="mp-list-row fav__row">
            <view class="fav__row-main">
              <text class="fav__row-title">{{ item.title || '未命名' }}</text>
              <text class="fav__row-meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
            </view>
            <button class="mp-btn-danger-plain" @click="onDelete(item)">删除</button>
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
import { useRecentPartitionedList } from '@/composables/useRecentPartitionedList'
import { useAppMessages } from '@/composables/useAppMessages'
import {
  fetchFavorites,
  deleteFavoriteById,
  BIZ_UNAUTHORIZED,
  BIZ_NOT_CONFIGURED,
} from '@/api/biz'
import { isSupabaseConfigured } from '@/lib/supabase'
import { formatListTime } from '@/utils/dateFormat'
import type { FavoriteRow } from '@/types/dto'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

useNavigationBarTitleFromConfig(config, 'favorites_title')

const ready = ref(false)
const list = ref<FavoriteRow[]>([])
const configured = computed(() => isSupabaseConfigured())

const { recentList, mainList } = useRecentPartitionedList(
  list,
  () => config.value.show_recent_favorites,
  3,
)

async function load(fromPull = false) {
  if (!fromPull) {
    ready.value = false
  }
  await syncAuthFromSupabase()
  ready.value = true

  list.value = []
  if (!configured.value || !isLoggedIn.value) {
    uni.stopPullDownRefresh()
    return
  }

  try {
    list.value = await fetchFavorites()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_UNAUTHORIZED || err.message === BIZ_UNAUTHORIZED) {
      list.value = []
    } else if (err.code === BIZ_NOT_CONFIGURED || err.message === BIZ_NOT_CONFIGURED) {
      /* template */
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

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.fav__lead {
  margin-bottom: 8rpx;
}

.fav__lead-text {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.fav__state {
  min-height: 320rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.fav__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
}

.fav__recent {
  margin-bottom: 24rpx;
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
  padding-top: 16rpx;
  border-top: 1rpx solid rgba(232, 221, 245, 0.9);
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

.fav__row {
  align-items: flex-start;
}

.fav__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.fav__row-title {
  font-size: 30rpx;
  font-weight: 600;
  color: $mp-text-primary;
  word-break: break-all;
}

.fav__row-meta {
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.fav__hint-only {
  padding: 28rpx 24rpx;
  text-align: center;
}
</style>
