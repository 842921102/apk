<template>
  <view class="mp-page hist has-bottom-nav">
    <view
      v-if="ready && isLoggedIn && backendReady && config.histories_subtitle"
      class="mp-card mp-card--inset hist__lead"
    >
      <text class="mp-kicker mp-kicker--accent">说明</text>
      <text class="hist__lead-text">{{ config.histories_subtitle }}</text>
    </view>

    <view v-if="!ready" class="mp-card hist__state">
      <text class="hist__muted">加载中…</text>
    </view>

    <view v-else-if="!isLoggedIn" class="mp-card hist__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔐</view>
        <text class="mp-empty__title">{{ config.common_empty_title }}</text>
        <text class="mp-empty__sub">{{ config.common_empty_subtitle }}</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="!hasApiBase" class="mp-card hist__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">⚙️</view>
        <text class="mp-empty__title">未配置接口地址</text>
        <text class="mp-empty__sub">请在项目中配置 VITE_API_BASE_URL（通常为 BFF 根地址，与微信登录一致）</text>
      </view>
    </view>

    <view v-else-if="isLoggedIn && !isLaravelSession" class="mp-card hist__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔗</view>
        <text class="mp-empty__title">请使用微信登录</text>
        <text class="mp-empty__sub">历史已写入饭否服务器，需通过微信一键登录后查看与同步</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="list.length === 0" class="mp-card hist__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">📜</view>
        <text class="mp-empty__title">{{ config.histories_empty_title }}</text>
        <text class="mp-empty__sub">{{ config.histories_empty_subtitle }}</text>
        <button class="mp-btn-primary" @click="goTodayEat">{{ config.histories_empty_button_text }}</button>
      </view>
    </view>

    <template v-else>
      <view v-if="recentList.length > 0" class="mp-card mp-card--accent-soft hist__recent">
        <view class="hist__recent-head">
          <text class="mp-kicker mp-kicker--accent">最近</text>
          <text class="hist__recent-title">最近历史</text>
        </view>
        <text class="hist__recent-hint">可在远端配置 show_recent_histories 关闭本区块</text>
        <view
          v-for="(item, idx) in recentList"
          :key="'r' + item.id"
          class="hist__recent-row"
          :class="{ 'hist__recent-row--first': idx === 0 }"
          @click="openHistory(item)"
        >
          <text class="hist__recent-line-title">{{ item.title || '未命名' }}</text>
          <text class="hist__recent-line-meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
        </view>
      </view>

      <scroll-view v-if="mainList.length > 0" class="hist__scroll" scroll-y>
        <view class="mp-list-shell">
          <view v-for="item in mainList" :key="item.id" class="mp-list-row hist__row">
            <view class="hist__row-main" @click="openHistory(item)">
              <text class="hist__row-title">{{ item.title || '未命名' }}</text>
              <text class="hist__row-meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
            </view>
            <button class="mp-btn-danger-plain" @click.stop="onDelete(item)">删除</button>
          </view>
        </view>
      </scroll-view>

      <view
        v-else-if="config.show_recent_histories && list.length > 0"
        class="mp-card mp-card--inset hist__hint-only"
      >
        <text class="hist__muted">当前条目已在「最近历史」中全部展示</text>
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
  fetchHistories,
  deleteHistoryById,
  BIZ_UNAUTHORIZED,
  BIZ_NEED_LARAVEL_AUTH,
} from '@/api/biz'
import { openResultDetail, toDetailPayloadFromHistory } from '@/lib/resultDetail'
import { formatListTime } from '@/utils/dateFormat'
import type { HistoryRow } from '@/types/dto'
import { API_BASE_URL } from '@/constants'
import { LARAVEL_ACCESS_TOKEN_PREFIX } from '@/composables/useAuth'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn, accessToken } = useAuth()

useNavigationBarTitleFromConfig(config, 'histories_title')

const ready = ref(false)
const list = ref<HistoryRow[]>([])
const hasApiBase = computed(() => Boolean(API_BASE_URL.trim()))
const isLaravelSession = computed(() => {
  const t = accessToken.value
  return typeof t === 'string' && t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)
})
const backendReady = computed(() => hasApiBase.value && isLaravelSession.value)

const { recentList, mainList } = useRecentPartitionedList(
  list,
  () => config.value.show_recent_histories,
  3,
)

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
    list.value = await fetchHistories()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_UNAUTHORIZED || err.message === BIZ_UNAUTHORIZED) {
      list.value = []
    } else if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      list.value = []
      msg.toastLoadFailed('请使用微信登录后查看历史')
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
  const redirect = encodeURIComponent('/pages/histories/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

function goTodayEat() {
  uni.switchTab({ url: '/pages/today-eat/index' })
}

function openHistory(item: HistoryRow) {
  openResultDetail(toDetailPayloadFromHistory(item))
}

function onDelete(item: HistoryRow) {
  if (item.id == null) return
  uni.showModal({
    title: '删除历史',
    content: '确定删除这条历史吗？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await deleteHistoryById(item.id as number)
        msg.toastHistoryDeleted()
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

.hist__lead {
  margin-bottom: 8rpx;
}

.hist__lead-text {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.hist__state {
  min-height: 320rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hist__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
}

.hist__recent {
  margin-bottom: 24rpx;
}

.hist__recent-head {
  display: flex;
  flex-direction: column;
  gap: 6rpx;
}

.hist__recent-title {
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.hist__recent-hint {
  display: block;
  margin-top: 8rpx;
  margin-bottom: 8rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
  line-height: 1.4;
}

.hist__recent-row {
  margin-top: 16rpx;
  padding-top: 16rpx;
  border-top: 1rpx solid rgba(232, 221, 245, 0.9);
}

.hist__recent-row--first {
  border-top-width: 0;
  padding-top: 12rpx;
  margin-top: 12rpx;
}

.hist__recent-line-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.hist__recent-line-meta {
  display: block;
  margin-top: 6rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.hist__scroll {
  max-height: calc(100vh - 120rpx);
}

.hist__row {
  align-items: flex-start;
}

.hist__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.hist__row-title {
  font-size: 30rpx;
  font-weight: 600;
  color: $mp-text-primary;
  word-break: break-all;
}

.hist__row-meta {
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.hist__hint-only {
  padding: 28rpx 24rpx;
  text-align: center;
}
</style>
