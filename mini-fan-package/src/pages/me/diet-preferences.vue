<template>
  <view class="pg">
    <view class="mp-card pg__card">
      <text class="pg__hint">本页信息会同步到推荐画像，用于长期优化推荐结果。</text>
      <text class="pg__label">口味偏好（可多项）</text>
      <input v-model="flavorText" class="pg__input" placeholder="例如：清淡、鲜香、微辣" />
      <text class="pg__label">忌口与不吃</text>
      <input v-model="tabooText" class="pg__input" placeholder="例如：香菜、内脏、重辣" />
      <text class="pg__label">饮食类型</text>
      <input v-model="dietText" class="pg__input" placeholder="例如：家常、减脂、健身、轻食" />
      <text class="pg__label">健康目标（选填）</text>
      <input v-model="healthGoal" class="pg__input" placeholder="例如：控糖、养胃、控盐" />
      <button class="mp-btn-primary pg__btn" :loading="loading" @click="onSave">保存饮食偏好</button>
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
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}
.pg__card {
  padding: 32rpx;
  border-color: rgba(122, 87, 209, 0.2);
}
.pg__hint {
  display: block;
  font-size: 25rpx;
  color: #6b7280;
  line-height: 1.6;
  margin-bottom: 24rpx;
}
.pg__label {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: #1f2937;
  margin-top: 24rpx;
  margin-bottom: 12rpx;
}
.pg__input {
  width: 100%;
  padding: 20rpx 22rpx;
  box-sizing: border-box;
  background: #f9fafb;
  border-radius: 14rpx;
  font-size: 26rpx;
  color: #111827;
}
.pg__btn {
  margin-top: 40rpx;
  width: 100%;
}
</style>
