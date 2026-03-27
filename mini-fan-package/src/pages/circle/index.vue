<template>
  <view class="mp-page cr-home">
    <view class="mp-card cr-home__search-card">
      <view class="cr-home__search-row">
        <text class="cr-home__search-ico">⌕</text>
        <input
          v-model="keyword"
          class="cr-home__search-input"
          type="text"
          placeholder="搜索正文、标题或话题（演示）"
          placeholder-class="cr-home__search-ph"
          confirm-type="search"
          @confirm="reload"
        />
      </view>
      <view class="cr-home__toolbar-row">
        <text class="cr-home__hint">发现同好，分享餐桌灵感</text>
        <button type="button" class="cr-home__link" @click="goMyPosts">我的发布</button>
      </view>
    </view>

    <TopicTabs :model-value="feedTab" @update:model-value="onTabChange" />

    <view v-if="loading && list.length === 0" class="mp-card">
      <text class="cr-home__muted">加载中…</text>
    </view>

    <view v-else-if="!loading && list.length === 0" class="mp-card">
      <view class="mp-empty">
        <view class="mp-empty__icon">💬</view>
        <text class="mp-empty__title">暂无帖子</text>
        <text class="mp-empty__sub">换个关键词或切换「推荐 / 最新 / 关注」试试</text>
        <button class="mp-btn-primary" @click="goPublish">去发帖</button>
      </view>
    </view>

    <scroll-view
      v-else
      class="cr-home__scroll"
      scroll-y
      :lower-threshold="120"
      @scrolltolower="loadMore"
    >
      <PostCard
        v-for="p in list"
        :key="p.id"
        :post="p"
        @open="openDetail(p.id)"
        @like="onLike(p.id)"
        @collect="onCollect(p.id)"
      />

      <view v-if="loadingMore" class="cr-home__foot">
        <text class="cr-home__foot-txt">加载中…</text>
      </view>
      <view v-else-if="!hasMore && list.length > 0" class="cr-home__foot">
        <text class="cr-home__foot-txt cr-home__foot-txt--muted">没有更多了</text>
      </view>
    </scroll-view>

    <view class="cr-home__fab" @click="goPublish">
      <text class="cr-home__fab-txt">发帖</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import TopicTabs from '@/components/circle/TopicTabs.vue'
import PostCard from '@/components/circle/PostCard.vue'
import {
  collectCirclePost,
  getCirclePostList,
  likeCirclePost,
} from '@/api/circle'
import type { CircleFeedTab, CirclePost } from '@/types/circle'

const feedTab = ref<CircleFeedTab>('recommend')
const keyword = ref('')
const list = ref<CirclePost[]>([])
const page = ref(1)
const hasMore = ref(true)
const loading = ref(false)
const loadingMore = ref(false)
const perPage = 10

onLoad(() => {
  uni.showToast({ title: '圈子已升级为灵感', icon: 'none' })
  setTimeout(() => {
    uni.switchTab({ url: '/pages/inspiration/index' })
  }, 80)
})

async function fetchList(append: boolean) {
  if (append) loadingMore.value = true
  else loading.value = true
  try {
    const res = await getCirclePostList({
      tab: feedTab.value,
      page: page.value,
      perPage,
      keyword: keyword.value.trim() || undefined,
    })
    if (append) list.value = list.value.concat(res.items)
    else list.value = res.items
    hasMore.value = res.hasMore
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '加载失败', icon: 'none' })
    if (!append) list.value = []
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

async function reload() {
  page.value = 1
  hasMore.value = true
  await fetchList(false)
}

async function loadMore() {
  if (!hasMore.value || loadingMore.value || loading.value) return
  page.value += 1
  await fetchList(true)
}

function onTabChange(tab: CircleFeedTab) {
  feedTab.value = tab
  void reload()
}

onShow(() => {
  void reload()
})

onPullDownRefresh(() => {
  void (async () => {
    await reload()
    uni.stopPullDownRefresh()
  })()
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

function goMyPosts() {
  uni.navigateTo({ url: '/pages/circle/my-posts' })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.cr-home {
  padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
}

.cr-home__search-card {
  padding: 22rpx 24rpx 18rpx;
  margin-bottom: 0;
}

.cr-home__search-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
  padding: 12rpx 16rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.cr-home__search-ico {
  font-size: 28rpx;
  color: $mp-text-muted;
}

.cr-home__search-input {
  flex: 1;
  font-size: 28rpx;
  color: $mp-text-primary;
  min-height: 48rpx;
}

.cr-home__search-ph {
  color: #b0b5bf;
}

.cr-home__toolbar-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  margin-top: 16rpx;
}

.cr-home__hint {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.cr-home__link {
  margin: 0;
  padding: 0;
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-accent !important;
  background: transparent !important;
  border: none !important;
  line-height: 1.2;
}

.cr-home__link::after {
  display: none;
}

.cr-home__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
  text-align: center;
  display: block;
  padding: 24rpx;
}

.cr-home__scroll {
  max-height: calc(100vh - 320rpx);
}

.cr-home__foot {
  padding: 24rpx 0 48rpx;
  text-align: center;
}

.cr-home__foot-txt {
  font-size: 24rpx;
  color: $mp-text-muted;
}

.cr-home__foot-txt--muted {
  opacity: 0.85;
}

.cr-home__fab {
  position: fixed;
  right: 32rpx;
  bottom: calc(32rpx + env(safe-area-inset-bottom));
  z-index: 900;
  padding: 20rpx 28rpx;
  border-radius: 999rpx;
  background: linear-gradient(90deg, #7a57d1 0%, #6743bf 100%);
  box-shadow: 0 16rpx 48rpx rgba(122, 87, 209, 0.35);
}

.cr-home__fab-txt {
  font-size: 28rpx;
  font-weight: 700;
  color: #fff;
}
</style>
