<template>
  <view class="mp-page cr-my">
    <view v-if="loading && list.length === 0" class="mp-card">
      <text class="cr-my__muted">加载中…</text>
    </view>
    <view v-else-if="!loading && list.length === 0" class="mp-card">
      <view class="mp-empty">
        <view class="mp-empty__icon">📝</view>
        <text class="mp-empty__title">你还没有发布过帖子</text>
        <text class="mp-empty__sub">去圈子首页发帖，和同好一起交流吧</text>
        <button class="mp-btn-primary" @click="goPublish">去发帖</button>
      </view>
    </view>
    <scroll-view v-else scroll-y class="cr-my__scroll">
      <PostCard
        v-for="p in list"
        :key="p.id"
        :post="p"
        @open="openDetail(p.id)"
        @like="onLike(p.id)"
        @collect="onCollect(p.id)"
      />
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import PostCard from '@/components/circle/PostCard.vue'
import { collectCirclePost, getMyCirclePosts, likeCirclePost } from '@/api/circle'
import type { CirclePost } from '@/types/circle'

const list = ref<CirclePost[]>([])
const loading = ref(false)

onLoad(() => {
  uni.showToast({ title: '已并入灵感首页', icon: 'none' })
  setTimeout(() => {
    uni.redirectTo({ url: '/pages/inspiration/index' })
  }, 80)
})

async function load() {
  loading.value = true
  try {
    list.value = await getMyCirclePosts()
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '加载失败', icon: 'none' })
    list.value = []
  } finally {
    loading.value = false
  }
}

onShow(() => {
  void load()
})

function openDetail(id: string) {
  uni.navigateTo({ url: `/pages/circle/detail?id=${encodeURIComponent(id)}` })
}

async function onLike(id: string) {
  try {
    const updated = await likeCirclePost(id)
    if (!updated) return
    list.value = list.value.map((p) => (p.id === id ? updated : p))
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '操作失败', icon: 'none' })
  }
}

async function onCollect(id: string) {
  try {
    const updated = await collectCirclePost(id)
    if (!updated) return
    list.value = list.value.map((p) => (p.id === id ? updated : p))
    uni.showToast({ title: updated.isCollected ? '已收藏' : '已取消', icon: 'none' })
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '操作失败', icon: 'none' })
  }
}

function goPublish() {
  uni.navigateTo({ url: '/pages/circle/publish' })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.cr-my__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
  text-align: center;
  display: block;
  padding: 32rpx;
}

.cr-my__scroll {
  max-height: calc(100vh - 120rpx);
}
</style>
