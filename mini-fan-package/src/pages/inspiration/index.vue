<template>
  <view class="mp-page ins">
    <view class="mp-card ins__head">
      <view class="ins__top">
        <text class="ins__title">灵感</text>
        <button class="ins__link" @click="goPublish">发布</button>
      </view>
      <view class="ins__search">
        <text>⌕</text>
        <input v-model="keyword" placeholder="搜索图片灵感（标题/描述/作者）" confirm-type="search" @confirm="reload" />
      </view>
      <view class="ins__tabs">
        <view
          v-for="t in tabs"
          :key="t.value"
          class="ins__tab"
          :class="{ 'ins__tab--on': tab === t.value }"
          @click="switchTab(t.value)"
        >{{ t.label }}</view>
      </view>
    </view>

    <view v-if="!list.length && !loading" class="mp-card">
      <view class="mp-empty">
        <view class="mp-empty__icon">🖼️</view>
        <text class="mp-empty__title">暂无灵感内容</text>
        <button class="mp-btn-primary" @click="goPublish">去发布</button>
      </view>
    </view>

    <scroll-view v-else class="ins__scroll" scroll-y :lower-threshold="120" @scrolltolower="loadMore">
      <view class="ins__cols">
        <view class="ins__col">
          <WaterfallCard
            v-for="item in leftList"
            :key="item.id"
            :item="item"
            @open="openDetail(item.id)"
            @favorite="toggleFavorite(item.id)"
            @like="toggleLike(item.id)"
          />
        </view>
        <view class="ins__col">
          <WaterfallCard
            v-for="item in rightList"
            :key="item.id"
            :item="item"
            @open="openDetail(item.id)"
            @favorite="toggleFavorite(item.id)"
            @like="toggleLike(item.id)"
          />
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import WaterfallCard from '@/components/inspiration/WaterfallCard.vue'
import { favoriteInspiration, getInspirationList, likeInspiration } from '@/api/inspiration'
import type { InspirationFeedTab, InspirationItem } from '@/types/inspiration'

const tabs: Array<{ label: string; value: InspirationFeedTab }> = [
  { label: '推荐', value: 'recommend' },
  { label: '最新', value: 'latest' },
  { label: 'AI生成', value: 'ai_generated' },
  { label: '用户实拍', value: 'user_uploaded' },
]

// 默认优先展示全部用户的最新公开内容。
const tab = ref<InspirationFeedTab>('latest')
const keyword = ref('')
const list = ref<InspirationItem[]>([])
const page = ref(1)
const perPage = 10
const hasMore = ref(true)
const loading = ref(false)

const leftList = computed(() => list.value.filter((_, i) => i % 2 === 0))
const rightList = computed(() => list.value.filter((_, i) => i % 2 === 1))

async function fetchList(append: boolean) {
  loading.value = true
  try {
    const res = await getInspirationList({ tab: tab.value, page: page.value, perPage, keyword: keyword.value.trim() || undefined })
    list.value = append ? list.value.concat(res.items) : res.items
    hasMore.value = res.hasMore
  } finally {
    loading.value = false
  }
}
async function reload() {
  page.value = 1
  hasMore.value = true
  await fetchList(false)
}
async function loadMore() {
  if (!hasMore.value || loading.value) return
  page.value += 1
  await fetchList(true)
}
function switchTab(v: InspirationFeedTab) {
  tab.value = v
  void reload()
}
function openDetail(id: string) {
  uni.navigateTo({ url: `/pages/inspiration/detail?id=${encodeURIComponent(id)}` })
}
async function toggleFavorite(id: string) {
  const updated = await favoriteInspiration(id)
  if (!updated) return
  list.value = list.value.map((x) => (x.id === id ? updated : x))
}
async function toggleLike(id: string) {
  const updated = await likeInspiration(id)
  if (!updated) return
  list.value = list.value.map((x) => (x.id === id ? updated : x))
}
function goPublish() {
  uni.navigateTo({ url: '/pages/inspiration/publish' })
}

onShow(() => void reload())
onPullDownRefresh(() => {
  void (async () => {
    await reload()
    uni.stopPullDownRefresh()
  })()
})
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.ins{padding-bottom:calc(120rpx + env(safe-area-inset-bottom))}
.ins__head{padding:20rpx}
.ins__top{display:flex;justify-content:space-between;align-items:center}
.ins__title{font-size:40rpx;font-weight:800;color:$mp-text-primary}
.ins__link{font-size:26rpx;color:$mp-accent;background:transparent;border:none;padding:0}
.ins__link::after{display:none}
.ins__search{margin-top:14rpx;padding:12rpx 14rpx;border-radius:14rpx;border:1rpx solid $mp-border;background:#fafbfc;display:flex;gap:10rpx;align-items:center}
.ins__search input{flex:1;font-size:26rpx}
.ins__tabs{display:flex;gap:10rpx;flex-wrap:wrap;margin-top:14rpx}
.ins__tab{padding:10rpx 16rpx;border-radius:999rpx;background:#f5f5f7;color:$mp-text-secondary;font-size:22rpx}
.ins__tab--on{color:$mp-accent;background:$mp-accent-soft;border:1rpx solid $mp-ring-accent}
.ins__scroll{max-height:calc(100vh - 300rpx)}
.ins__cols{display:flex;gap:14rpx;padding:0 4rpx}
.ins__col{flex:1;min-width:0}
</style>
