<template>
  <view class="pg">
    <view class="mp-card pg__card">
      <text class="pg__hint">与账号同步，用于生成推荐时的长期口味与饮食约束。</text>
      <text class="pg__label">口味偏好（逗号分隔）</text>
      <input v-model="flavorText" class="pg__input" placeholder="如：清淡、鲜" />
      <text class="pg__label">忌口</text>
      <input v-model="tabooText" class="pg__input" placeholder="不想吃的食材" />
      <text class="pg__label">饮食类型</text>
      <input v-model="dietText" class="pg__input" placeholder="如：少油、素食倾向" />
      <text class="pg__label">饮食 / 健康目标</text>
      <input v-model="healthGoal" class="pg__input" placeholder="选填" />
      <button class="mp-btn-primary pg__btn" :loading="loading" @click="onSave">保存</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { fetchMeProfile, putMeProfile } from '@/api/me'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'

const { isLoggedIn, syncAuthFromSupabase } = useAuth()
const loading = ref(false)
const flavorText = ref('')
const tabooText = ref('')
const dietText = ref('')
const healthGoal = ref('')

function splitTags(s: string): string[] {
  return s
    .split(/[,，、\n]/)
    .map((x) => x.trim())
    .filter(Boolean)
    .slice(0, 24)
}

async function load() {
  if (!isLoggedIn.value) {
    uni.navigateTo({ url: '/pages/login/index?redirect=/pages/me/diet-preferences' })
    return
  }
  await syncAuthFromSupabase()
  try {
    const res = await fetchMeProfile()
    flavorText.value = (res.profile.flavor_preferences || []).join('，')
    tabooText.value = (res.profile.taboo_ingredients || []).join('，')
    dietText.value = (res.profile.diet_preferences || []).join('，')
    healthGoal.value = res.profile.health_goal || ''
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
      flavor_preferences: splitTags(flavorText.value),
      taboo_ingredients: splitTags(tabooText.value),
      diet_preferences: splitTags(dietText.value),
      health_goal: healthGoal.value.trim() || null,
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
.pg__input {
  width: 100%;
  padding: 20rpx 22rpx;
  box-sizing: border-box;
  background: #f9fafb;
  border-radius: 14rpx;
  font-size: 28rpx;
}
.pg__btn {
  margin-top: 40rpx;
  width: 100%;
}
</style>
