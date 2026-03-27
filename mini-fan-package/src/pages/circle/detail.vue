<template>
  <view class="mp-page cr-detail">
    <view v-if="!post && !loading" class="mp-card">
      <text class="cr-detail__muted">帖子不存在或已删除</text>
    </view>

    <view v-else-if="loading && !post" class="mp-card">
      <text class="cr-detail__muted">加载中…</text>
    </view>

    <template v-else-if="post">
      <view class="mp-card cr-detail__post">
        <view class="cr-detail__head">
          <view class="cr-detail__avatar">
            <text class="cr-detail__avatar-txt">{{ avatarText }}</text>
          </view>
          <view class="cr-detail__head-text">
            <text class="cr-detail__nick">{{ post.nickname }}</text>
            <text class="cr-detail__meta">{{ formatListTime(post.createdAt) }} · {{ post.topic }}</text>
          </view>
        </view>
        <text v-if="post.title" class="cr-detail__title">{{ post.title }}</text>
        <text class="cr-detail__content">{{ post.content }}</text>
        <view v-if="post.images.length" class="cr-detail__imgs">
          <view
            v-for="(src, i) in post.images"
            :key="i"
            class="cr-detail__img-wrap"
            @click="preview(i)"
          >
            <image class="cr-detail__img" :src="src" mode="aspectFill" />
          </view>
        </view>

        <view class="cr-detail__actions">
          <button type="button" class="mp-btn-secondary cr-detail__btn" @click="toggleLike">
            <text v-if="post.isLiked">♥ 已赞 {{ post.likeCount }}</text>
            <text v-else>♡ 点赞 {{ post.likeCount }}</text>
          </button>
          <button type="button" class="mp-btn-ghost cr-detail__btn" @click="toggleCollect">
            <text>{{ post.isCollected ? '★ 已收藏' : '☆ 收藏' }}</text>
          </button>
        </view>
      </view>

      <view class="mp-card cr-detail__comments">
        <text class="mp-kicker mp-kicker--accent">评论 {{ comments.length }}</text>
        <view v-if="comments.length === 0" class="cr-detail__no-c">
          <text class="cr-detail__muted">还没有评论，来做沙发～</text>
        </view>
        <CommentItem v-for="c in comments" :key="c.id" :comment="c" />
      </view>
    </template>

    <view class="cr-detail__input-bar">
      <input
        v-model="draft"
        class="cr-detail__input"
        type="text"
        placeholder="写评论…"
        confirm-type="send"
        @confirm="sendComment"
      />
      <button type="button" class="mp-btn-primary cr-detail__send" :disabled="sending" @click="sendComment">
        {{ sending ? '…' : '发送' }}
      </button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import CommentItem from '@/components/circle/CommentItem.vue'
import {
  collectCirclePost,
  createCircleComment,
  getCircleComments,
  getCirclePostDetail,
  likeCirclePost,
} from '@/api/circle'
import type { CircleComment, CirclePost } from '@/types/circle'
import { formatListTime } from '@/utils/dateFormat'

const postId = ref('')
const post = ref<CirclePost | null>(null)
const comments = ref<CircleComment[]>([])
const loading = ref(false)
const draft = ref('')
const sending = ref(false)

const avatarText = computed(() => {
  const n = post.value?.nickname?.trim() || '访'
  return n.slice(0, 1)
})

async function loadAll() {
  if (!postId.value) return
  loading.value = true
  try {
    const [p, c] = await Promise.all([
      getCirclePostDetail(postId.value),
      getCircleComments(postId.value),
    ])
    post.value = p
    comments.value = c
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '加载失败', icon: 'none' })
    post.value = null
  } finally {
    loading.value = false
  }
}

onLoad((opts) => {
  const id = typeof opts?.id === 'string' ? opts.id : ''
  if (id) {
    uni.redirectTo({ url: `/pages/inspiration/detail?id=${encodeURIComponent(id)}` })
    return
  }
  uni.redirectTo({ url: '/pages/inspiration/index' })
})

onShow(() => {
  void loadAll()
})

function preview(i: number) {
  if (!post.value?.images.length) return
  uni.previewImage({
    current: post.value.images[i],
    urls: post.value.images,
  })
}

async function toggleLike() {
  if (!postId.value) return
  try {
    const p = await likeCirclePost(postId.value)
    if (p) post.value = p
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '操作失败', icon: 'none' })
  }
}

async function toggleCollect() {
  if (!postId.value) return
  try {
    const p = await collectCirclePost(postId.value)
    if (p) {
      post.value = p
      uni.showToast({ title: p.isCollected ? '已收藏' : '已取消', icon: 'none' })
    }
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '操作失败', icon: 'none' })
  }
}

async function sendComment() {
  const t = draft.value.trim()
  if (!t) {
    uni.showToast({ title: '请输入评论', icon: 'none' })
    return
  }
  if (!postId.value) return
  sending.value = true
  try {
    const c = await createCircleComment(postId.value, t)
    if (c) {
      comments.value = [...comments.value, c]
      draft.value = ''
      const refreshed = await getCirclePostDetail(postId.value)
      if (refreshed) post.value = refreshed
    }
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '发送失败', icon: 'none' })
  } finally {
    sending.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.cr-detail {
  padding-bottom: calc(160rpx + env(safe-area-inset-bottom));
}

.cr-detail__muted {
  font-size: 28rpx;
  color: $mp-text-muted;
  text-align: center;
  display: block;
  padding: 32rpx;
}

.cr-detail__post {
  margin-bottom: 24rpx;
}

.cr-detail__head {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
}

.cr-detail__avatar {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cr-detail__avatar-txt {
  font-size: 32rpx;
  font-weight: 700;
  color: $mp-accent;
}

.cr-detail__nick {
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.cr-detail__meta {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.cr-detail__title {
  display: block;
  margin-top: 24rpx;
  font-size: 34rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
}

.cr-detail__content {
  display: block;
  margin-top: 16rpx;
  font-size: 28rpx;
  line-height: 1.6;
  color: $mp-text-primary;
  white-space: pre-wrap;
}

.cr-detail__imgs {
  margin-top: 20rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12rpx;
}

.cr-detail__img-wrap {
  width: 31%;
  height: 200rpx;
  border-radius: 12rpx;
  overflow: hidden;
  border: 1rpx solid $mp-border;
}

.cr-detail__img {
  width: 100%;
  height: 100%;
}

.cr-detail__actions {
  display: flex;
  flex-direction: row;
  gap: 16rpx;
  margin-top: 24rpx;
}

.cr-detail__btn {
  flex: 1;
}

.cr-detail__comments {
  margin-bottom: 24rpx;
}

.cr-detail__no-c {
  padding: 24rpx 0 8rpx;
}

.cr-detail__input-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 940;
  padding: 16rpx 24rpx calc(16rpx + env(safe-area-inset-bottom));
  background: #fdfdfe;
  border-top: 1rpx solid $mp-border;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
}

.cr-detail__input {
  flex: 1;
  padding: 16rpx 20rpx;
  font-size: 28rpx;
  border-radius: 16rpx;
  border: 1rpx solid $mp-border;
  background: $mp-surface;
}

.cr-detail__send {
  width: 160rpx !important;
  flex-shrink: 0;
  padding: 22rpx 12rpx !important;
}
</style>
