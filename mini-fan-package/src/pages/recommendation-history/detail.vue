<template>
  <view class="mp-page rh-detail">
    <view v-if="loading" class="mp-card rh-detail__state">
      <text class="rh-detail__muted">加载中…</text>
    </view>

    <view v-else-if="!detail" class="mp-card rh-detail__state">
      <text class="rh-detail__muted">记录不存在或无权限查看</text>
      <button class="mp-btn-ghost rh-detail__back" @click="goBack">返回</button>
    </view>

    <scroll-view v-else class="te__scroll te__scroll--result" scroll-y>
      <view class="te__result-page">
        <view class="te__result-toolbar">
          <button class="mp-btn-primary te__tb-main" @click="goRecipeDetail">
            <text>查看做法</text>
          </button>
          <button
            class="mp-btn-ghost te__tb-main"
            :disabled="favoriteLoading"
            @click="onToggleFavorite"
          >
            <text>{{ detail.is_favorited ? '取消收藏' : '收藏本条推荐' }}</text>
          </button>
          <view class="rh-detail__recipe-fav-row">
            <button
              v-if="canFavoriteDishRecipe"
              class="mp-btn-ghost te__tb-main"
              :disabled="recipeFavoriteLoading"
              @click="onToggleRecipeFavorite"
            >
              <text>{{ isRecipeFavorited ? '已收藏菜谱' : '收藏这道菜' }}</text>
            </button>
            <text v-else class="rh-detail__recipe-fav-hint">
              标准菜谱入库后可收藏，便于在「菜谱收藏」中回看
            </text>
          </view>
        </view>

        <view class="rh-detail__banner mp-card mp-card--inset">
          <text class="rh-detail__banner-k">快照</text>
          <text class="rh-detail__banner-t">
            {{ detail.recommendation_date }} · {{ sourceLabel(detail.recommendation_source) }} · {{ mealLabel(detail.meal_type) }}
          </text>
        </view>

        <TodayEatResultBody
          :cover-image="null"
          :recommended-dish="detail.recommended_dish"
          :main-tagline="mainTagline"
          :tags="displayTags"
          :reason-text="detail.reason_text"
          :destiny-text="destinyText"
          :alternatives="displayAlternatives"
          :alternatives-interactive="false"
          :cuisine="detail.cuisine"
          :ingredients-text="ingredientsText"
          :recipe-content="detail.recipe_content || '（本条记录未保存制作正文）'"
        />
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import TodayEatResultBody from '@/components/TodayEatResultBody.vue'
import { HttpError } from '@/api/http'
import {
  isFavoriteRecipe,
  toggleFavoriteRecipe,
  BIZ_NEED_LARAVEL_AUTH,
  BIZ_NOT_CONFIGURED,
} from '@/api/biz'
import { apiGetRecommendationRecord, apiSetRecommendationRecordFavorite } from '@/api/recommendationRecords'
import { useAppMessages } from '@/composables/useAppMessages'
import { useAuth } from '@/composables/useAuth'
import type { RecommendationRecordDetail } from '@/types/recommendationHistory'

const msg = useAppMessages()
const { isLoggedIn } = useAuth()
const loading = ref(true)
const favoriteLoading = ref(false)
const recipeFavoriteLoading = ref(false)
const isRecipeFavorited = ref(false)
const detail = ref<RecommendationRecordDetail | null>(null)
const recordId = ref<number | null>(null)

const displayTags = computed(() => {
  const t = detail.value?.tags
  if (!Array.isArray(t)) return []
  return t.filter((x) => typeof x === 'string' && x.trim()).slice(0, 4)
})

const displayAlternatives = computed(() => {
  const a = detail.value?.alternatives
  if (!Array.isArray(a)) return []
  return a.filter((x) => typeof x === 'string' && x.trim())
})

const destinyText = computed(() => {
  const s = detail.value?.destiny_text
  return typeof s === 'string' ? s.trim() : ''
})

const mainTagline = computed(() => {
  const tag = displayTags.value[0]
  if (tag) return `${tag}——当晚的主角就是它。`
  return '以下为已保存的推荐快照，可与当时上下文对照回看。'
})

const ingredientsText = computed(() => {
  const ing = detail.value?.ingredients
  if (!ing?.length) return '—'
  return ing.join('、')
})

const canFavoriteDishRecipe = computed(() => {
  const d = detail.value?.dish_recipe_id
  return typeof d === 'number' && Number.isFinite(d)
})

const allDetailTags = computed(() => {
  const t = detail.value?.tags
  if (!Array.isArray(t)) return []
  return t.filter((x) => typeof x === 'string' && x.trim())
})

function sourceLabel(s: string): string {
  if (s === 'initial') return '首次推荐'
  if (s === 'reroll') return '换一个'
  if (s === 'alternative_selected') return '备选入选'
  return s || '—'
}

function mealLabel(m: string): string {
  if (m === 'dinner') return '晚餐'
  if (m === 'lunch') return '午餐'
  if (m === 'breakfast') return '早餐'
  return m || '用餐'
}

async function load() {
  if (recordId.value == null || !Number.isFinite(recordId.value)) {
    loading.value = false
    return
  }
  loading.value = true
  try {
    const res = await apiGetRecommendationRecord(recordId.value)
    detail.value = res.data ?? null
  } catch (e: unknown) {
    detail.value = null
    if (e instanceof HttpError && e.statusCode === 404) {
      /* empty */
    } else {
      const err = e as Error
      uni.showToast({ title: err.message?.slice(0, 18) || '加载失败', icon: 'none' })
    }
  } finally {
    loading.value = false
  }
  await syncRecipeFavoriteState()
}

async function syncRecipeFavoriteState() {
  const dr = detail.value?.dish_recipe_id
  if (dr == null || !Number.isFinite(dr) || !isLoggedIn.value) {
    isRecipeFavorited.value = false
    return
  }
  try {
    isRecipeFavorited.value = await isFavoriteRecipe({
      source_type: 'recipe',
      source_id: String(dr),
    })
  } catch {
    isRecipeFavorited.value = false
  }
}

onLoad((q) => {
  const raw = q?.id ?? (q as Record<string, string | undefined>)?.['id']
  const n = typeof raw === 'string' ? parseInt(raw, 10) : Number(raw)
  recordId.value = Number.isFinite(n) ? n : null
  void load()
})

function goBack() {
  uni.navigateBack()
}

function goRecipeDetail() {
  const rid = recordId.value
  const dr = detail.value?.dish_recipe_id
  if (rid != null && Number.isFinite(rid)) {
    const q =
      dr != null && Number.isFinite(dr)
        ? `recordId=${rid}&dishRecipeId=${dr}`
        : `recordId=${rid}`
    uni.navigateTo({ url: `/pages/recipe/detail?${q}` })
    return
  }
  if (dr != null && Number.isFinite(dr)) {
    uni.navigateTo({ url: `/pages/recipe/detail?dishRecipeId=${dr}` })
  }
}

async function onToggleFavorite() {
  if (!detail.value || favoriteLoading.value || recordId.value == null) return
  favoriteLoading.value = true
  try {
    const want = !detail.value.is_favorited
    await apiSetRecommendationRecordFavorite(recordId.value, want)
    detail.value = { ...detail.value, is_favorited: want }
    if (want) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message || '操作失败')
  } finally {
    favoriteLoading.value = false
  }
}

async function onToggleRecipeFavorite() {
  const dr = detail.value?.dish_recipe_id
  const d = detail.value
  if (dr == null || !Number.isFinite(dr) || !d || recipeFavoriteLoading.value) return
  if (!isLoggedIn.value) {
    const redirect = encodeURIComponent(`/pages/recommendation-history/detail?id=${recordId.value ?? ''}`)
    uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
    return
  }
  recipeFavoriteLoading.value = true
  try {
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'recipe',
      source_id: String(dr),
      title: d.recommended_dish || '—',
      cuisine: d.cuisine ?? null,
      ingredients: d.ingredients ?? [],
      recipe_content: '',
      image_url: null,
      extra_payload: {
        tags: allDetailTags.value,
        recommendation_record_id: d.id,
      },
    })
    isRecipeFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请先使用微信一键登录')
    } else if (err.code === BIZ_NOT_CONFIGURED || err.message === BIZ_NOT_CONFIGURED) {
      msg.toastSaveFailed('当前环境未开启收藏配置')
    } else {
      msg.toastSaveFailed(err.message || '操作失败')
    }
  } finally {
    recipeFavoriteLoading.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.rh-detail__state {
  margin: 24rpx;
  min-height: 200rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 20rpx;
}

.rh-detail__muted {
  font-size: 26rpx;
  color: $mp-text-muted;
}

.rh-detail__back {
  margin-top: 8rpx;
}

.rh-detail__banner {
  margin-bottom: 8rpx;
  padding: 20rpx 22rpx;
}

.rh-detail__recipe-fav-row {
  width: 100%;
}

.rh-detail__recipe-fav-hint {
  display: block;
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-muted;
  padding: 4rpx 6rpx 0;
}

.rh-detail__banner-k {
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: $mp-accent;
  text-transform: uppercase;
}

.rh-detail__banner-t {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.te__scroll--result {
  max-height: 100vh;
  padding: 24rpx 24rpx 40rpx;
  box-sizing: border-box;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

.te__result-page {
  display: flex;
  flex-direction: column;
  gap: 28rpx;
}

.te__result-toolbar {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.te__tb-main {
  width: 100%;
  margin: 0;
}
</style>
