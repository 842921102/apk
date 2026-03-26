<template>
  <view class="mp-page fc has-bottom-nav">
    <view class="mp-hero fc__hero">
      <view class="fc__hero-inner">
        <text class="mp-hero__kicker mp-kicker--on-dark">料理占卜</text>
        <text class="mp-hero__title fc__hero-title">玄学厨房</text>
        <text class="mp-hero__sub fc__hero-sub">
          今日运势、心情、数字与缘分四类占卜，经 BFF 代理生成；与 Web 端类型一致，便于后续扩展。
        </text>
        <view class="fc__hero-rule" />
      </view>
    </view>

    <scroll-view v-if="phase === 'idle'" class="fc__scroll-idle" scroll-y>
      <view class="mp-card fc__panel fc__panel--idle">
        <view class="fc__panel-badge">
          <text class="fc__panel-badge-txt">1 · 选择占卜类型</text>
        </view>
        <view class="fc__type-grid">
          <view
            v-for="t in typeCards"
            :key="t.id"
            class="fc__type-card"
            :class="{ 'fc__type-card--on': selectedType === t.id }"
            @click="selectType(t.id)"
          >
            <text class="fc__type-emoji">{{ t.emoji }}</text>
            <text class="fc__type-name">{{ t.name }}</text>
            <text class="fc__type-desc">{{ t.desc }}</text>
          </view>
        </view>

        <view class="fc__panel-badge fc__panel-badge--step2">
          <text class="fc__panel-badge-txt">2 · 配置参数</text>
        </view>

        <!-- 今日运势 -->
        <view v-if="selectedType === 'daily'" class="fc__block">
          <text class="fc__block-title">星座</text>
          <view class="fc__grid-3">
            <view
              v-for="z in zodiacs"
              :key="z.id"
              class="fc__pick"
              :class="{ 'fc__pick--on': daily.zodiac === z.id }"
              @click="daily.zodiac = z.id"
            >
              <text class="fc__pick-sym">{{ z.symbol }}</text>
              <text class="fc__pick-name">{{ z.name }}</text>
            </view>
          </view>
          <text class="fc__block-title fc__block-title--mt">生肖</text>
          <view class="fc__grid-3">
            <view
              v-for="a in animals"
              :key="a.id"
              class="fc__pick"
              :class="{ 'fc__pick--on': daily.animal === a.id }"
              @click="daily.animal = a.id"
            >
              <text class="fc__pick-sym">{{ a.symbol }}</text>
              <text class="fc__pick-name">{{ a.name }}</text>
            </view>
          </view>
        </view>

        <!-- 心情 -->
        <view v-else-if="selectedType === 'mood'" class="fc__block">
          <text class="fc__block-title">心情（可多选）</text>
          <view class="fc__mood-grid">
            <view
              v-for="m in moods"
              :key="m.id"
              class="fc__mood"
              :class="{ 'fc__mood--on': mood.moods.includes(m.id) }"
              @click="toggleMood(m.id)"
            >
              <text class="fc__mood-emoji">{{ m.emoji }}</text>
              <text class="fc__mood-name">{{ m.name }}</text>
            </view>
          </view>
          <text class="fc__block-title fc__block-title--mt">
            情绪强度：{{ intensityLabels[mood.intensity - 1] }}
          </text>
          <slider
            class="fc__slider"
            :value="mood.intensity"
            min="1"
            max="5"
            step="1"
            show-value
            active-color="#7a57d1"
            background-color="#e5e7eb"
            block-size="20"
            @change="onIntensityChange"
          />
        </view>

        <!-- 数字 -->
        <view v-else-if="selectedType === 'number'" class="fc__block fc__block--center">
          <text class="fc__block-title">幸运数字（1–99）</text>
          <view class="fc__num-row">
            <input v-model.number="numInput" class="fc__num-input" type="number" @blur="clampNumber" />
            <button class="fc__rand-btn" @click="randomNumber">随机</button>
          </view>
        </view>

        <!-- 缘分 -->
        <view v-else class="fc__block">
          <view v-for="slot in coupleSlots" :key="slot.key" class="fc__couple-section">
            <text class="fc__couple-label">{{ slot.label }}</text>
            <text class="fc__sub-label">星座</text>
            <view class="fc__grid-3">
              <view
                v-for="z in zodiacs"
                :key="`${slot.key}-z-${z.id}`"
                class="fc__pick fc__pick--sm"
                :class="{ 'fc__pick--on': slot.user.zodiac === z.id }"
                @click="slot.user.zodiac = z.id"
              >
                <text class="fc__pick-sym">{{ z.symbol }}</text>
                <text class="fc__pick-name">{{ z.name }}</text>
              </view>
            </view>
            <text class="fc__sub-label">生肖</text>
            <view class="fc__grid-3">
              <view
                v-for="a in animals"
                :key="`${slot.key}-a-${a.id}`"
                class="fc__pick fc__pick--sm"
                :class="{ 'fc__pick--on': slot.user.animal === a.id }"
                @click="slot.user.animal = a.id"
              >
                <text class="fc__pick-sym">{{ a.symbol }}</text>
                <text class="fc__pick-name">{{ a.name }}</text>
              </view>
            </view>
            <text class="fc__sub-label">性格（至少选 1 项）</text>
            <view class="fc__pill-grid">
              <view
                v-for="p in personalities"
                :key="`${slot.key}-p-${p.id}`"
                class="fc__pill"
                :class="{ 'fc__pill--on': slot.user.personality.includes(p.id) }"
                @click="togglePersonality(slot.key, p.id)"
              >
                <text class="fc__pill-txt">{{ p.name }}</text>
              </view>
            </view>
          </view>
        </view>

        <view v-if="!canStart" class="fc__warn">
          <text class="fc__warn-txt">{{ blockedHint }}</text>
        </view>

        <button class="mp-btn-primary fc__submit" :disabled="!canStart" @click="onFortune">
          <text class="fc__submit-txt">开始占卜</text>
          <text class="fc__submit-go">✦</text>
        </button>
        <text class="fc__idle-foot">需 BFF 实现 POST /api/ai/fortune；返回字段与 Web 占卜 JSON 一致。</text>
      </view>
    </scroll-view>

    <view v-else-if="phase === 'loading'" class="mp-card fc__panel fc__panel--state fc__panel--loading">
      <view class="fc__state-head">
        <text class="fc__state-kicker">感应中</text>
        <text class="fc__state-title">{{ processingHint }}</text>
      </view>
      <view class="mp-state-icon fc__loading-icon">🔮</view>
      <view class="fc__progress-track">
        <view class="fc__progress-fill" />
      </view>
      <text class="fc__loading-hint">星辰与灶火正在对齐，请稍候…</text>
    </view>

    <view v-else-if="phase === 'error'" class="mp-card fc__panel fc__panel--state fc__panel--state-error">
      <view class="fc__state-head">
        <text class="fc__state-kicker fc__state-kicker--danger">未成功</text>
        <text class="fc__state-title">本次占卜失败</text>
      </view>
      <view class="mp-state-icon mp-state-icon--danger fc__err-icon">!</view>
      <view class="fc__err-box">
        <text class="fc__err-box-label">原因说明</text>
        <text class="fc__err-msg">{{ errorMessage }}</text>
      </view>
      <view class="fc__err-actions">
        <button v-if="needLogin" class="mp-btn-primary fc__stack-btn" @click="goLogin">
          {{ appConfig.common_empty_button_text }}
        </button>
        <button class="mp-btn-ghost fc__stack-btn" @click="resetIdle">返回重新配置</button>
      </view>
    </view>

    <scroll-view v-else-if="phase === 'success' && result" class="fc__scroll" scroll-y>
      <view class="mp-card fc__result">
        <view class="fc__result-head">
          <text class="fc__result-dish">{{ result.dishName }}</text>
          <view class="fc__result-meta">
            <text class="fc__meta-chip">{{ fortuneTypeLabel(result.type) }}</text>
            <text class="fc__meta-chip">⏱ {{ result.cookingTime }} 分钟</text>
            <text class="fc__meta-chip fc__meta-chip--diff">{{ difficultyLabel(result.difficulty) }}</text>
          </view>
          <view class="fc__lucky-row">
            <text class="fc__lucky-label">幸运指数</text>
            <view class="fc__stars">
              <text
                v-for="i in 10"
                :key="i"
                class="fc__star"
                :class="{ 'fc__star--on': i <= result.luckyIndex }"
              >
                ★
              </text>
            </view>
            <text class="fc__lucky-num">{{ result.luckyIndex }}/10</text>
          </view>
        </view>

        <view class="fc__sheet fc__sheet--purple">
          <text class="fc__sheet-k">占卜理由</text>
          <text class="fc__sheet-body">{{ result.reason }}</text>
        </view>
        <view class="fc__sheet fc__sheet--pink">
          <text class="fc__sheet-k">神秘解析</text>
          <text class="fc__sheet-body">{{ result.description }}</text>
        </view>

        <view v-if="result.ingredients.length" class="fc__section">
          <text class="fc__section-k">神秘食材</text>
          <view v-for="(ing, idx) in result.ingredients" :key="idx" class="fc__ing-row">
            <text class="fc__ing-dot" />
            <text class="fc__ing-txt">{{ ing }}</text>
          </view>
        </view>

        <view v-if="result.steps.length" class="fc__section">
          <text class="fc__section-k">制作步骤</text>
          <view v-for="(step, idx) in result.steps" :key="idx" class="fc__step-row">
            <text class="fc__step-num">{{ idx + 1 }}</text>
            <text class="fc__step-txt">{{ step }}</text>
          </view>
        </view>

        <view v-if="result.tips.length" class="fc__section">
          <text class="fc__section-k">神秘建议</text>
          <view v-for="(tip, idx) in result.tips" :key="idx" class="fc__tip-row">
            <text class="fc__tip-ico">💫</text>
            <text class="fc__tip-txt">{{ tip }}</text>
          </view>
        </view>

        <view class="fc__mystic">
          <text class="fc__mystic-k">占卜师的话</text>
          <text class="fc__mystic-body">{{ result.mysticalMessage }}</text>
        </view>

        <view v-if="historyNote" class="fc__history-banner">
          <text class="fc__history-note">{{ historyNote }}</text>
        </view>

        <view class="fc__fav-row">
          <button class="mp-btn-ghost fc__fav-btn" :disabled="favoriteLoading" @click="onToggleFavorite">
            <text>{{ isFavorited ? '取消收藏' : '加入收藏' }}</text>
          </button>
        </view>
      </view>

      <button class="mp-btn-primary fc__again" @click="resetIdle">
        <text class="fc__again-txt">再卜一次</text>
        <text class="fc__again-go">→</text>
      </button>
    </scroll-view>

    <MpIcoTabBar />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import MpIcoTabBar from '@/components/MpIcoTabBar.vue'
import { requestFortune } from '@/api/fortune'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import { insertRecipeHistoryFromTodayEat, isFavoriteRecipe, toggleFavoriteRecipe, BIZ_UNAUTHORIZED } from '@/api/biz'
import type { FortuneType, FortuneResult, FortuneRequestBody } from '@/types/fortune'
import {
  FORTUNE_TYPE_CARDS,
  FORTUNE_ZODIACS,
  FORTUNE_ANIMALS,
  FORTUNE_MOODS,
  FORTUNE_PERSONALITY,
  FORTUNE_INTENSITY_LABELS,
  fortuneTypeLabel,
  difficultyLabel,
} from '@/constants/fortuneCooking'

type Phase = 'idle' | 'loading' | 'success' | 'error'

const { config: appConfig } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()

const typeCards = FORTUNE_TYPE_CARDS
const zodiacs = FORTUNE_ZODIACS
const animals = FORTUNE_ANIMALS
const moods = FORTUNE_MOODS
const personalities = FORTUNE_PERSONALITY
const intensityLabels = FORTUNE_INTENSITY_LABELS

const selectedType = ref<FortuneType>('daily')
const phase = ref<Phase>('idle')
const result = ref<FortuneResult | null>(null)
const errorMessage = ref('')
const needLogin = ref(false)
const historyNote = ref('')
const processingHint = ref('正在解读星象…')
const numInput = ref(7)
const favoriteLoading = ref(false)
const isFavorited = ref(false)

const daily = reactive({
  zodiac: '',
  animal: '',
  date: '',
})

const mood = reactive({
  moods: [] as string[],
  intensity: 3,
})

const numberState = reactive({
  number: 7,
  is_random: false,
})

const couple = reactive({
  user1: { zodiac: '', animal: '', personality: [] as string[] },
  user2: { zodiac: '', animal: '', personality: [] as string[] },
})

function todayISO(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

daily.date = todayISO()
numInput.value = numberState.number

const coupleSlots = computed(() => [
  { key: 'user1' as const, label: '甲方', user: couple.user1 },
  { key: 'user2' as const, label: '乙方', user: couple.user2 },
])

const canStart = computed(() => {
  switch (selectedType.value) {
    case 'daily':
      return Boolean(daily.zodiac && daily.animal)
    case 'mood':
      return mood.moods.length > 0
    case 'number': {
      const n = numberState.number
      return n >= 1 && n <= 99
    }
    case 'couple':
      return (
        couple.user1.zodiac &&
        couple.user1.animal &&
        couple.user1.personality.length > 0 &&
        couple.user2.zodiac &&
        couple.user2.animal &&
        couple.user2.personality.length > 0
      )
    default:
      return false
  }
})

const blockedHint = computed(() => {
  switch (selectedType.value) {
    case 'daily':
      return '请选择星座与生肖。'
    case 'mood':
      return '请至少选择一种心情。'
    case 'number':
      return '请输入 1–99 之间的数字。'
    case 'couple':
      return '缘分配菜需双方均选择星座、生肖，并各选至少一种性格。'
    default:
      return ''
  }
})

onShow(() => {
  void syncAuthFromSupabase()
  if (!daily.date) daily.date = todayISO()
})

function selectType(t: FortuneType) {
  selectedType.value = t
}

function toggleMood(id: string) {
  const i = mood.moods.indexOf(id)
  if (i >= 0) mood.moods.splice(i, 1)
  else mood.moods.push(id)
}

function onIntensityChange(e: { detail?: { value?: number } }) {
  const v = e.detail?.value
  if (typeof v === 'number' && v >= 1 && v <= 5) mood.intensity = v
}

function clampNumber() {
  let n = Number(numInput.value)
  if (!Number.isFinite(n)) n = 1
  if (n < 1) n = 1
  if (n > 99) n = 99
  numberState.number = n
  numInput.value = n
}

function randomNumber() {
  numberState.number = Math.floor(Math.random() * 99) + 1
  numberState.is_random = true
  numInput.value = numberState.number
}

function togglePersonality(who: 'user1' | 'user2', id: string) {
  const u = couple[who].personality
  const i = u.indexOf(id)
  if (i >= 0) u.splice(i, 1)
  else u.push(id)
}

const PROCESSING_HINTS: Record<FortuneType, string[]> = {
  daily: ['正在解读星座的秘密…', '生肖的智慧正在显现…', '感应今日宇宙能量…'],
  mood: ['正在抚平情绪潮汐…', '寻找治愈味蕾的配方…'],
  number: ['正在演算数字灵韵…', '解析幸运数与灶火…'],
  couple: ['推演双人星盘默契…', '缘分红线与食材交汇…'],
}

function pickProcessingHint() {
  const list = PROCESSING_HINTS[selectedType.value]
  processingHint.value = list[Math.floor(Math.random() * list.length)]
}

function buildRequest(): FortuneRequestBody {
  const locale = 'zh-CN'
  switch (selectedType.value) {
    case 'daily':
      return {
        fortune_type: 'daily',
        locale,
        daily: {
          zodiac: daily.zodiac,
          animal: daily.animal,
          date: daily.date || todayISO(),
        },
      }
    case 'mood':
      return {
        fortune_type: 'mood',
        locale,
        mood: {
          moods: [...mood.moods],
          intensity: mood.intensity,
        },
      }
    case 'number':
      clampNumber()
      return {
        fortune_type: 'number',
        locale,
        number: {
          number: numberState.number,
          is_random: numberState.is_random,
        },
      }
    case 'couple': {
      const toNames = (ids: string[]) =>
        ids.map((id) => personalities.find((p) => p.id === id)?.name ?? id)
      return {
        fortune_type: 'couple',
        locale,
        couple: {
          user1: {
            zodiac: couple.user1.zodiac,
            animal: couple.user1.animal,
            personality: toNames(couple.user1.personality),
          },
          user2: {
            zodiac: couple.user2.zodiac,
            animal: couple.user2.animal,
            personality: toNames(couple.user2.personality),
          },
        },
      }
    }
  }
}

function formatFortuneContent(f: FortuneResult): string {
  const parts = [
    `【${fortuneTypeLabel(f.type)}】`,
    f.reason,
    f.description,
    f.mysticalMessage,
    f.tips.length ? `建议：${f.tips.join('；')}` : '',
    f.ingredients.length ? `食材：${f.ingredients.join('、')}` : '',
    f.steps.length ? `步骤：\n${f.steps.map((s, i) => `${i + 1}. ${s}`).join('\n')}` : '',
  ]
  return parts.filter(Boolean).join('\n\n')
}

async function maybeSaveHistory(data: { history_saved?: boolean }, f: FortuneResult, req: FortuneRequestBody) {
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
      title: f.dishName,
      cuisine: fortuneTypeLabel(f.type),
      ingredients: f.ingredients,
      response_content: formatFortuneContent(f),
      request_payload: { ...req, source: 'mp-fortune-cooking' } as unknown as Record<string, unknown>,
    })
    msg.toastSaveSuccess()
  } catch (err: unknown) {
    const e = err as Error & { code?: string }
    if (e.code === BIZ_UNAUTHORIZED || e.message === BIZ_UNAUTHORIZED) {
      msg.toastSaveFailed('登录已过期')
    } else {
      msg.toastSaveFailed(e.message)
      console.error('[fortune-cooking] history insert failed:', err)
    }
  }
}

function mapHttpError(e: unknown): string {
  if (e instanceof HttpError) {
    if (e.statusCode === 401) return '__NEED_LOGIN__'
    if (e.statusCode === 404) {
      return '玄学厨房接口未部署。请在 BFF 实现 POST /api/ai/fortune 后重试。'
    }
    return e.message || `请求失败 (${e.statusCode})`
  }
  if (e instanceof Error) return e.message || '请求失败'
  return '请求失败'
}

async function syncFavoriteState() {
  if (!result.value) return
  if (!isLoggedIn.value) {
    isFavorited.value = false
    return
  }

  try {
    const recipeContent = formatFortuneContent(result.value)
    isFavorited.value = await isFavoriteRecipe({
      title: result.value.dishName,
      recipe_content: recipeContent,
    })
  } catch {
    isFavorited.value = false
  }
}

async function onToggleFavorite() {
  if (!result.value) return
  if (!isLoggedIn.value) {
    goLogin()
    return
  }

  if (favoriteLoading.value) return
  favoriteLoading.value = true

  try {
    const recipeContent = formatFortuneContent(result.value)
    const { favorited } = await toggleFavoriteRecipe({
      title: result.value.dishName,
      cuisine: fortuneTypeLabel(result.value.type),
      ingredients: result.value.ingredients ?? [],
      recipe_content: recipeContent,
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

async function onFortune() {
  if (!canStart.value) return
  needLogin.value = false
  historyNote.value = ''
  errorMessage.value = ''
  await syncAuthFromSupabase()
  pickProcessingHint()
  phase.value = 'loading'
  const req = buildRequest()
  try {
    const { result: data, history_saved } = await requestFortune(req)
    if (!data.dishName.trim()) {
      throw new Error('接口返回异常，缺少菜品名称')
    }
    result.value = data
    await maybeSaveHistory({ history_saved }, data, req)
    await syncFavoriteState()
    phase.value = 'success'
  } catch (e: unknown) {
    const mapped = mapHttpError(e)
    if (mapped === '__NEED_LOGIN__') {
      needLogin.value = true
      errorMessage.value = '需要登录后才能占卜，或登录已过期'
    } else {
      errorMessage.value = mapped
    }
    result.value = null
    phase.value = 'error'
  }
}

function resetIdle() {
  phase.value = 'idle'
  result.value = null
  errorMessage.value = ''
  needLogin.value = false
  historyNote.value = ''
  numberState.is_random = false
  favoriteLoading.value = false
  isFavorited.value = false
}

function goLogin() {
  const redirect = encodeURIComponent('/pages/fortune-cooking/index')
  uni.navigateTo({ url: `/pages/login/index?redirect=${redirect}` })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.has-bottom-nav {
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.fc__hero-inner {
  text-align: center;
}

.fc__hero-title {
  max-width: 640rpx;
  margin-left: auto;
  margin-right: auto;
}

.fc__hero-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

.fc__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

.fc__scroll-idle {
  max-height: calc(100vh - 200rpx);
  padding-bottom: 48rpx;
}

.fc__panel--idle {
  position: relative;
  padding-top: 36rpx;
  border-color: $mp-ring-accent;
  box-shadow: 0 12rpx 40rpx rgba(122, 87, 209, 0.12);
  overflow: hidden;
  margin-bottom: 32rpx;
}

.fc__panel--idle::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.fc__panel-badge {
  align-self: flex-start;
  margin-bottom: 16rpx;
}

.fc__panel-badge--step2 {
  margin-top: 36rpx;
}

.fc__panel-badge-txt {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: $mp-accent;
}

.fc__type-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16rpx;
}

.fc__type-card {
  width: calc(50% - 8rpx);
  box-sizing: border-box;
  padding: 24rpx 16rpx;
  border-radius: 20rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.fc__type-card--on {
  background: linear-gradient(145deg, #8b6fd8 0%, #7a57d1 55%, #6743bf 100%);
  border-color: transparent;
}

.fc__type-card--on .fc__type-name,
.fc__type-card--on .fc__type-desc {
  color: #fff;
}

.fc__type-emoji {
  display: block;
  font-size: 40rpx;
  text-align: center;
  margin-bottom: 8rpx;
}

.fc__type-name {
  display: block;
  text-align: center;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.fc__type-desc {
  display: block;
  text-align: center;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-secondary;
  line-height: 1.35;
}

.fc__block {
  padding: 24rpx;
  border-radius: 20rpx;
  background: #f5f6f8;
  border: 1rpx solid $mp-border;
}

.fc__block--center {
  text-align: center;
}

.fc__block-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: #374151;
  margin-bottom: 16rpx;
}

.fc__block-title--mt {
  margin-top: 28rpx;
}

.fc__grid-3 {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12rpx;
}

.fc__pick {
  width: calc(33.33% - 8rpx);
  box-sizing: border-box;
  padding: 16rpx 8rpx;
  border-radius: 14rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.fc__pick--sm {
  padding: 12rpx 6rpx;
}

.fc__pick--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.fc__pick-sym {
  font-size: 32rpx;
  line-height: 1.2;
}

.fc__pick-name {
  font-size: 22rpx;
  font-weight: 600;
  color: $mp-text-primary;
  margin-top: 4rpx;
  text-align: center;
}

.fc__mood-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12rpx;
}

.fc__mood {
  width: calc(25% - 9rpx);
  box-sizing: border-box;
  padding: 16rpx 8rpx;
  border-radius: 14rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.fc__mood--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.fc__mood-emoji {
  font-size: 36rpx;
}

.fc__mood-name {
  font-size: 22rpx;
  font-weight: 600;
  margin-top: 6rpx;
  color: $mp-text-primary;
}

.fc__slider {
  margin: 16rpx 0 8rpx;
}

.fc__num-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 20rpx;
  margin-top: 16rpx;
}

.fc__num-input {
  width: 160rpx;
  text-align: center;
  font-size: 40rpx;
  font-weight: 800;
  padding: 20rpx;
  border-radius: 16rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.fc__rand-btn {
  padding: 0 32rpx;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #9575e8, #7a57d1);
  border-radius: 16rpx;
  border: none;
}

.fc__couple-section {
  margin-bottom: 32rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx dashed $mp-border;
}

.fc__couple-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.fc__couple-label {
  display: block;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-accent-deep;
  margin-bottom: 16rpx;
}

.fc__sub-label {
  display: block;
  font-size: 24rpx;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 10rpx;
  margin-top: 16rpx;
}

.fc__pill-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 10rpx;
}

.fc__pill {
  padding: 12rpx 20rpx;
  border-radius: 999rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.fc__pill--on {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

.fc__pill-txt {
  font-size: 24rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.fc__warn {
  margin-top: 20rpx;
  padding: 20rpx;
  border-radius: 14rpx;
  background: #fff8f1;
  border: 1rpx solid #f6d9b4;
}

.fc__warn-txt {
  font-size: 24rpx;
  color: #b45309;
  line-height: 1.45;
}

.fc__submit {
  margin-top: 28rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
}

.fc__submit-txt {
  font-size: 30rpx;
  font-weight: 800;
}

.fc__submit-go {
  font-size: 28rpx;
}

.fc__idle-foot {
  display: block;
  margin-top: 20rpx;
  text-align: center;
  font-size: 22rpx;
  color: $mp-text-muted;
  line-height: 1.45;
}

.fc__panel--state {
  padding: 48rpx 32rpx;
  text-align: center;
}

.fc__state-head {
  margin-bottom: 24rpx;
}

.fc__state-kicker {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: $mp-accent;
  text-transform: uppercase;
}

.fc__state-kicker--danger {
  color: #dc2626;
}

.fc__state-title {
  display: block;
  margin-top: 8rpx;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.4;
}

.fc__loading-icon {
  font-size: 72rpx;
  margin: 24rpx 0;
}

.fc__progress-track {
  height: 8rpx;
  background: #e5e7eb;
  border-radius: 999rpx;
  overflow: hidden;
  margin: 0 48rpx 24rpx;
}

.fc__progress-fill {
  height: 100%;
  width: 40%;
  background: linear-gradient(90deg, #9575e8, #7a57d1);
  border-radius: 999rpx;
  animation: fc-pulse 1.2s ease-in-out infinite;
}

@keyframes fc-pulse {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(350%);
  }
}

.fc__loading-hint {
  font-size: 26rpx;
  color: $mp-text-secondary;
}

.fc__err-icon {
  font-size: 56rpx;
  font-weight: 900;
  margin: 16rpx 0;
}

.fc__err-box {
  text-align: left;
  margin: 24rpx 0;
  padding: 24rpx;
  border-radius: 16rpx;
  background: #fef2f2;
  border: 1rpx solid #fecaca;
}

.fc__err-box-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: #991b1b;
  margin-bottom: 8rpx;
}

.fc__err-msg {
  font-size: 26rpx;
  color: #7f1d1d;
  line-height: 1.5;
}

.fc__err-actions {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.fc__stack-btn {
  margin: 0;
}

.fc__scroll {
  max-height: calc(100vh - 120rpx);
  padding-bottom: 48rpx;
}

.fc__result {
  margin-bottom: 24rpx;
  overflow: hidden;
}

.fc__result-head {
  padding: 32rpx 28rpx;
  background: linear-gradient(120deg, #7a57d1 0%, #c084fc 50%, #e879f9 100%);
}

.fc__result-dish {
  display: block;
  font-size: 38rpx;
  font-weight: 900;
  color: #fff;
  line-height: 1.25;
}

.fc__result-meta {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 16rpx;
}

.fc__meta-chip {
  font-size: 22rpx;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.95);
  padding: 6rpx 14rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.2);
  border: 1rpx solid rgba(255, 255, 255, 0.35);
}

.fc__meta-chip--diff {
  background: rgba(255, 255, 255, 0.28);
}

.fc__lucky-row {
  margin-top: 24rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 12rpx;
}

.fc__lucky-label {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 600;
}

.fc__stars {
  display: flex;
  flex-direction: row;
  gap: 2rpx;
}

.fc__star {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.25);
}

.fc__star--on {
  color: #fde047;
}

.fc__lucky-num {
  font-size: 28rpx;
  font-weight: 800;
  color: #fef08a;
}

.fc__sheet {
  margin: 24rpx 28rpx 0;
  padding: 24rpx;
  border-radius: 16rpx;
}

.fc__sheet--purple {
  background: #f5f3ff;
  border: 1rpx solid #ddd6fe;
}

.fc__sheet--pink {
  background: #fdf2f8;
  border: 1rpx solid #fbcfe8;
}

.fc__sheet-k {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 12rpx;
}

.fc__sheet-body {
  font-size: 26rpx;
  color: #374151;
  line-height: 1.55;
}

.fc__section {
  margin: 28rpx 28rpx 0;
}

.fc__section-k {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 12rpx;
}

.fc__ing-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
  padding: 14rpx 16rpx;
  margin-bottom: 8rpx;
  background: #f9fafb;
  border-radius: 12rpx;
}

.fc__ing-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 999rpx;
  background: $mp-accent;
}

.fc__ing-txt {
  font-size: 26rpx;
  color: #374151;
}

.fc__step-row {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
  padding: 20rpx;
  margin-bottom: 12rpx;
  background: #f9fafb;
  border-radius: 12rpx;
  border-left: 6rpx solid $mp-accent;
}

.fc__step-num {
  width: 44rpx;
  height: 44rpx;
  line-height: 44rpx;
  text-align: center;
  border-radius: 999rpx;
  background: $mp-accent;
  color: #fff;
  font-size: 24rpx;
  font-weight: 800;
  flex-shrink: 0;
}

.fc__step-txt {
  flex: 1;
  font-size: 26rpx;
  color: #1f2937;
  line-height: 1.5;
}

.fc__tip-row {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 10rpx;
  padding: 16rpx;
  margin-bottom: 8rpx;
  background: #fffbeb;
  border-radius: 12rpx;
  border: 1rpx solid #fde68a;
}

.fc__tip-ico {
  font-size: 28rpx;
}

.fc__tip-txt {
  flex: 1;
  font-size: 26rpx;
  color: #78350f;
  line-height: 1.45;
}

.fc__mystic {
  margin: 28rpx 28rpx 32rpx;
  padding: 28rpx;
  border-radius: 16rpx;
  background: linear-gradient(135deg, #f3ecff 0%, #fce7f3 100%);
  border: 1rpx solid $mp-ring-accent;
}

.fc__mystic-k {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-accent-deep;
  margin-bottom: 12rpx;
}

.fc__mystic-body {
  font-size: 26rpx;
  color: #4b5563;
  line-height: 1.55;
  font-style: italic;
  text-align: center;
}

.fc__history-banner {
  margin: 0 28rpx 24rpx;
  padding: 16rpx 20rpx;
  border-radius: 12rpx;
  background: #f0fdf4;
  border: 1rpx solid #bbf7d0;
}

.fc__history-note {
  font-size: 24rpx;
  color: #166534;
  line-height: 1.45;
}

.fc__fav-row {
  margin: 0 28rpx 20rpx;
}

.fc__fav-btn {
  width: 100%;
}

.fc__again {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
}

.fc__again-txt {
  font-size: 30rpx;
  font-weight: 800;
}

.fc__again-go {
  font-size: 32rpx;
}
</style>
