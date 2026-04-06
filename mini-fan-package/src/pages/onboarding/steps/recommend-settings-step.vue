<template>
  <view class="rs">
    <text class="rs__title">再补充一点推荐相关设置</text>
    <text class="rs__lead">这些不会影响正常使用，但能让推荐更像「今天轮到你」的安排。</text>

    <view class="mp-card rs__module">
      <view class="rs__mod-head">
        <text class="rs__mod-title">食命推荐</text>
        <text class="rs__mod-hint">单选</text>
      </view>
      <text class="rs__mod-desc">开启后，推荐会附带更有情绪价值的理由与「食命」文案。</text>
      <OptionSingleCardGroup v-model="destinySel" :options="onOffOptions" />
    </view>

    <view class="mp-card rs__module">
      <view class="rs__mod-head">
        <text class="rs__mod-title">特殊状态贴心推荐</text>
        <text class="rs__mod-hint">单选</text>
      </view>
      <text class="rs__mod-desc">
        在疲惫、胃口一般、特殊状态等情况下，优先推荐更适合当天的饮食。仅在你主动开启后才会询问当日状态，与性别等基础资料无自动关联。
      </text>
      <OptionSingleCardGroup v-model="periodSel" :options="onOffOptions" />
    </view>

    <view class="mp-card rs__module">
      <view class="rs__mod-head">
        <text class="rs__mod-title">商品推荐</text>
        <text class="rs__mod-hint">单选</text>
      </view>
      <text class="rs__mod-desc">后续推荐中可能会出现食材包或相关商品入口。</text>
      <OptionSingleCardGroup v-model="productSel" :options="acceptOptions" />
    </view>

    <view class="mp-card rs__module">
      <text class="rs__mod-title">生日（可选）</text>
      <text class="rs__mod-desc rs__mod-desc--compact">生日当天会给你更有仪式感的推荐。</text>
      <picker mode="date" :value="birthdayPickerValue" @change="onBirthChange">
        <view class="rs__picker">{{ birthdayDisplay }}</view>
      </picker>
    </view>

    <view class="mp-card rs__module">
      <view class="rs__mod-head">
        <text class="rs__mod-title">性别（可选）</text>
        <text class="rs__mod-hint">基础资料</text>
      </view>
      <text class="rs__mod-desc rs__mod-desc--compact">
        仅用于展示与统计，不会自动开启任何敏感能力，也不会单独决定上述推荐开关。
      </text>
      <OptionSingleCardGroup v-model="genderSel" :options="genderOptions" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import OptionSingleCardGroup from '@/components/onboarding/OptionSingleCardGroup.vue'
import type { OnboardingGender } from '@/composables/useOnboardingDraft'

const props = defineProps<{
  destinyModeEnabled: boolean | null
  periodFeatureEnabled: boolean | null
  acceptsProductRecommendation: boolean | null
  birthday: string
  gender: OnboardingGender
}>()

const emit = defineEmits<{
  'update:destinyModeEnabled': [boolean | null]
  'update:periodFeatureEnabled': [boolean | null]
  'update:acceptsProductRecommendation': [boolean | null]
  'update:birthday': [string]
  'update:gender': [OnboardingGender]
}>()

function triToSel(v: boolean | null, onId: string, offId: string): string {
  if (v === true) return onId
  if (v === false) return offId
  return ''
}

function selToTri(s: string, onId: string, offId: string): boolean | null {
  if (s === onId) return true
  if (s === offId) return false
  return null
}

const onOffOptions = [
  { id: 'on', label: '开启' },
  { id: 'off', label: '先不开启' },
]

const acceptOptions = [
  { id: 'accept', label: '接受' },
  { id: 'reject', label: '先不要' },
]

const genderOptions = [
  { id: 'male', label: '男' },
  { id: 'female', label: '女' },
  { id: 'undisclosed', label: '不方便填写' },
  { id: 'unknown', label: '暂未设置' },
]

const destinySel = computed({
  get: () => triToSel(props.destinyModeEnabled, 'on', 'off'),
  set: (s) => emit('update:destinyModeEnabled', selToTri(s, 'on', 'off')),
})

const periodSel = computed({
  get: () => triToSel(props.periodFeatureEnabled, 'on', 'off'),
  set: (s) => emit('update:periodFeatureEnabled', selToTri(s, 'on', 'off')),
})

const productSel = computed({
  get: () => triToSel(props.acceptsProductRecommendation, 'accept', 'reject'),
  set: (s) => emit('update:acceptsProductRecommendation', selToTri(s, 'accept', 'reject')),
})

const genderSel = computed({
  get: () => props.gender,
  set: (s) => emit('update:gender', s as OnboardingGender),
})

const birthdayPickerValue = computed(() => {
  const b = props.birthday.trim()
  return b || '2000-01-01'
})

const birthdayDisplay = computed(() => {
  const b = props.birthday.trim()
  return b || '点击选择日期'
})

function onBirthChange(e: { detail?: { value?: string } }) {
  emit('update:birthday', String(e.detail?.value || ''))
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.rs {
  padding: 6rpx 0 28rpx;
}

.rs__title {
  display: block;
  font-size: 34rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.rs__lead {
  display: block;
  margin-top: 12rpx;
  margin-bottom: 22rpx;
  font-size: 23rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.rs__module {
  padding: 22rpx 20rpx 22rpx;
}

.rs__module + .rs__module {
  margin-top: 16rpx;
}

.rs__mod-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 20rpx;
  margin-bottom: 10rpx;
}

.rs__mod-title {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.rs__mod-hint {
  font-size: 20rpx;
  color: $mp-text-muted;
  font-weight: 500;
}

.rs__mod-desc {
  display: block;
  margin-bottom: 14rpx;
  font-size: 22rpx;
  line-height: 1.48;
  color: $mp-text-secondary;
}

.rs__mod-desc--compact {
  margin-bottom: 12rpx;
}

.rs__picker {
  margin-top: 8rpx;
  height: 80rpx;
  padding: 0 20rpx;
  background: #f3f4f6;
  border-radius: 14rpx;
  font-size: 24rpx;
  line-height: 80rpx;
  color: $mp-text-primary;
}
</style>
