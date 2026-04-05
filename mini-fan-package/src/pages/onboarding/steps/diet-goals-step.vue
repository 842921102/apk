<template>
  <view class="dgs">
    <text class="dgs__title">你现在更在意哪一种饮食方向？</text>
    <text class="dgs__desc">这个会影响推荐的取向、理由和后续服务方向。</text>
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

const options = [
  { id: '减脂轻负担', label: '减脂轻负担' },
  { id: '增肌补能量', label: '增肌补能量' },
  { id: '保持体重', label: '保持体重' },
  { id: '养胃温和', label: '养胃温和' },
  { id: '清淡饮食', label: '清淡饮食' },
  { id: '解馋优先', label: '解馋优先' },
  { id: '高蛋白', label: '高蛋白' },
  { id: '家人一起吃更重要', label: '家人一起吃更重要' },
]
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.dgs {
  padding: 8rpx 0 32rpx;
}

.dgs__title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.dgs__desc {
  display: block;
  margin-top: 16rpx;
  margin-bottom: 36rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}
</style>
