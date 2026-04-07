<template>
  <view class="bab" :style="{ paddingBottom: `calc(24rpx + ${safeBottom}px)` }">
    <view class="bab__row">
      <!-- 小程序里 button 作 flex 子节点常不按比例分配宽度，用 view 包一层再写死 3:7 -->
      <view class="bab__slot bab__slot--prev">
        <slot name="left" />
      </view>
      <view class="bab__slot bab__slot--next">
        <slot name="right" />
      </view>
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

.bab__slot {
  min-width: 0;
  display: flex;
  align-items: stretch;
}

.bab__slot--prev {
  flex: 3 3 0%;
}

.bab__slot--next {
  flex: 7 7 0%;
}

.bab__slot :deep(button) {
  margin: 0;
  width: 100% !important;
}

/* 配色沿用全局 mp-btn-*；宽度由外层 bab__slot 的 3:7 决定 */
.bab__slot :deep(.mp-btn-primary) {
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

.bab__slot :deep(.mp-btn-ghost) {
  height: 44rpx;
  padding: 0 16rpx;
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

.bab__slot :deep(.mp-btn-ghost text),
.bab__slot :deep(.mp-btn-primary text) {
  white-space: nowrap;
  word-break: keep-all;
  display: inline-block;
  line-height: 1;
}
</style>
