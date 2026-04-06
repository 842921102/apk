<template>
  <view class="osh" :style="{ paddingTop: `${statusBarPx}px` }">
    <view class="osh__row" :style="{ height: `${navBarPx}px` }">
      <view class="osh__side osh__side--left">
        <text class="osh__link" @click="emit('back')">返回</text>
      </view>
      <view class="osh__mid">
        <text class="osh__progress">{{ progressCurrent }} / {{ progressTotal }}</text>
      </view>
      <view class="osh__side osh__side--right" />
    </view>
  </view>
</template>

<script setup lang="ts">
defineProps<{
  statusBarPx: number
  navBarPx: number
  progressCurrent: number
  progressTotal: number
}>()

const emit = defineEmits<{
  back: []
  skip: []
}>()
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.osh {
  flex-shrink: 0;
  background: $mp-surface;
  border-bottom: 1rpx solid $mp-border;
}

.osh__row {
  display: flex;
  align-items: center;
  padding: 0 18rpx;
  box-sizing: border-box;
}

.osh__side {
  flex: 1;
  min-width: 0;
}

.osh__side--right {
  display: flex;
  justify-content: flex-end;
}

.osh__mid {
  flex: 0 0 auto;
}

.osh__progress {
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
  letter-spacing: 0.04em;
}

.osh__link {
  font-size: 26rpx;
  font-weight: 600;
  color: $mp-accent;
  padding: 10rpx 8rpx;
}
</style>
