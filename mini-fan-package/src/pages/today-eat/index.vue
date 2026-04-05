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
      <text class="te__panel-desc">点下方按钮，用标签选今日状态与口味忌口；无需打字，也可跳过直接生成。</text>

      <view class="te__status-hint te__status-hint--muted">
        <text class="te__status-hint-txt">弹窗里点选即可，不会弹出键盘。</text>
      </view>

      <button class="mp-btn-primary te__submit te__submit--hero" @click="openTodayStatusSheet">
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

        <view class="te__recent">
          <text class="te__recent-title">最近吃么么记录</text>
          <view v-if="recentLoading" class="te__recent-empty">加载中…</view>
          <view v-else-if="recentRecords.length === 0" class="te__recent-empty">暂无记录</view>
          <view v-else class="te__recent-list">
            <view v-for="row in recentRecords" :key="row.id" class="te__recent-item">
              <view class="te__recent-main" @click="onReplayRecord(row.id)">
                <text class="te__recent-name">{{ row.result_title || '未命名结果' }}</text>
                <text class="te__recent-meta">{{ row.result_cuisine || '—' }} · {{ row.created_at || '' }}</text>
              </view>
              <text class="te__recent-del" @click="onDeleteRecord(row.id)">删除</text>
            </view>
          </view>
        </view>
      </view>
      <button class="mp-btn-primary te__again te__again--hero" @click="resetIdle">
        <text class="te__again-txt">再生成一次</text>
        <text class="te__again-go">→</text>
      </button>
    </scroll-view>

    <view v-if="statusSheetOpen" class="te__sheet-mask" @click="onStatusMaskTap">
      <view class="te__sheet" @click.stop>
        <view class="te__sheet-head">
          <text class="te__sheet-title">今日状态</text>
          <text class="te__sheet-sub">标签描述今天的你；口味与忌口点选即可（多选）。登录后写入今日状态并参与推荐。</text>
        </view>

        <scroll-view scroll-y class="te__sheet-scroll" :show-scrollbar="false">
          <view class="te__sheet-block">
            <text class="te__sheet-k">心情</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_MOOD_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetMood === opt.value }"
                @click="toggleSheetMood(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view class="te__sheet-block">
            <text class="te__sheet-k">想吃的方向</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_WANT_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetWant === opt.value }"
                @click="toggleSheetWant(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view class="te__sheet-block">
            <text class="te__sheet-k">身体感受</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_BODY_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetBody === opt.value }"
                @click="toggleSheetBody(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view v-if="periodFeatureEnabled" class="te__sheet-block">
            <text class="te__sheet-k">特殊时期</text>
            <view class="te__chip-row">
              <view
                v-for="opt in DAILY_PERIOD_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetPeriod === opt.value }"
                @click="toggleSheetPeriod(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>

          <view class="te__sheet-block">
            <text class="te__sheet-k">口味偏好</text>
            <text class="te__sheet-multi-hint">可多选</text>
            <view class="te__chip-row">
              <view
                v-for="opt in MEAL_FLAVOR_TAG_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetFlavorTags.includes(opt.value) }"
                @click="toggleSheetFlavorTag(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
          <view class="te__sheet-block te__sheet-block--last">
            <text class="te__sheet-k">忌口 / 不吃</text>
            <text class="te__sheet-multi-hint">可多选；选「暂无」会清空其它项</text>
            <view class="te__chip-row">
              <view
                v-for="opt in MEAL_TABOO_TAG_OPTIONS"
                :key="opt.value"
                class="te__chip"
                :class="{ 'te__chip--on': sheetTabooTags.includes(opt.value) }"
                @click="toggleSheetTabooTag(opt.value)"
              >
                <text>{{ opt.label }}</text>
              </view>
            </view>
          </view>
        </scroll-view>

        <view class="te__sheet-foot">
          <button class="mp-btn-ghost te__sheet-foot-btn" @click="onSkipTodayStatus">
            <text>跳过，直接推荐</text>
          </button>
          <button class="mp-btn-primary te__sheet-foot-btn" @click="onConfirmTodayStatus">
            <text>开始推荐</text>
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { requestTodayEat } from '@/api/ai'
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
import { fetchEatMemeRecords, deleteEatMemeRecord, type EatMemeRecordItem } from '@/api/eatMeme'
import { favoriteContentDigest } from '@/lib/favoriteDigest'
import type { TodayEatResult } from '@/types/ai'
import { fetchMeProfile, fetchMeDailyToday, putMeDailyToday } from '@/api/me'
import {
  DAILY_BODY_OPTIONS,
  DAILY_MOOD_OPTIONS,
  DAILY_PERIOD_OPTIONS,
  DAILY_WANT_OPTIONS,
  normalizeBodyFromApi,
  normalizeMoodFromApi,
  normalizePeriodFromApi,
  normalizeWantFromApi,
} from '@/constants/dailyStatus'
import {
  MEAL_FLAVOR_TAG_OPTIONS,
  MEAL_TABOO_TAG_OPTIONS,
  normalizeFlavorTagsFromApi,
  normalizeTabooTagsFromApi,
} from '@/constants/mealPreferenceTags'

type Phase = 'idle' | 'loading' | 'success' | 'error'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const phase = ref<Phase>('idle')
/** 防止连点重复请求 */
const generateInFlight = ref(false)
const result = ref<TodayEatResult | null>(null)
const errorMessage = ref('')
const needLogin = ref(false)
const historyNote = ref('')
const favoriteLoading = ref(false)
const isFavorited = ref(false)
const recentRecords = ref<EatMemeRecordItem[]>([])
const recentLoading = ref(false)

const periodFeatureEnabled = ref(false)
const dailyMood = ref('')
const dailyWant = ref('')
const dailyBody = ref('')
const dailyPeriod = ref('')
const dailyFlavorTags = ref<string[]>([])
const dailyTabooTags = ref<string[]>([])

const statusSheetOpen = ref(false)
const sheetMood = ref('')
const sheetWant = ref('')
const sheetBody = ref('')
const sheetPeriod = ref('')
const sheetFlavorTags = ref<string[]>([])
const sheetTabooTags = ref<string[]>([])

function syncSheetFromDaily() {
  sheetMood.value = dailyMood.value
  sheetWant.value = dailyWant.value
  sheetBody.value = dailyBody.value
  sheetPeriod.value = dailyPeriod.value || 'none'
  sheetFlavorTags.value = [...dailyFlavorTags.value]
  sheetTabooTags.value = [...dailyTabooTags.value]
}

function toggleSheetFlavorTag(v: string) {
  const i = sheetFlavorTags.value.indexOf(v)
  if (i >= 0) {
    sheetFlavorTags.value = sheetFlavorTags.value.filter((x) => x !== v)
  } else {
    sheetFlavorTags.value = [...sheetFlavorTags.value, v]
  }
}

function toggleSheetTabooTag(v: string) {
  if (v === 'none') {
    sheetTabooTags.value = ['none']
    return
  }
  let next = sheetTabooTags.value.filter((x) => x !== 'none')
  const i = next.indexOf(v)
  if (i >= 0) {
    next = next.filter((x) => x !== v)
  } else {
    next = [...next, v]
  }
  sheetTabooTags.value = next
}

function applySheetToDaily() {
  dailyMood.value = sheetMood.value
  dailyWant.value = sheetWant.value
  dailyBody.value = sheetBody.value
  dailyPeriod.value = periodFeatureEnabled.value ? sheetPeriod.value || 'none' : ''
  dailyFlavorTags.value = [...sheetFlavorTags.value]
  dailyTabooTags.value = [...sheetTabooTags.value]
}

function flavorTagsToPreferenceTaste(tags: string[]): string | undefined {
  if (!tags.length) return undefined
  const labels = tags
    .map((k) => MEAL_FLAVOR_TAG_OPTIONS.find((o) => o.value === k)?.label)
    .filter((x): x is string => Boolean(x))
  return labels.length ? labels.join('、') : undefined
}

function tabooTagsToPreferenceAvoid(tags: string[]): string | undefined {
  if (!tags.length || tags.includes('none')) return undefined
  const labels = tags
    .filter((k) => k !== 'none')
    .map((k) => MEAL_TABOO_TAG_OPTIONS.find((o) => o.value === k)?.label)
    .filter((x): x is string => Boolean(x))
  return labels.length ? labels.join('、') : undefined
}

watch(periodFeatureEnabled, (ok) => {
  if (!ok) {
    dailyPeriod.value = ''
    sheetPeriod.value = 'none'
  }
})

function toggleSheetMood(v: string) {
  sheetMood.value = sheetMood.value === v ? '' : v
}

function toggleSheetWant(v: string) {
  sheetWant.value = sheetWant.value === v ? '' : v
}

function toggleSheetBody(v: string) {
  sheetBody.value = sheetBody.value === v ? '' : v
}

function toggleSheetPeriod(v: string) {
  sheetPeriod.value = sheetPeriod.value === v ? 'none' : v
}

function openTodayStatusSheet() {
  syncSheetFromDaily()
  statusSheetOpen.value = true
}

function onStatusMaskTap() {
  statusSheetOpen.value = false
}

function onSkipTodayStatus() {
  applySheetToDaily()
  statusSheetOpen.value = false
  void runGenerate(false)
}

function onConfirmTodayStatus() {
  applySheetToDaily()
  statusSheetOpen.value = false
  void runGenerate(true)
}

const ingredientsText = computed(() => {
  const ing = result.value?.ingredients
  if (!ing?.length) return '—'
  return ing.join('、')
})

async function hydrateMeContext() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value) {
    periodFeatureEnabled.value = false
    return
  }
  try {
    const res = await fetchMeProfile()
    periodFeatureEnabled.value = Boolean(res.profile.period_feature_enabled)
    const t = res.today_status
    if (t) {
      dailyMood.value = normalizeMoodFromApi(t.mood)
      dailyWant.value = normalizeWantFromApi(t.wanted_food_style)
      dailyBody.value = normalizeBodyFromApi(t.body_state)
      dailyPeriod.value = periodFeatureEnabled.value
        ? normalizePeriodFromApi(t.period_status)
        : ''
      dailyFlavorTags.value = normalizeFlavorTagsFromApi(t.flavor_tags)
      dailyTabooTags.value = normalizeTabooTagsFromApi(t.taboo_tags)
    } else {
      dailyFlavorTags.value = []
      dailyTabooTags.value = []
    }
  } catch {
    /* 未登录或网络失败时忽略 */
  }
}

onShow(() => {
  void hydrateMeContext()
  void loadRecentRecords()
})

function buildPreferences() {
  return {
    taste: flavorTagsToPreferenceTaste(dailyFlavorTags.value),
    avoid: tabooTagsToPreferenceAvoid(dailyTabooTags.value),
  }
}

async function getContextTagsForGenerate(saveDaily: boolean): Promise<string[] | undefined> {
  if (!isLoggedIn.value) return undefined
  if (saveDaily) {
    try {
      const dailyRes = await putMeDailyToday({
        mood: dailyMood.value || null,
        wanted_food_style: dailyWant.value || null,
        body_state: dailyBody.value || null,
        flavor_tags: dailyFlavorTags.value.length ? dailyFlavorTags.value : null,
        taboo_tags: dailyTabooTags.value.length ? dailyTabooTags.value : null,
        period_status: periodFeatureEnabled.value ? dailyPeriod.value || 'none' : 'none',
      })
      return dailyRes.recommendation_context_tags
    } catch (e) {
      console.warn('[today-eat] daily status / context tags skipped:', e)
      return undefined
    }
  }
  try {
    const d = await fetchMeDailyToday()
    return d.recommendation_context_tags
  } catch (e) {
    console.warn('[today-eat] fetch daily context tags skipped:', e)
    return undefined
  }
}

async function runGenerate(saveDaily: boolean) {
  if (generateInFlight.value || phase.value === 'loading') {
    return
  }
  generateInFlight.value = true
  needLogin.value = false
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
  phase.value = 'loading'
  errorMessage.value = ''

  await syncAuthFromSupabase()

  const preferences = buildPreferences()
  const contextTags = await getContextTagsForGenerate(saveDaily)

  try {
    const data = await requestTodayEat({
      preferences,
      locale: 'zh-CN',
      context_tags: contextTags,
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
    await loadRecentRecords()

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
  } finally {
    generateInFlight.value = false
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
    } else if (e.code === BIZ_NEED_LARAVEL_AUTH || e.message === BIZ_NEED_LARAVEL_AUTH) {
      msg.toastSaveFailed('请使用微信一键登录后再试')
    } else if (e.code === BIZ_NOT_CONFIGURED || e.message === BIZ_NOT_CONFIGURED) {
      historyNote.value = '当前环境未启用历史写入配置，已跳过历史记录保存。'
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
    const sid = favoriteContentDigest(result.value.title, result.value.content)
    isFavorited.value = await isFavoriteRecipe({
      source_type: 'today_eat',
      source_id: sid,
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
    const sid = favoriteContentDigest(result.value.title, result.value.content)
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'today_eat',
      source_id: sid,
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

function resetIdle() {
  phase.value = 'idle'
  result.value = null
  errorMessage.value = ''
  needLogin.value = false
  historyNote.value = ''
  favoriteLoading.value = false
  isFavorited.value = false
  statusSheetOpen.value = false
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/today-eat/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}

async function loadRecentRecords() {
  recentLoading.value = true
  try {
    recentRecords.value = await fetchEatMemeRecords(1, 10)
  } catch {
    recentRecords.value = []
  } finally {
    recentLoading.value = false
  }
}

function onReplayRecord(id: number) {
  const row = recentRecords.value.find((x) => x.id === id)
  if (!row || !row.result_content || !row.result_title) return
  result.value = {
    title: row.result_title,
    cuisine: row.result_cuisine ?? undefined,
    ingredients: row.result_ingredients ?? [],
    content: row.result_content,
    history_saved: false,
  }
  phase.value = 'success'
}

async function onDeleteRecord(id: number) {
  try {
    await deleteEatMemeRecord(id)
    recentRecords.value = recentRecords.value.filter((x) => x.id !== id)
    uni.showToast({ title: '已删除', icon: 'success' })
  } catch (e: unknown) {
    const err = e as Error
    msg.toastSaveFailed(err.message || '删除失败')
  }
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

.te__idle-foot {
  display: block;
  margin-top: 20rpx;
  text-align: center;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}

.te__status-hint {
  margin-top: 24rpx;
  padding: 18rpx 22rpx;
  border-radius: 16rpx;
  background: rgba(243, 236, 255, 0.5);
  border: 1rpx solid rgba(122, 87, 209, 0.2);
}

.te__status-hint--muted {
  background: #f5f6f8;
  border-color: $mp-border;
}

.te__status-hint-txt {
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.te__sheet-mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 500;
  background: rgba(17, 24, 39, 0.45);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.te__sheet {
  width: 100%;
  max-height: 82vh;
  border-radius: 28rpx 28rpx 0 0;
  background: #fff;
  box-shadow: 0 -8rpx 40rpx rgba(0, 0, 0, 0.12);
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}

.te__sheet-head {
  padding: 28rpx 28rpx 12rpx;
  border-bottom: 1rpx solid #f3f4f6;
}

.te__sheet-title {
  display: block;
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.te__sheet-sub {
  display: block;
  margin-top: 10rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.te__sheet-scroll {
  flex: 1;
  min-height: 200rpx;
  max-height: 56vh;
  padding: 8rpx 28rpx 16rpx;
  box-sizing: border-box;
}

.te__sheet-block {
  margin-top: 20rpx;
}

.te__sheet-block--last {
  margin-bottom: 12rpx;
}

.te__sheet-k {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: $mp-text-muted;
  margin-bottom: 14rpx;
}

.te__chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 14rpx;
}

.te__chip {
  padding: 14rpx 22rpx;
  border-radius: 999rpx;
  background: #f3f4f6;
  border: 1rpx solid #e5e7eb;
  font-size: 26rpx;
  color: #4b5563;
}

.te__chip--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
  color: $mp-accent;
  font-weight: 700;
}

.te__sheet-multi-hint {
  display: block;
  margin: -6rpx 0 14rpx;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}

.te__sheet-foot {
  display: flex;
  flex-direction: row;
  gap: 16rpx;
  padding: 20rpx 28rpx calc(24rpx + env(safe-area-inset-bottom));
  border-top: 1rpx solid #f3f4f6;
  background: #fff;
}

.te__sheet-foot-btn {
  flex: 1;
  margin: 0;
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

.te__recent {
  margin: 4rpx 28rpx 22rpx;
  padding-top: 16rpx;
  border-top: 1rpx dashed $mp-border;
}

.te__recent-title {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.te__recent-empty {
  margin-top: 10rpx;
  font-size: 24rpx;
  color: $mp-text-muted;
}

.te__recent-list {
  margin-top: 10rpx;
  display: flex;
  flex-direction: column;
  gap: 10rpx;
}

.te__recent-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 14rpx;
  background: #f6f7fb;
}

.te__recent-main {
  display: flex;
  flex-direction: column;
  gap: 4rpx;
  min-width: 0;
  flex: 1;
}

.te__recent-name {
  font-size: 26rpx;
  color: $mp-text-primary;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.te__recent-meta {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.te__recent-del {
  font-size: 24rpx;
  color: #e55151;
}
</style>
