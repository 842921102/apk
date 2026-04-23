<template>
  <view class="mp-page rh has-bottom-nav">
    <view v-if="!ready" class="mp-card rh__state">
      <text class="rh__muted">加载中…</text>
    </view>

    <view v-else-if="!isLoggedIn" class="mp-card rh__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔐</view>
        <text class="mp-empty__title">{{ config.common_empty_title }}</text>
        <text class="mp-empty__sub">{{ config.common_empty_subtitle }}</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="!hasApiBase" class="mp-card rh__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">⚙️</view>
        <text class="mp-empty__title">服务暂不可用</text>
        <text class="mp-empty__sub">当前无法加载内容，请稍后再试。</text>
      </view>
    </view>

    <view v-else-if="!isLaravelSession" class="mp-card rh__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔗</view>
        <text class="mp-empty__title">请先微信登录</text>
        <text class="mp-empty__sub">最近推荐保存在服务器，需微信一键登录后查看</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="list.length === 0 && !loadingMore" class="mp-card rh__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🍜</view>
        <text class="mp-empty__title">{{ keyword.trim() ? '暂无匹配记录' : '暂无推荐记录' }}</text>
        <text class="mp-empty__sub">
          {{ keyword.trim() ? '换个关键词试试（支持菜名与推荐理由关键词）。' : '在「今日菜单」生成成功后，会按次保留最近推荐，便于回看与分析。' }}
        </text>
        <button class="mp-btn-primary" @click="goTodayEat">去今日菜单</button>
      </view>
    </view>

    <scroll-view v-else class="rh__scroll" scroll-y @scrolltolower="onScrollLower">
      <view class="mp-card rh__search">
        <view class="rh__search-bar">
          <text class="rh__search-ico">🔎</text>
          <input
            v-model="keyword"
            class="rh__search-input"
            type="text"
            placeholder="搜索菜名或推荐理由"
            confirm-type="search"
            @confirm="onSearchConfirm"
          />
          <text v-if="keyword.trim()" class="rh__search-clear" @click="onSearchClear">清空</text>
        </view>
      </view>
      <view class="mp-list-shell">
        <view v-for="item in list" :key="item.id" class="mp-card rh__row">
          <view class="rh__row-head" @click="openDetail(item.id)">
            <view class="rh__row-title-wrap">
              <text class="rh__row-title">{{ item.recommended_dish }}</text>
              <text v-if="item.is_favorited" class="rh__fav-badge">已收藏</text>
            </view>
            <button
              class="rh__fav-btn"
              :disabled="Boolean(favBusyId === item.id)"
              @click.stop="onToggleFavorite(item)"
            >
              <text class="rh__fav-btn-txt">{{ item.is_favorited ? '★' : '☆' }}</text>
            </button>
          </view>
          <view v-if="item.tags?.length" class="rh__tags">
            <text v-for="(t, i) in item.tags.slice(0, 3)" :key="i" class="rh__tag">{{ t }}</text>
          </view>
          <text class="rh__summary">{{ item.reason_summary }}</text>
          <view class="rh__meta-row" @click="openDetail(item.id)">
            <text class="rh__meta">{{ item.recommendation_date }} · {{ sourceLabel(item.recommendation_source) }}</text>
            <text class="rh__link">详情 ›</text>
          </view>
        </view>
      </view>
      <view v-if="loadingMore" class="rh__foot">
        <text class="rh__muted">加载中…</text>
      </view>
      <view v-else-if="page >= lastPage && list.length > 0" class="rh__foot">
        <text class="rh__muted">没有更多了</text>
      </view>
    </scroll-view>

    <MpIcoTabBar />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow, onPullDownRefresh, onReachBottom } from '@dcloudio/uni-app'
import MpIcoTabBar from '@/components/MpIcoTabBar.vue'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import { HttpError } from '@/api/http'
import { apiListRecommendationRecords, apiSetRecommendationRecordFavorite } from '@/api/recommendationRecords'
import type { RecommendationRecordListItem } from '@/types/recommendationHistory'
import { API_BASE_URL } from '@/constants'
import { LARAVEL_ACCESS_TOKEN_PREFIX } from '@/composables/useAuth'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn, accessToken } = useAuth()

const ready = ref(false)
const list = ref<RecommendationRecordListItem[]>([])
const page = ref(1)
const lastPage = ref(1)
const loadingMore = ref(false)
const favBusyId = ref<number | null>(null)
const keyword = ref('')

const hasApiBase = computed(() => Boolean(API_BASE_URL.trim()))
const isLaravelSession = computed(() => {
  const t = accessToken.value
  return typeof t === 'string' && t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)
})

function sourceLabel(s: string): string {
  if (s === 'initial') return '首次推荐'
  if (s === 'reroll') return '换一个'
  if (s === 'alternative_selected') return '备选入选'
  return s || '—'
}

async function fetchPage(p: number, append: boolean) {
  const kw = keyword.value.trim()
  const params: { page: number; per_page: number; keyword?: string } = {
    page: p,
    per_page: 20,
  }
  if (kw) {
    params.keyword = kw
  }
  const res = await apiListRecommendationRecords({
    ...params,
  })
  const rows = res.data ?? []
  const pag = res.meta?.pagination
  lastPage.value = pag?.last_page ?? 1
  if (append) {
    list.value = list.value.concat(rows)
  } else {
    list.value = rows
  }
  page.value = p
}

async function load(fromPull = false, append = false) {
  if (!fromPull && !append) {
    ready.value = false
  }
  await syncAuthFromSupabase()
  ready.value = true

  if (!hasApiBase.value || !isLoggedIn.value || !isLaravelSession.value) {
    list.value = []
    uni.stopPullDownRefresh()
    return
  }

  try {
    if (append) {
      loadingMore.value = true
      const next = page.value + 1
      if (next > lastPage.value && list.value.length > 0) {
        loadingMore.value = false
        uni.stopPullDownRefresh()
        return
      }
      await fetchPage(next, true)
    } else {
      await fetchPage(1, false)
    }
  } catch (e: unknown) {
    if (e instanceof HttpError && e.statusCode === 401) {
      list.value = []
    } else {
      const err = e as Error
      msg.toastLoadFailed(err.message || '加载失败')
    }
  } finally {
    loadingMore.value = false
    uni.stopPullDownRefresh()
  }
}

function onScrollLower() {
  if (!isLoggedIn.value || !isLaravelSession.value) return
  if (keyword.value.trim()) return
  if (loadingMore.value || page.value >= lastPage.value) return
  void load(false, true)
}

onShow(() => {
  void load(false, false)
})

onPullDownRefresh(() => {
  void load(true, false)
})

function onSearchConfirm() {
  void load(false, false)
}

function onSearchClear() {
  keyword.value = ''
  void load(false, false)
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/recommendation-history/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

function goTodayEat() {
  uni.switchTab({ url: '/pages/today-eat/index' })
}

function openDetail(id: number) {
  uni.navigateTo({ url: `/pages/recommendation-history/detail?id=${id}` })
}

async function onToggleFavorite(item: RecommendationRecordListItem) {
  if (favBusyId.value != null) return
  favBusyId.value = item.id
  try {
    const want = !item.is_favorited
    await apiSetRecommendationRecordFavorite(item.id, want)
    const idx = list.value.findIndex((x) => x.id === item.id)
    if (idx >= 0) {
      list.value[idx] = { ...list.value[idx], is_favorited: want }
    }
    uni.showToast({ title: want ? '已收藏' : '已取消收藏', icon: 'success' })
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message || '操作失败')
  } finally {
    favBusyId.value = null
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.rh {
  min-height: 100vh;
  padding: 24rpx 24rpx 0;
  box-sizing: border-box;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.rh__state {
  min-height: 320rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rh__scroll {
  max-height: calc(100vh - 120rpx);
}

.rh__search {
  margin-bottom: 18rpx;
  padding: 18rpx 20rpx;
  border-color: rgba(122, 87, 209, 0.28);
  background: linear-gradient(180deg, rgba(243, 236, 255, 0.55) 0%, #fff 100%);
}

.rh__search-bar {
  display: flex;
  align-items: center;
  gap: 10rpx;
  padding: 12rpx 16rpx;
  border-radius: 16rpx;
  background: #fff;
  border: 1rpx solid rgba(122, 87, 209, 0.2);
}

.rh__search-ico {
  font-size: 24rpx;
}

.rh__search-input {
  flex: 1;
  min-width: 0;
  height: 56rpx;
  font-size: 26rpx;
  color: $mp-text-primary;
}

.rh__search-clear {
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent;
  padding: 0 6rpx;
}

.rh__row {
  margin-bottom: 20rpx;
  padding: 20rpx;
  border-color: rgba(122, 87, 209, 0.2);
  background: #fff;
  box-shadow: 0 8rpx 24rpx rgba(122, 87, 209, 0.07);
}

.rh__row-head {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16rpx;
}

.rh__row-title-wrap {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 12rpx;
}

.rh__row-title {
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.35;
  word-break: break-word;
}

.rh__fav-badge {
  font-size: 20rpx;
  font-weight: 700;
  color: $mp-accent;
  padding: 4rpx 12rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid rgba(122, 87, 209, 0.35);
}

.rh__fav-btn {
  margin: 0;
  padding: 0 20rpx;
  min-width: 72rpx;
  height: 64rpx;
  line-height: 64rpx;
  background: #f9fafb;
  border: 1rpx solid $mp-border;
  border-radius: 16rpx;
}

.rh__fav-btn-txt {
  font-size: 36rpx;
  color: #f59e0b;
}

.rh__tags {
  margin-top: 14rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12rpx;
}

.rh__tag {
  font-size: 22rpx;
  font-weight: 600;
  color: $mp-accent;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid rgba(122, 87, 209, 0.3);
}

.rh__summary {
  display: block;
  margin-top: 14rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rh__meta-row {
  margin-top: 16rpx;
  padding-top: 16rpx;
  border-top: 1rpx solid #f3f4f6;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.rh__meta {
  flex: 1;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.rh__link {
  font-size: 24rpx;
  font-weight: 700;
  color: $mp-accent;
}

.rh__foot {
  padding: 24rpx;
  text-align: center;
}

.rh__muted {
  font-size: 24rpx;
  color: $mp-text-muted;
}
</style>
