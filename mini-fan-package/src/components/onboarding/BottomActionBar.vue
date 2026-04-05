<template>
  <view class="bab" :style="{ paddingBottom: `calc(24rpx + ${safeBottom}px)` }">
    <view class="bab__row">
      <slot name="left" />
      <slot name="right" />
    </view>
  </view>
</template>

<script setup lang="ts">
defineProps<{
  /** 底部安全区增量（px），由页面传入 uni.getSystemInfoSync().safeAreaInsets.bottom */
  safeBottom: number
}>()
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.bab {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 20;
  padding: 20rpx 32rpx 24rpx;
  box-sizing: border-box;
  background: linear-gradient(180deg, rgba(253, 253, 254, 0) 0%, $mp-surface 28%);
  border-top: 1rpx solid $mp-border;
}

.bab__row {
  display: flex;
  align-items: stretch;
  gap: 20rpx;
}

.bab__row :deep(button) {
  margin: 0;
}

/* mp-ui 主按钮默认 width:100%，底栏双键需横向分摊 */
.bab__row :deep(.mp-btn-primary) {
  width: auto !important;
  flex: 1;
}

.bab__row :deep(.mp-btn-ghost) {
  width: auto !important;
  flex: 0 0 auto;
  min-width: 200rpx;
}
</style>
