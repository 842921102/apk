<template>
  <view class="mp-page recipe-page">
    <view v-if="loading" class="mp-card recipe-page__state">
      <text class="recipe-page__muted">加载中…</text>
    </view>

    <view v-else-if="loadError" class="mp-card recipe-page__state">
      <text class="recipe-page__muted">{{ loadErrorMessage }}</text>
      <button class="mp-btn-ghost recipe-page__back" @click="goBack">返回</button>
    </view>

    <template v-else-if="hasContent">
      <view class="recipe-page__toolbar">
        <button
          v-if="effectiveDishRecipeId != null"
          class="mp-btn-primary recipe-page__fav-btn"
          :disabled="recipeFavoriteLoading"
          @click="onToggleRecipeFavorite"
        >
          <text>{{ isRecipeFavorited ? '已收藏菜谱' : '收藏这道菜' }}</text>
        </button>
        <text v-else class="recipe-page__toolbar-hint">该菜尚未入库标准菜谱，暂无法加入「菜谱收藏」</text>
      </view>

      <scroll-view class="recipe-page__scroll" scroll-y>
        <view class="recipe-page__inner">
          <view class="mp-card recipe-page__hero">
            <text class="recipe-page__hero-k">{{ heroKicker }}</text>
            <image
              v-if="heroCover"
              class="recipe-page__hero-cover"
              :src="heroCover"
              mode="aspectFill"
            />
            <text class="recipe-page__hero-title">{{ recipeTitle }}</text>
            <text v-if="recipeDescription" class="recipe-page__hero-desc">{{ recipeDescription }}</text>
            <text v-else class="recipe-page__hero-desc recipe-page__hero-desc--muted">暂无简介</text>
            <view v-if="displayTags.length" class="recipe-page__tags">
              <text v-for="(t, i) in displayTags" :key="i" class="recipe-page__tag">{{ t }}</text>
            </view>
            <view v-if="pageCuisine" class="recipe-page__meta-inline">
              <text class="recipe-page__meta-label">菜系</text>
              <text class="recipe-page__meta-val">{{ pageCuisine }}</text>
            </view>
          </view>

          <view
            v-if="recipe?.cooking_time || recipe?.difficulty"
            class="mp-card recipe-page__card recipe-page__time-row"
          >
            <view v-if="recipe?.cooking_time" class="recipe-page__time-cell">
              <text class="recipe-page__time-k">预计耗时</text>
              <text class="recipe-page__time-v">{{ recipe.cooking_time }}</text>
            </view>
            <view v-if="recipe?.difficulty" class="recipe-page__time-cell">
              <text class="recipe-page__time-k">难度</text>
              <text class="recipe-page__time-v">{{ recipe.difficulty }}</text>
            </view>
          </view>

          <view class="mp-card recipe-page__card">
            <text class="recipe-page__sec-title">主要食材</text>
            <view v-if="mainIngredients.length" class="recipe-page__chip-row">
              <text v-for="(x, i) in mainIngredients" :key="'m' + i" class="recipe-page__chip">{{ x }}</text>
            </view>
            <text v-else class="recipe-page__empty-line">暂无</text>
            <text class="recipe-page__sec-title recipe-page__sec-title--sub">辅料 / 调料</text>
            <view v-if="seasonings.length" class="recipe-page__chip-row">
              <text
                v-for="(x, i) in seasonings"
                :key="'s' + i"
                class="recipe-page__chip recipe-page__chip--soft"
                >{{ x }}</text
              >
            </view>
            <text v-else class="recipe-page__empty-line">暂无</text>
          </view>

          <view class="mp-card recipe-page__card">
            <text class="recipe-page__sec-title">做法步骤</text>
            <view v-if="steps.length" class="recipe-page__steps">
              <view v-for="st in steps" :key="st.step_no" class="recipe-page__step">
                <view class="recipe-page__step-no">
                  <text>{{ st.step_no }}</text>
                </view>
                <text class="recipe-page__step-body">{{ st.content }}</text>
              </view>
            </view>
            <view v-else class="recipe-page__fallback-block">
              <text class="recipe-page__fallback-title">暂无分步做法</text>
              <text class="recipe-page__fallback-desc">
                {{
                  legacyBody
                    ? '以下为推荐时保存的文字说明，可作参考。'
                    : '后台尚未维护该菜的步骤，可先按食材自由发挥，或稍后再来查看。'
                }}
              </text>
              <view v-if="legacyBody" class="recipe-page__legacy">
                <text class="recipe-page__legacy-txt">{{ legacyBody }}</text>
              </view>
            </view>
          </view>

          <view v-if="tips.length" class="mp-card recipe-page__card recipe-page__card--tips">
            <text class="recipe-page__sec-title">小贴士</text>
            <view v-for="(tip, i) in tips" :key="i" class="recipe-page__tip-line">
              <text class="recipe-page__tip-dot">·</text>
              <text class="recipe-page__tip-txt">{{ tip }}</text>
            </view>
          </view>

          <view v-if="showEmptyWarn" class="mp-card recipe-page__card recipe-page__warn">
            <text class="recipe-page__warn-txt">暂时缺少可跟做的详细做法，我们正在完善菜谱库。</text>
          </view>

          <view class="recipe-page__foot-space" />
        </view>
      </scroll-view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { HttpError } from '@/api/http'
import { apiGetDishRecipe } from '@/api/dishRecipes'
import { apiCheckFavorite } from '@/api/favorites'
import { apiGetRecommendationRecord } from '@/api/recommendationRecords'
import {
  BIZ_NEED_LARAVEL_AUTH,
  toggleFavoriteRecipe,
} from '@/api/biz'
import { useAuth } from '@/composables/useAuth'
import { useAppMessages } from '@/composables/useAppMessages'
import type { RecommendationRecordDetail, RecipeDetailPayload } from '@/types/recommendationHistory'

const { isLoggedIn } = useAuth()
const msg = useAppMessages()

const loading = ref(true)
const loadError = ref(false)
const loadErrorMessage = ref('无法加载')
const recordId = ref<number | null>(null)
const dishRecipeIdParam = ref<number | null>(null)
const detail = ref<RecommendationRecordDetail | null>(null)
const standaloneRecipe = ref<RecipeDetailPayload | null>(null)

const isRecipeFavorited = ref(false)
const recipeFavoriteLoading = ref(false)

const recipe = computed<RecipeDetailPayload | null>(
  () => standaloneRecipe.value ?? detail.value?.recipe_detail ?? null,
)

const hasContent = computed(() => detail.value !== null || standaloneRecipe.value !== null)

const effectiveDishRecipeId = computed((): number | null => {
  if (dishRecipeIdParam.value != null && Number.isFinite(dishRecipeIdParam.value)) {
    return dishRecipeIdParam.value
  }
  const d = detail.value?.dish_recipe_id
  if (typeof d === 'number' && Number.isFinite(d)) return d
  return null
})

const heroKicker = computed(() => (dishRecipeIdParam.value != null ? '标准菜谱' : '今日推荐菜'))

const heroCover = computed(() => {
  /* 推荐快照暂无统一封面字段，后续可扩展 extra */
  return null as string | null
})

const recipeTitle = computed(() => {
  const t = recipe.value?.title?.trim()
  if (t) return t
  return detail.value?.recommended_dish?.trim() || '—'
})

const recipeDescription = computed(() => {
  const d = recipe.value?.description
  return typeof d === 'string' && d.trim() ? d.trim() : ''
})

const pageCuisine = computed(() => {
  const c = recipe.value?.cuisine ?? detail.value?.cuisine
  return typeof c === 'string' && c.trim() ? c.trim() : ''
})

const displayTags = computed(() => {
  const fromRecipe = recipe.value?.display_tags
  if (Array.isArray(fromRecipe) && fromRecipe.length) {
    return fromRecipe.filter((x) => typeof x === 'string' && x.trim()).slice(0, 6)
  }
  const t = detail.value?.tags
  if (!Array.isArray(t)) return []
  return t.filter((x) => typeof x === 'string' && x.trim()).slice(0, 6)
})

const mainIngredients = computed(() => {
  const from = recipe.value?.ingredients
  if (Array.isArray(from) && from.length) {
    return from.filter((x) => typeof x === 'string' && x.trim())
  }
  const ing = detail.value?.ingredients
  if (!Array.isArray(ing)) return []
  return ing.filter((x) => typeof x === 'string' && x.trim())
})

const seasonings = computed(() => {
  const s = recipe.value?.seasonings
  return Array.isArray(s) ? s.filter((x) => typeof x === 'string' && x.trim()) : []
})

const steps = computed(() => {
  const st = recipe.value?.steps
  return Array.isArray(st) ? st : []
})

const legacyBody = computed(() => {
  const a = recipe.value?.legacy_recipe_content
  if (typeof a === 'string' && a.trim()) return a.trim()
  const b = detail.value?.recipe_content
  return typeof b === 'string' && b.trim() ? b.trim() : ''
})

const tips = computed(() => {
  const t = recipe.value?.tips
  return Array.isArray(t) ? t.filter((x) => typeof x === 'string' && x.trim()) : []
})

const showEmptyWarn = computed(() => {
  const r = recipe.value
  if (r && r.is_actionable === true) return false
  if (r && r.is_actionable === false) return true
  if (!r) return steps.value.length === 0 && !legacyBody.value
  return false
})

async function syncRecipeFavoriteState() {
  const id = effectiveDishRecipeId.value
  if (id == null || !isLoggedIn.value) {
    isRecipeFavorited.value = false
    return
  }
  try {
    const { data } = await apiCheckFavorite('recipe', String(id))
    isRecipeFavorited.value = Boolean(data.is_favorited)
  } catch {
    isRecipeFavorited.value = false
  }
}

watch(effectiveDishRecipeId, () => {
  void syncRecipeFavoriteState()
})

async function load() {
  loadError.value = false
  loading.value = true
  detail.value = null
  standaloneRecipe.value = null

  const rid = recordId.value
  if (rid != null && Number.isFinite(rid)) {
    try {
      const res = await apiGetRecommendationRecord(rid)
      detail.value = res.data ?? null
      if (!detail.value) {
        loadError.value = true
        loadErrorMessage.value = '记录不存在或无权限查看'
      }
    } catch (e: unknown) {
      detail.value = null
      loadError.value = true
      loadErrorMessage.value =
        e instanceof HttpError && e.statusCode === 404 ? '记录不存在或无权限查看' : '加载失败'
    } finally {
      loading.value = false
    }
    await syncRecipeFavoriteState()
    return
  }

  const dr = dishRecipeIdParam.value
  if (dr != null && Number.isFinite(dr)) {
    try {
      const res = await apiGetDishRecipe(dr)
      standaloneRecipe.value = res.data?.recipe_detail ?? null
      if (!standaloneRecipe.value) {
        loadError.value = true
        loadErrorMessage.value = '菜谱不存在或已下架'
      }
    } catch (e: unknown) {
      loadError.value = true
      loadErrorMessage.value =
        e instanceof HttpError && e.statusCode === 404 ? '菜谱不存在或已下架' : '加载失败'
    } finally {
      loading.value = false
    }
    await syncRecipeFavoriteState()
    return
  }

  loading.value = false
  loadError.value = true
  loadErrorMessage.value = '缺少记录或菜谱参数'
  await syncRecipeFavoriteState()
}

onLoad((q) => {
  const rawR = q?.recordId ?? (q as Record<string, string | undefined>)?.['recordId']
  const nR = typeof rawR === 'string' ? parseInt(rawR, 10) : Number(rawR)
  recordId.value = Number.isFinite(nR) ? nR : null

  const rawD = q?.dishRecipeId ?? (q as Record<string, string | undefined>)?.['dishRecipeId']
  const nD = typeof rawD === 'string' ? parseInt(rawD, 10) : Number(rawD)
  dishRecipeIdParam.value = Number.isFinite(nD) ? nD : null

  void load()
})

function goBack() {
  uni.navigateBack()
}

async function onToggleRecipeFavorite() {
  const id = effectiveDishRecipeId.value
  if (id == null || recipeFavoriteLoading.value) return
  if (!isLoggedIn.value) {
    const q: string[] = [`dishRecipeId=${encodeURIComponent(String(id))}`]
    if (recordId.value != null) q.unshift(`recordId=${encodeURIComponent(String(recordId.value))}`)
    const redirect = encodeURIComponent(`/pages/recipe/detail?${q.join('&')}`)
    uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
    return
  }
  recipeFavoriteLoading.value = true
  try {
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'recipe',
      source_id: String(id),
      title: recipeTitle.value,
      cuisine: pageCuisine.value || null,
      ingredients: mainIngredients.value,
      recipe_content: '',
      image_url: heroCover.value,
      extra_payload: {
        tags: displayTags.value,
        recommendation_record_id: recordId.value,
      },
    })
    isRecipeFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请先微信一键登录')
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

.recipe-page__toolbar {
  padding: 16rpx 24rpx 0;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.recipe-page__fav-btn {
  width: 100%;
  margin: 0;
}

.recipe-page__toolbar-hint {
  font-size: 24rpx;
  color: $mp-text-muted;
  line-height: 1.45;
  padding: 0 4rpx;
}

.recipe-page__scroll {
  max-height: calc(100vh - 120rpx);
  box-sizing: border-box;
}

.recipe-page__inner {
  padding: 16rpx 24rpx 48rpx;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

.recipe-page__state {
  margin: 24rpx;
  min-height: 200rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 20rpx;
}

.recipe-page__muted {
  font-size: 26rpx;
  color: $mp-text-muted;
}

.recipe-page__back {
  margin-top: 8rpx;
}

.recipe-page__hero {
  padding: 28rpx 26rpx 32rpx;
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 36rpx rgba(122, 87, 209, 0.1);
}

.recipe-page__hero-cover {
  width: 100%;
  height: 240rpx;
  border-radius: 16rpx;
  margin-top: 16rpx;
  background: #eceef2;
}

.recipe-page__hero-k {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: $mp-accent;
  text-transform: uppercase;
}

.recipe-page__hero-title {
  display: block;
  margin-top: 12rpx;
  font-size: 44rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.25;
  word-break: break-word;
}

.recipe-page__hero-desc {
  display: block;
  margin-top: 16rpx;
  font-size: 28rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.recipe-page__hero-desc--muted {
  color: $mp-text-muted;
}

.recipe-page__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 20rpx;
}

.recipe-page__tag {
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  font-size: 24rpx;
  font-weight: 600;
  color: $mp-accent;
  background: rgba(122, 87, 209, 0.1);
}

.recipe-page__meta-inline {
  margin-top: 18rpx;
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.recipe-page__meta-label {
  font-size: 22rpx;
  color: $mp-text-muted;
  font-weight: 700;
}

.recipe-page__meta-val {
  font-size: 26rpx;
  color: $mp-text-secondary;
}

.recipe-page__card {
  margin-top: 20rpx;
  padding: 24rpx 22rpx 26rpx;
}

.recipe-page__card--tips {
  background: linear-gradient(180deg, #fff 0%, rgba(243, 236, 255, 0.35) 100%);
}

.recipe-page__time-row {
  display: flex;
  flex-direction: row;
  gap: 24rpx;
}

.recipe-page__time-cell {
  flex: 1;
  min-width: 0;
}

.recipe-page__time-k {
  font-size: 22rpx;
  color: $mp-text-muted;
  font-weight: 700;
}

.recipe-page__time-v {
  display: block;
  margin-top: 8rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.recipe-page__sec-title {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 16rpx;
}

.recipe-page__sec-title--sub {
  margin-top: 28rpx;
  margin-bottom: 14rpx;
  font-size: 26rpx;
  color: $mp-text-secondary;
}

.recipe-page__chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.recipe-page__chip {
  padding: 10rpx 18rpx;
  border-radius: 12rpx;
  font-size: 26rpx;
  color: $mp-text-primary;
  background: #f3f4f6;
  font-weight: 600;
}

.recipe-page__chip--soft {
  background: rgba(122, 87, 209, 0.08);
  color: $mp-text-secondary;
}

.recipe-page__empty-line {
  font-size: 26rpx;
  color: $mp-text-muted;
}

.recipe-page__steps {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.recipe-page__step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
}

.recipe-page__step-no {
  width: 48rpx;
  height: 48rpx;
  border-radius: 14rpx;
  background: linear-gradient(135deg, rgba(122, 87, 209, 0.95), rgba(167, 139, 250, 0.95));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.recipe-page__step-no text {
  font-size: 24rpx;
  font-weight: 800;
  color: #fff;
}

.recipe-page__step-body {
  flex: 1;
  font-size: 28rpx;
  line-height: 1.55;
  color: $mp-text-primary;
}

.recipe-page__fallback-block {
  padding: 8rpx 0;
}

.recipe-page__fallback-title {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-text-secondary;
}

.recipe-page__fallback-desc {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-muted;
}

.recipe-page__legacy {
  margin-top: 16rpx;
  padding: 18rpx 18rpx;
  border-radius: 16rpx;
  background: #f9fafb;
  border: 1rpx solid #eceef2;
}

.recipe-page__legacy-txt {
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
  white-space: pre-wrap;
  word-break: break-word;
}

.recipe-page__tip-line {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 8rpx;
  margin-bottom: 10rpx;
}

.recipe-page__tip-dot {
  color: $mp-accent;
  font-weight: 800;
}

.recipe-page__tip-txt {
  flex: 1;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.recipe-page__warn {
  border-color: rgba(251, 191, 36, 0.45);
  background: rgba(254, 252, 232, 0.85);
}

.recipe-page__warn-txt {
  font-size: 26rpx;
  line-height: 1.5;
  color: #92400e;
}

.recipe-page__foot-space {
  height: 24rpx;
}
</style>
