<template>
  <view class="c-comment">
    <view class="c-comment__avatar">
      <text class="c-comment__avatar-txt">{{ avatarText }}</text>
    </view>
    <view class="c-comment__body">
      <text class="c-comment__nick">{{ comment.nickname }}</text>
      <text class="c-comment__meta">{{ formatListTime(comment.createdAt) }}</text>
      <text class="c-comment__content">{{ comment.content }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CircleComment } from '@/types/circle'
import { formatListTime } from '@/utils/dateFormat'

const props = defineProps<{
  comment: CircleComment
}>()

const avatarText = computed(() => {
  const n = props.comment.nickname?.trim() || '访'
  return n.slice(0, 1)
})
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.c-comment {
  display: flex;
  flex-direction: row;
  gap: 20rpx;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f3f4f6;
}

.c-comment:last-child {
  border-bottom-width: 0;
}

.c-comment__avatar {
  width: 64rpx;
  height: 64rpx;
  border-radius: 50%;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.c-comment__avatar-txt {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-accent;
}

.c-comment__body {
  flex: 1;
  min-width: 0;
}

.c-comment__nick {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.c-comment__meta {
  display: block;
  margin-top: 4rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.c-comment__content {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-primary;
  white-space: pre-wrap;
}
</style>
