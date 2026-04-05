<template>
  <view class="oscg">
    <view
      v-for="opt in options"
      :key="opt.id"
      class="oscg__card"
      :class="{ 'oscg__card--active': modelValue === opt.id }"
      @click="select(opt.id)"
    >
      <text class="oscg__txt">{{ opt.label }}</text>
      <view v-if="modelValue === opt.id" class="oscg__check">✓</view>
    </view>
  </view>
</template>

<script setup lang="ts">
export type SingleCardOption = { id: string; label: string }

const props = defineProps<{
  options: SingleCardOption[]
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string]
}>()

function select(id: string) {
  if (props.modelValue === id) return
  emit('update:modelValue', id)
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.oscg {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.oscg__card {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 32rpx;
  border-radius: 20rpx;
  background: $mp-surface;
  border: 2rpx solid $mp-border;
  box-sizing: border-box;
  box-shadow: $mp-shadow-soft;
}

.oscg__card--active {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
  box-shadow: 0 8rpx 24rpx rgba(122, 87, 209, 0.1);
}

.oscg__txt {
  flex: 1;
  font-size: 30rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.oscg__check {
  flex-shrink: 0;
  margin-left: 16rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-accent;
}

.oscg__card--active .oscg__txt {
  color: $mp-accent-deep;
}
</style>
