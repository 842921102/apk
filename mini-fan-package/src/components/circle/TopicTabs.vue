<template>
  <view class="topic-tabs mp-card">
    <view
      v-for="opt in options"
      :key="opt.value"
      class="topic-tabs__pill"
      :class="{ 'topic-tabs__pill--on': modelValue === opt.value }"
      @click="$emit('update:modelValue', opt.value)"
    >
      <text class="topic-tabs__txt">{{ opt.label }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import type { CircleFeedTab } from '@/types/circle'

defineProps<{
  modelValue: CircleFeedTab
}>()

defineEmits<{
  'update:modelValue': [v: CircleFeedTab]
}>()

const options: { value: CircleFeedTab; label: string }[] = [
  { value: 'recommend', label: '推荐' },
  { value: 'latest', label: '最新' },
  { value: 'following', label: '关注' },
]
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.topic-tabs {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  gap: 16rpx;
  padding: 20rpx 20rpx;
  margin-bottom: 24rpx;
}

.topic-tabs__pill {
  flex: 1;
  text-align: center;
  padding: 16rpx 12rpx;
  border-radius: 999rpx;
  border: 1rpx solid $mp-border;
  background: #fafbfc;
}

.topic-tabs__pill--on {
  border-color: $mp-ring-accent;
  background: $mp-accent-soft;
}

.topic-tabs__txt {
  font-size: 26rpx;
  font-weight: 600;
  color: $mp-text-secondary;
}

.topic-tabs__pill--on .topic-tabs__txt {
  color: $mp-accent;
}
</style>
