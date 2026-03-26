<template>
  <view class="mp-page te">
    <!-- 标题区：对齐 Web「今日灵感」氛围头图结构 -->
    <view class="mp-hero te__hero">
      <view class="te__hero-inner">
        <text class="mp-hero__kicker mp-kicker--on-dark">今日灵感</text>
        <text class="mp-hero__title te__hero-title">吃什么</text>
        <text class="mp-hero__sub te__hero-sub">
          填写偏好，一键生成可执行的晚餐方案。请求经自有 BFF 代理，不直连第三方 AI。
        </text>
        <view class="te__hero-rule" />
      </view>
    </view>

    <!-- 初始：表单（主操作卡片，贴近 Web 主白卡片 + 主操作区标签） -->
    <view v-if="phase === 'idle'" class="mp-card te__panel te__panel--idle">
      <view class="te__panel-badge">
        <text class="te__panel-badge-txt">主操作区</text>
      </view>
      <view class="te__hero-icon">
        <text class="te__hero-icon-emoji">🎲</text>
      </view>
      <text class="te__panel-title">生成今日推荐</text>
      <text class="te__panel-desc">口味、忌口、人数均为选填；可直接点下方按钮尝试生成。</text>

      <view class="te__section-label">
        <text class="te__section-label-k">偏好</text>
        <text class="te__section-label-h">本次用餐条件</text>
      </view>
      <view class="te__fieldset">
        <view class="te__field">
          <view class="te__label-row">
            <text class="te__label">口味偏好</text>
            <text class="te__label-opt">选填</text>
          </view>
          <input v-model="taste" class="te__input" placeholder="如：清淡、微辣" />
        </view>
        <view class="te__field">
          <view class="te__label-row">
            <text class="te__label">忌口 / 不吃</text>
            <text class="te__label-opt">选填</text>
          </view>
          <input v-model="avoid" class="te__input" placeholder="不想吃的食材" />
        </view>
        <view class="te__field te__field--last">
          <view class="te__label-row">
            <text class="te__label">用餐人数</text>
            <text class="te__label-opt">选填</text>
          </view>
          <input v-model="peopleStr" class="te__input" type="number" placeholder="可选，默认不限" />
        </view>
      </view>

      <view v-if="prefsEmpty" class="te__prefs-status te__prefs-status--empty">
        <text class="te__prefs-status-title">尚未填写任何偏好</text>
        <text class="te__prefs-status-desc">可直接生成通用推荐；填写后结果会更贴合你的口味</text>
      </view>
      <view v-else class="te__prefs-status">
        <text class="te__prefs-status-ok">已填写 {{ prefFilledCount }} 项条件，将一并提交生成</text>
      </view>

      <button class="mp-btn-primary te__submit te__submit--hero" @click="onGenerate">
        <text class="te__submit-txt">立即生成推荐</text>
        <text class="te__submit-go">→</text>
      </button>
      <text class="te__idle-foot">已登录时，生成结果可按策略写入历史记录</text>
    </view>

    <!-- loading -->
    <view v-else-if="phase === 'loading'" class="mp-card te__panel te__panel--state te__panel--loading">
      <view class="te__state-head">
        <text class="te__state-kicker">生成中</text>
        <text class="te__state-title">正在为你搭配方案</text>
      </view>
      <view class="mp-state-icon te__loading-icon">✨</view>
      <view class="te__progress-track">
        <view class="te__progress-fill" />
      </view>
      <text class="te__loading-hint">请稍候，服务端正在通过 AI 代理生成内容…</text>
    </view>

    <!-- 错误 -->
    <view v-else-if="phase === 'error'" class="mp-card te__panel te__panel--state te__panel--state-error">
      <view class="te__state-head">
        <text class="te__state-kicker te__state-kicker--danger">未成功</text>
        <text class="te__state-title">本次生成失败</text>
      </view>
      <view class="mp-state-icon mp-state-icon--danger te__err-icon">!</view>
      <view class="te__err-box">
        <text class="te__err-box-label">原因说明</text>
        <text class="te__err-msg">{{ errorMessage }}</text>
      </view>
      <view class="te__err-actions">
        <button v-if="needLogin" class="mp-btn-primary te__stack-btn" @click="goLogin">
          {{ config.common_empty_button_text }}
        </button>
        <button class="mp-btn-ghost te__stack-btn" @click="resetIdle">返回修改偏好</button>
      </view>
    </view>

    <!-- 成功 -->
    <scroll-view v-else-if="phase === 'success' && result" class="te__scroll" scroll-y>
      <view class="mp-card te__result">
        <view class="te__result-hero">
          <text class="te__result-hero-k">推荐完成</text>
          <text class="te__result-hero-title">你的今日灵感已就绪</text>
          <text class="te__result-hero-sub">以下是依据你本次偏好生成的方案，可作为晚餐参考</text>
        </view>

        <view class="te__result-core">
          <view class="te__result-core-head">
            <view class="te__result-core-ico">
              <text class="te__result-core-emoji">🍽️</text>
            </view>
            <view class="te__result-core-titles">
              <text class="te__result-core-k">菜谱名称</text>
              <text class="te__result-title">{{ result.title }}</text>
            </view>
          </view>

          <text class="te__block-title">菜品信息</text>
          <view class="te__meta-grid">
            <view class="te__meta-cell">
              <text class="te__meta-label">菜系</text>
              <text class="te__meta-value">{{ result.cuisine || '—' }}</text>
            </view>
            <view class="te__meta-cell te__meta-cell--wide">
              <text class="te__meta-label">食材</text>
              <text class="te__meta-value">{{ ingredientsText }}</text>
            </view>
          </view>

          <view class="te__body">
            <text class="te__body-k">详细说明</text>
            <view class="te__body-sheet">
              <text class="te__body-text">{{ result.content }}</text>
            </view>
          </view>
        </view>

        <view v-if="historyNote" class="te__history-banner">
          <text class="te__history-note">{{ historyNote }}</text>
        </view>

        <view class="te__fav-row">
          <button class="mp-btn-ghost te__fav-btn" :disabled="favoriteLoading" @click="onToggleFavorite">
            <text>{{ isFavorited ? '取消收藏' : '加入收藏' }}</text>
          </button>
        </view>
      </view>
      <button class="mp-btn-primary te__again te__again--hero" @click="resetIdle">
        <text class="te__again-txt">再生成一次</text>
        <text class="te__again-go">→</text>
      </button>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { requestTodayEat } from '@/api/ai'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import { insertRecipeHistoryFromTodayEat, isFavoriteRecipe, toggleFavoriteRecipe, BIZ_UNAUTHORIZED } from '@/api/biz'
import type { TodayEatResult } from '@/types/ai'

type Phase = 'idle' | 'loading' | 'success' | 'error'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const phase = ref<Phase>('idle')
const taste = ref('')
const avoid = ref('')
const peopleStr = ref('')
const result = ref<TodayEatResult | null>(null)
const errorMessage = ref('')
const needLogin = ref(false)
const historyNote = ref('')
const favoriteLoading = ref(false)
const isFavorited = ref(false)

const ingredientsText = computed(() => {
  const ing = result.value?.ingredients
  if (!ing?.length) return '—'
  return ing.join('、')
})

const prefsEmpty = computed(() => {
  return !taste.value.trim() && !avoid.value.trim() && !peopleStr.value.trim()
})

const prefFilledCount = computed(() => {
  let n = 0
  if (taste.value.trim()) n++
  if (avoid.value.trim()) n++
  if (peopleStr.value.trim()) n++
  return n
})

onShow(() => {
  void syncAuthFromSupabase()
})

function buildPeople(): number | undefined {
  const n = parseInt(peopleStr.value.trim(), 10)
  return Number.isFinite(n) && n > 0 ? n : undefined
}

async function onGenerate() {
  needLogin.value = false
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
  phase.value = 'loading'
  errorMessage.value = ''

  await syncAuthFromSupabase()

  const preferences = {
    taste: taste.value.trim() || undefined,
    avoid: avoid.value.trim() || undefined,
    people: buildPeople(),
  }

  try {
    const data = await requestTodayEat({
      preferences,
      locale: 'zh-CN',
    })

    if (!data || typeof data.content !== 'string' || !data.title) {
      throw new Error('接口返回格式异常')
    }

    result.value = {
      title: data.title,
      cuisine: data.cuisine,
      ingredients: data.ingredients,
      content: data.content,
      history_saved: data.history_saved,
    }

    await maybeSaveHistoryLocally(data, preferences)
    await syncFavoriteState()

    phase.value = 'success'
  } catch (e: unknown) {
    if (e instanceof HttpError && e.statusCode === 401) {
      needLogin.value = true
      errorMessage.value = '需要登录后才能生成，或登录已过期'
    } else if (e instanceof Error) {
      errorMessage.value = e.message || '请求失败'
    } else {
      errorMessage.value = '请求失败'
    }
    phase.value = 'error'
    result.value = null
  }
}

async function maybeSaveHistoryLocally(data: TodayEatResult, preferences: Record<string, unknown>) {
  historyNote.value = ''
  if (data.history_saved === true) {
    msg.toastSaveSuccess()
    return
  }
  if (!isLoggedIn.value) {
    historyNote.value =
      '未登录：本次未写入历史；登录后可在 BFF 支持自动写入或客户端补写'
    return
  }
  try {
    await insertRecipeHistoryFromTodayEat({
      title: data.title,
      cuisine: data.cuisine ?? null,
      ingredients: data.ingredients,
      response_content: data.content,
      request_payload: { preferences, source: 'mp-today-eat' },
    })
    msg.toastSaveSuccess()
  } catch (err: unknown) {
    const e = err as Error & { code?: string }
    if (e.code === BIZ_UNAUTHORIZED || e.message === BIZ_UNAUTHORIZED) {
      msg.toastSaveFailed('登录已过期')
    } else {
      msg.toastSaveFailed(e.message)
      console.error('[today-eat] history insert failed:', err)
    }
  }
}

async function syncFavoriteState() {
  if (!result.value?.title || !result.value?.content) return
  if (!isLoggedIn.value) {
    isFavorited.value = false
    return
  }
  try {
    isFavorited.value = await isFavoriteRecipe({
      title: result.value.title,
      recipe_content: result.value.content,
    })
  } catch {
    isFavorited.value = false
  }
}

async function onToggleFavorite() {
  if (!result.value?.title || !result.value?.content) return
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  if (favoriteLoading.value) return

  favoriteLoading.value = true
  try {
    const { favorited } = await toggleFavoriteRecipe({
      title: result.value.title,
      cuisine: result.value.cuisine ?? null,
      ingredients: result.value.ingredients ?? [],
      recipe_content: result.value.content,
      image_url: null,
    })
    isFavorited.value = favorited
    if (favorited) msg.toastFavoriteSuccess()
    else msg.toastFavoriteCancel()
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message)
  } finally {
    favoriteLoading.value = false
  }
}

function resetIdle() {
  phase.value = 'idle'
  result.value = null
  errorMessage.value = ''
  needLogin.value = false
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/today-eat/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.te__hero-inner {
  text-align: center;
}

.te__hero-title {
  max-width: 640rpx;
  margin-left: auto;
  margin-right: auto;
}

.te__hero-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

.te__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

/* 主操作 idle 卡片：紫色顶条 + 图标锚点，贴近 Web */
.te__panel--idle {
  position: relative;
  padding-top: 36rpx;
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.12);
  overflow: hidden;
}

.te__panel--idle::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.te__panel-badge {
  align-self: flex-start;
  margin-bottom: 8rpx;
}

.te__panel-badge-txt {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: $mp-accent;
}

.te__hero-icon {
  width: 120rpx;
  height: 120rpx;
  margin: 12rpx auto 8rpx;
  border-radius: 28rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  box-shadow: 0 4rpx 16rpx rgba(122, 87, 209, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
}

.te__hero-icon-emoji {
  font-size: 64rpx;
  line-height: 1;
}

.te__panel-title {
  display: block;
  text-align: center;
  margin-top: 8rpx;
  font-size: 36rpx;
  font-weight: 800;
  color: $mp-text-primary;
  letter-spacing: -0.02em;
}

.te__panel-desc {
  display: block;
  margin-top: 12rpx;
  text-align: center;
  font-size: 26rpx;
  color: $mp-text-secondary;
  line-height: 1.55;
  padding: 0 8rpx;
}

.te__section-label {
  margin-top: 32rpx;
  margin-bottom: 16rpx;
  padding-left: 4rpx;
}

.te__section-label-k {
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: $mp-text-muted;
  text-transform: uppercase;
}

.te__section-label-h {
  display: block;
  margin-top: 6rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.te__fieldset {
  padding: 28rpx 24rpx;
  border-radius: 20rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.te__field {
  display: flex;
  flex-direction: column;
}

.te__field + .te__field {
  margin-top: 24rpx;
}

.te__label-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.te__label {
  font-size: 26rpx;
  font-weight: 600;
  color: #374151;
}

.te__label-opt {
  flex-shrink: 0;
  font-size: 22rpx;
  font-weight: 600;
  color: $mp-text-muted;
  padding: 4rpx 12rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.85);
  border: 1rpx solid $mp-border;
}

.te__input {
  margin-top: 12rpx;
  padding: 22rpx 24rpx;
  font-size: 28rpx;
  border-radius: 16rpx;
  border: 1rpx solid $mp-border;
  background: #fff;
}

.te__submit--hero {
  margin-top: 40rpx;
  display: flex !important;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding-top: 32rpx !important;
  padding-bottom: 32rpx !important;
  font-size: 32rpx !important;
  box-shadow: 0 20rpx 56rpx rgba(122, 87, 209, 0.38);
}

.te__submit-txt {
  font-weight: 800;
}

.te__submit-go {
  font-size: 34rpx;
  font-weight: 800;
}

.te__prefs-status {
  margin-top: 24rpx;
  padding: 20rpx 22rpx;
  border-radius: 16rpx;
  background: #fff;
  border: 1rpx solid #e8ddf5;
}

.te__prefs-status--empty {
  background: rgba(243, 236, 255, 0.35);
}

.te__prefs-status-title {
  display: block;
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-accent;
}

.te__prefs-status-desc {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.te__prefs-status-ok {
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.te__idle-foot {
  display: block;
  margin-top: 20rpx;
  text-align: center;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}

.te__fav-row {
  margin-top: 18rpx;
}

.te__fav-btn {
  width: 100%;
}

/* loading / error 态 */
.te__panel--state {
  position: relative;
  padding-top: 36rpx;
  padding-bottom: 40rpx;
  overflow: hidden;
}

.te__panel--loading {
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.1);
}

.te__panel--loading::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.te__panel--state-error {
  border-color: #fecaca;
  box-shadow: 0 8rpx 28rpx rgba(185, 28, 28, 0.08);
}

.te__panel--state-error::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #f87171 0%, #dc2626 100%);
}

.te__state-head {
  text-align: center;
  margin-bottom: 28rpx;
}

.te__state-kicker {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $mp-accent;
}

.te__state-kicker--danger {
  color: #b91c1c;
}

.te__state-title {
  display: block;
  margin-top: 10rpx;
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.te__loading-icon {
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 28rpx;
}

.te__progress-track {
  height: 12rpx;
  border-radius: 999rpx;
  background: #eceef2;
  overflow: hidden;
  margin: 0 24rpx 24rpx;
  box-shadow: inset 0 2rpx 6rpx rgba(0, 0, 0, 0.06);
}

.te__progress-fill {
  height: 100%;
  width: 55%;
  border-radius: 999rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 45%, #6743bf 100%);
}

.te__loading-hint {
  display: block;
  text-align: center;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  padding: 0 32rpx;
}

.te__err-icon {
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 24rpx;
}

.te__err-box {
  align-self: stretch;
  margin: 0 8rpx;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fef2f2;
  border: 1rpx solid #fecaca;
}

.te__err-box-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: #991b1b;
  letter-spacing: 0.06em;
  margin-bottom: 12rpx;
}

.te__err-msg {
  font-size: 26rpx;
  color: #7f1d1d;
  line-height: 1.55;
  word-break: break-word;
}

.te__err-actions {
  margin-top: 32rpx;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.te__stack-btn {
  width: 100%;
}

/* 结果区 */
.te__scroll {
  max-height: calc(100vh - 32rpx);
}

.te__result {
  padding: 0;
  overflow: hidden;
}

.te__result-hero {
  padding: 36rpx 28rpx 32rpx;
  text-align: center;
  background: linear-gradient(180deg, rgba(243, 236, 255, 0.45) 0%, #fff 100%);
  border-bottom: 1rpx solid $mp-border;
}

.te__result-hero-k {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $mp-accent;
}

.te__result-hero-title {
  display: block;
  margin-top: 12rpx;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.te__result-hero-sub {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  padding: 0 16rpx;
}

.te__result-core {
  padding: 28rpx 28rpx 32rpx;
}

.te__block-title {
  display: block;
  margin-top: 28rpx;
  margin-bottom: 12rpx;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: $mp-text-muted;
}

.te__result-core-head {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 20rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #f3f4f6;
}

.te__result-core-ico {
  flex-shrink: 0;
  width: 72rpx;
  height: 72rpx;
  border-radius: 20rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.te__result-core-emoji {
  font-size: 40rpx;
  line-height: 1;
}

.te__result-core-titles {
  flex: 1;
  min-width: 0;
}

.te__result-core-k {
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
  letter-spacing: 0.06em;
}

.te__result-title {
  display: block;
  margin-top: 8rpx;
  font-size: 36rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.35;
  word-break: break-word;
}

.te__meta-grid {
  margin-top: 24rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16rpx;
}

.te__meta-cell {
  flex: 1;
  min-width: 200rpx;
  padding: 20rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.te__meta-cell--wide {
  flex: 1 1 100%;
  min-width: 0;
}

.te__meta-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
}

.te__meta-value {
  display: block;
  margin-top: 10rpx;
  font-size: 28rpx;
  color: #374151;
  line-height: 1.45;
  word-break: break-all;
}

.te__body {
  margin-top: 28rpx;
}

.te__body-k {
  font-size: 24rpx;
  font-weight: 800;
  color: $mp-accent;
  letter-spacing: 0.04em;
}

.te__body-sheet {
  margin-top: 12rpx;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.te__body-text {
  font-size: 28rpx;
  line-height: 1.65;
  color: #1f2937;
  white-space: pre-wrap;
  word-break: break-word;
}

.te__history-banner {
  padding: 20rpx 28rpx 28rpx;
  background: #fff;
}

.te__history-note {
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  padding: 16rpx 20rpx;
  border-radius: 12rpx;
  background: #f9fafb;
  border: 1rpx dashed #e5e7eb;
}

.te__again {
  margin-top: 24rpx;
  margin-bottom: 48rpx;
}

.te__again--hero {
  display: flex !important;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding-top: 30rpx !important;
  padding-bottom: 30rpx !important;
}

.te__again-txt {
  font-weight: 800;
}

.te__again-go {
  font-size: 32rpx;
  font-weight: 800;
}
</style>
