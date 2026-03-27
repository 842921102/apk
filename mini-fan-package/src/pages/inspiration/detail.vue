<template>
  <view class="mp-page idt">
    <view v-if="!item" class="mp-card"><text class="idt__muted">内容不存在或已下架</text></view>
    <template v-else>
      <view class="mp-card">
        <swiper v-if="item.images.length" class="idt__swiper" circular indicator-dots>
          <swiper-item v-for="(src, i) in item.images" :key="i" @click="preview(i)">
            <image class="idt__img" :src="src" mode="aspectFill" />
          </swiper-item>
        </swiper>
        <text v-if="item.title" class="idt__title">{{ item.title }}</text>
        <text v-if="item.description" class="idt__desc">{{ item.description }}</text>
        <text class="idt__meta">{{ item.nickname }} · {{ item.sourceType === 'ai_generated' ? 'AI生成' : '用户实拍' }} · {{ formatListTime(item.createdAt) }}</text>
        <view class="idt__actions">
          <button class="mp-btn-secondary" @click="onFavorite">{{ item.isFavorited ? '已收藏' : '收藏' }} {{ item.favoriteCount }}</button>
          <button class="mp-btn-ghost" @click="onLike">点赞 {{ item.likeCount }}</button>
        </view>
      </view>

      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">关联商品（预留）</text>
        <ProductCard
          v-if="item.relatedProduct"
          :product="item.relatedProduct"
          @view="onViewProduct"
        />
        <text v-else class="idt__muted">当前内容未绑定商品</text>
      </view>

      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">评论 {{ comments.length }}</text>
        <CommentItem v-for="c in comments" :key="c.id" :comment="c" />
        <text v-if="comments.length === 0" class="idt__muted">还没有评论，来抢沙发吧</text>
      </view>
    </template>

    <view class="idt__bar">
      <input v-model="draft" class="idt__input" placeholder="写评论..." confirm-type="send" @confirm="sendComment" />
      <button class="mp-btn-primary idt__send" @click="sendComment">发送</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { createInspirationComment, favoriteInspiration, getInspirationComments, getInspirationDetail, likeInspiration } from '@/api/inspiration'
import CommentItem from '@/components/inspiration/CommentItem.vue'
import ProductCard from '@/components/inspiration/ProductCard.vue'
import type { InspirationComment, InspirationItem } from '@/types/inspiration'
import { formatListTime } from '@/utils/dateFormat'

const id = ref('')
const item = ref<InspirationItem | null>(null)
const comments = ref<InspirationComment[]>([])
const draft = ref('')

async function load() {
  if (!id.value) return
  const [d, cs] = await Promise.all([getInspirationDetail(id.value), getInspirationComments(id.value)])
  item.value = d
  comments.value = cs
}
onLoad((q) => { id.value = typeof q?.id === 'string' ? decodeURIComponent(q.id) : '' })
onShow(() => void load())

function preview(i: number) {
  if (!item.value?.images.length) return
  uni.previewImage({ current: item.value.images[i], urls: item.value.images })
}
async function onFavorite() {
  if (!id.value) return
  const d = await favoriteInspiration(id.value)
  if (d) item.value = d
}
async function onLike() {
  if (!id.value) return
  const d = await likeInspiration(id.value)
  if (d) item.value = d
}
async function sendComment() {
  const content = draft.value.trim()
  if (!content || !id.value) return
  const c = await createInspirationComment(id.value, content)
  if (!c) return
  comments.value = comments.value.concat(c)
  draft.value = ''
}
function onViewProduct() {
  const productId = item.value?.relatedProduct?.id
  if (!productId) return
  uni.navigateTo({ url: `/pages/mall/product-detail?id=${encodeURIComponent(productId)}` })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.idt{padding-bottom:calc(160rpx + env(safe-area-inset-bottom))}
.idt__swiper{height:560rpx;border-radius:16rpx;overflow:hidden;background:#f5f5f7}
.idt__img{width:100%;height:100%}
.idt__title{display:block;margin-top:16rpx;font-size:34rpx;font-weight:800;color:$mp-text-primary}
.idt__desc{display:block;margin-top:10rpx;font-size:27rpx;line-height:1.55;color:$mp-text-primary;white-space:pre-wrap}
.idt__meta{display:block;margin-top:10rpx;font-size:22rpx;color:$mp-text-muted}
.idt__actions{margin-top:14rpx;display:flex;gap:10rpx}
.idt__actions button{flex:1}
.idt__muted{display:block;padding:16rpx 0;font-size:24rpx;color:$mp-text-muted}
.idt__bar{position:fixed;left:0;right:0;bottom:0;background:#fdfdfe;border-top:1rpx solid $mp-border;padding:12rpx 20rpx calc(12rpx + env(safe-area-inset-bottom));display:flex;gap:10rpx}
.idt__input{flex:1;padding:14rpx 16rpx;border-radius:14rpx;border:1rpx solid $mp-border;background:#fafbfc;font-size:26rpx}
.idt__send{width:150rpx;flex-shrink:0}
</style>
