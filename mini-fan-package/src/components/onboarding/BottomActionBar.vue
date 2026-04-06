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
  padding: 14rpx 24rpx 18rpx;
  box-sizing: border-box;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  border-top: 1rpx solid rgba($mp-border, 0.9);
}

.bab__row {
  display: flex;
  align-items: stretch;
  gap: 14rpx;
}

.bab__row :deep(button) {
  margin: 0;
}

/* mp-ui 主按钮默认 width:100%，底栏双键需横向分摊 */
.bab__row :deep(.mp-btn-primary) {
  width: auto !important;
  flex: 1;
  height: 44rpx;
  padding: 0 20rpx;
  border-radius: 12rpx;
  font-size: 28rpx;
  line-height: 44rpx;
  box-sizing: border-box;
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bab__row :deep(.mp-btn-ghost) {
  width: auto !important;
  flex: 0 0 auto;
  min-width: 252rpx;
  height: 44rpx;
  padding: 0 28rpx;
  border-radius: 12rpx;
  font-size: 24rpx;
  line-height: 44rpx;
  box-sizing: border-box;
  color: #6b7280 !important;
  background: #f8fafc !important;
  border-color: #e5e7eb !important;
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bab__row :deep(.mp-btn-ghost text),
.bab__row :deep(.mp-btn-primary text) {
  white-space: nowrap;
  word-break: keep-all;
  display: inline-block;
  line-height: 1;
}
</style>
