<template>
  <view class="psu">
    <view class="mp-card mp-card--accent-soft psu__celebrate">
      <text class="psu__badge">完成</text>
      <text class="psu__hero-title">你的推荐画像已建立</text>
      <text class="psu__hero-sub">
        后续系统会结合你的饮食偏好、体征信息、生活方式和当天状态，生成更有理由的菜品推荐与食命解释。
      </text>
    </view>

    <view class="mp-card psu__block">
      <text class="psu__block-k">口味与忌口</text>
      <view class="psu__row">
        <text class="psu__k">口味偏好</text>
        <text class="psu__v">{{ lineList(payload.flavorPreferences) }}</text>
      </view>
      <view class="psu__row">
        <text class="psu__k">不喜欢吃</text>
        <text class="psu__v">{{ lineList(payload.dislikeIngredients) }}</text>
      </view>
      <view class="psu__row psu__row--last">
        <text class="psu__k">过敏 / 必须避免</text>
        <text class="psu__v">{{ lineList(payload.allergyIngredients) }}</text>
      </view>
    </view>

    <view class="mp-card psu__block">
      <text class="psu__block-k">饮食目标与体征</text>
      <view class="psu__row">
        <text class="psu__k">饮食目标</text>
        <text class="psu__v">{{ lineList(payload.dietGoals) }}</text>
      </view>
      <view class="psu__row">
        <text class="psu__k">身高</text>
        <text class="psu__v">{{ fmtCm(payload.heightCm) }}</text>
      </view>
      <view class="psu__row" :class="{ 'psu__row--last': payload.targetWeightKg == null }">
        <text class="psu__k">体重</text>
        <text class="psu__v">{{ fmtKg(payload.weightKg) }}</text>
      </view>
      <view v-if="payload.targetWeightKg != null" class="psu__row psu__row--last">
        <text class="psu__k">目标体重</text>
        <text class="psu__v">{{ fmtKg(payload.targetWeightKg) }}</text>
      </view>
    </view>

    <view class="mp-card psu__block">
      <text class="psu__block-k">生活方式</text>
      <view class="psu__row">
        <text class="psu__k">做饭频率</text>
        <text class="psu__v">{{ labelCooking(payload.cookingFrequency) }}</text>
      </view>
      <view class="psu__row">
        <text class="psu__k">生活习惯</text>
        <text class="psu__v">{{ lineList(payload.lifestyleTags) }}</text>
      </view>
      <view class="psu__row psu__row--last">
        <text class="psu__k">用餐场景</text>
        <text class="psu__v">{{ labelFamily(payload.familySize) }}</text>
      </view>
    </view>

    <view class="mp-card psu__block">
      <text class="psu__block-k">推荐设置</text>
      <view class="psu__row">
        <text class="psu__k">食命推荐</text>
        <text class="psu__v">{{ labelBoolOnOff(payload.destinyModeEnabled) }}</text>
      </view>
      <view class="psu__row">
        <text class="psu__k">贴心推荐</text>
        <text class="psu__v">{{ labelBoolOnOff(payload.periodFeatureEnabled) }}</text>
      </view>
      <view class="psu__row">
        <text class="psu__k">商品推荐</text>
        <text class="psu__v">{{ labelBoolAccept(payload.acceptsProductRecommendation) }}</text>
      </view>
      <view class="psu__row">
        <text class="psu__k">生日</text>
        <text class="psu__v" :class="{ 'psu__v--muted': !payload.birthday }">
          {{ payload.birthday || '未填写' }}
        </text>
      </view>
      <view class="psu__row psu__row--last">
        <text class="psu__k">性别</text>
        <text class="psu__v" :class="{ 'psu__v--muted': payload.gender === 'unknown' }">
          {{ labelGender(payload.gender) }}
        </text>
      </view>
    </view>

    <view class="mp-card psu__value-card">
      <text class="psu__value-txt">
        从现在开始，推荐不再只是随机给你一道菜，而是会结合「今天的你」和「今天的状态」，给你更像专属安排的推荐理由。
      </text>
    </view>

    <view class="psu__bottom-spacer" />
  </view>
</template>

<script setup lang="ts">
import type { OnboardingGender } from '@/composables/useOnboardingDraft'

export type ProfileSummaryPayload = {
  flavorPreferences: string[]
  dislikeIngredients: string[]
  allergyIngredients: string[]
  dietGoals: string[]
  heightCm: number | null
  weightKg: number | null
  targetWeightKg: number | null
  cookingFrequency: string
  lifestyleTags: string[]
  familySize: string
  destinyModeEnabled: boolean | null
  periodFeatureEnabled: boolean | null
  acceptsProductRecommendation: boolean | null
  birthday: string
  gender: OnboardingGender
}

defineProps<{
  payload: ProfileSummaryPayload
}>()

const COOKING: Record<string, string> = {
  often: '经常做饭',
  sometimes: '偶尔做饭',
  rarely: '很少做饭',
  takeout: '主要点外卖',
}

const FAMILY: Record<string, string> = {
  single: '单人用餐',
  couple: '两人用餐',
  family3: '家庭 3~4 人',
  family5: '家庭 5 人以上',
}

const GENDER: Record<OnboardingGender, string> = {
  male: '男',
  female: '女',
  undisclosed: '不方便填写',
  unknown: '暂未设置',
}

function lineList(arr: string[]) {
  if (!arr.length) return '—'
  return arr.join('、')
}

function fmtCm(n: number | null) {
  if (n == null || !Number.isFinite(n)) return '—'
  return `${Math.round(n)} cm`
}

function fmtKg(n: number | null) {
  if (n == null || !Number.isFinite(n)) return '—'
  const s = String(n)
  return s.includes('.') ? `${n} kg` : `${n} kg`
}

function labelCooking(key: string) {
  return COOKING[key] || '—'
}

function labelFamily(key: string) {
  return FAMILY[key] || '—'
}

function labelGender(g: OnboardingGender) {
  return GENDER[g] || '—'
}

function labelBoolOnOff(v: boolean | null) {
  if (v === true) return '开启'
  if (v === false) return '先不开启'
  return '—'
}

function labelBoolAccept(v: boolean | null) {
  if (v === true) return '接受'
  if (v === false) return '先不要'
  return '—'
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.psu {
  padding: 8rpx 0 0;
}

.psu__celebrate {
  padding: 36rpx 32rpx 40rpx;
  margin-bottom: 24rpx;
}

.psu__badge {
  display: inline-block;
  padding: 8rpx 20rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent-deep;
  background: rgba(255, 255, 255, 0.7);
  border: 1rpx solid $mp-ring-accent;
}

.psu__hero-title {
  display: block;
  margin-top: 20rpx;
  font-size: 40rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.psu__hero-sub {
  display: block;
  margin-top: 16rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.psu__block {
  padding: 28rpx 28rpx 24rpx;
}

.psu__block + .psu__block {
  margin-top: 20rpx;
}

.psu__block-k {
  display: block;
  margin-bottom: 20rpx;
  font-size: 24rpx;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: $mp-accent;
  text-transform: uppercase;
}

.psu__row {
  padding: 18rpx 0;
  border-bottom: 1rpx solid #f3f4f6;
}

.psu__row--last {
  border-bottom-width: 0;
  padding-bottom: 4rpx;
}

.psu__k {
  display: block;
  font-size: 24rpx;
  color: $mp-text-muted;
  margin-bottom: 8rpx;
}

.psu__v {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  line-height: 1.5;
  color: $mp-text-primary;
}

.psu__v--muted {
  font-weight: 500;
  color: $mp-text-muted;
}

.psu__value-card {
  margin-top: 24rpx;
  padding: 28rpx 28rpx 32rpx;
  background: rgba(253, 250, 255, 0.9);
  border-color: $mp-ring-accent;
}

.psu__value-txt {
  font-size: 26rpx;
  line-height: 1.65;
  color: $mp-text-secondary;
}

.psu__bottom-spacer {
  height: 280rpx;
}
</style>
