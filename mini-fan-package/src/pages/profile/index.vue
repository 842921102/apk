<template>
  <view class="mp-page profile">
    <view v-if="isLoggedIn" class="profile__wrap">
      <view class="mp-card profile__card">
        <text class="profile__kicker">个人资料</text>

        <view class="profile__row">
          <view class="profile__avatar">
            <text class="profile__avatar-text">{{ avatarLetter }}</text>
          </view>
          <view class="profile__meta">
            <text class="profile__name">{{ displayPrimary }}</text>
            <text class="profile__id">用户ID：{{ shortId }}</text>
          </view>
        </view>

        <view class="profile__divider" />

        <view class="profile__item">
          <text class="profile__item-label">登录方式</text>
          <text class="profile__item-value">微信登录</text>
        </view>

        <view class="profile__item">
          <text class="profile__item-label">当前状态</text>
          <text class="profile__item-value">已登录</text>
        </view>

        <view class="profile__actions">
          <button class="mp-btn-primary" @click="onEditProfile">
            编辑资料（占位）
          </button>
          <button class="mp-btn-ghost" @click="onBack">
            返回
          </button>
        </view>
      </view>
    </view>

    <view v-else class="mp-card profile__card profile__card--guest">
      <text class="profile__kicker">未登录</text>
      <text class="profile__guest-sub">请先登录后再查看个人资料。</text>
      <button class="mp-btn-primary" @click="goLogin">去登录</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useAppMessages } from '@/composables/useAppMessages'
import { useAppConfig } from '@/composables/useAppConfig'

const msg = useAppMessages()
const { config } = useAppConfig()

const { currentUser, isLoggedIn, syncAuthFromSupabase } = useAuth()

syncAuthFromSupabase()

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

function goLogin() {
  uni.navigateTo({ url: '/pages/login/index?redirect=/pages/profile/index' })
}

function onBack() {
  uni.navigateBack()
}

function onEditProfile() {
  msg.toastLoadFailed('编辑资料功能暂未接入（占位）')
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.profile__wrap {
  padding-bottom: 40rpx;
}

.profile__card {
  padding: 26rpx 22rpx;
}

.profile__card--guest {
  margin: 60rpx 0;
  display: flex;
  flex-direction: column;
  gap: 18rpx;
}

.profile__kicker {
  display: block;
  font-size: 24rpx;
  font-weight: 900;
  color: $mp-accent;
  margin-bottom: 18rpx;
}

.profile__row {
  display: flex;
  flex-direction: row;
  gap: 18rpx;
  align-items: center;
}

.profile__avatar {
  width: 104rpx;
  height: 104rpx;
  border-radius: 28rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile__avatar-text {
  font-size: 40rpx;
  font-weight: 900;
  color: $mp-accent;
}

.profile__meta {
  flex: 1;
  min-width: 0;
}

.profile__name {
  display: block;
  font-size: 34rpx;
  font-weight: 900;
  color: $mp-text-primary;
  word-break: break-all;
}

.profile__id {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
  word-break: break-all;
}

.profile__divider {
  height: 1rpx;
  background: #f3f4f6;
  margin: 20rpx 0;
}

.profile__item {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
  margin-top: 14rpx;
}

.profile__item-label {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.profile__item-value {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.profile__actions {
  margin-top: 22rpx;
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.profile__guest-sub {
  color: $mp-text-secondary;
  font-size: 26rpx;
}
</style>

