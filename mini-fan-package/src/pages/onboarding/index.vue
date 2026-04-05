<template>
  <view class="ob">
    <StepHeader
      :status-bar-px="statusBarPx"
      :nav-bar-px="navBarPx"
      :progress-current="headerProgressCurrent"
      :progress-total="TOTAL_STEPS"
      @back="onBack"
      @skip="onSkip"
    />

    <scroll-view
      scroll-y
      class="ob__scroll"
      :style="{ height: `${scrollHeightPx}px` }"
      :show-scrollbar="false"
    >
      <view v-if="screen === 'welcome'" class="ob__body">
        <view class="mp-card ob__hero-card">
          <text class="ob__hero-title">先了解你一点，后面的推荐会更懂你</text>
          <text class="ob__hero-sub">
            这不是普通问卷，而是你的饮食画像采集。填写后，系统会结合你的口味、健康目标和生活方式，给你更有理由的菜品推荐与食命文案。
          </text>
        </view>

        <text class="ob__section-k">你能获得</text>
        <view class="mp-card ob__value-card">
          <view v-for="(row, i) in valueRows" :key="i" class="ob__value-row">
            <text class="ob__value-dot">·</text>
            <text class="ob__value-txt">{{ row }}</text>
          </view>
        </view>

        <button class="mp-btn-primary ob__cta" @click="goFlavor">
          <text>开始设置</text>
        </button>
        <text class="ob__cta-hint">约 1～2 分钟完成，可稍后再补充</text>
      </view>

      <view v-else-if="screen === 'flavor'" class="ob__body ob__body--step">
        <FlavorStep v-model="flavorPreferences" />
        <view class="ob__scroll-pad" />
      </view>

      <view v-else-if="screen === 'taboo'" class="ob__body ob__body--step">
        <TabooStep
          :dislike-ingredients="dislikeIngredients"
          :allergy-ingredients="allergyIngredients"
          @update:dislike-ingredients="dislikeIngredients = $event"
          @update:allergy-ingredients="allergyIngredients = $event"
        />
        <view class="ob__scroll-pad" />
      </view>

      <view v-else-if="screen === 'goals'" class="ob__body ob__body--step">
        <DietGoalsStep v-model="dietGoals" />
        <view class="ob__scroll-pad" />
      </view>

      <view v-else-if="screen === 'vitals'" class="ob__body ob__body--step">
        <VitalsStep
          :height-cm="heightCm"
          :weight-kg="weightKg"
          :target-weight-kg="targetWeightKg"
          @update:height-cm="heightCm = $event"
          @update:weight-kg="weightKg = $event"
          @update:target-weight-kg="targetWeightKg = $event"
        />
        <view class="ob__scroll-pad" />
      </view>

      <view v-else-if="screen === 'lifestyle'" class="ob__body ob__body--step">
        <LifestyleStep
          :cooking-frequency="cookingFrequency"
          :lifestyle-tags="lifestyleTags"
          :family-size="familySize"
          @update:cooking-frequency="cookingFrequency = $event"
          @update:lifestyle-tags="lifestyleTags = $event"
          @update:family-size="familySize = $event"
        />
        <view class="ob__scroll-pad" />
      </view>

      <view v-else-if="screen === 'recommend_settings'" class="ob__body ob__body--step">
        <RecommendSettingsStep
          :destiny-mode-enabled="destinyModeEnabled"
          :period-feature-enabled="periodFeatureEnabled"
          :accepts-product-recommendation="acceptsProductRecommendation"
          :birthday="recoBirthday"
          :gender="recoGender"
          @update:destiny-mode-enabled="destinyModeEnabled = $event"
          @update:period-feature-enabled="periodFeatureEnabled = $event"
          @update:accepts-product-recommendation="acceptsProductRecommendation = $event"
          @update:birthday="recoBirthday = $event"
          @update:gender="recoGender = $event"
        />
        <view class="ob__scroll-pad" />
      </view>

      <view v-else-if="screen === 'summary'" class="ob__body ob__body--step">
        <ProfileSummaryStep :payload="summaryPayload" />
      </view>
    </scroll-view>

    <view
      v-if="showSummaryFooter"
      class="ob__summary-foot"
      :style="{ paddingBottom: `calc(24rpx + ${safeBottomPx}px)` }"
    >
      <button
        class="mp-btn-primary ob__summary-primary"
        :loading="submittingProfile"
        :disabled="submittingProfile"
        @click="onCompleteOnboarding"
      >
        <text>开始体验推荐</text>
      </button>
      <button class="mp-btn-secondary ob__summary-secondary" @click="onSummaryEditBack">
        <text>返回修改</text>
      </button>
    </view>

    <BottomActionBar v-if="hasBottomBar" :safe-bottom="safeBottomPx">
      <template #left>
        <button class="mp-btn-ghost" @click="onStepPrev">上一步</button>
      </template>
      <template #right>
        <button class="mp-btn-primary" @click="onStepNext">下一步</button>
      </template>
    </BottomActionBar>
  </view>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import StepHeader from '@/components/onboarding/StepHeader.vue'
import BottomActionBar from '@/components/onboarding/BottomActionBar.vue'
import FlavorStep from './steps/flavor-step.vue'
import TabooStep from './steps/taboo-step.vue'
import DietGoalsStep from './steps/diet-goals-step.vue'
import VitalsStep from './steps/vitals-step.vue'
import LifestyleStep from './steps/lifestyle-step.vue'
import RecommendSettingsStep from './steps/recommend-settings-step.vue'
import ProfileSummaryStep from './steps/profile-summary-step.vue'
import {
  readOnboardingDraft,
  writeOnboardingDraft,
  type OnboardingGender,
  type OnboardingScreenKey,
} from '@/composables/useOnboardingDraft'
import { CURRENT_ONBOARDING_VERSION } from '@/constants/onboarding'
import {
  migrateOnboardingDraftIfNeeded,
  markLocalOnboardingCompleted,
  markLocalOnboardingSkipped,
} from '@/composables/useOnboardingFlow'
import { patchCurrentUser } from '@/composables/useAuth'
import { postUserProfileOnboarding } from '@/api/me'
import { HttpError } from '@/api/http'
import type { OnboardingProfileSubmitPayload } from '@/types/profile'

const TOTAL_STEPS = 8

const redirectRaw = ref('/pages/today-eat/index')
const statusBarPx = ref(20)
const navBarPx = ref(44)
const safeBottomPx = ref(0)
const windowHeightPx = ref(667)
const windowWidthPx = ref(375)
const headerBottomPx = ref(88)
const scrollHeightPx = ref(500)

const screen = ref<OnboardingScreenKey>('welcome')
const flavorPreferences = ref<string[]>([])
const dislikeIngredients = ref<string[]>([])
const allergyIngredients = ref<string[]>([])
const dietGoals = ref<string[]>([])
const heightCm = ref<number | null>(null)
const weightKg = ref<number | null>(null)
const targetWeightKg = ref<number | null>(null)
const cookingFrequency = ref('')
const lifestyleTags = ref<string[]>([])
const familySize = ref('')
const destinyModeEnabled = ref<boolean | null>(null)
const periodFeatureEnabled = ref<boolean | null>(null)
const acceptsProductRecommendation = ref<boolean | null>(null)
const recoBirthday = ref('')
const recoGender = ref<OnboardingGender>('unknown')
const onboardingCompleted = ref(false)
const onboardingSkipped = ref(false)
const submittingProfile = ref(false)

const headerProgressCurrent = computed(() => {
  const m: Record<OnboardingScreenKey, number> = {
    welcome: 1,
    flavor: 2,
    taboo: 3,
    goals: 4,
    vitals: 5,
    lifestyle: 6,
    recommend_settings: 7,
    summary: 8,
  }
  return m[screen.value]
})

const hasBottomBar = computed(() => screen.value !== 'welcome' && screen.value !== 'summary')

const showSummaryFooter = computed(() => screen.value === 'summary')

const summaryPayload = computed(() => ({
  flavorPreferences: flavorPreferences.value,
  dislikeIngredients: dislikeIngredients.value,
  allergyIngredients: allergyIngredients.value,
  dietGoals: dietGoals.value,
  heightCm: heightCm.value,
  weightKg: weightKg.value,
  targetWeightKg: targetWeightKg.value,
  cookingFrequency: cookingFrequency.value,
  lifestyleTags: lifestyleTags.value,
  familySize: familySize.value,
  destinyModeEnabled: destinyModeEnabled.value,
  periodFeatureEnabled: periodFeatureEnabled.value,
  acceptsProductRecommendation: acceptsProductRecommendation.value,
  birthday: recoBirthday.value.trim(),
  gender: recoGender.value,
}))

const valueRows = [
  '推荐更贴口味：不是随机给菜，而是优先推荐你更容易接受的方向',
  '后续可做健康饮食服务：例如减脂、养胃、轻负担、高蛋白等建议',
  '让今天的推荐更有意义：天气、节日、生日、状态都能成为推荐依据',
]

const tabPaths = [
  '/pages/today-eat/index',
  '/pages/index/index',
  '/pages/plaza/index',
  '/pages/inspiration/index',
  '/pages/me/index',
]

function rpxToPx(rpx: number) {
  return (rpx / 750) * windowWidthPx.value
}

function measureScroll() {
  const wh = windowHeightPx.value
  const top = headerBottomPx.value
  let bottomReserved = safeBottomPx.value
  if (hasBottomBar.value) bottomReserved += rpxToPx(168)
  else if (showSummaryFooter.value) bottomReserved += rpxToPx(248)
  scrollHeightPx.value = Math.max(200, wh - top - bottomReserved)
}

function initLayout() {
  try {
    const sys = uni.getSystemInfoSync()
    statusBarPx.value = Number(sys.statusBarHeight) || 20
    windowHeightPx.value = Number(sys.windowHeight) || 667
    windowWidthPx.value = Number(sys.windowWidth) || 375
    const si = sys.safeAreaInsets
    safeBottomPx.value = si && typeof si.bottom === 'number' ? si.bottom : 0
    let navH = 44
    // #ifdef MP-WEIXIN
    try {
      const mb = uni.getMenuButtonBoundingClientRect()
      if (mb && typeof mb.top === 'number' && mb.height) {
        navH = mb.height + (mb.top - statusBarPx.value) * 2
      }
    } catch {
      /* ignore */
    }
    // #endif
    navBarPx.value = navH
  } catch {
    statusBarPx.value = 20
    navBarPx.value = 44
  }
  headerBottomPx.value = statusBarPx.value + navBarPx.value
  nextTick(() => measureScroll())
}

function persistDraft() {
  writeOnboardingDraft({
    screen: screen.value,
    onboarding_completed: onboardingCompleted.value,
    onboarding_skipped: onboardingSkipped.value,
    onboarding_version: CURRENT_ONBOARDING_VERSION,
    flavorPreferences: flavorPreferences.value,
    dislike_ingredients: dislikeIngredients.value,
    allergy_ingredients: allergyIngredients.value,
    diet_goals: dietGoals.value,
    height_cm: heightCm.value,
    weight_kg: weightKg.value,
    target_weight_kg: targetWeightKg.value,
    cooking_frequency: cookingFrequency.value,
    lifestyle_tags: lifestyleTags.value,
    family_size: familySize.value,
    destiny_mode_enabled: destinyModeEnabled.value,
    period_feature_enabled: periodFeatureEnabled.value,
    accepts_product_recommendation: acceptsProductRecommendation.value,
    birthday: recoBirthday.value,
    gender: recoGender.value,
  })
}

watch(
  [
    screen,
    onboardingCompleted,
    onboardingSkipped,
    flavorPreferences,
    dislikeIngredients,
    allergyIngredients,
    dietGoals,
    heightCm,
    weightKg,
    targetWeightKg,
    cookingFrequency,
    lifestyleTags,
    familySize,
    destinyModeEnabled,
    periodFeatureEnabled,
    acceptsProductRecommendation,
    recoBirthday,
    recoGender,
  ],
  () => persistDraft(),
  { deep: true },
)

watch([screen, hasBottomBar, showSummaryFooter], () => nextTick(() => measureScroll()))

onLoad((options) => {
  migrateOnboardingDraftIfNeeded()
  if (options?.redirect) {
    try {
      redirectRaw.value = decodeURIComponent(String(options.redirect))
    } catch {
      redirectRaw.value = '/pages/today-eat/index'
    }
  }
  const d = readOnboardingDraft()
  if (d.onboarding_completed === true) {
    finishToRedirect()
    return
  }
  screen.value = d.screen
  onboardingCompleted.value = d.onboarding_completed
  onboardingSkipped.value = d.onboarding_skipped
  flavorPreferences.value = [...d.flavorPreferences]
  dislikeIngredients.value = [...d.dislike_ingredients]
  allergyIngredients.value = [...d.allergy_ingredients]
  dietGoals.value = [...d.diet_goals]
  heightCm.value = d.height_cm
  weightKg.value = d.weight_kg
  targetWeightKg.value = d.target_weight_kg
  cookingFrequency.value = d.cooking_frequency
  lifestyleTags.value = [...d.lifestyle_tags]
  familySize.value = d.family_size
  destinyModeEnabled.value = d.destiny_mode_enabled
  periodFeatureEnabled.value = d.period_feature_enabled
  acceptsProductRecommendation.value = d.accepts_product_recommendation
  recoBirthday.value = d.birthday
  recoGender.value = d.gender
})

onMounted(() => {
  initLayout()
})

function finishToRedirect() {
  const target = redirectRaw.value.trim() || '/pages/today-eat/index'
  if (tabPaths.includes(target)) {
    uni.switchTab({ url: target })
    return
  }
  uni.redirectTo({ url: target })
}

function onBack() {
  if (screen.value === 'summary') {
    screen.value = 'recommend_settings'
    return
  }
  if (screen.value === 'recommend_settings') {
    screen.value = 'lifestyle'
    return
  }
  if (screen.value === 'lifestyle') {
    screen.value = 'vitals'
    return
  }
  if (screen.value === 'vitals') {
    screen.value = 'goals'
    return
  }
  if (screen.value === 'goals') {
    screen.value = 'taboo'
    return
  }
  if (screen.value === 'taboo') {
    screen.value = 'flavor'
    return
  }
  if (screen.value === 'flavor') {
    screen.value = 'welcome'
    return
  }
  const stack = getCurrentPages()
  if (stack.length > 1) {
    uni.navigateBack()
    return
  }
  finishToRedirect()
}

function onSkip() {
  onboardingSkipped.value = true
  markLocalOnboardingSkipped()
  persistDraft()
  finishToRedirect()
}

function goFlavor() {
  screen.value = 'flavor'
}

function onStepPrev() {
  if (screen.value === 'flavor') {
    screen.value = 'welcome'
    return
  }
  if (screen.value === 'taboo') {
    screen.value = 'flavor'
    return
  }
  if (screen.value === 'goals') {
    screen.value = 'taboo'
    return
  }
  if (screen.value === 'vitals') {
    screen.value = 'goals'
    return
  }
  if (screen.value === 'lifestyle') {
    screen.value = 'vitals'
    return
  }
  if (screen.value === 'recommend_settings') {
    screen.value = 'lifestyle'
    return
  }
  if (screen.value === 'summary') {
    screen.value = 'recommend_settings'
  }
}

function onSummaryEditBack() {
  screen.value = 'recommend_settings'
}

function toastSubmitError(e: unknown): string {
  if (e instanceof HttpError) {
    return e.message.slice(0, 240)
  }
  if (e instanceof Error) {
    return e.message.slice(0, 240)
  }
  return '保存失败，请检查网络后重试'
}

function buildOnboardingProfilePayload(): OnboardingProfileSubmitPayload {
  const birthday = recoBirthday.value.trim()
  return {
    birthday: birthday === '' ? null : birthday,
    gender: recoGender.value,
    height_cm: heightCm.value as number,
    weight_kg: weightKg.value as number,
    target_weight_kg: targetWeightKg.value,
    diet_goal: [...dietGoals.value],
    flavor_preferences: [...flavorPreferences.value],
    cuisine_preferences: [],
    dislike_ingredients: [...dislikeIngredients.value],
    allergy_ingredients: [...allergyIngredients.value],
    taboo_ingredients: [],
    cooking_frequency: cookingFrequency.value,
    meal_pattern: null,
    family_size: familySize.value,
    lifestyle_tags: [...lifestyleTags.value],
    recommendation_style: null,
    destiny_mode_enabled: destinyModeEnabled.value === true,
    period_feature_enabled: periodFeatureEnabled.value === true,
    accepts_product_recommendation: acceptsProductRecommendation.value === true,
    onboarding_version: CURRENT_ONBOARDING_VERSION,
  }
}

async function onCompleteOnboarding() {
  if (destinyModeEnabled.value === null || periodFeatureEnabled.value === null || acceptsProductRecommendation.value === null) {
    uni.showToast({ title: '请先完成推荐相关设置', icon: 'none' })
    return
  }
  if (heightCm.value == null || weightKg.value == null) {
    uni.showToast({ title: '缺少身高或体重', icon: 'none' })
    return
  }

  submittingProfile.value = true
  try {
    await postUserProfileOnboarding(buildOnboardingProfilePayload())
    onboardingCompleted.value = true
    onboardingSkipped.value = false
    persistDraft()
    markLocalOnboardingCompleted()
    patchCurrentUser({ needsOnboarding: false })
    finishToRedirect()
  } catch (e) {
    console.error('[onboarding submit]', e)
    uni.showToast({ title: toastSubmitError(e), icon: 'none', duration: 2800 })
  } finally {
    submittingProfile.value = false
  }
}

function validateVitals(): string | null {
  const h = heightCm.value
  const w = weightKg.value
  if (h == null) return '请填写身高（cm）'
  if (!Number.isFinite(h) || h < 80 || h > 250) return '身高需在 80～250 cm'
  if (w == null) return '请填写体重（kg）'
  if (!Number.isFinite(w) || w < 20 || w > 300) return '体重需在 20～300 kg'
  const t = targetWeightKg.value
  if (t != null && (!Number.isFinite(t) || t < 20 || t > 300)) return '目标体重需在 20～300 kg'
  return null
}

const COOKING_OK = new Set(['often', 'sometimes', 'rarely', 'takeout'])
const FAMILY_OK = new Set(['single', 'couple', 'family3', 'family5'])

function validateLifestyle(): string | null {
  if (!cookingFrequency.value || !COOKING_OK.has(cookingFrequency.value)) return '请选择做饭频率'
  if (!familySize.value || !FAMILY_OK.has(familySize.value)) return '请选择家庭人数 / 用餐场景'
  return null
}

function validateRecommendSettings(): string | null {
  if (destinyModeEnabled.value === null) return '请选择是否开启食命推荐'
  if (periodFeatureEnabled.value === null) return '请选择是否开启特殊状态贴心推荐'
  if (acceptsProductRecommendation.value === null) return '请选择是否接受商品推荐'
  return null
}

function onStepNext() {
  persistDraft()
  if (screen.value === 'flavor') {
    screen.value = 'taboo'
    return
  }
  if (screen.value === 'taboo') {
    screen.value = 'goals'
    return
  }
  if (screen.value === 'goals') {
    screen.value = 'vitals'
    return
  }
  if (screen.value === 'vitals') {
    const err = validateVitals()
    if (err) {
      uni.showToast({ title: err, icon: 'none' })
      return
    }
    screen.value = 'lifestyle'
    return
  }
  if (screen.value === 'lifestyle') {
    const err = validateLifestyle()
    if (err) {
      uni.showToast({ title: err, icon: 'none' })
      return
    }
    screen.value = 'recommend_settings'
    return
  }
  if (screen.value === 'recommend_settings') {
    const err = validateRecommendSettings()
    if (err) {
      uni.showToast({ title: err, icon: 'none' })
      return
    }
    screen.value = 'summary'
    return
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.ob {
  min-height: 100vh;
  box-sizing: border-box;
  background: $mp-bg-page;
}

.ob__scroll {
  box-sizing: border-box;
}

.ob__body {
  padding: 24rpx 32rpx 48rpx;
  box-sizing: border-box;
}

.ob__body--step {
  padding-top: 16rpx;
}

.ob__hero-card {
  padding: 36rpx 32rpx;
}

.ob__hero-title {
  display: block;
  font-size: 36rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.4;
  letter-spacing: -0.02em;
}

.ob__hero-sub {
  display: block;
  margin-top: 20rpx;
  font-size: 26rpx;
  line-height: 1.6;
  color: $mp-text-secondary;
}

.ob__section-k {
  display: block;
  margin: 32rpx 8rpx 16rpx;
  font-size: 22rpx;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $mp-text-muted;
}

.ob__value-card {
  padding: 28rpx 28rpx 32rpx;
}

.ob__value-row {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
  padding: 12rpx 0;
}

.ob__value-row + .ob__value-row {
  border-top: 1rpx solid #f3f4f6;
}

.ob__value-dot {
  flex-shrink: 0;
  font-size: 32rpx;
  line-height: 1.4;
  color: $mp-accent;
  font-weight: 700;
}

.ob__value-txt {
  flex: 1;
  font-size: 28rpx;
  line-height: 1.55;
  color: $mp-text-primary;
}

.ob__cta {
  margin-top: 40rpx;
  width: 100%;
}

.ob__cta-hint {
  display: block;
  margin-top: 20rpx;
  text-align: center;
  font-size: 24rpx;
  color: $mp-text-muted;
  line-height: 1.5;
}

.ob__scroll-pad {
  height: 24rpx;
}

.ob__summary-foot {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 22;
  padding: 20rpx 32rpx 24rpx;
  box-sizing: border-box;
  background: linear-gradient(180deg, rgba(245, 245, 247, 0) 0%, $mp-bg-page 24%);
  border-top: 1rpx solid $mp-border;
}

.ob__summary-primary {
  width: 100%;
  margin: 0;
}

.ob__summary-secondary {
  width: 100%;
  margin: 20rpx 0 0;
}
</style>
