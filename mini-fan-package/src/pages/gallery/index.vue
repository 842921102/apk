<template>
  <view class="mp-page gl has-bottom-nav">
    <view class="mp-hero gl__hero">
      <view class="gl__hero-inner">
        <text class="mp-hero__kicker mp-kicker--on-dark">封神图鉴</text>
        <text class="mp-hero__title gl__hero-title">美食图鉴</text>
        <text class="mp-hero__sub gl__hero-sub">
          浏览 AI 厨艺成品图；支持云端列表（BFF）与本机缓存，删除与清空仅影响本机数据直至云端对接完成。
        </text>
        <view class="gl__hero-rule" />
      </view>
    </view>

    <view class="mp-card gl__head-card">
      <view class="gl__head-row">
        <view class="gl__head-ico">
          <text class="gl__head-emoji">🖼️</text>
        </view>
        <view class="gl__head-text">
          <text class="gl__head-title">AI 厨艺的视觉宝典</text>
          <text class="gl__head-sub">共 {{ items.length }} 张菜品图</text>
        </view>
        <button v-if="items.length > 0" class="gl__head-clear" @click="onClearTap">清空</button>
      </view>
      <view v-if="loadHint" class="gl__hint">
        <text class="gl__hint-txt">{{ loadHint }}</text>
      </view>
    </view>

    <view v-if="items.length > 0" class="gl__toolbar mp-card">
      <input v-model="searchQuery" class="gl__search" placeholder="搜索菜名、菜系或食材…" />
      <view class="gl__filters">
        <picker mode="selector" :range="cuisinePickerLabels" :value="cuisinePickerIndex" @change="onCuisinePick">
          <view class="gl__pick-display">
            <text class="gl__pick-label">菜系</text>
            <text class="gl__pick-val">{{ cuisinePickerLabels[cuisinePickerIndex] }}</text>
            <text class="gl__pick-chev">▼</text>
          </view>
        </picker>
        <picker mode="selector" :range="sortLabels" :value="sortIndex" @change="onSortPick">
          <view class="gl__pick-display">
            <text class="gl__pick-label">排序</text>
            <text class="gl__pick-val">{{ sortLabels[sortIndex] }}</text>
            <text class="gl__pick-chev">▼</text>
          </view>
        </picker>
      </view>
    </view>

    <view v-if="loading" class="mp-card gl__state gl__state--load">
      <text class="gl__state-ico">✨</text>
      <text class="gl__state-title">加载图鉴中…</text>
    </view>

    <view v-else-if="filtered.length > 0" class="gl__grid">
      <view v-for="img in filtered" :key="img.id" class="gl__cell mp-card" @click="openDetail(img)">
        <view class="gl__thumb-wrap">
          <image
            v-if="!imgFailed[img.id]"
            class="gl__thumb"
            :src="img.url"
            mode="aspectFill"
            @error="onImgError(img.id)"
          />
          <view v-else class="gl__thumb-fail">
            <text class="gl__thumb-fail-txt">图片加载失败</text>
          </view>
          <view class="gl__thumb-mask">
            <view class="gl__thumb-actions" @click.stop>
              <text class="gl__act" @click.stop="previewImage(img)">预览</text>
              <text class="gl__act gl__act--danger" @click.stop="confirmDelete(img.id)">删除</text>
            </view>
            <view class="gl__thumb-info">
              <text class="gl__thumb-title">{{ img.recipeName }}</text>
              <view class="gl__thumb-meta">
                <text class="gl__thumb-cuisine">{{ img.cuisine || '未分类' }}</text>
                <text class="gl__thumb-date">{{ formatDate(img.generatedAt) }}</text>
              </view>
              <view v-if="img.ingredients.length" class="gl__thumb-tags">
                <text
                  v-for="(ing, ix) in img.ingredients.slice(0, 3)"
                  :key="`${img.id}-ing-${ix}`"
                  class="gl__thumb-tag"
                >
                  {{ ing }}
                </text>
                <text v-if="img.ingredients.length > 3" class="gl__thumb-more">+{{ img.ingredients.length - 3 }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>
    </view>

    <view v-else-if="items.length === 0" class="mp-card gl__empty">
      <text class="gl__empty-ico">🍽️</text>
      <text class="gl__empty-title">图库还是空的</text>
      <text class="gl__empty-desc">
        在 App/Web 生成带图菜谱并保存后，可通过 BFF 同步到此处；也可在后续版本从小程序直接保存到本机图鉴。
      </text>
      <button class="mp-btn-primary gl__empty-btn" @click="goHome">去首页看看</button>
    </view>

    <view v-else class="mp-card gl__empty">
      <text class="gl__empty-title">没有匹配的条目</text>
      <text class="gl__empty-desc">试试更换搜索词或菜系筛选。</text>
      <button class="mp-btn-ghost gl__empty-btn" @click="clearFilters">清除筛选</button>
    </view>

    <!-- 详情 -->
    <view v-if="selected" class="gl__mask" @click="closeDetail">
      <scroll-view scroll-y class="gl__modal" @click.stop>
        <view class="gl__modal-head">
          <view class="gl__modal-titles">
            <text class="gl__modal-title">{{ selected.recipeName }}</text>
            <text class="gl__modal-sub">{{ selected.cuisine || '未分类' }} · {{ formatDate(selected.generatedAt) }}</text>
          </view>
          <text class="gl__modal-x" @click="closeDetail">✕</text>
        </view>
        <view class="gl__modal-img-wrap">
          <image
            v-if="!imgFailed[selected.id]"
            class="gl__modal-img"
            :src="selected.url"
            mode="widthFix"
            @error="onImgError(selected.id)"
            @click="previewImage(selected)"
          />
          <view v-else class="gl__modal-img-fail">图片暂不可用</view>
        </view>
        <view class="gl__modal-body">
          <text v-if="selected.ingredients.length" class="gl__modal-k">食材</text>
          <view v-if="selected.ingredients.length" class="gl__modal-tags">
            <text v-for="ing in selected.ingredients" :key="ing" class="gl__modal-tag">{{ ing }}</text>
          </view>
          <text v-if="selected.prompt" class="gl__modal-k">生成说明</text>
          <text v-if="selected.prompt" class="gl__modal-prompt">{{ selected.prompt }}</text>
          <view class="gl__modal-actions">
            <button
              v-if="isLoggedIn"
              class="mp-btn-ghost gl__modal-btn"
              :disabled="galleryFavLoading"
              @click="onToggleGalleryFavorite"
            >
              <text>{{ isGalleryFavorited ? '取消收藏' : '加入收藏' }}</text>
            </button>
            <button class="mp-btn-primary gl__modal-btn" @click="previewImage(selected)">全屏预览</button>
            <button class="mp-btn-ghost gl__modal-btn" @click="confirmDelete(selected.id)">删除此条</button>
          </view>
        </view>
      </scroll-view>
    </view>

    <MpIcoTabBar />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import MpIcoTabBar from '@/components/MpIcoTabBar.vue'
import {
  fetchGalleryList,
  removeLocalGalleryItem,
  clearLocalGallery,
} from '@/api/gallery'
import { useAuth } from '@/composables/useAuth'
import { useAppMessages } from '@/composables/useAppMessages'
import { isFavoriteRecipe, toggleFavoriteRecipe } from '@/api/biz'
import type { GalleryItem } from '@/types/gallery'

const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const isGalleryFavorited = ref(false)
const galleryFavLoading = ref(false)

const items = ref<GalleryItem[]>([])
const loading = ref(true)
const loadHint = ref('')
const searchQuery = ref('')
const selectedCuisine = ref('')
const sortBy = ref<'date-desc' | 'date-asc' | 'name-asc' | 'name-desc'>('date-desc')
const selected = ref<GalleryItem | null>(null)
const imgFailed = ref<Record<string, boolean>>({})

const sortLabels = ['最新生成', '最早生成', '菜名 A-Z', '菜名 Z-A']
const sortValues = ['date-desc', 'date-asc', 'name-asc', 'name-desc'] as const

const sortIndex = computed(() => {
  const i = sortValues.indexOf(sortBy.value)
  return i >= 0 ? i : 0
})

const cuisinePickerLabels = computed(() => {
  const set = new Set<string>()
  for (const img of items.value) {
    if (img.cuisine?.trim()) set.add(img.cuisine.trim())
  }
  return ['全部', ...Array.from(set).sort()]
})

const cuisinePickerIndex = computed(() => {
  const labels = cuisinePickerLabels.value
  if (!selectedCuisine.value) return 0
  const i = labels.indexOf(selectedCuisine.value)
  return i >= 0 ? i : 0
})

const filtered = computed(() => {
  let list = [...items.value]
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(
      (img) =>
        img.recipeName.toLowerCase().includes(q) ||
        (img.cuisine || '').toLowerCase().includes(q) ||
        img.ingredients.some((ing) => ing.toLowerCase().includes(q)),
    )
  }
  if (selectedCuisine.value) {
    list = list.filter((img) => (img.cuisine || '') === selectedCuisine.value)
  }
  list.sort((a, b) => {
    switch (sortBy.value) {
      case 'date-desc':
        return new Date(b.generatedAt).getTime() - new Date(a.generatedAt).getTime()
      case 'date-asc':
        return new Date(a.generatedAt).getTime() - new Date(b.generatedAt).getTime()
      case 'name-asc':
        return a.recipeName.localeCompare(b.recipeName, 'zh-CN')
      case 'name-desc':
        return b.recipeName.localeCompare(a.recipeName, 'zh-CN')
      default:
        return 0
    }
  })
  return list
})

function formatDate(dateString: string) {
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return '—'
  const now = new Date()
  const diffTime = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  if (diffDays === 0) return '今天'
  if (diffDays === 1) return '昨天'
  if (diffDays < 7) return `${diffDays}天前`
  return `${date.getMonth() + 1}/${date.getDate()}`
}

async function loadGallery(showFullSpinner = false) {
  if (showFullSpinner || items.value.length === 0) {
    loading.value = true
  }
  loadHint.value = ''
  await syncAuthFromSupabase()
  try {
    const { items: list, hint } = await fetchGalleryList()
    items.value = list
    loadHint.value = hint
  } finally {
    loading.value = false
  }
}

onShow(() => {
  void loadGallery(false)
})

onPullDownRefresh(() => {
  void loadGallery(true).finally(() => {
    uni.stopPullDownRefresh()
  })
})

function onCuisinePick(e: { detail: { value: number | string } }) {
  const labels = cuisinePickerLabels.value
  const idx = Number(e.detail.value)
  const label = labels[Number.isFinite(idx) ? idx : 0]
  selectedCuisine.value = label === '全部' ? '' : label
}

function onSortPick(e: { detail: { value: number | string } }) {
  const idx = Number(e.detail.value)
  const v = sortValues[Number.isFinite(idx) ? idx : 0]
  sortBy.value = v
}

function clearFilters() {
  searchQuery.value = ''
  selectedCuisine.value = ''
  sortBy.value = 'date-desc'
}

function galleryRecipeContent(img: GalleryItem): string {
  return (img.prompt?.trim() || img.recipeName || '').trim()
}

async function syncGalleryFavoriteState() {
  const img = selected.value
  if (!img || !isLoggedIn.value) {
    isGalleryFavorited.value = false
    return
  }
  try {
    isGalleryFavorited.value = await isFavoriteRecipe({
      source_type: 'gallery',
      source_id: String(img.id),
    })
  } catch {
    isGalleryFavorited.value = false
  }
}

watch([selected, isLoggedIn], () => {
  void syncGalleryFavoriteState()
})

async function onToggleGalleryFavorite() {
  const img = selected.value
  if (!img || !isLoggedIn.value) return
  if (galleryFavLoading.value) return
  galleryFavLoading.value = true
  try {
    const recipeContent = galleryRecipeContent(img)
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'gallery',
      source_id: String(img.id),
      title: img.recipeName,
      cuisine: img.cuisine ?? null,
      ingredients: img.ingredients ?? [],
      recipe_content: recipeContent || img.recipeName,
      extra_payload: { image_url: img.url },
    })
    isGalleryFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message?.slice(0, 42) || '操作失败', icon: 'none' })
  } finally {
    galleryFavLoading.value = false
  }
}

function openDetail(img: GalleryItem) {
  selected.value = img
}

function closeDetail() {
  selected.value = null
}

function previewImage(img: GalleryItem) {
  uni.previewImage({ urls: [img.url], current: img.url })
}

function onImgError(id: string) {
  imgFailed.value = { ...imgFailed.value, [id]: true }
}

function confirmDelete(id: string) {
  uni.showModal({
    title: '确认删除',
    content: '确定删除这张图片记录吗？此操作不可恢复（本机数据）。',
    success: (res) => {
      if (!res.confirm) return
      removeLocalGalleryItem(id)
      items.value = items.value.filter((x) => x.id !== id)
      if (selected.value?.id === id) selected.value = null
      uni.showToast({ title: '已删除', icon: 'none' })
    },
  })
}

function onClearTap() {
  uni.showModal({
    title: '清空图鉴',
    content: '确定清空本机图鉴全部记录吗？不可恢复。',
    success: (res) => {
      if (!res.confirm) return
      clearLocalGallery()
      items.value = []
      selected.value = null
      uni.showToast({ title: '已清空', icon: 'none' })
    },
  })
}

function goHome() {
  uni.switchTab({ url: '/pages/today-eat/index' })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.gl__hero-inner {
  text-align: center;
}

.gl__hero-title {
  max-width: 640rpx;
  margin-left: auto;
  margin-right: auto;
}

.gl__hero-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

.gl__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

.gl__head-card {
  margin-bottom: 24rpx;
  padding: 28rpx;
  border-color: $mp-ring-accent;
  box-shadow: 0 8rpx 32rpx rgba(122, 87, 209, 0.1);
}

.gl__head-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 20rpx;
}

.gl__head-ico {
  width: 96rpx;
  height: 96rpx;
  border-radius: 24rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.gl__head-emoji {
  font-size: 48rpx;
}

.gl__head-text {
  flex: 1;
  min-width: 0;
}

.gl__head-title {
  display: block;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.gl__head-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.gl__head-clear {
  padding: 0 24rpx;
  height: 64rpx;
  line-height: 64rpx;
  font-size: 24rpx;
  font-weight: 700;
  color: #b91c1c;
  background: #fef2f2;
  border: 1rpx solid #fecaca;
  border-radius: 12rpx;
}

.gl__hint {
  margin-top: 20rpx;
  padding: 16rpx 20rpx;
  border-radius: 12rpx;
  background: #eff6ff;
  border: 1rpx solid #bfdbfe;
}

.gl__hint-txt {
  font-size: 24rpx;
  color: #1e40af;
  line-height: 1.45;
}

.gl__toolbar {
  margin-bottom: 24rpx;
  padding: 24rpx;
}

.gl__search {
  width: 100%;
  padding: 20rpx 22rpx;
  border-radius: 14rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
  font-size: 26rpx;
  box-sizing: border-box;
  margin-bottom: 20rpx;
}

.gl__filters {
  display: flex;
  flex-direction: row;
  gap: 16rpx;
}

.gl__pick-display {
  flex: 1;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
  padding: 20rpx 22rpx;
  border-radius: 14rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.gl__pick-label {
  font-size: 22rpx;
  color: $mp-text-muted;
  font-weight: 600;
}

.gl__pick-val {
  flex: 1;
  font-size: 26rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.gl__pick-chev {
  font-size: 20rpx;
  color: $mp-text-muted;
}

.gl__state {
  padding: 56rpx;
  text-align: center;
  margin-bottom: 24rpx;
}

.gl__state-ico {
  font-size: 56rpx;
}

.gl__state-title {
  display: block;
  margin-top: 12rpx;
  font-size: 28rpx;
  color: $mp-text-secondary;
}

.gl__grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 20rpx;
  padding-bottom: 48rpx;
}

.gl__cell {
  width: calc(50% - 10rpx);
  box-sizing: border-box;
  padding: 0;
  overflow: hidden;
}

.gl__thumb-wrap {
  position: relative;
  width: 100%;
  height: 280rpx;
  background: #e5e7eb;
}

.gl__thumb {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: block;
}

.gl__thumb-fail {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
}

.gl__thumb-fail-txt {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.gl__thumb-mask {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  top: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.82) 0%, transparent 55%);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  pointer-events: none;
}

.gl__thumb-actions {
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
  gap: 12rpx;
  padding: 12rpx;
  pointer-events: auto;
}

.gl__act {
  font-size: 22rpx;
  font-weight: 700;
  color: #fff;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(59, 130, 246, 0.85);
}

.gl__act--danger {
  background: rgba(239, 68, 68, 0.9);
}

.gl__thumb-info {
  padding: 16rpx;
  pointer-events: none;
}

.gl__thumb-title {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.gl__thumb-meta {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  margin-top: 8rpx;
}

.gl__thumb-cuisine,
.gl__thumb-date {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.88);
}

.gl__thumb-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6rpx;
  margin-top: 10rpx;
}

.gl__thumb-tag {
  font-size: 20rpx;
  color: #fff;
  padding: 4rpx 10rpx;
  border-radius: 8rpx;
  background: rgba(255, 255, 255, 0.2);
  border: 1rpx solid rgba(255, 255, 255, 0.35);
}

.gl__thumb-more {
  font-size: 20rpx;
  color: rgba(255, 255, 255, 0.75);
  padding: 4rpx 8rpx;
}

.gl__empty {
  padding: 56rpx 32rpx;
  text-align: center;
  margin-bottom: 32rpx;
}

.gl__empty-ico {
  font-size: 72rpx;
}

.gl__empty-title {
  display: block;
  margin-top: 16rpx;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.gl__empty-desc {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.55;
  text-align: left;
}

.gl__empty-btn {
  margin-top: 32rpx;
  max-width: 400rpx;
}

.gl__mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 0;
  box-sizing: border-box;
}

.gl__modal {
  width: 100%;
  max-height: 90vh;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  border: 1rpx solid $mp-border;
}

.gl__modal-head {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  padding: 28rpx 32rpx;
  border-bottom: 1rpx solid $mp-border;
  background: linear-gradient(90deg, $mp-accent-soft, #fff);
}

.gl__modal-titles {
  flex: 1;
  min-width: 0;
  padding-right: 16rpx;
}

.gl__modal-title {
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.gl__modal-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-secondary;
}

.gl__modal-x {
  font-size: 40rpx;
  color: $mp-text-muted;
  padding: 8rpx;
}

.gl__modal-img-wrap {
  background: #0a0a0a;
  min-height: 360rpx;
}

.gl__modal-img {
  width: 100%;
  display: block;
}

.gl__modal-img-fail {
  padding: 80rpx;
  text-align: center;
  color: #9ca3af;
  font-size: 26rpx;
}

.gl__modal-body {
  padding: 28rpx 32rpx 48rpx;
}

.gl__modal-k {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-accent-deep;
  margin-bottom: 12rpx;
  margin-top: 8rpx;
}

.gl__modal-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-bottom: 20rpx;
}

.gl__modal-tag {
  font-size: 24rpx;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: #f3f4f6;
  color: #374151;
}

.gl__modal-prompt {
  font-size: 26rpx;
  color: #374151;
  line-height: 1.55;
  white-space: pre-wrap;
}

.gl__modal-actions {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  margin-top: 32rpx;
}

.gl__modal-btn {
  margin: 0;
}
</style>
