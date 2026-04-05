<template>
  <view class="pg">
    <view class="mp-card pg__card">
      <text class="pg__hint">基础资料仅用于展示与运营统计，不会自动决定任何推荐能力。</text>
      <text class="pg__label">生日</text>
      <picker mode="date" :value="birthday" @change="onBirthChange">
        <view class="pg__picker">{{ birthday || '点击选择' }}</view>
      </picker>
      <text class="pg__label">性别</text>
      <picker :range="genderLabels" :value="genderIndex" @change="onGenderChange">
        <view class="pg__picker">{{ genderLabels[genderIndex] }}</view>
      </picker>
      <button class="mp-btn-primary pg__btn" :loading="loading" @click="onSave">保存</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { fetchMeProfile, putMeProfile } from '@/api/me'
import { HttpError } from '@/api/http'
import type { UserProfileDto } from '@/types/profile'
import { useAuth } from '@/composables/useAuth'

const { isLoggedIn, syncAuthFromSupabase } = useAuth()
const loading = ref(false)
const birthday = ref('')
const gender = ref<UserProfileDto['gender']>('unknown')

const genderOptions = [
  { value: 'unknown' as const, label: '未设置' },
  { value: 'male' as const, label: '男' },
  { value: 'female' as const, label: '女' },
  { value: 'undisclosed' as const, label: '不愿透露' },
]

const genderLabels = genderOptions.map((g) => g.label)
const genderIndex = computed(() => {
  const i = genderOptions.findIndex((g) => g.value === gender.value)
  return i >= 0 ? i : 0
})

async function load() {
  if (!isLoggedIn.value) {
    uni.navigateTo({ url: '/pages/login/index?redirect=/pages/me/basic-profile' })
    return
  }
  await syncAuthFromSupabase()
  try {
    const res = await fetchMeProfile()
    birthday.value = res.profile.birthday || ''
    gender.value = res.profile.gender || 'unknown'
  } catch {
    uni.showToast({ title: '加载失败', icon: 'none' })
  }
}

onShow(() => {
  void load()
})

function onBirthChange(e: { detail?: { value?: string } }) {
  birthday.value = String(e.detail?.value || '')
}

function onGenderChange(e: { detail?: { value?: string | number } }) {
  const i = Number(e.detail?.value)
  const opt = genderOptions[i]
  if (opt) gender.value = opt.value
}

async function onSave() {
  loading.value = true
  try {
    await putMeProfile({
      birthday: birthday.value.trim() || null,
      gender: gender.value,
    })
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
  margin-top: 24rpx;
  margin-bottom: 12rpx;
}
.pg__picker {
  padding: 20rpx 22rpx;
  background: #f9fafb;
  border-radius: 14rpx;
  font-size: 28rpx;
  color: #111827;
}
.pg__btn {
  margin-top: 40rpx;
  width: 100%;
}
</style>
