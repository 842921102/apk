<template>
  <view class="pe">
    <scroll-view scroll-y class="pe__scroll" :show-scrollbar="false">
      <view class="pe__inner">
        <text class="pe__page-desc">修改后将在后续「吃什么」推荐中生效，与账号同步保存。</text>

        <!-- 1 基础信息 -->
        <view class="pe__mod">
          <text class="pe__mod-k">基础信息</text>
          <view class="mp-card pe__card">
            <text class="pe__label">性别</text>
            <OptionSingleCardGroup v-model="genderSel" :options="genderOptions" />
            <text class="pe__label pe__label--sp">生日</text>
            <picker mode="date" :value="birthdayVal" @change="onBirthChange">
              <view class="pe__picker-cell">{{ birthdayVal || '选填，点击选择' }}</view>
            </picker>
            <text class="pe__label pe__label--sp">身高</text>
            <picker mode="selector" :range="heightRange" :value="heightIndex" @change="onHeightChange">
              <view class="pe__picker-cell">{{ heightDisplay }}</view>
            </picker>
            <text class="pe__label pe__label--sp">体重（kg）</text>
            <input
              v-model="weightStr"
              class="pe__input"
              type="digit"
              placeholder="如 58.5"
            />
          </view>
        </view>

        <!-- 2 饮食偏好 -->
        <view class="pe__mod">
          <text class="pe__mod-k">饮食偏好</text>
          <view class="mp-card pe__card">
            <text class="pe__label">口味偏好</text>
            <text class="pe__hint">可多选</text>
            <OptionChipGroup v-model="flavorPreferences" :options="PROFILE_FLAVOR_OPTIONS" />
            <text class="pe__label pe__label--sp">饮食类型</text>
            <text class="pe__hint">家常、减脂、健身等场景，可多选</text>
            <OptionChipGroup v-model="cuisinePreferences" :options="PROFILE_DIET_TYPE_OPTIONS" />
          </view>
        </view>

        <!-- 3 忌口与限制 -->
        <view class="pe__mod">
          <text class="pe__mod-k">忌口与限制</text>
          <view class="mp-card pe__card">
            <text class="pe__label">过敏食物</text>
            <text class="pe__hint">涉及安全，请如实填写</text>
            <OptionChipGroup
              v-model="allergyIngredients"
              mutual-exclusive-none-id="暂无"
              :options="PROFILE_ALLERGY_OPTIONS"
            />
            <text class="pe__label pe__label--sp">不吃的食材</text>
            <OptionChipGroup
              v-model="dislikeIngredients"
              mutual-exclusive-none-id="暂无"
              :options="PROFILE_DISLIKE_OPTIONS"
            />
            <text class="pe__label pe__label--sp">宗教 / 文化饮食</text>
            <text class="pe__hint">可选；选「无」会清空其它项</text>
            <OptionChipGroup
              v-model="religiousRestrictions"
              mutual-exclusive-none-id="无"
              :options="PROFILE_RELIGIOUS_OPTIONS"
            />
          </view>
        </view>

        <!-- 4 饮食目标 -->
        <view class="pe__mod">
          <text class="pe__mod-k">饮食目标</text>
          <view class="mp-card pe__card">
            <text class="pe__hint">单选，影响推荐取向</text>
            <OptionSingleCardGroup v-model="dietGoalSingle" :options="PROFILE_DIET_GOAL_SINGLE_OPTIONS" />
          </view>
        </view>

        <!-- 5 女性专属 -->
        <view v-if="genderSel === 'female'" class="pe__mod">
          <text class="pe__mod-k">女性专属</text>
          <view class="mp-card pe__card">
            <view class="pe__switch-row">
              <view class="pe__switch-mid">
                <text class="pe__switch-title">记录生理期</text>
                <text class="pe__switch-sub">开启后可在「吃什么」前填写今日状态</text>
              </view>
              <switch :checked="periodFeatureEnabled" color="#7A57D1" @change="onPeriodSwitch" />
            </view>
            <text class="pe__label pe__label--sp">最近一次月经开始日</text>
            <picker mode="date" :value="lastPeriodStart" @change="onPeriodDateChange">
              <view class="pe__picker-cell">{{ lastPeriodStart || '选填' }}</view>
            </picker>
            <text class="pe__label pe__label--sp">周期天数</text>
            <picker mode="selector" :range="cycleDayLabels" :value="cycleDayIndex" @change="onCycleChange">
              <view class="pe__picker-cell">{{ cycleDayDisplay }}</view>
            </picker>
          </view>
        </view>

        <!-- 6 推荐风格 -->
        <view class="pe__mod">
          <text class="pe__mod-k">推荐风格</text>
          <view class="mp-card pe__card">
            <view class="pe__style-grid">
              <view
                v-for="s in PROFILE_RECOMMENDATION_STYLE_OPTIONS"
                :key="s.id"
                class="pe__style-card"
                :class="{ 'pe__style-card--on': recommendationStyle === s.id }"
                @click="recommendationStyle = s.id"
              >
                <text class="pe__style-name">{{ s.label }}</text>
                <text class="pe__style-desc">{{ s.desc }}</text>
              </view>
            </view>
            <view class="pe__switch-row pe__switch-row--sp">
              <view class="pe__switch-mid">
                <text class="pe__switch-title">食命文案</text>
                <text class="pe__switch-sub">推荐里附带更有情绪价值的「食命」短句</text>
              </view>
              <switch :checked="destinyModeEnabled" color="#7A57D1" @change="onDestinySwitch" />
            </view>
          </view>
        </view>

        <view class="pe__scroll-pad" />
      </view>
    </scroll-view>

    <view class="pe__bar">
      <button class="mp-btn-primary pe__save" :loading="saving" :disabled="saving" @click="onSave">
        保存偏好
      </button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import OptionChipGroup from '@/components/onboarding/OptionChipGroup.vue'
import OptionSingleCardGroup from '@/components/onboarding/OptionSingleCardGroup.vue'
import { fetchMeProfile, putMeProfile } from '@/api/me'
import { HttpError } from '@/api/http'
import { patchCurrentUser, useAuth } from '@/composables/useAuth'
import {
  PROFILE_ALLERGY_OPTIONS,
  PROFILE_DIET_GOAL_SINGLE_OPTIONS,
  PROFILE_DIET_TYPE_OPTIONS,
  PROFILE_DISLIKE_OPTIONS,
  PROFILE_FLAVOR_OPTIONS,
  PROFILE_RECOMMENDATION_STYLE_OPTIONS,
  PROFILE_RELIGIOUS_OPTIONS,
  cycleDaysRange,
  heightCmRange,
  type ProfileRecommendationStyleId,
} from '@/constants/userProfileEdit'
import type { UserProfileDto } from '@/types/profile'

const { isLoggedIn, syncAuthFromSupabase } = useAuth()

const saving = ref(false)
const loaded = ref(false)

const genderSel = ref<'unknown' | 'male' | 'female' | 'undisclosed'>('unknown')
const birthdayVal = ref('')
const heightIndex = ref(50)
const weightStr = ref('')
const flavorPreferences = ref<string[]>([])
const cuisinePreferences = ref<string[]>([])
const allergyIngredients = ref<string[]>([])
const dislikeIngredients = ref<string[]>([])
const religiousRestrictions = ref<string[]>([])
const dietGoalSingle = ref('随便吃')
const periodFeatureEnabled = ref(false)
const lastPeriodStart = ref('')
const cycleDayIndex = ref(7)
const recommendationStyle = ref<ProfileRecommendationStyleId>('practical')
const destinyModeEnabled = ref(false)

const heightRange = heightCmRange()
const cycleDayLabels = cycleDaysRange()

const genderOptions = [
  { id: 'male', label: '男' },
  { id: 'female', label: '女' },
  { id: 'undisclosed', label: '不展示' },
  { id: 'unknown', label: '未填' },
]

const heightDisplay = computed(() => {
  const v = heightRange[heightIndex.value]
  return v ? `${v} cm` : '请选择'
})

const cycleDayDisplay = computed(() => {
  const v = cycleDayLabels[cycleDayIndex.value]
  return v ? `${v} 天` : '默认 28 天'
})

function parseWeight(): number | null {
  const t = weightStr.value.trim()
  if (!t) return null
  const n = parseFloat(t)
  if (!Number.isFinite(n) || n < 15 || n > 400) return null
  return Math.round(n * 10) / 10
}

function normalizeListForSave(arr: string[], noneId: string): string[] {
  const filtered = arr.filter((x) => x && x !== noneId)
  return filtered
}

function pickDietGoalFromProfile(p: UserProfileDto): string {
  const g = Array.isArray(p.diet_goal) && p.diet_goal.length ? p.diet_goal[0] : ''
  if (g && PROFILE_DIET_GOAL_SINGLE_OPTIONS.some((o) => o.id === g)) return g
  const h = p.health_goal?.trim()
  if (h && PROFILE_DIET_GOAL_SINGLE_OPTIONS.some((o) => o.id === h)) return h
  const allowed = new Set(PROFILE_DIET_GOAL_SINGLE_OPTIONS.map((o) => o.id))
  if (g && allowed.size) {
    for (const o of PROFILE_DIET_GOAL_SINGLE_OPTIONS) {
      if (g.includes(o.id)) return o.id
    }
  }
  return '随便吃'
}

function pickStyleFromProfile(p: UserProfileDto): ProfileRecommendationStyleId {
  const s = (p.recommendation_style || '').trim()
  if (s === 'caring' || s === 'practical' || s === 'destiny_light') return s
  if (p.destiny_mode_enabled) return 'destiny_light'
  return 'practical'
}

function applyProfile(p: UserProfileDto) {
  genderSel.value = p.gender || 'unknown'
  birthdayVal.value = p.birthday || ''
  const h = p.height_cm
  if (h != null && h >= 120 && h <= 220) {
    heightIndex.value = h - 120
  } else {
    heightIndex.value = 50
  }
  weightStr.value = p.weight_kg != null ? String(p.weight_kg) : ''
  flavorPreferences.value = [...(p.flavor_preferences || [])]
  cuisinePreferences.value = [...(p.cuisine_preferences || [])]
  if (!cuisinePreferences.value.length && Array.isArray(p.diet_preferences)) {
    const known = new Set(PROFILE_DIET_TYPE_OPTIONS.map((x) => x.id))
    cuisinePreferences.value = p.diet_preferences.filter((x) => known.has(x))
  }
  allergyIngredients.value = [...(p.allergy_ingredients || [])]
  dislikeIngredients.value = [...(p.dislike_ingredients || [])]
  religiousRestrictions.value = [...(p.religious_restrictions || [])]
  dietGoalSingle.value = pickDietGoalFromProfile(p)
  periodFeatureEnabled.value = Boolean(p.period_feature_enabled)
  const pt = p.period_tracking || {}
  lastPeriodStart.value =
    typeof pt.last_period_start === 'string' && pt.last_period_start ? pt.last_period_start : ''
  const cd = typeof pt.cycle_days === 'number' && Number.isFinite(pt.cycle_days) ? pt.cycle_days : 28
  const idx = cycleDayLabels.indexOf(String(cd))
  cycleDayIndex.value = idx >= 0 ? idx : 7
  recommendationStyle.value = pickStyleFromProfile(p)
  destinyModeEnabled.value = Boolean(p.destiny_mode_enabled)
}

function onBirthChange(e: { detail?: { value?: string } }) {
  birthdayVal.value = e.detail?.value || ''
}

function onHeightChange(e: { detail?: { value?: string | number } }) {
  const v = e.detail?.value
  const n = typeof v === 'number' ? v : parseInt(String(v), 10)
  if (Number.isFinite(n)) heightIndex.value = n
}

function onPeriodDateChange(e: { detail?: { value?: string } }) {
  lastPeriodStart.value = e.detail?.value || ''
}

function onCycleChange(e: { detail?: { value?: string | number } }) {
  const v = e.detail?.value
  const n = typeof v === 'number' ? v : parseInt(String(v), 10)
  if (Number.isFinite(n)) cycleDayIndex.value = n
}

function onPeriodSwitch(e: { detail?: { value?: boolean } }) {
  periodFeatureEnabled.value = Boolean(e.detail?.value)
}

function onDestinySwitch(e: { detail?: { value?: boolean } }) {
  destinyModeEnabled.value = Boolean(e.detail?.value)
}

async function load() {
  if (!isLoggedIn.value) {
    uni.navigateTo({
      url: '/pages/login/index?redirect=' + encodeURIComponent('/pages/me/recommendation-profile-edit'),
    })
    return
  }
  await syncAuthFromSupabase()
  try {
    const res = await fetchMeProfile()
    applyProfile(res.profile)
    loaded.value = true
  } catch {
    uni.showToast({ title: '加载失败', icon: 'none' })
  }
}

onShow(() => {
  void load()
})

async function onSave() {
  if (!loaded.value || saving.value) return
  const wRaw = weightStr.value.trim()
  const w = wRaw === '' ? null : parseWeight()
  if (wRaw !== '' && w === null) {
    uni.showToast({ title: '体重格式无效', icon: 'none' })
    return
  }
  const heightCm = 120 + heightIndex.value

  saving.value = true
  try {
    const dg = dietGoalSingle.value
    const dislike = normalizeListForSave(dislikeIngredients.value, '暂无')
    const allergy = normalizeListForSave(allergyIngredients.value, '暂无')
    const religious = normalizeListForSave(religiousRestrictions.value, '无')

    const periodPayload =
      genderSel.value === 'female'
        ? {
            last_period_start: lastPeriodStart.value.trim() || null,
            cycle_days: parseInt(cycleDayLabels[cycleDayIndex.value] || '28', 10) || null,
          }
        : null

    await putMeProfile({
      gender: genderSel.value,
      birthday: birthdayVal.value.trim() || null,
      height_cm: heightCm,
      weight_kg: w,
      flavor_preferences: flavorPreferences.value.filter(Boolean),
      cuisine_preferences: cuisinePreferences.value.filter(Boolean),
      dislike_ingredients: dislike,
      allergy_ingredients: allergy,
      religious_restrictions: religious,
      taboo_ingredients: dislike,
      diet_goal: dg === '随便吃' ? [] : [dg],
      health_goal: dg === '随便吃' ? null : dg,
      period_feature_enabled: genderSel.value === 'female' ? periodFeatureEnabled.value : false,
      period_tracking: periodPayload,
      recommendation_style: recommendationStyle.value,
      destiny_mode_enabled: destinyModeEnabled.value,
    })
    patchCurrentUser({
      periodFeatureEnabled: genderSel.value === 'female' ? periodFeatureEnabled.value : false,
    })
    uni.showToast({ title: '已保存', icon: 'success' })
    setTimeout(() => {
      uni.switchTab({ url: '/pages/me/index' })
    }, 400)
  } catch (e) {
    const msg = e instanceof HttpError ? e.message : '保存失败'
    uni.showToast({ title: msg.slice(0, 200), icon: 'none' })
  } finally {
    saving.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.pe {
  min-height: 100vh;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 140rpx);
  box-sizing: border-box;
  padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
}

.pe__scroll {
  max-height: 100vh;
  box-sizing: border-box;
}

.pe__inner {
  padding: 20rpx 24rpx 0;
  box-sizing: border-box;
}

.pe__page-desc {
  display: block;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-muted;
  padding: 0 8rpx 20rpx;
}

.pe__mod {
  margin-bottom: 28rpx;
}

.pe__mod-k {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: $mp-accent;
  text-transform: uppercase;
  margin-bottom: 12rpx;
  padding-left: 8rpx;
}

.pe__card {
  padding: 26rpx 22rpx 28rpx;
  border-color: rgba(122, 87, 209, 0.2);
}

.pe__label {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.pe__label--sp {
  margin-top: 28rpx;
}

.pe__hint {
  display: block;
  font-size: 22rpx;
  color: $mp-text-muted;
  margin-top: 8rpx;
  margin-bottom: 16rpx;
}

.pe__picker-cell {
  margin-top: 12rpx;
  padding: 22rpx 20rpx;
  border-radius: 16rpx;
  background: #f3f4f6;
  font-size: 28rpx;
  color: $mp-text-primary;
  font-weight: 600;
}

.pe__input {
  margin-top: 12rpx;
  padding: 22rpx 20rpx;
  border-radius: 16rpx;
  background: #f3f4f6;
  font-size: 28rpx;
  color: $mp-text-primary;
  box-sizing: border-box;
}

.pe__switch-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
}

.pe__switch-row--sp {
  margin-top: 28rpx;
  padding-top: 24rpx;
  border-top: 1rpx solid rgba($mp-border, 0.85);
}

.pe__switch-mid {
  flex: 1;
  min-width: 0;
}

.pe__switch-title {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.pe__switch-sub {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
  line-height: 1.35;
}

.pe__style-grid {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.pe__style-card {
  padding: 24rpx 22rpx;
  border-radius: 18rpx;
  background: #f3f4f6;
  border: 2rpx solid transparent;
}

.pe__style-card--on {
  background: rgba(122, 87, 209, 0.1);
  border-color: $mp-ring-accent;
}

.pe__style-name {
  display: block;
  font-size: 30rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.pe__style-desc {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.pe__scroll-pad {
  height: 40rpx;
}

.pe__bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;
  padding: 16rpx 24rpx calc(16rpx + env(safe-area-inset-bottom));
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  border-top: 1rpx solid rgba($mp-border, 0.85);
  box-sizing: border-box;
}

.pe__save {
  width: 100%;
  margin: 0;
}
</style>
