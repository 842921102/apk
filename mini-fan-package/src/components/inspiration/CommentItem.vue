<template>
  <view class="ic">
    <view class="ic__avatar"><text>{{ (comment.nickname || '访').slice(0, 1) }}</text></view>
    <view class="ic__body">
      <text class="ic__nick">{{ comment.nickname }}</text>
      <text class="ic__time">{{ formatListTime(comment.createdAt) }}</text>
      <text class="ic__content">{{ comment.content }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { formatListTime } from '@/utils/dateFormat'
import type { InspirationComment } from '@/types/inspiration'

defineProps<{ comment: InspirationComment }>()
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.ic{display:flex;gap:14rpx;padding:18rpx 0;border-bottom:1rpx solid #f3f4f6}
.ic__avatar{width:58rpx;height:58rpx;border-radius:50%;background:$mp-accent-soft;color:$mp-accent;display:flex;align-items:center;justify-content:center;font-size:24rpx}
.ic__body{flex:1;min-width:0}
.ic__nick{font-size:24rpx;font-weight:700;color:$mp-text-primary}
.ic__time{display:block;margin-top:4rpx;font-size:20rpx;color:$mp-text-muted}
.ic__content{display:block;margin-top:8rpx;font-size:25rpx;line-height:1.5;color:$mp-text-primary;white-space:pre-wrap}
</style>
