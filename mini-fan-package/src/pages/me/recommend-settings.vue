<template>
  <view class="pg">
    <view class="mp-card pg__card">
      <text class="pg__hint">「食命推荐」与玄学厨房等玩法联动；特殊时期能力与性别无关，仅在你开启后出现相关提问。</text>
      <text class="pg__label">推荐风格（选填）</text>
      <input v-model="styleDraft" class="pg__input" placeholder="如：清淡优先、快手" />
      <view class="pg__row">
        <text class="pg__row-title">食命推荐</text>
        <switch :checked="destiny" color="#7A57D1" @change="onDestinyChange" />
      </view>
      <view class="pg__row">
        <view class="pg__row-mid">
          <text class="pg__row-title">特殊时期贴心推荐</text>
          <text class="pg__row-sub">开启后，在「吃什么」生成前可填写今日状态</text>
        </view>
        <switch :checked="period" color="#7A57D1" @change="onPeriodChange" />
      </view>
      <button class="mp-btn-primary pg__btn" :loading="loading" @click="onSave">保存</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { fetchMeProfile, putMeProfile } from '@/api/me'
import { HttpError } from '@/api/http'
import { patchCurrentUser, useAuth } from '@/composables/useAuth'

const { isLoggedIn, syncAuthFromSupabase } = useAuth()
const loading = ref(false)
const styleDraft = ref('')
const destiny = ref(false)
const period = ref(false)

function onDestinyChange(e: unknown) {
  const d = e as { detail?: { value?: boolean } }
  destiny.value = Boolean(d.detail?.value)
}

function onPeriodChange(e: unknown) {
  const d = e as { detail?: { value?: boolean } }
  period.value = Boolean(d.detail?.value)
}

async function load() {
  if (!isLoggedIn.value) {
    uni.navigateTo({ url: '/pages/login/index?redirect=/pages/me/recommend-settings' })
    return
  }
  await syncAuthFromSupabase()
  try {
    const res = await fetchMeProfile()
    styleDraft.value = res.profile.recommendation_style || ''
    destiny.value = res.profile.destiny_mode_enabled
    period.value = res.profile.period_feature_enabled
  } catch {
    uni.showToast({ title: '加载失败', icon: 'none' })
  }
}

onShow(() => {
  void load()
})

async function onSave() {
  loading.value = true
  try {
    await putMeProfile({
      recommendation_style: styleDraft.value.trim() || null,
      destiny_mode_enabled: destiny.value,
      period_feature_enabled: period.value,
    })
    patchCurrentUser({ periodFeatureEnabled: period.value })
    uni.showToast({ title: '已保存', icon: 'success' })
  } catch (e) {
    const msg = e instanceof HttpError ? e.message : '保存失败'
    uni.showToast({ title: msg.slice(0, 200), icon: 'none' })
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.pg {
  min-height: 100vh;
  padding: 32rpx;
  box-sizing: border-box;
  background: #f5f5f7;
}
.pg__card {
  padding: 32rpx;
}
.pg__hint {
  display: block;
  font-size: 24rpx;
  color: #6b7280;
  line-height: 1.5;
  margin-bottom: 24rpx;
}
.pg__label {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #374151;
  margin-bottom: 12rpx;
}
.pg__input {
  width: 100%;
  padding: 20rpx 22rpx;
  box-sizing: border-box;
  background: #f9fafb;
  border-radius: 14rpx;
  font-size: 28rpx;
}
.pg__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 32rpx;
  padding-top: 28rpx;
  border-top: 1rpx solid #e5e7eb;
}
.pg__row-mid {
  flex: 1;
  padding-right: 20rpx;
}
.pg__row-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #111827;
}
.pg__row-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: #6b7280;
  line-height: 1.45;
}
.pg__btn {
  margin-top: 40rpx;
  width: 100%;
}
</style>
