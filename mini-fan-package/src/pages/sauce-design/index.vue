<template>
  <view class="mp-page sc has-bottom-nav">
    <view class="mp-hero sc__hero">
      <view class="sc__hero-inner">
        <text class="mp-hero__kicker mp-kicker--on-dark">调味灵感</text>
        <text class="mp-hero__title sc__hero-title">酱料大师</text>
        <text class="mp-hero__sub sc__hero-sub">
          先按口味与场景拿 AI 推荐酱名，再生成详细配方；或直接搜索酱名。均由 BFF 代理。
        </text>
        <view class="sc__hero-rule" />
      </view>
    </view>

    <scroll-view class="sc__scroll" scroll-y>
      <!-- 1 智能推荐 -->
      <view class="mp-card sc__panel sc__panel--idle">
        <view class="sc__badge">
          <text class="sc__badge-txt">1 · 智能推荐</text>
        </view>
        <text class="sc__panel-desc">调节四维口味、选择使用场景，可添加手头食材。</text>

        <view class="sc__sliders">
          <view v-for="row in tasteRows" :key="row.key" class="sc__slider-row">
            <view class="sc__slider-label">
              <text>{{ row.icon }} {{ row.label }}</text>
              <text class="sc__slider-val">{{ prefs[row.key] }}</text>
            </view>
            <slider
              :value="prefs[row.key]"
              min="1"
              max="5"
              step="1"
              show-value
              active-color="#7a57d1"
              background-color="#e5e7eb"
              block-size="18"
              @change="(e) => onTasteChange(row.key, e)"
            />
          </view>
        </view>

        <text class="sc__block-title">使用场景（可多选）</text>
        <view class="sc__use-grid">
          <view
            v-for="u in useCases"
            :key="u.id"
            class="sc__use-pill"
            :class="{ 'sc__use-pill--on': prefs.useCase.includes(u.id) }"
            @click="toggleUseCase(u.id)"
          >
            <text class="sc__use-txt">{{ u.icon }} {{ u.name }}</text>
          </view>
        </view>

        <text class="sc__block-title sc__block-title--mt">现有食材（可选）</text>
        <view class="sc__add-row">
          <input
            v-model="ingredientInput"
            class="sc__input sc__input--grow"
            placeholder="输入食材，点添加"
            confirm-type="done"
            @confirm="addIngredient"
          />
          <button class="sc__mini-btn" @click="addIngredient">添加</button>
        </view>
        <view v-if="prefs.availableIngredients.length" class="sc__tags">
          <view v-for="ing in prefs.availableIngredients" :key="ing" class="sc__tag">
            <text class="sc__tag-txt">{{ ing }}</text>
            <text class="sc__tag-x" @click="removeIngredient(ing)">✕</text>
          </view>
        </view>

        <button
          class="mp-btn-primary sc__btn-rec"
          :disabled="loadingRec"
          @click="onGetRecommendations"
        >
          <text v-if="loadingRec">推荐中…</text>
          <text v-else>获取智能推荐</text>
        </button>
      </view>

      <!-- 推荐列表 -->
      <view v-if="loadingRec || recommendations.length" class="mp-card sc__panel">
        <view class="sc__badge">
          <text class="sc__badge-txt">为您推荐</text>
        </view>
        <view v-if="loadingRec" class="sc__mini-loading">
          <text class="sc__mini-loading-ico">🤖</text>
          <text class="sc__mini-loading-txt">AI 正在匹配酱料灵感…</text>
        </view>
        <view v-else>
          <text class="sc__rec-head">根据偏好推荐以下酱料（点选生成配方）</text>
          <view class="sc__rec-grid">
            <view
              v-for="(name, idx) in recommendations"
              :key="name"
              class="sc__rec-card"
              @click="onSelectRecommended(name)"
            >
              <text class="sc__rec-idx">{{ idx + 1 }}</text>
              <text class="sc__rec-name">{{ name }}</text>
              <text class="sc__rec-sub">查看制作方法 →</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 2 搜索 -->
      <view class="mp-card sc__panel">
        <view class="sc__badge">
          <text class="sc__badge-txt">2 · 酱料搜索</text>
        </view>
        <view class="sc__search-row">
          <input
            v-model="searchQuery"
            class="sc__input sc__input--grow"
            placeholder="输入酱料名称，如：蒜蓉辣椒酱"
            confirm-type="search"
            @confirm="onSearchQuery"
          />
          <button
            class="sc__search-btn"
            :disabled="!searchQuery.trim() || recipeLoading"
            @click="onSearchQuery"
          >
            搜索
          </button>
        </view>
      </view>

      <!-- 3 制作教程 -->
      <view class="mp-card sc__panel sc__panel--result">
        <view class="sc__badge">
          <text class="sc__badge-txt">3 · 制作教程</text>
        </view>

        <view v-if="!currentSauce && !recipeLoading && !recipeError" class="sc__empty">
          <text class="sc__empty-title">开始你的酱料之旅</text>
          <text class="sc__empty-line">· 配置口味后获取推荐，点选酱名生成配方</text>
          <text class="sc__empty-line">· 或直接搜索想做的酱料名称</text>
        </view>

        <view v-if="recipeLoading" class="sc__state-loading">
          <text class="sc__state-ico">👨‍🍳</text>
          <text class="sc__state-title">酱料大师正在调配配方…</text>
          <text class="sc__state-sub">请稍候</text>
        </view>

        <view v-else-if="recipeError" class="sc__state-err">
          <text class="sc__err-title">未能生成配方</text>
          <text class="sc__err-msg">{{ recipeError }}</text>
          <button v-if="recipeNeedLogin" class="mp-btn-primary sc__err-btn" @click="goLogin">
            {{ appConfig.common_empty_button_text }}
          </button>
        </view>

        <view v-else-if="currentSauce" class="sc__recipe">
          <view class="sc__rh">
            <text class="sc__r-name">{{ currentSauce.name }}</text>
            <view class="sc__r-meta">
              <text class="sc__chip">{{ categoryLabel(currentSauce.category) }}</text>
              <text class="sc__chip">⏱ {{ currentSauce.makingTime }} 分钟</text>
              <text class="sc__chip">{{ difficultyLabel(currentSauce.difficulty) }}</text>
            </view>
            <view v-if="flavorLine" class="sc__flavor">
              <text class="sc__flavor-txt">{{ flavorLine }}</text>
            </view>
          </view>

          <view v-if="currentSauce.description" class="sc__sheet sc__sheet--orange">
            <text class="sc__sheet-k">酱料特色</text>
            <text class="sc__sheet-body">{{ currentSauce.description }}</text>
          </view>

          <text class="sc__sec-k">食材清单</text>
          <view v-for="(ing, i) in currentSauce.ingredients" :key="i" class="sc__ing">
            <text class="sc__ing-dot" />
            <text>{{ ing }}</text>
          </view>

          <text class="sc__sec-k">制作步骤</text>
          <view v-for="(st, si) in currentSauce.steps" :key="si" class="sc__step">
            <text class="sc__step-n">{{ st.step }}</text>
            <view class="sc__step-main">
              <text class="sc__step-desc">{{ st.description }}</text>
              <text v-if="stepMeta(st)" class="sc__step-meta">{{ stepMeta(st) }}</text>
            </view>
          </view>

          <view v-if="currentSauce.tips.length" class="sc__tips">
            <text class="sc__sec-k">技巧与注意</text>
            <view v-for="(tip, i) in currentSauce.tips" :key="i" class="sc__tip">
              <text>💡 {{ tip }}</text>
            </view>
          </view>

          <view class="sc__storage">
            <text class="sc__sec-k">保存建议</text>
            <text class="sc__stor-line"
              >{{ currentSauce.storage.method }} · {{ currentSauce.storage.duration }} ·
              {{ currentSauce.storage.temperature }}</text
            >
          </view>

          <view v-if="currentSauce.pairings.length" class="sc__pair">
            <text class="sc__sec-k">推荐搭配</text>
            <view class="sc__pair-row">
              <text v-for="p in currentSauce.pairings" :key="p" class="sc__pair-chip">{{ p }}</text>
            </view>
          </view>

          <view v-if="currentSauce.tags.length" class="sc__tags-b">
            <text v-for="t in currentSauce.tags" :key="t" class="sc__hash">#{{ t }}</text>
          </view>

          <view v-if="historyNote" class="sc__hist">
            <text class="sc__hist-txt">{{ historyNote }}</text>
          </view>

          <view class="sc__fav-row">
            <button class="mp-btn-ghost sc__fav-btn" :disabled="favoriteLoading" @click="onToggleFavorite">
              <text>{{ isFavorited ? '取消收藏' : '加入收藏' }}</text>
            </button>
          </view>
        </view>
      </view>

      <!-- 最近查看 -->
      <view v-if="history.length" class="mp-card sc__panel sc__panel--hist">
        <text class="sc__hist-title">最近查看</text>
        <view class="sc__hist-row">
          <text
            v-for="h in history.slice(0, 10)"
            :key="h"
            class="sc__hist-pill"
            @click="pickHistory(h)"
          >
            {{ h }}
          </text>
          <text class="sc__hist-clear" @click="clearHistory">清除</text>
        </view>
      </view>
    </scroll-view>

    <MpIcoTabBar />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import MpIcoTabBar from '@/components/MpIcoTabBar.vue'
import { requestSauceRecommend, requestSauceRecipe } from '@/api/sauce'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import {
  insertRecipeHistoryFromTodayEat,
  isFavoriteRecipe,
  toggleFavoriteRecipe,
  BIZ_UNAUTHORIZED,
  BIZ_NEED_LARAVEL_AUTH,
  BIZ_NOT_CONFIGURED,
} from '@/api/biz'
import { favoriteContentDigest } from '@/lib/favoriteDigest'
import type { SaucePreference, SauceRecipe, SauceStep } from '@/types/sauce'
import {
  SAUCE_USE_CASE_OPTIONS,
  sauceCategoryLabel as categoryLabel,
  sauceDifficultyLabel as difficultyLabel,
} from '@/constants/sauceDesign'

const STORAGE_KEY = 'mp_sauce_design_history'

const { config: appConfig } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const useCases = SAUCE_USE_CASE_OPTIONS
const tasteRows = [
  { key: 'spiceLevel' as const, label: '辣度', icon: '🌶️' },
  { key: 'sweetLevel' as const, label: '甜度', icon: '🍯' },
  { key: 'saltLevel' as const, label: '咸度', icon: '🧂' },
  { key: 'sourLevel' as const, label: '酸度', icon: '🍋' },
]

const prefs = reactive<SaucePreference>({
  spiceLevel: 3,
  sweetLevel: 2,
  saltLevel: 3,
  sourLevel: 2,
  useCase: [],
  availableIngredients: [],
})

const ingredientInput = ref('')
const searchQuery = ref('')
const recommendations = ref<string[]>([])
const loadingRec = ref(false)
const recipeLoading = ref(false)
const recipeError = ref('')
const recipeNeedLogin = ref(false)
const currentSauce = ref<SauceRecipe | null>(null)
const history = ref<string[]>([])
const historyNote = ref('')
const favoriteLoading = ref(false)
const isFavorited = ref(false)

const flavorLine = computed(() => {
  const s = currentSauce.value
  if (!s) return ''
  const parts: string[] = []
  if (s.spiceLevel != null) parts.push(`辣${s.spiceLevel}/5`)
  if (s.sweetLevel != null) parts.push(`甜${s.sweetLevel}/5`)
  if (s.saltLevel != null) parts.push(`咸${s.saltLevel}/5`)
  if (s.sourLevel != null) parts.push(`酸${s.sourLevel}/5`)
  return parts.length ? `口味：${parts.join(' · ')}` : ''
})

function loadHistory() {
  try {
    const raw = uni.getStorageSync(STORAGE_KEY) as string | undefined
    if (raw) history.value = JSON.parse(raw) as string[]
  } catch {
    history.value = []
  }
}

function saveHistory() {
  try {
    uni.setStorageSync(STORAGE_KEY, JSON.stringify(history.value))
  } catch {
    /* ignore */
  }
}

function pushHistory(name: string) {
  const n = name.trim()
  if (!n) return
  const i = history.value.indexOf(n)
  if (i >= 0) history.value.splice(i, 1)
  history.value.unshift(n)
  if (history.value.length > 20) history.value = history.value.slice(0, 20)
  saveHistory()
}

onShow(() => {
  void syncAuthFromSupabase()
  loadHistory()
})

function onTasteChange(
  key: 'spiceLevel' | 'sweetLevel' | 'saltLevel' | 'sourLevel',
  e: { detail?: { value?: number } },
) {
  const v = e.detail?.value
  if (typeof v === 'number' && v >= 1 && v <= 5) prefs[key] = v
}

function toggleUseCase(id: string) {
  const i = prefs.useCase.indexOf(id)
  if (i >= 0) prefs.useCase.splice(i, 1)
  else prefs.useCase.push(id)
}

function addIngredient() {
  const t = ingredientInput.value.trim()
  if (!t || prefs.availableIngredients.includes(t)) return
  prefs.availableIngredients.push(t)
  ingredientInput.value = ''
}

function removeIngredient(ing: string) {
  const i = prefs.availableIngredients.indexOf(ing)
  if (i >= 0) prefs.availableIngredients.splice(i, 1)
}

function mapRecipeError(e: unknown): { msg: string; needLogin: boolean } {
  if (e instanceof HttpError) {
    if (e.statusCode === 401) return { msg: '需要登录后才能生成，或登录已过期', needLogin: true }
    if (e.statusCode === 404) {
      return {
        msg: '酱料配方接口未部署。请在 BFF 实现 POST /api/ai/sauce-recipe（及推荐接口 sauce-recommend）后重试。',
        needLogin: false,
      }
    }
    return { msg: e.message || `请求失败 (${e.statusCode})`, needLogin: false }
  }
  if (e instanceof Error) return { msg: e.message || '请求失败', needLogin: false }
  return { msg: '请求失败', needLogin: false }
}

function formatSauceText(s: SauceRecipe): string {
  const stepLines = s.steps.map((t) => `${t.step}. ${t.description}${stepMeta(t) ? `（${stepMeta(t)}）` : ''}`)
  return [
    s.description,
    `食材：${s.ingredients.join('、')}`,
    `步骤：\n${stepLines.join('\n')}`,
    `保存：${s.storage.method} / ${s.storage.duration} / ${s.storage.temperature}`,
    s.tips.length ? `提示：${s.tips.join('；')}` : '',
    s.pairings.length ? `搭配：${s.pairings.join('、')}` : '',
  ]
    .filter(Boolean)
    .join('\n\n')
}

async function maybeSaveRecipeHistory(
  data: { history_saved?: boolean },
  s: SauceRecipe,
  sauceName: string,
) {
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
      title: s.name,
      cuisine: categoryLabel(s.category),
      ingredients: s.ingredients,
      response_content: formatSauceText(s),
      request_payload: { sauce_name: sauceName, source: 'mp-sauce-design' },
    })
    msg.toastSaveSuccess()
  } catch (err: unknown) {
    const e = err as Error & { code?: string }
    if (e.code === BIZ_UNAUTHORIZED || e.message === BIZ_UNAUTHORIZED) {
      msg.toastSaveFailed('登录已过期')
    } else if (e.code === BIZ_NEED_LARAVEL_AUTH || e.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请使用微信一键登录后再试')
    } else if (e.code === BIZ_NOT_CONFIGURED || e.message === BIZ_NOT_CONFIGURED) {
      historyNote.value = '当前环境未启用历史写入配置，已跳过保存。'
    } else {
      msg.toastSaveFailed(e.message)
      console.error('[sauce-design] history insert failed:', err)
    }
  }
}

async function syncFavoriteState() {
  if (!currentSauce.value) return
  if (!isLoggedIn.value) {
    isFavorited.value = false
    return
  }
  try {
    const recipeContent = formatSauceText(currentSauce.value)
    const sid = favoriteContentDigest(currentSauce.value.name, recipeContent)
    isFavorited.value = await isFavoriteRecipe({
      source_type: 'sauce_design',
      source_id: sid,
    })
  } catch {
    isFavorited.value = false
  }
}

async function onToggleFavorite() {
  if (!currentSauce.value) return
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  if (favoriteLoading.value) return
  favoriteLoading.value = true
  try {
    const recipeContent = formatSauceText(currentSauce.value)
    const sid = favoriteContentDigest(currentSauce.value.name, recipeContent)
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'sauce_design',
      source_id: sid,
      title: currentSauce.value.name,
      cuisine: categoryLabel(currentSauce.value.category),
      ingredients: currentSauce.value.ingredients ?? [],
      recipe_content: recipeContent,
      image_url: null,
    })
    isFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error & { code?: string }
    if (err.code === BIZ_NEED_LARAVEL_AUTH || err.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请先使用微信一键登录')
    } else if (err.code === BIZ_NOT_CONFIGURED || err.message === BIZ_NOT_CONFIGURED) {
      msg.toastSaveFailed('当前环境未开启收藏配置')
    } else {
      msg.toastSaveFailed(err.message || '收藏失败')
    }
  } finally {
    favoriteLoading.value = false
  }
}

function stepMeta(st: SauceStep): string {
  const bits: string[] = []
  if (st.time != null) bits.push(`${st.time} 分钟`)
  if (st.temperature) bits.push(st.temperature)
  if (st.technique) bits.push(st.technique)
  return bits.join(' · ')
}

async function onGetRecommendations() {
  loadingRec.value = true
  recommendations.value = []
  currentSauce.value = null
  recipeError.value = ''
  recipeNeedLogin.value = false
  historyNote.value = ''
  try {
    await syncAuthFromSupabase()
    const list = await requestSauceRecommend({ ...prefs }, 'zh-CN')
    recommendations.value = list
    if (!list.length) {
      uni.showToast({ title: '暂无推荐，试试调整偏好', icon: 'none' })
    }
  } catch (e: unknown) {
    const { msg, needLogin } = mapRecipeError(e)
    if (needLogin) {
      uni.showToast({ title: '请先登录后再试推荐', icon: 'none' })
    } else {
      const hint =
        e instanceof HttpError && e.statusCode === 404
          ? '推荐接口未部署（POST /api/ai/sauce-recommend）'
          : msg
      uni.showToast({ title: hint.slice(0, 42), icon: 'none' })
    }
  } finally {
    loadingRec.value = false
  }
}

async function loadRecipe(sauceName: string, clearRecommendations = false) {
  const name = sauceName.trim()
  if (!name) return
  pushHistory(name)
  searchQuery.value = name
  if (clearRecommendations) recommendations.value = []
  recipeLoading.value = true
  recipeError.value = ''
  recipeNeedLogin.value = false
  currentSauce.value = null
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
  try {
    await syncAuthFromSupabase()
    const { recipe, history_saved } = await requestSauceRecipe(name, 'zh-CN')
    if (!recipe.name.trim()) throw new Error('接口返回异常')
    currentSauce.value = recipe
    await maybeSaveRecipeHistory({ history_saved }, recipe, name)
    await syncFavoriteState()
  } catch (e: unknown) {
    const { msg, needLogin } = mapRecipeError(e)
    recipeError.value = msg
    recipeNeedLogin.value = needLogin
  } finally {
    recipeLoading.value = false
  }
}

function onSelectRecommended(name: string) {
  void loadRecipe(name)
}

function onSearchQuery() {
  void loadRecipe(searchQuery.value, true)
}

function pickHistory(h: string) {
  searchQuery.value = h
  void loadRecipe(h, true)
}

function clearHistory() {
  history.value = []
  saveHistory()
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/sauce-design/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.sc__hero-inner {
  text-align: center;
}

.sc__hero-title {
  max-width: 640rpx;
  margin-left: auto;
  margin-right: auto;
}

.sc__hero-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

.sc__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

.sc__scroll {
  max-height: calc(100vh - 200rpx);
  padding-bottom: 48rpx;
}

.sc__panel {
  margin-bottom: 24rpx;
}

.sc__panel--idle {
  position: relative;
  padding-top: 28rpx;
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.1);
  overflow: hidden;
}

.sc__panel--idle::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.sc__badge {
  align-self: flex-start;
  margin-bottom: 12rpx;
}

.sc__badge-txt {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: $mp-accent;
  text-transform: uppercase;
}

.sc__panel-desc {
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.5;
  margin-bottom: 24rpx;
}

.sc__sliders {
  padding: 20rpx;
  background: #f5f6f8;
  border-radius: 16rpx;
  border: 1rpx solid $mp-border;
  margin-bottom: 24rpx;
}

.sc__slider-row + .sc__slider-row {
  margin-top: 20rpx;
}

.sc__slider-label {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  font-size: 26rpx;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8rpx;
}

.sc__slider-val {
  color: $mp-accent;
  font-weight: 800;
}

.sc__block-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
  margin-bottom: 12rpx;
}

.sc__block-title--mt {
  margin-top: 24rpx;
}

.sc__use-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12rpx;
}

.sc__use-pill {
  padding: 16rpx 20rpx;
  border-radius: 14rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.sc__use-pill--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.sc__use-txt {
  font-size: 24rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.sc__add-row {
  display: flex;
  flex-direction: row;
  gap: 12rpx;
  align-items: center;
}

.sc__input {
  padding: 20rpx 22rpx;
  border-radius: 14rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
  font-size: 26rpx;
}

.sc__input--grow {
  flex: 1;
}

.sc__mini-btn {
  padding: 0 24rpx;
  height: 72rpx;
  line-height: 72rpx;
  font-size: 26rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #9575e8, #7a57d1);
  border-radius: 14rpx;
  border: none;
}

.sc__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 12rpx;
}

.sc__tag {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: #ffedd5;
  border: 1rpx solid #fdba74;
}

.sc__tag-txt {
  font-size: 24rpx;
  color: #9a3412;
}

.sc__tag-x {
  font-size: 22rpx;
  color: #c2410c;
}

.sc__btn-rec {
  margin-top: 28rpx;
  width: 100%;
}

.sc__mini-loading {
  padding: 40rpx;
  text-align: center;
}

.sc__mini-loading-ico {
  font-size: 56rpx;
}

.sc__mini-loading-txt {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  color: $mp-text-secondary;
}

.sc__rec-head {
  display: block;
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
  margin-bottom: 16rpx;
}

.sc__rec-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16rpx;
}

.sc__rec-card {
  width: calc(50% - 8rpx);
  box-sizing: border-box;
  padding: 24rpx 20rpx;
  border-radius: 16rpx;
  background: linear-gradient(145deg, #faf5ff 0%, #fdf2f8 100%);
  border: 1rpx solid $mp-ring-accent;
  position: relative;
}

.sc__rec-idx {
  position: absolute;
  top: 12rpx;
  right: 12rpx;
  width: 40rpx;
  height: 40rpx;
  line-height: 40rpx;
  text-align: center;
  border-radius: 999rpx;
  background: $mp-accent;
  color: #fff;
  font-size: 22rpx;
  font-weight: 800;
}

.sc__rec-name {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
  padding-right: 48rpx;
}

.sc__rec-sub {
  display: block;
  margin-top: 10rpx;
  font-size: 22rpx;
  color: $mp-accent-deep;
}

.sc__search-row {
  display: flex;
  flex-direction: row;
  gap: 12rpx;
  align-items: center;
}

.sc__search-btn {
  padding: 0 28rpx;
  height: 80rpx;
  line-height: 80rpx;
  font-size: 28rpx;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, #6366f1, #7a57d1);
  border-radius: 16rpx;
  border: none;
}

.sc__search-btn[disabled] {
  opacity: 0.45;
}

.sc__empty {
  padding: 48rpx 24rpx;
  text-align: center;
}

.sc__empty-title {
  display: block;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 16rpx;
}

.sc__empty-line {
  display: block;
  font-size: 24rpx;
  color: $mp-text-secondary;
  line-height: 1.6;
}

.sc__state-loading {
  padding: 56rpx 24rpx;
  text-align: center;
}

.sc__state-ico {
  font-size: 64rpx;
}

.sc__state-title {
  display: block;
  margin-top: 12rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.sc__state-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 26rpx;
  color: $mp-text-secondary;
}

.sc__state-err {
  padding: 32rpx 24rpx;
  text-align: center;
}

.sc__err-title {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: #b91c1c;
}

.sc__err-msg {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  color: #7f1d1d;
  line-height: 1.5;
  text-align: left;
}

.sc__err-btn {
  margin-top: 24rpx;
}

.sc__recipe {
  padding-bottom: 16rpx;
}

.sc__rh {
  padding: 28rpx 24rpx;
  background: linear-gradient(120deg, #ea580c 0%, #f97316 40%, #fb923c 100%);
  border-radius: 16rpx;
  margin: -8rpx -8rpx 24rpx;
}

.sc__r-name {
  display: block;
  font-size: 36rpx;
  font-weight: 900;
  color: #fff;
}

.sc__r-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 16rpx;
}

.sc__chip {
  font-size: 22rpx;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.95);
  padding: 6rpx 14rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.22);
  border: 1rpx solid rgba(255, 255, 255, 0.35);
}

.sc__flavor {
  margin-top: 16rpx;
}

.sc__flavor-txt {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.9);
}

.sc__sheet {
  margin-bottom: 24rpx;
  padding: 24rpx;
  border-radius: 16rpx;
}

.sc__sheet--orange {
  background: #fff7ed;
  border: 1rpx solid #fed7aa;
}

.sc__sheet-k {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 10rpx;
}

.sc__sheet-body {
  font-size: 26rpx;
  color: #374151;
  line-height: 1.55;
}

.sc__sec-k {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin: 28rpx 0 12rpx;
}

.sc__ing {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
  padding: 14rpx 16rpx;
  margin-bottom: 8rpx;
  background: #f9fafb;
  border-radius: 12rpx;
  font-size: 26rpx;
  color: #374151;
}

.sc__ing-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 999rpx;
  background: #f97316;
}

.sc__step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
  padding: 20rpx;
  margin-bottom: 12rpx;
  background: #f9fafb;
  border-radius: 12rpx;
  border-left: 6rpx solid #fb923c;
}

.sc__step-n {
  width: 44rpx;
  height: 44rpx;
  line-height: 44rpx;
  text-align: center;
  border-radius: 999rpx;
  background: #ea580c;
  color: #fff;
  font-size: 24rpx;
  font-weight: 800;
  flex-shrink: 0;
}

.sc__step-main {
  flex: 1;
}

.sc__step-desc {
  font-size: 26rpx;
  color: #1f2937;
  line-height: 1.5;
}

.sc__step-meta {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.sc__tip {
  padding: 14rpx 16rpx;
  margin-bottom: 8rpx;
  background: #fffbeb;
  border-radius: 12rpx;
  border: 1rpx solid #fde68a;
  font-size: 26rpx;
  color: #78350f;
}

.sc__storage {
  margin-top: 8rpx;
}

.sc__stor-line {
  font-size: 26rpx;
  color: #374151;
  line-height: 1.5;
}

.sc__pair-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
}

.sc__pair-chip {
  font-size: 24rpx;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: #dcfce7;
  color: #166534;
  border: 1rpx solid #86efac;
}

.sc__tags-b {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 20rpx;
}

.sc__hash {
  font-size: 22rpx;
  color: #6b7280;
  padding: 6rpx 12rpx;
  background: #f3f4f6;
  border-radius: 8rpx;
}

.sc__hist {
  margin-top: 24rpx;
  padding: 16rpx 20rpx;
  border-radius: 12rpx;
  background: #f0fdf4;
  border: 1rpx solid #bbf7d0;
}

.sc__hist-txt {
  font-size: 24rpx;
  color: #166534;
  line-height: 1.45;
}

.sc__fav-row {
  margin-top: 18rpx;
}

.sc__fav-btn {
  width: 100%;
}

.sc__panel--hist {
  padding-bottom: 32rpx;
}

.sc__hist-title {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 16rpx;
}

.sc__hist-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  align-items: center;
}

.sc__hist-pill {
  font-size: 24rpx;
  padding: 12rpx 20rpx;
  border-radius: 999rpx;
  background: #ffedd5;
  color: #9a3412;
  border: 1rpx solid #fdba74;
}

.sc__hist-clear {
  font-size: 24rpx;
  color: #dc2626;
  text-decoration: underline;
  padding: 12rpx;
}
</style>
