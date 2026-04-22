<template>
  <view class="iw-card" @click="$emit('open')">
    <view class="iw-card__cover-wrap">
      <image class="iw-card__cover" :src="item.coverImage || item.images[0]" mode="widthFix" />
      <view class="iw-card__cover-mask" />
    </view>
    <view class="iw-card__body">
      <text v-if="item.title" class="iw-card__title">{{ item.title }}</text>
      <text v-if="item.description" class="iw-card__desc">{{ shortDesc }}</text>
      <view class="iw-card__meta">
        <view class="iw-card__author">
          <view class="iw-card__avatar"><text>{{ avatarText }}</text></view>
          <text class="iw-card__nick">{{ item.nickname }}</text>
        </view>
        <text class="iw-card__type">{{ item.sourceType === 'ai_generated' ? 'AI生成' : '用户实拍' }}</text>
      </view>
      <view class="iw-card__stats">
        <text @click.stop="$emit('favorite')">★ {{ item.favoriteCount }}</text>
        <text @click.stop="$emit('like')">♥ {{ item.likeCount }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { InspirationItem } from '@/types/inspiration'

const props = defineProps<{ item: InspirationItem }>()
defineEmits<{ open: []; favorite: []; like: [] }>()

const shortDesc = computed(() => {
  const t = props.item.description || ''
  return t.length > 44 ? `${t.slice(0, 44)}...` : t
})
const avatarText = computed(() => (props.item.nickname || '访').slice(0, 1))
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.iw-card { background:#fff; border:1rpx solid $mp-border; border-radius:18rpx; overflow:hidden; margin-bottom:16rpx; box-shadow:$mp-shadow-soft; }
.iw-card__cover-wrap { position:relative; overflow:hidden; }
.iw-card__cover { width:100%; min-height:220rpx; background:#f5f5f7; display:block; }
.iw-card__cover-mask { position:absolute; inset:0; pointer-events:none; background:linear-gradient(180deg, rgba(0,0,0,0.06), rgba(0,0,0,0.22)); }
.iw-card__body { padding:14rpx; }
.iw-card__title { display:block; font-size:26rpx; font-weight:700; color:$mp-text-primary; }
.iw-card__desc { display:block; margin-top:8rpx; font-size:22rpx; color:$mp-text-secondary; line-height:1.45; }
.iw-card__meta { margin-top:10rpx; display:flex; justify-content:space-between; align-items:center; gap:8rpx; }
.iw-card__author { display:flex; align-items:center; gap:8rpx; min-width:0; }
.iw-card__avatar { width:32rpx; height:32rpx; border-radius:50%; background:$mp-accent-soft; color:$mp-accent; font-size:20rpx; display:flex; align-items:center; justify-content:center; }
.iw-card__nick { font-size:20rpx; color:$mp-text-muted; }
.iw-card__type { font-size:20rpx; color:$mp-accent; }
.iw-card__stats { margin-top:10rpx; display:flex; gap:16rpx; font-size:22rpx; color:$mp-text-muted; }
</style>
