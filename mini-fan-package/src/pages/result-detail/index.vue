<template>
  <view class="mp-page rd">
    <view v-if="!detail" class="mp-card rd__state">
      <text class="rd__muted">详情数据不存在或已过期，请返回重试。</text>
    </view>

    <template v-else>
      <view class="mp-card rd__head">
        <text class="mp-kicker mp-kicker--accent">结果详情</text>
        <text class="rd__title">{{ detail.title || '未命名' }}</text>
        <text class="rd__meta">
          {{ detail.cuisine || '—' }} · {{ formatListTime(detail.created_at) }}
        </text>
      </view>

      <view v-if="displayNarrative" class="mp-card rd__section rd__section--narrative">
        <text class="rd__label">{{ narrativeSectionLabel }}</text>
        <text class="rd__content rd__content--narrative">{{ displayNarrative }}</text>
      </view>

      <view v-if="ingredientsTags.length" class="mp-card rd__section">
        <text class="rd__label">食材</text>
        <view class="rd__tags">
          <view v-for="(tag, idx) in ingredientsTags" :key="idx" class="rd__tag">
            <text class="rd__tag-txt">{{ tag }}</text>
          </view>
        </view>
      </view>

      <view class="mp-card rd__section">
        <text class="rd__label">{{ operationStepsLabel }}</text>
        <view v-if="displaySteps.length" class="rd__steps">
          <view v-for="(step, index) in displaySteps" :key="index" class="rd__step">
            <view class="rd__step-index">{{ index + 1 }}</view>
            <text class="rd__step-text">{{ step }}</text>
          </view>
        </view>
        <text v-else class="rd__content">{{ detail.content || '暂无正文' }}</text>
      </view>

      <view v-if="extraPayloadText" class="mp-card rd__section">
        <text class="rd__label">额外说明</text>
        <text class="rd__content">{{ extraPayloadText }}</text>
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
import { favoriteApiSourceType, getResultDetailByKey, sourceLabel, type ResultDetailPayload } from '@/lib/resultDetail'
import { isFavoriteRecipe, toggleFavoriteRecipe } from '@/api/biz'
import { requestRecipeImage } from '@/api/ai'
import { upsertLocalGalleryItem } from '@/api/gallery'
import { favoriteContentDigest } from '@/lib/favoriteDigest'
import { parseRecipeDetailDisplay, stripEmbeddedIngredientsLine } from '@/lib/recipeContentDisplay'

const msg = useAppMessages()
const { isLoggedIn, syncAuthFromSupabase } = useAuth()

const detail = ref<ResultDetailPayload | null>(null)
const detailKey = ref('')
const isFavorited = ref(false)
const favoriteLoading = ref(false)
const imageLoading = ref(false)

const ingredientsTags = computed<string[]>(() => {
  const raw: unknown = detail.value?.ingredients ?? []
  if (Array.isArray(raw) && raw.length) {
    return raw.filter((x): x is string => typeof x === 'string' && x.trim().length > 0)
  }
  if (typeof raw === 'string') {
    return raw
      .split(/[、，,]/)
      .map((x) => x.trim())
      .filter(Boolean)
  }
  return []
})

const parsedRecipeBody = computed(() => parseRecipeDetailDisplay(detail.value?.content || ''))

const displayNarrative = computed(() => {
  let t = (parsedRecipeBody.value.narrative || '').trim()
  if (!t) return ''
  if (ingredientsTags.value.length) t = stripEmbeddedIngredientsLine(t)
  return t.trim()
})

const displaySteps = computed<string[]>(() => parsedRecipeBody.value.steps)

const narrativeSectionLabel = computed(() => {
  if (detail.value?.source_type === 'fortune_cooking') return '运势与解析'
  return '说明'
})

const operationStepsLabel = computed(() =>
  displayNarrative.value ? '操作步骤' : '烹饪步骤',
)

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
      source_type: favoriteApiSourceType(detail.value.source_type),
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
      source_type: favoriteApiSourceType(detail.value.source_type),
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
  if (!detail.value) return
  if (!isLoggedIn.value) {
    const target = detailKey.value
      ? `/pages/result-detail/index?key=${encodeURIComponent(detailKey.value)}`
      : '/pages/result-detail/index'
    const redirect = encodeURIComponent(target)
    uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
    return
  }
  if (!detail.value.image_url) {
    uni.showToast({ title: '请先生成配图', icon: 'none' })
    return
  }
  const images = encodeURIComponent(detail.value.image_url)
  const title = encodeURIComponent(detail.value.title || '')
  const description = encodeURIComponent(detail.value.content?.slice(0, 200) || '')
  uni.navigateTo({
    url: `/pages/inspiration/publish?from=ai_result&images=${images}&title=${title}&description=${description}`,
  })
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

.rd {
  min-height: 100vh;
  padding: 24rpx 24rpx calc(24rpx + env(safe-area-inset-bottom));
  box-sizing: border-box;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

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

.rd__section--narrative {
  border-color: rgba(122, 87, 209, 0.22);
  background: linear-gradient(180deg, #faf8ff 0%, #fff 48rpx);
}

.rd__content--narrative {
  color: $mp-text-secondary;
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

.rd__tags {
  margin-top: 10rpx;
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
}

.rd__tag {
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(122, 87, 209, 0.06);
  border: 1rpx solid rgba(122, 87, 209, 0.16);
}

.rd__tag-txt {
  font-size: 24rpx;
  color: $mp-text-primary;
}

.rd__steps {
  margin-top: 12rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.rd__step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 10rpx;
}

.rd__step-index {
  width: 40rpx;
  height: 40rpx;
  border-radius: 999rpx;
  background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
  color: #fff;
  font-size: 24rpx;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.rd__step-text {
  flex: 1;
  font-size: 26rpx;
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

