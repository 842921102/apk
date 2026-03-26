<template>
  <view class="mp-page tm has-bottom-nav">
    <view class="mp-hero tm__hero">
      <view class="tm__hero-inner">
        <text class="mp-hero__kicker mp-kicker--on-dark">满汉全席</text>
        <text class="mp-hero__title tm__hero-title">一桌好菜</text>
        <text class="mp-hero__sub tm__hero-sub">
          配置用餐场景与口味，由 BFF 代理生成整桌菜单；单道菜可再请求详细菜谱。
        </text>
        <view class="tm__hero-rule" />
      </view>
    </view>

    <!-- 表单 -->
    <scroll-view v-if="phase === 'idle'" class="tm__scroll-idle" scroll-y>
      <view class="mp-card tm__panel tm__panel--idle">
        <view class="tm__panel-badge">
          <text class="tm__panel-badge-txt">主操作区</text>
        </view>
        <view class="tm__hero-icon">
          <text class="tm__hero-icon-emoji">🍽️</text>
        </view>
        <text class="tm__panel-title">设计一桌菜</text>
        <text class="tm__panel-desc">与 Web 端结构一致：先选模式与菜品，再调偏好（均可选）。</text>

        <view class="tm__step-tag">
          <text class="tm__step-tag-txt">1 · 菜品配置</text>
        </view>

        <view class="tm__mode-row">
          <view
            class="tm__mode-card"
            :class="{ 'tm__mode-card--on': !menuConfig.flexibleCount }"
            @click="menuConfig.flexibleCount = false"
          >
            <text class="tm__mode-title">固定数量</text>
            <text class="tm__mode-sub">指定确切道数</text>
          </view>
          <view
            class="tm__mode-card"
            :class="{ 'tm__mode-card--on': menuConfig.flexibleCount }"
            @click="menuConfig.flexibleCount = true"
          >
            <text class="tm__mode-title">智能搭配</text>
            <text class="tm__mode-sub">AI 调整数量与搭配</text>
          </view>
        </view>

        <view v-if="!menuConfig.flexibleCount" class="tm__block">
          <text class="tm__block-title">菜品数量</text>
          <view class="tm__chips">
            <view
              v-for="n in dishPresets"
              :key="n"
              class="tm__chip"
              :class="{ 'tm__chip--on': menuConfig.dishCount === n }"
              @click="menuConfig.dishCount = n"
            >
              <text class="tm__chip-txt">{{ n }}道</text>
            </view>
          </view>
          <view class="tm__inline-num">
            <text class="tm__inline-label">自定义</text>
            <input
              v-model.number="dishCountInput"
              class="tm__num-input"
              type="number"
              @blur="clampDishCount"
            />
            <text class="tm__inline-unit">道</text>
          </view>
        </view>

        <view class="tm__block">
          <text class="tm__block-title">
            {{ menuConfig.flexibleCount ? '想要的菜品' : '指定菜品（可选）' }}
          </text>
          <view v-if="menuConfig.flexibleCount && customDishes.length === 0" class="tm__warn">
            <text class="tm__warn-txt">智能搭配需至少添加一道参考菜。</text>
          </view>
          <view v-if="customDishes.length > 0" class="tm__tags">
            <view v-for="d in customDishes" :key="d" class="tm__tag">
              <text class="tm__tag-txt">{{ d }}</text>
              <text class="tm__tag-x" @click="removeDish(d)">✕</text>
            </view>
          </view>
          <view class="tm__add-row">
            <input
              v-model="currentCustomDish"
              class="tm__input tm__input--grow"
              placeholder="输入菜名，点添加或确认"
              confirm-type="done"
              @confirm="addCustomDish"
            />
            <button
              class="tm__add-btn"
              :disabled="!currentCustomDish.trim() || customDishes.length >= 10"
              @click="addCustomDish"
            >
              添加
            </button>
          </view>
          <text class="tm__hint">{{ customDishes.length }}/10 · 如：红烧肉、清蒸鱼</text>
        </view>

        <view class="tm__step-tag tm__step-tag--mt">
          <text class="tm__step-tag-txt">2 · 偏好（可选）</text>
        </view>

        <view class="tm__accordion" @click="showTasteBlock = !showTasteBlock">
          <text class="tm__accordion-title">口味与场景</text>
          <text class="tm__accordion-chev" :class="{ 'tm__accordion-chev--open': showTasteBlock }">▼</text>
        </view>
        <view v-show="showTasteBlock" class="tm__accordion-body">
          <text class="tm__sub-label">口味</text>
          <view class="tm__pill-grid">
            <view
              v-for="t in tasteOptions"
              :key="t.id"
              class="tm__pill"
              :class="{ 'tm__pill--on': tastesSet.has(t.id) }"
              @click="toggleTaste(t.id)"
            >
              <text class="tm__pill-txt">{{ t.name }}</text>
            </view>
          </view>
          <text class="tm__sub-label">菜系风格</text>
          <view class="tm__pill-grid tm__pill-grid--4">
            <view
              v-for="s in cuisineStyles"
              :key="s.id"
              class="tm__pill"
              :class="{ 'tm__pill--on': menuConfig.cuisineStyle === s.id }"
              @click="menuConfig.cuisineStyle = s.id"
            >
              <text class="tm__pill-txt">{{ s.name }}</text>
            </view>
          </view>
          <text class="tm__sub-label">用餐场景</text>
          <view class="tm__pill-grid">
            <view
              v-for="sc in diningScenes"
              :key="sc.id"
              class="tm__pill"
              :class="{ 'tm__pill--on': menuConfig.diningScene === sc.id }"
              @click="menuConfig.diningScene = sc.id"
            >
              <text class="tm__pill-txt">{{ sc.name }}</text>
            </view>
          </view>
        </view>

        <view class="tm__accordion" @click="showNutritionBlock = !showNutritionBlock">
          <text class="tm__accordion-title">营养与特殊要求</text>
          <text class="tm__accordion-chev" :class="{ 'tm__accordion-chev--open': showNutritionBlock }">▼</text>
        </view>
        <view v-show="showNutritionBlock" class="tm__accordion-body">
          <text class="tm__sub-label">营养侧重</text>
          <view class="tm__pill-grid">
            <view
              v-for="n in nutritionOptions"
              :key="n.id"
              class="tm__pill"
              :class="{ 'tm__pill--on': menuConfig.nutritionFocus === n.id }"
              @click="menuConfig.nutritionFocus = n.id"
            >
              <text class="tm__pill-txt">{{ n.name }}</text>
            </view>
          </view>
          <text class="tm__sub-label">特殊要求</text>
          <textarea
            v-model="menuConfig.customRequirement"
            class="tm__textarea"
            maxlength="200"
            placeholder="如：少油、适合老人小孩…"
          />
          <text class="tm__hint tm__hint--right">{{ menuConfig.customRequirement.length }}/200</text>
        </view>

        <view v-if="!canGenerate" class="tm__prefs-status tm__prefs-status--empty">
          <text class="tm__prefs-status-title">暂无法生成</text>
          <text class="tm__prefs-status-desc">智能搭配模式下请先添加至少一道菜品。</text>
        </view>

        <button
          class="mp-btn-primary tm__submit"
          :disabled="!canGenerate"
          @click="onGenerateMenu"
        >
          <text class="tm__submit-txt">交给大师生成菜单</text>
          <text class="tm__submit-go">→</text>
        </button>
        <text class="tm__idle-foot">需 BFF 开放 POST /api/ai/table-menu；菜谱详情为 POST /api/ai/table-dish-recipe</text>
      </view>
    </scroll-view>

    <!-- loading -->
    <view v-else-if="phase === 'loading'" class="mp-card tm__panel tm__panel--state tm__panel--loading">
      <view class="tm__state-head">
        <text class="tm__state-kicker">生成中</text>
        <text class="tm__state-title">正在搭配整桌菜单</text>
      </view>
      <view class="mp-state-icon tm__loading-icon">✨</view>
      <view class="tm__progress-track">
        <view class="tm__progress-fill" />
      </view>
      <text class="tm__loading-hint">请稍候，服务端通过 AI 代理生成中…</text>
    </view>

    <!-- error -->
    <view v-else-if="phase === 'error'" class="mp-card tm__panel tm__panel--state tm__panel--state-error">
      <view class="tm__state-head">
        <text class="tm__state-kicker tm__state-kicker--danger">未成功</text>
        <text class="tm__state-title">本次生成失败</text>
      </view>
      <view class="mp-state-icon mp-state-icon--danger tm__err-icon">!</view>
      <view class="tm__err-box">
        <text class="tm__err-box-label">原因说明</text>
        <text class="tm__err-msg">{{ errorMessage }}</text>
      </view>
      <view class="tm__err-actions">
        <button v-if="needLogin" class="mp-btn-primary tm__stack-btn" @click="goLogin">
          {{ appConfig.common_empty_button_text }}
        </button>
        <button class="mp-btn-ghost tm__stack-btn" @click="resetIdle">返回修改配置</button>
      </view>
    </view>

    <!-- success -->
    <scroll-view v-else-if="phase === 'success'" class="tm__scroll" scroll-y>
      <view class="mp-card tm__result">
        <view class="tm__result-hero">
          <text class="tm__result-hero-k">生成完成</text>
          <text class="tm__result-hero-title">你的专属一桌菜</text>
          <text class="tm__result-hero-sub">共 {{ dishes.length }} 道菜，可点击生成单道菜谱</text>
        </view>

        <view v-if="historyNote" class="tm__history-banner">
          <text class="tm__history-note">{{ historyNote }}</text>
        </view>

        <view v-for="(dish, index) in dishes" :key="index" class="tm__dish-card">
          <view class="tm__dish-head">
            <text class="tm__dish-name">{{ dish.name }}</text>
            <text class="tm__dish-cat">{{ dish.category || '菜品' }}</text>
          </view>
          <text class="tm__dish-desc">{{ dish.description }}</text>
          <view v-if="dish.tags.length" class="tm__dish-tags">
            <text v-for="tag in dish.tags" :key="tag" class="tm__mini-tag">{{ tag }}</text>
          </view>
          <button
            class="tm__recipe-btn"
            :disabled="dish.recipeLoading"
            @click="onDishRecipe(dish, index)"
          >
            <text v-if="dish.recipeLoading">生成中…</text>
            <text v-else-if="dish.recipe">查看菜谱</text>
            <text v-else>生成菜谱</text>
          </button>
        </view>
      </view>

      <button class="mp-btn-primary tm__again" @click="resetIdle">
        <text class="tm__again-txt">再生成一桌</text>
        <text class="tm__again-go">→</text>
      </button>
    </scroll-view>

    <!-- 菜谱浮层 -->
    <view v-if="recipeOverlay" class="tm__mask" @click="closeRecipe">
      <scroll-view scroll-y class="tm__modal" @click.stop>
        <view class="tm__modal-head">
          <text class="tm__modal-title">{{ recipeOverlay.name }}</text>
          <text class="tm__modal-close" @click="closeRecipe">✕</text>
        </view>
        <view class="tm__modal-body">
          <button
            class="mp-btn-ghost tm__fav-btn"
            :disabled="overlayFavoriteLoading"
            @click="onToggleOverlayFavorite"
          >
            <text>{{ isOverlayFavorited ? '取消收藏' : '加入收藏' }}</text>
          </button>

          <text class="tm__modal-section">食材</text>
          <text class="tm__modal-p">{{ ingredientsJoined }}</text>
          <text class="tm__modal-section">步骤</text>
          <view v-for="st in recipeOverlay.steps" :key="st.step" class="tm__step-line">
            <text class="tm__step-num">{{ st.step }}.</text>
            <view class="tm__step-main">
              <text class="tm__step-desc">{{ st.description }}</text>
              <text v-if="st.time != null || st.temperature" class="tm__step-meta">
                {{ st.time != null ? `${st.time} 分钟` : '' }}{{ st.temperature ? ` · ${st.temperature}` : '' }}
              </text>
            </view>
          </view>
          <template v-if="recipeOverlay.tips?.length">
            <text class="tm__modal-section">小贴士</text>
            <text v-for="(tip, i) in recipeOverlay.tips" :key="i" class="tm__modal-p">· {{ tip }}</text>
          </template>
        </view>
      </scroll-view>
    </view>

    <MpIcoTabBar />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import MpIcoTabBar from '@/components/MpIcoTabBar.vue'
import { requestTableMenu, requestTableDishRecipe } from '@/api/tableMenu'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import {
  insertRecipeHistoryFromTodayEat,
  isFavoriteRecipe,
  toggleFavoriteRecipe,
  BIZ_UNAUTHORIZED,
} from '@/api/biz'
import type { TableMenuConfigPayload, TableMenuDishItem, TableDishRecipeResponse } from '@/types/tableMenu'
import {
  TABLE_MENU_TASTE_OPTIONS,
  TABLE_MENU_CUISINE_STYLES,
  TABLE_MENU_DINING_SCENES,
  TABLE_MENU_NUTRITION_OPTIONS,
  TABLE_MENU_DISH_COUNT_PRESETS,
} from '@/constants/tableMenu'

type Phase = 'idle' | 'loading' | 'success' | 'error'

type DisplayDish = TableMenuDishItem & {
  recipe?: TableDishRecipeResponse
  recipeLoading?: boolean
}

const { config: appConfig } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const phase = ref<Phase>('idle')
const errorMessage = ref('')
const needLogin = ref(false)
const historyNote = ref('')
const dishes = ref<DisplayDish[]>([])
const recipeOverlay = ref<TableDishRecipeResponse | null>(null)
const overlayFavoriteLoading = ref(false)
const isOverlayFavorited = ref(false)

const showTasteBlock = ref(true)
const showNutritionBlock = ref(false)
const currentCustomDish = ref('')

const menuConfig = reactive<TableMenuConfigPayload>({
  dishCount: 6,
  flexibleCount: true,
  tastes: [],
  cuisineStyle: 'mixed',
  diningScene: 'family',
  nutritionFocus: 'balanced',
  customRequirement: '',
  customDishes: [],
})

const dishPresets = TABLE_MENU_DISH_COUNT_PRESETS
const tasteOptions = TABLE_MENU_TASTE_OPTIONS
const cuisineStyles = TABLE_MENU_CUISINE_STYLES
const diningScenes = TABLE_MENU_DINING_SCENES
const nutritionOptions = TABLE_MENU_NUTRITION_OPTIONS

const customDishes = computed(() => menuConfig.customDishes)

const dishCountInput = ref(menuConfig.dishCount)

watch(
  () => menuConfig.dishCount,
  (v) => {
    dishCountInput.value = v
  },
)

const tastesSet = computed(() => new Set(menuConfig.tastes))

const canGenerate = computed(() => {
  if (!menuConfig.flexibleCount) return true
  return menuConfig.customDishes.length > 0
})

const ingredientsJoined = computed(() => {
  const r = recipeOverlay.value
  if (!r?.ingredients?.length) return '—'
  return r.ingredients.join('、')
})

function formatTableDishRecipeContent(r: TableDishRecipeResponse): string {
  const lines: string[] = []
  const ings = r.ingredients?.length ? `食材：${r.ingredients.join('、')}` : ''
  if (ings) lines.push(ings)

  const stepLines = r.steps?.length
    ? r.steps.map((st) => `${st.step}. ${st.description}`).join('\n')
    : ''
  if (stepLines) lines.push(`步骤：\n${stepLines}`)

  const tips = r.tips?.length ? `小贴士：${r.tips.join('、')}` : ''
  if (tips) lines.push(tips)

  return lines.filter(Boolean).join('\n\n')
}

async function syncOverlayFavoriteState() {
  const r = recipeOverlay.value
  if (!r) {
    isOverlayFavorited.value = false
    return
  }
  if (!isLoggedIn.value) {
    isOverlayFavorited.value = false
    return
  }
  try {
    const recipeContent = formatTableDishRecipeContent(r)
    isOverlayFavorited.value = await isFavoriteRecipe({
      title: r.name,
      recipe_content: recipeContent,
    })
  } catch {
    isOverlayFavorited.value = false
  }
}

watch([recipeOverlay, isLoggedIn], async () => {
  await syncOverlayFavoriteState()
})

async function onToggleOverlayFavorite() {
  const r = recipeOverlay.value
  if (!r) return
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  if (overlayFavoriteLoading.value) return

  overlayFavoriteLoading.value = true
  try {
    const recipeContent = formatTableDishRecipeContent(r)
    const { favorited } = await toggleFavoriteRecipe({
      title: r.name,
      cuisine: r.cuisine ?? null,
      ingredients: r.ingredients ?? [],
      recipe_content: recipeContent,
      image_url: null,
    })
    isOverlayFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message)
  } finally {
    overlayFavoriteLoading.value = false
  }
}

onShow(() => {
  void syncAuthFromSupabase()
})

function clampDishCount() {
  let v = Number(dishCountInput.value)
  if (!Number.isFinite(v) || v < 1) v = 1
  if (v > 20) v = 20
  menuConfig.dishCount = v
  dishCountInput.value = v
}

function toggleTaste(id: string) {
  const i = menuConfig.tastes.indexOf(id)
  if (i >= 0) menuConfig.tastes.splice(i, 1)
  else menuConfig.tastes.push(id)
}

function addCustomDish() {
  const d = currentCustomDish.value.trim()
  if (!d || menuConfig.customDishes.length >= 10) return
  if (menuConfig.customDishes.includes(d)) {
    currentCustomDish.value = ''
    return
  }
  menuConfig.customDishes.push(d)
  currentCustomDish.value = ''
}

function removeDish(d: string) {
  const i = menuConfig.customDishes.indexOf(d)
  if (i >= 0) menuConfig.customDishes.splice(i, 1)
}

function buildPayload(): TableMenuConfigPayload {
  return {
    dishCount: menuConfig.dishCount,
    flexibleCount: menuConfig.flexibleCount,
    tastes: [...menuConfig.tastes],
    cuisineStyle: menuConfig.cuisineStyle,
    diningScene: menuConfig.diningScene,
    nutritionFocus: menuConfig.nutritionFocus,
    customRequirement: menuConfig.customRequirement.trim(),
    customDishes: [...menuConfig.customDishes],
  }
}

function formatMenuText(list: TableMenuDishItem[]): string {
  return list
    .map((d, i) => {
      const tags = d.tags.length ? `【${d.tags.join('、')}】` : ''
      return `${i + 1}. ${d.name}（${d.category}）${tags}\n${d.description}`
    })
    .join('\n\n')
}

async function maybeSaveHistory(data: { history_saved?: boolean }, payload: TableMenuConfigPayload, list: TableMenuDishItem[]) {
  historyNote.value = ''
  if (data.history_saved === true) {
    msg.toastSaveSuccess()
    return
  }
  if (!isLoggedIn.value) {
    historyNote.value = '未登录：本次未写入历史；登录后可在 BFF 支持自动写入或客户端补写'
    return
  }
  try {
    await insertRecipeHistoryFromTodayEat({
      title: `一桌好菜（${list.length}道）`,
      cuisine: null,
      ingredients: list.map((d) => d.name),
      response_content: formatMenuText(list),
      request_payload: { config: payload, source: 'mp-table-menu' },
    })
    msg.toastSaveSuccess()
  } catch (err: unknown) {
    const e = err as Error & { code?: string }
    if (e.code === BIZ_UNAUTHORIZED || e.message === BIZ_UNAUTHORIZED) {
      msg.toastSaveFailed('登录已过期')
    } else {
      msg.toastSaveFailed(e.message)
      console.error('[table-menu] history insert failed:', err)
    }
  }
}

function mapHttpError(e: unknown): string {
  if (e instanceof HttpError) {
    if (e.statusCode === 401) return '__NEED_LOGIN__'
    if (e.statusCode === 404) {
      return '一桌好菜接口未部署或路径不匹配。请在 BFF 实现 POST /api/ai/table-menu（及可选 POST /api/ai/table-dish-recipe）后重试。'
    }
    return e.message || `请求失败 (${e.statusCode})`
  }
  if (e instanceof Error) return e.message || '请求失败'
  return '请求失败'
}

async function onGenerateMenu() {
  needLogin.value = false
  historyNote.value = ''
  errorMessage.value = ''
  await syncAuthFromSupabase()
  clampDishCount()
  const payload = buildPayload()
  phase.value = 'loading'
  try {
    const data = await requestTableMenu({ config: payload, locale: 'zh-CN' })
    if (!data.dishes.length) {
      throw new Error('接口未返回有效菜品，请稍后重试')
    }
    dishes.value = data.dishes.map((d) => ({ ...d, recipeLoading: false }))
    await maybeSaveHistory(data, payload, data.dishes)
    phase.value = 'success'
  } catch (e: unknown) {
    const mapped = mapHttpError(e)
    if (mapped === '__NEED_LOGIN__') {
      needLogin.value = true
      errorMessage.value = '需要登录后才能生成，或登录已过期'
    } else {
      errorMessage.value = mapped
    }
    dishes.value = []
    phase.value = 'error'
  }
}

async function onDishRecipe(dish: DisplayDish, index: number) {
  if (dish.recipe) {
    recipeOverlay.value = dish.recipe
    return
  }
  const row = dishes.value[index]
  if (!row) return
  row.recipeLoading = true
  try {
    await syncAuthFromSupabase()
    const r = await requestTableDishRecipe({
      dishName: dish.name,
      dishDescription: dish.description,
      category: dish.category,
      locale: 'zh-CN',
    })
    if (!r.name && !r.steps.length && !r.ingredients.length) {
      throw new Error('菜谱接口返回为空，请确认 BFF 已实现 POST /api/ai/table-dish-recipe')
    }
    row.recipe = r
    recipeOverlay.value = r
  } catch (e: unknown) {
    const m = mapHttpError(e)
    if (m === '__NEED_LOGIN__') {
      uni.showToast({ title: '请先登录', icon: 'none' })
    } else {
      uni.showToast({ title: m.slice(0, 48), icon: 'none' })
    }
  } finally {
    row.recipeLoading = false
  }
}

function resetIdle() {
  phase.value = 'idle'
  dishes.value = []
  errorMessage.value = ''
  needLogin.value = false
  historyNote.value = ''
  recipeOverlay.value = null
}

function closeRecipe() {
  recipeOverlay.value = null
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/table-menu/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.tm__hero-inner {
  text-align: center;
}

.tm__hero-title {
  max-width: 640rpx;
  margin-left: auto;
  margin-right: auto;
}

.tm__hero-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

.tm__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

.tm__scroll-idle {
  max-height: calc(100vh - 200rpx);
  padding-bottom: 48rpx;
}

.tm__panel--idle {
  position: relative;
  padding-top: 36rpx;
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.12);
  overflow: hidden;
  margin-bottom: 32rpx;
}

.tm__panel--idle::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.tm__panel-badge {
  align-self: flex-start;
  margin-bottom: 8rpx;
}

.tm__panel-badge-txt {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: $mp-accent;
}

.tm__hero-icon {
  width: 120rpx;
  height: 120rpx;
  margin: 12rpx auto 8rpx;
  border-radius: 28rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tm__hero-icon-emoji {
  font-size: 64rpx;
  line-height: 1;
}

.tm__panel-title {
  display: block;
  text-align: center;
  margin-top: 8rpx;
  font-size: 36rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.tm__panel-desc {
  display: block;
  margin-top: 12rpx;
  text-align: center;
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.55;
  padding: 0 8rpx;
}

.tm__step-tag {
  margin-top: 32rpx;
  align-self: flex-start;
  padding: 8rpx 20rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.tm__step-tag--mt {
  margin-top: 40rpx;
}

.tm__step-tag-txt {
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent-deep;
}

.tm__mode-row {
  display: flex;
  flex-direction: row;
  gap: 16rpx;
  margin-top: 16rpx;
}

.tm__mode-card {
  flex: 1;
  padding: 20rpx 16rpx;
  border-radius: 20rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.tm__mode-card--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.tm__mode-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.tm__mode-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
}

.tm__block {
  margin-top: 24rpx;
  padding: 24rpx;
  border-radius: 20rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.tm__block-title {
  display: block;
  font-size: 26rpx;
  font-weight: 700;
  color: #374151;
  margin-bottom: 16rpx;
}

.tm__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.tm__chip {
  padding: 12rpx 24rpx;
  border-radius: 12rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.tm__chip--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.tm__chip-txt {
  font-size: 26rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.tm__inline-num {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
  margin-top: 16rpx;
}

.tm__inline-label,
.tm__inline-unit {
  font-size: 26rpx;
  color: $mp-text-secondary;
}

.tm__num-input {
  width: 100rpx;
  text-align: center;
  padding: 12rpx;
  background: #fff;
  border-radius: 12rpx;
  border: 1rpx solid $mp-border;
  font-size: 28rpx;
  font-weight: 700;
}

.tm__warn {
  padding: 16rpx;
  border-radius: 12rpx;
  background: #fff8f1;
  border: 1rpx solid #f6d9b4;
  margin-bottom: 12rpx;
}

.tm__warn-txt {
  font-size: 24rpx;
  color: #b45309;
}

.tm__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-bottom: 12rpx;
}

.tm__tag {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.tm__tag-txt {
  font-size: 24rpx;
  color: $mp-accent-deep;
}

.tm__tag-x {
  font-size: 22rpx;
  color: $mp-text-secondary;
  padding: 4rpx;
}

.tm__add-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
}

.tm__input {
  padding: 20rpx 24rpx;
  border-radius: 16rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
  font-size: 26rpx;
}

.tm__input--grow {
  flex: 1;
}

.tm__add-btn {
  padding: 0 28rpx;
  height: 72rpx;
  line-height: 72rpx;
  font-size: 26rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #9575e8, #7a57d1);
  border-radius: 16rpx;
  border: none;
}

.tm__add-btn[disabled] {
  opacity: 0.45;
}

.tm__hint {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.tm__hint--right {
  text-align: right;
}

.tm__accordion {
  margin-top: 16rpx;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
}

.tm__accordion-title {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.tm__accordion-chev {
  font-size: 24rpx;
  color: $mp-text-muted;
  transition: transform 0.2s;
}

.tm__accordion-chev--open {
  transform: rotate(180deg);
}

.tm__accordion-body {
  margin-top: 8rpx;
  padding: 20rpx;
  border-radius: 16rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.tm__sub-label {
  display: block;
  font-size: 24rpx;
  font-weight: 600;
  color: #374151;
  margin-bottom: 12rpx;
  margin-top: 16rpx;
}

.tm__sub-label:first-child {
  margin-top: 0;
}

.tm__pill-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.tm__pill-grid--4 .tm__pill {
  width: calc(50% - 6rpx);
  box-sizing: border-box;
  justify-content: center;
}

.tm__pill {
  padding: 14rpx 20rpx;
  border-radius: 12rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.tm__pill--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.tm__pill-txt {
  font-size: 24rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.tm__textarea {
  width: 100%;
  min-height: 160rpx;
  padding: 20rpx;
  box-sizing: border-box;
  border-radius: 16rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
  font-size: 26rpx;
}

.tm__prefs-status {
  margin-top: 24rpx;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #eef2ff;
  border: 1rpx solid #c7d2fe;
}

.tm__prefs-status--empty {
  background: #fff8f1;
  border-color: #f6d9b4;
}

.tm__prefs-status-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: #92400e;
}

.tm__prefs-status-desc {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: #b45309;
  line-height: 1.5;
}

.tm__submit {
  margin-top: 32rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
}

.tm__submit-txt {
  font-size: 30rpx;
  font-weight: 800;
}

.tm__submit-go {
  font-size: 32rpx;
}

.tm__idle-foot {
  display: block;
  margin-top: 20rpx;
  text-align: center;
  font-size: 22rpx;
  color: $mp-text-muted;
  line-height: 1.45;
}

.tm__panel--state {
  padding: 48rpx 32rpx;
  text-align: center;
}

.tm__state-head {
  margin-bottom: 24rpx;
}

.tm__state-kicker {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: $mp-accent;
  text-transform: uppercase;
}

.tm__state-kicker--danger {
  color: #dc2626;
}

.tm__state-title {
  display: block;
  margin-top: 8rpx;
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.tm__loading-icon {
  font-size: 72rpx;
  margin: 24rpx 0;
}

.tm__progress-track {
  height: 8rpx;
  background: #e5e7eb;
  border-radius: 999rpx;
  overflow: hidden;
  margin: 0 48rpx 24rpx;
}

.tm__progress-fill {
  height: 100%;
  width: 40%;
  background: linear-gradient(90deg, #9575e8, #7a57d1);
  border-radius: 999rpx;
  animation: tm-pulse 1.2s ease-in-out infinite;
}

@keyframes tm-pulse {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(350%);
  }
}

.tm__loading-hint {
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.5;
}

.tm__err-icon {
  font-size: 56rpx;
  font-weight: 900;
  margin: 16rpx 0;
}

.tm__err-box {
  text-align: left;
  margin: 24rpx 0;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fef2f2;
  border: 1rpx solid #fecaca;
}

.tm__err-box-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: #991b1b;
  margin-bottom: 8rpx;
}

.tm__err-msg {
  font-size: 26rpx;
  color: #7f1d1d;
  line-height: 1.5;
}

.tm__err-actions {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  margin-top: 8rpx;
}

.tm__stack-btn {
  margin: 0;
}

.tm__scroll {
  max-height: calc(100vh - 120rpx);
  padding-bottom: 48rpx;
}

.tm__result {
  margin-bottom: 24rpx;
}

.tm__result-hero {
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid $mp-border;
  margin-bottom: 24rpx;
}

.tm__result-hero-k {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
  letter-spacing: 0.08em;
}

.tm__result-hero-title {
  display: block;
  margin-top: 8rpx;
  font-size: 36rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.tm__result-hero-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.45;
}

.tm__history-banner {
  margin-bottom: 20rpx;
  padding: 16rpx 20rpx;
  border-radius: 12rpx;
  background: #f0fdf4;
  border: 1rpx solid #bbf7d0;
}

.tm__history-note {
  font-size: 24rpx;
  color: #166534;
  line-height: 1.45;
}

.tm__dish-card {
  padding: 28rpx;
  margin-bottom: 20rpx;
  border-radius: 20rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.tm__dish-head {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16rpx;
}

.tm__dish-name {
  flex: 1;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.tm__dish-cat {
  font-size: 22rpx;
  font-weight: 600;
  color: $mp-accent-deep;
  padding: 6rpx 14rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.tm__dish-desc {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.5;
}

.tm__dish-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
  margin-top: 12rpx;
}

.tm__mini-tag {
  font-size: 22rpx;
  color: $mp-accent-deep;
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  background: $mp-accent-soft;
}

.tm__recipe-btn {
  margin-top: 20rpx;
  width: 100%;
  height: 72rpx;
  line-height: 72rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(90deg, #6366f1, #7a57d1);
  border-radius: 16rpx;
  border: none;
}

.tm__recipe-btn[disabled] {
  opacity: 0.55;
}

.tm__again {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
}

.tm__again-txt {
  font-size: 30rpx;
  font-weight: 800;
}

.tm__again-go {
  font-size: 32rpx;
}

.tm__mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32rpx;
  box-sizing: border-box;
}

.tm__modal {
  width: 100%;
  max-height: 85vh;
  background: #fff;
  border-radius: 24rpx;
  border: 1rpx solid $mp-border;
  box-shadow: $mp-shadow-card;
}

.tm__modal-head {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 32rpx;
  border-bottom: 1rpx solid $mp-border;
  background: linear-gradient(90deg, #f3ecff, #fff);
}

.tm__modal-title {
  flex: 1;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
  padding-right: 16rpx;
}

.tm__modal-close {
  font-size: 36rpx;
  color: $mp-text-muted;
  padding: 8rpx;
}

.tm__modal-body {
  padding: 28rpx 32rpx 48rpx;
}

.tm__fav-btn {
  width: 100%;
  margin-bottom: 20rpx;
}

.tm__modal-section {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-accent-deep;
  margin-top: 24rpx;
  margin-bottom: 12rpx;
}

.tm__modal-section:first-child {
  margin-top: 0;
}

.tm__modal-p {
  display: block;
  font-size: 26rpx;
  color: $mp-text-primary;
  line-height: 1.55;
  margin-bottom: 8rpx;
}

.tm__step-line {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 12rpx;
  margin-bottom: 20rpx;
}

.tm__step-num {
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-accent;
  min-width: 40rpx;
}

.tm__step-main {
  flex: 1;
}

.tm__step-desc {
  font-size: 26rpx;
  color: $mp-text-primary;
  line-height: 1.55;
}

.tm__step-meta {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}
</style>
