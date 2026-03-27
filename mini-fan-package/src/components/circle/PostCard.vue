<template>
  <view class="post-card mp-card" @click="onCardClick">
    <view class="post-card__head">
      <view class="post-card__avatar">
        <text class="post-card__avatar-txt">{{ avatarText }}</text>
      </view>
      <view class="post-card__head-text">
        <text class="post-card__nick">{{ post.nickname }}</text>
        <text class="post-card__meta">{{ formatListTime(post.createdAt) }}</text>
      </view>
      <view v-if="post.topic" class="post-card__topic">
        <text class="post-card__topic-txt">{{ post.topic }}</text>
      </view>
    </view>

    <text v-if="post.title" class="post-card__title">{{ post.title }}</text>
    <text class="post-card__summary">{{ summary }}</text>

    <view v-if="post.images.length" class="post-card__imgs" @click.stop>
      <view
        v-if="post.images.length === 1"
        class="post-card__img-wrap post-card__img-wrap--1"
        @click.stop="preview(0)"
      >
        <image class="post-card__img" :src="post.images[0]" mode="aspectFill" />
      </view>
      <view v-else class="post-card__grid">
        <view
          v-for="(src, i) in displayImages"
          :key="i"
          class="post-card__grid-cell"
          @click.stop="preview(i)"
        >
          <image class="post-card__img" :src="src" mode="aspectFill" />
        </view>
      </view>
    </view>

    <view class="post-card__foot" @click.stop>
      <view class="post-card__stat" @click.stop="emitLike">
        <text class="post-card__stat-ico" :class="{ 'post-card__stat-ico--on': post.isLiked }">♥</text>
        <text class="post-card__stat-num">{{ post.likeCount }}</text>
      </view>
      <view class="post-card__stat" @click.stop="onCardClick">
        <text class="post-card__stat-ico">💬</text>
        <text class="post-card__stat-num">{{ post.commentCount }}</text>
      </view>
      <view class="post-card__stat" @click.stop="emitCollect">
        <text class="post-card__stat-ico" :class="{ 'post-card__stat-ico--on': post.isCollected }">★</text>
        <text class="post-card__stat-lbl">{{ post.isCollected ? '已藏' : '收藏' }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CirclePost } from '@/types/circle'
import { formatListTime } from '@/utils/dateFormat'

const props = defineProps<{
  post: CirclePost
}>()

const emit = defineEmits<{
  like: []
  collect: []
  open: []
}>()

const avatarText = computed(() => {
  const n = props.post.nickname?.trim() || '访'
  return n.slice(0, 1)
})

const summary = computed(() => {
  const t = props.post.content?.trim() || ''
  if (t.length <= 140) return t
  return `${t.slice(0, 140)}…`
})

const displayImages = computed(() => props.post.images.slice(0, 9))

function onCardClick() {
  emit('open')
}

function emitLike() {
  emit('like')
}

function emitCollect() {
  emit('collect')
}

function preview(index: number) {
  const urls = props.post.images.filter(Boolean)
  if (!urls.length) return
  uni.previewImage({
    current: urls[index],
    urls,
  })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.post-card {
  padding: 24rpx 24rpx 20rpx;
  margin-bottom: 24rpx;
}

.post-card:last-child {
  margin-bottom: 0;
}

.post-card__head {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
}

.post-card__avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.post-card__avatar-txt {
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-accent;
}

.post-card__head-text {
  flex: 1;
  min-width: 0;
}

.post-card__nick {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.post-card__meta {
  display: block;
  margin-top: 4rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.post-card__topic {
  flex-shrink: 0;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.post-card__topic-txt {
  font-size: 22rpx;
  font-weight: 600;
  color: $mp-accent;
}

.post-card__title {
  display: block;
  margin-top: 16rpx;
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
}

.post-card__summary {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-primary;
  white-space: pre-wrap;
}

.post-card__imgs {
  margin-top: 16rpx;
}

.post-card__img-wrap {
  border-radius: 16rpx;
  overflow: hidden;
  border: 1rpx solid $mp-border;
}

.post-card__img-wrap--1 {
  max-height: 360rpx;
}

.post-card__img {
  width: 100%;
  height: 240rpx;
  display: block;
}

.post-card__img-wrap--1 .post-card__img {
  height: 360rpx;
}

.post-card__grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 8rpx;
}

.post-card__grid-cell {
  width: 31%;
  height: 200rpx;
  border-radius: 12rpx;
  overflow: hidden;
  border: 1rpx solid $mp-border;
}

.post-card__grid-cell .post-card__img {
  height: 100%;
  width: 100%;
}

.post-card__foot {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 28rpx;
  margin-top: 20rpx;
  padding-top: 16rpx;
  border-top: 1rpx solid #f3f4f6;
}

.post-card__stat {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8rpx;
}

.post-card__stat-ico {
  font-size: 28rpx;
  color: $mp-text-muted;
}

.post-card__stat-ico--on {
  color: #e55151;
}

.post-card__stat:nth-child(3) .post-card__stat-ico--on {
  color: $mp-accent;
}

.post-card__stat-num,
.post-card__stat-lbl {
  font-size: 24rpx;
  color: $mp-text-secondary;
  font-weight: 600;
}
</style>
