<template>
  <view class="mp-page rf">
    <view v-if="!ready" class="mp-card rf__state">
      <text class="rf__muted">加载中…</text>
    </view>

    <view v-else-if="!isLoggedIn" class="mp-card rf__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔐</view>
        <text class="mp-empty__title">{{ config.common_empty_title }}</text>
        <text class="mp-empty__sub">{{ config.common_empty_subtitle }}</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="!hasApiBase" class="mp-card rf__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">⚙️</view>
        <text class="mp-empty__title">未配置接口地址</text>
        <text class="mp-empty__sub">请在 config/env 中配置 API 根地址（与微信登录一致）</text>
      </view>
    </view>

    <view v-else-if="!isLaravelSession" class="mp-card rf__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">🔗</view>
        <text class="mp-empty__title">请使用微信登录</text>
        <text class="mp-empty__sub">菜谱收藏保存在饭否服务器，需微信一键登录后查看</text>
        <button class="mp-btn-primary" @click="goLogin">{{ config.common_empty_button_text }}</button>
      </view>
    </view>

    <view v-else-if="list.length === 0" class="mp-card rf__state">
      <view class="mp-empty">
        <view class="mp-empty__icon">📖</view>
        <text class="mp-empty__title">暂无菜谱收藏</text>
        <text class="mp-empty__sub">在「今日菜单」推荐结果或做法详情页可收藏标准菜谱</text>
        <button class="mp-btn-primary" @click="goTodayEat">去今日菜单</button>
      </view>
    </view>

    <scroll-view v-else class="rf__scroll" scroll-y>
      <view class="rf__list">
        <view v-for="item in list" :key="item.id" class="mp-card rf__row">
          <view class="rf__row-main" @click="openRecipe(item)">
            <image
              v-if="item.image_url"
              class="rf__cover"
              :src="item.image_url"
              mode="aspectFill"
            />
            <view v-else class="rf__cover rf__cover--ph">
              <text class="rf__cover-ph">🍽</text>
            </view>
            <view class="rf__body">
              <text class="rf__title">{{ item.title || '未命名' }}</text>
              <view v-if="rowTags(item).length" class="rf__tags">
                <text v-for="(t, i) in rowTags(item)" :key="i" class="rf__tag">{{ t }}</text>
              </view>
              <text class="rf__meta">{{ item.cuisine || '—' }} · {{ formatListTime(item.created_at) }}</text>
            </view>
          </view>
          <button class="mp-btn-danger-plain rf__del" @click.stop="onDelete(item)">删除</button>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import { API_BASE_URL } from '@/constants'
import { LARAVEL_ACCESS_TOKEN_PREFIX } from '@/composables/useAuth'
import {
  fetchRecipeFavorites,
  deleteFavoriteById,
  BIZ_UNAUTHORIZED,
  BIZ_NEED_LARAVEL_AUTH,
} from '@/api/biz'
import { formatListTime } from '@/utils/dateFormat'
import type { FavoriteRow } from '@/types/dto'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn, accessToken } = useAuth()

const ready = ref(false)
const list = ref<FavoriteRow[]>([])
const hasApiBase = computed(() => Boolean(API_BASE_URL.trim()))
const isLaravelSession = computed(() => {
  const t = accessToken.value
  return typeof t === 'string' && t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)
})

function rowTags(item: FavoriteRow): string[] {
  const t = item.tags
  if (!Array.isArray(t)) return []
  return t.filter((x) => typeof x === 'string' && x.trim()).slice(0, 6)
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
    list.value = await fetchRecipeFavorites()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_UNAUTHORIZED || err.message === BIZ_UNAUTHORIZED) {
      list.value = []
    } else if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      list.value = []
      msg.toastLoadFailed('请使用微信登录后查看')
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
  const redirect = encodeURIComponent('/pages/recipe-favorites/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

function goTodayEat() {
  uni.switchTab({ url: '/pages/today-eat/index' })
}

function openRecipe(item: FavoriteRow) {
  const sid = item.source_id
  if (sid == null || String(sid).trim() === '') {
    uni.showToast({ title: '缺少菜谱编号', icon: 'none' })
    return
  }
  uni.navigateTo({ url: `/pages/recipe/detail?dishRecipeId=${encodeURIComponent(String(sid))}` })
}

function onDelete(item: FavoriteRow) {
  if (item.id == null) return
  uni.showModal({
    title: '删除收藏',
    content: '确定从菜谱收藏中移除吗？',
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

.rf {
  min-height: 100vh;
  padding: 24rpx 24rpx 48rpx;
  box-sizing: border-box;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

.rf__state {
  min-height: 360rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rf__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
}

.rf__scroll {
  max-height: calc(100vh - 48rpx);
}

.rf__list {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.rf__row {
  padding: 20rpx 20rpx 20rpx 22rpx;
  display: flex;
  flex-direction: row;
  align-items: stretch;
  gap: 16rpx;
}

.rf__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: row;
  gap: 20rpx;
}

.rf__cover {
  width: 160rpx;
  height: 160rpx;
  border-radius: 16rpx;
  flex-shrink: 0;
  background: #eceef2;
}

.rf__cover--ph {
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(122, 87, 209, 0.08);
}

.rf__cover-ph {
  font-size: 48rpx;
}

.rf__body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10rpx;
}

.rf__title {
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.3;
  word-break: break-word;
}

.rf__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.rf__tag {
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
  color: $mp-accent;
  background: rgba(122, 87, 209, 0.1);
}

.rf__meta {
  font-size: 24rpx;
  color: $mp-text-muted;
}

.rf__del {
  align-self: center;
  flex-shrink: 0;
  margin: 0;
  padding: 0 12rpx;
  font-size: 26rpx;
}
</style>
