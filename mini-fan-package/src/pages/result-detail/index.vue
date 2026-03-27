<template>
  <view class="mp-page rd">
    <view v-if="!detail" class="mp-card rd__state">
      <text class="rd__muted">详情数据不存在或已过期，请返回重试。</text>
    </view>

    <template v-else>
      <view class="mp-card rd__head">
        <text class="mp-kicker mp-kicker--accent">统一详情</text>
        <text class="rd__title">{{ detail.title || '未命名' }}</text>
        <text class="rd__meta">
          {{ sourceLabel(detail.source_type) }} · {{ detail.cuisine || '—' }} · {{ formatListTime(detail.created_at) }}
        </text>
      </view>

      <view class="mp-card rd__section">
        <text class="rd__label">来源类型</text>
        <text class="rd__value">{{ detail.source_type }}</text>
      </view>

      <view v-if="ingredientsText" class="mp-card rd__section">
        <text class="rd__label">食材</text>
        <text class="rd__value">{{ ingredientsText }}</text>
      </view>

      <view class="mp-card rd__section">
        <text class="rd__label">正文内容</text>
        <text class="rd__content">{{ detail.content || '暂无正文' }}</text>
      </view>

      <view v-if="extraPayloadText" class="mp-card rd__section">
        <text class="rd__label">额外说明</text>
        <text class="rd__content">{{ extraPayloadText }}</text>
      </view>

      <view v-if="requestPayloadText" class="mp-card rd__section">
        <text class="rd__label">请求参数</text>
        <text class="rd__content">{{ requestPayloadText }}</text>
      </view>

      <view class="mp-card rd__actions">
        <button class="mp-btn-primary" :disabled="favoriteLoading || !isLoggedIn" @click="onToggleFavorite">
          {{ favoriteLoading ? '处理中…' : isFavorited ? '取消收藏' : '加入收藏' }}
        </button>
        <button class="mp-btn-secondary" :disabled="!detail.image_url" @click="onPublishToInspiration">
          发布到灵感
        </button>
        <button class="mp-btn-ghost" :disabled="imageLoading" @click="onGenerateImage">
          {{ imageLoading ? '生成配图中…' : detail.image_url ? '重新生成配图' : '生成配图' }}
        </button>
        <button v-if="detail.image_url" class="mp-btn-ghost" @click="onPreviewImage">预览配图</button>
        <button class="mp-btn-ghost" @click="onRegenerate">再来一次</button>
        <button class="mp-btn-ghost" @click="onBack">返回上一页</button>
      </view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useAuth } from '@/composables/useAuth'
import { useAppMessages } from '@/composables/useAppMessages'
import { formatListTime } from '@/utils/dateFormat'
import { getResultDetailByKey, sourceLabel, sourcePagePath, type ResultDetailPayload } from '@/lib/resultDetail'
import { isFavoriteRecipe, toggleFavoriteRecipe } from '@/api/biz'
import { requestRecipeImage } from '@/api/ai'
import { upsertLocalGalleryItem } from '@/api/gallery'
import { favoriteContentDigest } from '@/lib/favoriteDigest'

const msg = useAppMessages()
const { isLoggedIn, syncAuthFromSupabase } = useAuth()

const detail = ref<ResultDetailPayload | null>(null)
const detailKey = ref('')
const isFavorited = ref(false)
const favoriteLoading = ref(false)
const imageLoading = ref(false)

const ingredientsText = computed(() => {
  const arr = detail.value?.ingredients ?? []
  return arr.length ? arr.join('、') : ''
})

const requestPayloadText = computed(() => {
  const payload = detail.value?.request_payload
  if (payload == null) return ''
  if (typeof payload === 'string') return payload
  try {
    return JSON.stringify(payload, null, 2)
  } catch {
    return ''
  }
})

const extraPayloadText = computed(() => {
  const payload = detail.value?.extra_payload
  if (payload == null) return ''
  if (typeof payload === 'string') return payload
  try {
    return JSON.stringify(payload, null, 2)
  } catch {
    return ''
  }
})

onLoad((query) => {
  const key = typeof query?.key === 'string' ? decodeURIComponent(query.key) : ''
  detailKey.value = key
  detail.value = key ? getResultDetailByKey(key) : null
})

onShow(async () => {
  await syncAuthFromSupabase()
  await refreshFavoriteState()
})

async function refreshFavoriteState() {
  if (!detail.value || !isLoggedIn.value) {
    isFavorited.value = false
    return
  }
  try {
    isFavorited.value = await isFavoriteRecipe({
      source_type: detail.value.source_type,
      source_id: detail.value.source_id,
    })
  } catch {
    isFavorited.value = false
  }
}

async function onToggleFavorite() {
  if (!detail.value) return
  if (!isLoggedIn.value) {
    const target = detailKey.value
      ? `/pages/result-detail/index?key=${encodeURIComponent(detailKey.value)}`
      : '/pages/result-detail/index'
    const redirect = encodeURIComponent(target)
    uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
    return
  }
  if (favoriteLoading.value) return

  favoriteLoading.value = true
  try {
    const res = await toggleFavoriteRecipe({
      source_type: detail.value.source_type,
      source_id: detail.value.source_id,
      title: detail.value.title,
      cuisine: detail.value.cuisine ?? null,
      ingredients: detail.value.ingredients ?? [],
      recipe_content: detail.value.content ?? '',
      image_url: detail.value.image_url ?? null,
      extra_payload: null,
    })
    isFavorited.value = res.favorited
    if (res.favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message)
  } finally {
    favoriteLoading.value = false
  }
}

function onRegenerate() {
  if (!detail.value) return
  const path = sourcePagePath(detail.value.source_type)
  if (detail.value.source_type === 'today_eat') {
    uni.switchTab({ url: path })
    return
  }
  uni.navigateTo({ url: path })
}

function onBack() {
  uni.navigateBack()
}

async function onGenerateImage() {
  if (!detail.value || imageLoading.value) return

  imageLoading.value = true
  try {
    const prompt = buildImagePrompt(detail.value)
    const result = await requestRecipeImage({ prompt, size: '1024x1024' })
    if (!result?.url) {
      msg.toastSaveFailed('生成配图失败')
      return
    }

    detail.value = {
      ...detail.value,
      image_url: result.url,
    }

    const itemId = favoriteContentDigest(`${detail.value.source_type}:${detail.value.source_id}:${detail.value.title}`, result.url)
    upsertLocalGalleryItem({
      id: itemId,
      url: result.url,
      recipeName: detail.value.title || '未命名',
      recipeId: detail.value.source_id || '',
      cuisine: detail.value.cuisine || '',
      ingredients: detail.value.ingredients || [],
      generatedAt: new Date().toISOString(),
      prompt,
    })

    uni.showToast({ title: '配图生成成功', icon: 'success' })
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err?.message || '生成配图失败')
  } finally {
    imageLoading.value = false
  }
}

function onPreviewImage() {
  const imageUrl = detail.value?.image_url
  if (!imageUrl) return
  uni.previewImage({
    urls: [imageUrl],
    current: imageUrl,
  })
}

function onPublishToInspiration() {
  if (!detail.value?.image_url) {
    uni.showToast({ title: '请先生成配图', icon: 'none' })
    return
  }
  const images = encodeURIComponent(detail.value.image_url)
  uni.navigateTo({ url: `/pages/inspiration/publish?from=ai_result&images=${images}` })
}

function buildImagePrompt(payload: ResultDetailPayload): string {
  const lines = [
    `请生成一道美食成品图，菜名：${payload.title || '未命名菜品'}`,
    `来源：${sourceLabel(payload.source_type)}`,
    `菜系：${payload.cuisine || '不限'}`,
  ]

  if (payload.ingredients?.length) {
    lines.push(`食材：${payload.ingredients.slice(0, 12).join('、')}`)
  }

  const text = (payload.content || '').replace(/\s+/g, ' ').trim()
  if (text) {
    lines.push(`描述：${text.slice(0, 240)}`)
  }

  lines.push('风格：高清写实、美食摄影、干净背景、构图完整')
  return lines.join('\n')
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.rd__state {
  min-height: 320rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rd__muted {
  color: $mp-text-muted;
  font-size: 28rpx;
}

.rd__head {
  margin-bottom: 16rpx;
}

.rd__title {
  display: block;
  margin-top: 12rpx;
  font-size: 34rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.rd__meta {
  margin-top: 10rpx;
  display: block;
  color: $mp-text-secondary;
  font-size: 24rpx;
}

.rd__section {
  margin-bottom: 14rpx;
}

.rd__label {
  display: block;
  font-size: 24rpx;
  color: $mp-text-muted;
}

.rd__value {
  margin-top: 8rpx;
  display: block;
  font-size: 28rpx;
  color: $mp-text-primary;
}

.rd__content {
  margin-top: 8rpx;
  white-space: pre-wrap;
  font-size: 28rpx;
  line-height: 1.6;
  color: $mp-text-primary;
}

.rd__actions {
  margin-top: 18rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}
</style>

