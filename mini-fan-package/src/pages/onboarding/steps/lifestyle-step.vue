<template>
  <view class="ls">
    <text class="ls__title">你的生活方式更接近哪一种？</text>
    <text class="ls__lead">
      这些信息会影响系统判断你更适合快手菜、治愈型还是健康型推荐。
    </text>

    <view class="mp-card ls__module">
      <view class="ls__mod-head">
        <text class="ls__mod-title">做饭频率</text>
        <text class="ls__mod-hint">单选</text>
      </view>
      <OptionSingleCardGroup v-model="cookingInner" :options="cookingOptions" />
    </view>

    <view class="mp-card ls__module">
      <view class="ls__mod-head">
        <text class="ls__mod-title">生活习惯标签</text>
        <text class="ls__mod-hint">可多选</text>
      </view>
      <OptionChipGroup v-model="tagsInner" :options="lifestyleTagOptions" />
    </view>

    <view class="mp-card ls__module">
      <view class="ls__mod-head">
        <text class="ls__mod-title">家庭人数 / 用餐场景</text>
        <text class="ls__mod-hint">单选</text>
      </view>
      <OptionSingleCardGroup v-model="familyInner" :options="familyOptions" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import OptionChipGroup from '@/components/onboarding/OptionChipGroup.vue'
import OptionSingleCardGroup from '@/components/onboarding/OptionSingleCardGroup.vue'

const props = defineProps<{
  cookingFrequency: string
  lifestyleTags: string[]
  familySize: string
}>()

const emit = defineEmits<{
  'update:cookingFrequency': [string]
  'update:lifestyleTags': [string[]]
  'update:familySize': [string]
}>()

const cookingInner = computed({
  get: () => props.cookingFrequency,
  set: (v) => emit('update:cookingFrequency', v),
})

const tagsInner = computed({
  get: () => props.lifestyleTags,
  set: (v) => emit('update:lifestyleTags', v),
})

const familyInner = computed({
  get: () => props.familySize,
  set: (v) => emit('update:familySize', v),
})

const cookingOptions = [
  { id: 'often', label: '经常做饭' },
  { id: 'sometimes', label: '偶尔做饭' },
  { id: 'rarely', label: '很少做饭' },
  { id: 'takeout', label: '主要点外卖' },
]

const lifestyleTagOptions = [
  { id: '工作日很忙', label: '工作日很忙' },
  { id: '经常熬夜', label: '经常熬夜' },
  { id: '经常点外卖', label: '经常点外卖' },
  { id: '喜欢自己做饭', label: '喜欢自己做饭' },
  { id: '周末会下厨', label: '周末会下厨' },
  { id: '想省时间', label: '想省时间' },
]

const familyOptions = [
  { id: 'single', label: '单人用餐' },
  { id: 'couple', label: '两人用餐' },
  { id: 'family3', label: '家庭 3~4 人' },
  { id: 'family5', label: '家庭 5 人以上' },
]
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.ls {
  padding: 6rpx 0 28rpx;
}

.ls__title {
  display: block;
  font-size: 34rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.ls__lead {
  display: block;
  margin-top: 12rpx;
  margin-bottom: 22rpx;
  font-size: 23rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.ls__module {
  padding: 22rpx 20rpx 22rpx;
}

.ls__module + .ls__module {
  margin-top: 16rpx;
}

.ls__mod-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 20rpx;
  margin-bottom: 14rpx;
  padding-bottom: 14rpx;
  border-bottom: 1rpx solid #f0f2f5;
}

.ls__mod-title {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.ls__mod-hint {
  font-size: 20rpx;
  color: $mp-text-muted;
  font-weight: 500;
}
</style>
