<template>
  <view class="fs">
    <text class="fs__title">你平时更喜欢哪种口味？</text>
    <text class="fs__desc">可多选，用来决定推荐的基础口味方向。</text>
    <OptionChipGroup v-model="inner" :options="options" />
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import OptionChipGroup from '@/components/onboarding/OptionChipGroup.vue'

const props = defineProps<{
  modelValue: string[]
}>()

const emit = defineEmits<{
  'update:modelValue': [string[]]
}>()

const inner = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

/** 与草稿、后续接口字段对齐：存中文文案 */
const options = [
  { id: '清淡', label: '清淡' },
  { id: '香辣', label: '香辣' },
  { id: '酸甜', label: '酸甜' },
  { id: '家常', label: '家常' },
  { id: '重口', label: '重口' },
  { id: '汤汤水水', label: '汤汤水水' },
  { id: '下饭型', label: '下饭型' },
  { id: '烧烤风味', label: '烧烤风味' },
]
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.fs {
  padding: 8rpx 0 32rpx;
}

.fs__title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.fs__desc {
  display: block;
  margin-top: 16rpx;
  margin-bottom: 36rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}
</style>
