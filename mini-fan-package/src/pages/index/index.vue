<template>
  <view class="mp-page home">
    <!-- 1. 顶部标题区：运营 Banner（配置）或默认品牌头图 -->
    <view v-if="config.show_home_banner" class="mp-hero home__banner">
      <text class="mp-hero__kicker mp-kicker--on-dark">生活厨房</text>
      <text class="mp-hero__title home__banner-title">{{ config.home_banner_title }}</text>
      <text class="mp-hero__sub home__banner-sub">{{ config.home_banner_subtitle }}</text>
    </view>

    <view v-else class="mp-hero home__masthead">
      <text class="mp-hero__kicker mp-kicker--on-dark">饭否</text>
      <text class="mp-hero__title home__masthead-title">{{ config.home_title }}</text>
      <text class="mp-hero__sub home__masthead-sub">{{ config.home_subtitle }}</text>
    </view>

    <view class="home__section home__wizard">
      <view class="home__section-rail">
        <text class="home__section-rail-k home__section-rail-k--accent">吃什么工作区</text>
        <text class="home__section-rail-h">逻辑与 UI 对齐 PC 端</text>
      </view>

      <view class="mp-card home__wizard-card">
        <view class="home__wizard-step">
          <text class="home__wizard-step-num">1</text>
          <view class="home__wizard-body">
            <text class="home__wizard-title">添加食材</text>
            <input v-model="currentIngredient" class="home__wizard-input" placeholder="输入食材，回车添加" @confirm="addIngredient" />
            <button class="mp-btn-ghost home__wizard-photo" :disabled="photoLoading" @click="pickIngredientPhoto">
              {{ photoLoading ? '识别中…' : '上传照片识别食材' }}
            </button>
            <view v-if="recognizedCandidates.length" class="home__recognized">
              <view class="home__recognized-head">
                <text class="home__wizard-picker-title">识别候选（可勾选后加入）</text>
                <view class="home__recognized-actions">
                  <text class="home__recognized-link" @click="selectAllRecognized">全选</text>
                  <text class="home__recognized-link" @click="clearRecognizedSelection">清空</text>
                </view>
              </view>
              <checkbox-group class="home__recognized-list" @change="onRecognizedChange">
                <label v-for="item in recognizedCandidates" :key="item" class="home__recognized-item">
                  <template v-if="editingCandidate !== item">
                    <checkbox :value="item" :checked="recognizedSelected.includes(item)" color="#7c4dff" />
                    <text class="home__recognized-txt">{{ item }}</text>
                    <text class="home__recognized-edit" @click.stop.prevent="startEditCandidate(item)">编辑</text>
                  </template>
                  <template v-else>
                    <input v-model="editingCandidateDraft" class="home__recognized-input" maxlength="20" />
                    <text class="home__recognized-edit" @click.stop.prevent="saveEditCandidate(item)">保存</text>
                    <text class="home__recognized-edit home__recognized-edit--muted" @click.stop.prevent="cancelEditCandidate">取消</text>
                  </template>
                </label>
              </checkbox-group>
              <button class="mp-btn-secondary home__recognized-confirm" @click="addRecognizedIngredients">加入已勾选食材</button>
            </view>
            <view class="home__wizard-picker">
              <text class="home__wizard-picker-title">快速选择食材</text>
              <view class="home__wizard-picker-groups">
                <view v-for="g in ingredientGroups" :key="g.id" class="home__wizard-picker-group">
                  <text class="home__wizard-picker-k">{{ g.icon }} {{ g.name }}</text>
                  <view class="home__wizard-picker-items">
                    <view v-for="item in g.items" :key="item" class="home__wizard-pick" @click="addIngredientByName(item)">
                      {{ item }}
                    </view>
                  </view>
                </view>
              </view>
            </view>
            <view class="home__wizard-tags">
              <view v-for="ing in ingredients" :key="ing" class="home__wizard-tag" @click="removeIngredient(ing)">
                {{ ing }} · 删除
              </view>
            </view>
          </view>
        </view>

        <view class="home__wizard-step">
          <text class="home__wizard-step-num">2</text>
          <view class="home__wizard-body">
            <text class="home__wizard-title">选择菜系 / 自定义要求</text>
            <view class="home__wizard-cuisines">
              <view
                v-for="c in cuisineOptions"
                :key="c.id"
                class="home__wizard-cuisine"
                :class="{ 'home__wizard-cuisine--on': selectedCuisines.includes(c.id) }"
                @click="toggleCuisine(c.id)"
              >
                {{ c.icon }} {{ c.name }}
              </view>
            </view>
            <textarea
              v-model="customPrompt"
              class="home__wizard-textarea"
              maxlength="200"
              placeholder="例如：想吃清淡、少油、适合晚餐的一道菜"
            />
            <view class="home__wizard-presets">
              <text class="home__wizard-picker-title">场景预设</text>
              <view class="home__wizard-preset-row">
                <view v-for="p in scenePresets" :key="p.id" class="home__wizard-preset" @click="applyPreset(p.prompt)">
                  {{ p.name }}
                </view>
              </view>
              <text class="home__wizard-picker-title">口味预设</text>
              <view class="home__wizard-preset-row">
                <view v-for="p in tastePresets" :key="p.id" class="home__wizard-preset" @click="applyPreset(p.prompt)">
                  {{ p.name }}
                </view>
              </view>
            </view>
            <button class="mp-btn-ghost home__wizard-rand" @click="getRandomInspiration">随机灵感</button>
          </view>
        </view>

        <view class="home__wizard-step">
          <text class="home__wizard-step-num">3</text>
          <view class="home__wizard-body">
            <text class="home__wizard-title">交给大师</text>
            <text class="home__wizard-desc">已选食材 {{ ingredients.length }} 个，菜系 {{ selectedCuisines.length }} 个</text>
            <button class="mp-btn-primary home__wizard-go" :disabled="wizardLoading" @click="generateFromWizard">
              {{ wizardLoading ? '生成中…' : '开始生成' }}
            </button>
          </view>
        </view>

        <view class="home__wizard-step">
          <text class="home__wizard-step-num">4</text>
          <view class="home__wizard-body">
            <text class="home__wizard-title">菜谱结果</text>
            <view v-if="wizardLoading" class="home__wizard-loading home__wizard-loading--animated">
              <view class="home__ai-core">
                <view class="home__ai-orbit home__ai-orbit--a" />
                <view class="home__ai-orbit home__ai-orbit--b" />
                <view class="home__ai-glow home__ai-glow--inner" />
                <view class="home__ai-glow home__ai-glow--outer" />
                <view class="home__ai-dot home__ai-dot--1" />
                <view class="home__ai-dot home__ai-dot--2" />
                <view class="home__ai-dot home__ai-dot--3" />
                <view class="home__ai-dot home__ai-dot--4" />
              </view>
              <text class="home__wizard-loading-text">{{ wizardStageText }}</text>
              <view class="home__wizard-progress-track">
                <view class="home__wizard-progress-fill" />
              </view>
              <text class="home__wizard-progress-val">{{ Math.round(wizardProgress) }}%</text>
            </view>
            <text v-if="wizardError" class="home__wizard-err">{{ wizardError }}</text>
            <view v-else-if="wizardRecipe" class="home__wizard-result">
              <text class="home__wizard-r-title">{{ wizardRecipe.title }}</text>
              <text class="home__wizard-r-meta">{{ wizardRecipe.cuisine || '—' }}</text>
              <text v-if="wizardRecipe.ingredients?.length" class="home__wizard-r-ing">
                食材：{{ wizardRecipe.ingredients.join('、') }}
              </text>
              <text class="home__wizard-r-content">{{ wizardRecipe.content }}</text>
              <view v-if="historyNote" class="home__wizard-note">
                <text class="home__wizard-note-txt">{{ historyNote }}</text>
              </view>
              <button class="mp-btn-ghost home__wizard-fav-btn" :disabled="favoriteLoading" @click="onToggleFavorite">
                <text>{{ isFavorited ? '取消收藏' : '加入收藏' }}</text>
              </button>
              <button class="mp-btn-ghost home__wizard-image-btn" :disabled="recipeImageLoading" @click="generateWizardImage">
                {{ recipeImageLoading ? '生成图片中…' : '生成菜品图片' }}
              </button>
              <image v-if="wizardImageUrl" class="home__wizard-image" :src="wizardImageUrl" mode="widthFix" />
            </view>
            <text v-else class="home__wizard-empty">等待生成结果…</text>
          </view>
        </view>
      </view>
    </view>

    <view class="home__section home__section--last">
      <view class="home__section-rail">
        <text class="home__section-rail-k home__section-rail-k--accent">新手</text>
        <text class="home__section-rail-h">新手指引</text>
      </view>
      <view class="mp-card mp-card--accent-soft home__newbie-card">
        <text class="home__card-kicker home__card-kicker--accent">上手三步</text>
        <view class="home__steps">
          <view v-for="(s, i) in newbieSteps" :key="s" class="home__step">
            <text class="home__step-num">{{ i + 1 }}</text>
            <text class="home__step-text">{{ s }}</text>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAppMessages } from '@/composables/useAppMessages'
import { useAuth } from '@/composables/useAuth'
import { requestTodayEat, requestRecipeImage, requestRecognizeIngredients } from '@/api/ai'
import { HttpError } from '@/api/http'
import {
  insertRecipeHistoryFromTodayEat,
  isFavoriteRecipe,
  toggleFavoriteRecipe,
  BIZ_UNAUTHORIZED,
  BIZ_NEED_LARAVEL_AUTH,
  BIZ_NOT_CONFIGURED,
} from '@/api/biz'
import { upsertLocalGalleryItem } from '@/api/gallery'
import { favoriteContentDigest } from '@/lib/favoriteDigest'
import { NEWBIE_GUIDE_STEPS } from '@/config/newbieGuide'
import type { TodayEatResult } from '@/types/ai'

const { config } = useAppConfig()
const msg = useAppMessages()
const { syncAuthFromSupabase, isLoggedIn } = useAuth()
const currentIngredient = ref('')
const ingredients = ref<string[]>([])
const selectedCuisines = ref<string[]>([])
const customPrompt = ref('')
const wizardLoading = ref(false)
const wizardProgress = ref(0)
const wizardStageText = ref('准备食材中…')
const wizardError = ref('')
const wizardRecipe = ref<{ title: string; cuisine?: string; content: string; ingredients?: string[] } | null>(null)
const historyNote = ref('')
const favoriteLoading = ref(false)
const isFavorited = ref(false)
const photoLoading = ref(false)
const recognizedCandidates = ref<string[]>([])
const recognizedSelected = ref<string[]>([])
const editingCandidate = ref('')
const editingCandidateDraft = ref('')
const recipeImageLoading = ref(false)
const wizardImageUrl = ref('')
let wizardTimer: ReturnType<typeof setInterval> | null = null

const cuisineOptions = [
  { id: 'sichuan', name: '川菜', icon: '🌶️' },
  { id: 'cantonese', name: '粤菜', icon: '🥬' },
  { id: 'hunan', name: '湘菜', icon: '🔥' },
  { id: 'jiangsu', name: '苏菜', icon: '🍤' },
  { id: 'japanese', name: '日式', icon: '🍣' },
  { id: 'western', name: '西式', icon: '🥩' },
] as const

const randomInspirations = [
  '下班快手菜，20 分钟完成',
  '清淡低脂，晚餐无负担',
  '重口下饭，适合配米饭',
  '适合 2 人共享，口味平衡',
  '汤菜搭配，暖胃舒服',
]

const ingredientGroups = [
  { id: 'meat', name: '荤菜', icon: '🥩', items: ['猪肉', '牛肉', '鸡肉', '排骨'] },
  { id: 'veg', name: '蔬菜', icon: '🥬', items: ['西红柿', '土豆', '青椒', '西兰花'] },
  { id: 'egg', name: '蛋奶豆', icon: '🥚', items: ['鸡蛋', '豆腐', '牛奶', '香菇'] },
] as const

const scenePresets = [
  { id: 'quick', name: '快手菜', prompt: '20 分钟内完成，步骤简洁' },
  { id: 'family', name: '家庭晚餐', prompt: '适合家庭共享，口味平衡' },
  { id: 'light', name: '清淡养胃', prompt: '清淡少油，晚餐无负担' },
] as const

const tastePresets = [
  { id: 'spicy', name: '香辣', prompt: '偏香辣，开胃下饭' },
  { id: 'fresh', name: '鲜香', prompt: '突出鲜味，层次清晰' },
  { id: 'sweet', name: '微甜', prompt: '口味温和，带一点甜' },
] as const

const newbieSteps = NEWBIE_GUIDE_STEPS

onShow(() => {
  void syncAuthFromSupabase()
})

function addIngredient() {
  const v = currentIngredient.value.trim()
  if (!v || ingredients.value.includes(v) || ingredients.value.length >= 10) return
  ingredients.value.push(v)
  currentIngredient.value = ''
}

function removeIngredient(ing: string) {
  ingredients.value = ingredients.value.filter((x) => x !== ing)
}

function addIngredientByName(name: string) {
  if (!name || ingredients.value.includes(name) || ingredients.value.length >= 10) return
  ingredients.value.push(name)
}

async function pickIngredientPhoto() {
  if (photoLoading.value) return
  photoLoading.value = true
  try {
    const choose = await uni.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['camera', 'album'],
    })
    const path = choose.tempFilePaths?.[0]
    if (!path) return
    const imageBase64 = await readFileAsBase64(path)
    const result = await requestRecognizeIngredients({ image_base64: imageBase64 })
    const items = Array.isArray(result.ingredients) ? result.ingredients : []
    recognizedCandidates.value = items.map((x) => String(x)).filter(Boolean)
    recognizedSelected.value = [...recognizedCandidates.value]
    uni.showToast({ title: items.length ? `识别到 ${items.length} 种食材` : '未识别到食材', icon: 'none' })
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '图片识别失败', icon: 'none' })
  } finally {
    photoLoading.value = false
  }
}

function readFileAsBase64(path: string): Promise<string> {
  return new Promise((resolve, reject) => {
    const fs = uni.getFileSystemManager()
    fs.readFile({
      filePath: path,
      encoding: 'base64',
      success: (res) => {
        const data = typeof res.data === 'string' ? res.data : ''
        if (!data) reject(new Error('图片读取失败'))
        else resolve(data)
      },
      fail: () => reject(new Error('图片读取失败')),
    })
  })
}

function onRecognizedChange(e: { detail?: { value?: string[] } }) {
  const values = Array.isArray(e?.detail?.value) ? e.detail.value : []
  recognizedSelected.value = values.map(String)
}

function startEditCandidate(item: string) {
  editingCandidate.value = item
  editingCandidateDraft.value = item
}

function cancelEditCandidate() {
  editingCandidate.value = ''
  editingCandidateDraft.value = ''
}

function saveEditCandidate(oldItem: string) {
  const next = editingCandidateDraft.value.trim()
  if (!next) {
    uni.showToast({ title: '食材名不能为空', icon: 'none' })
    return
  }
  recognizedCandidates.value = recognizedCandidates.value.map((x) => (x === oldItem ? next : x))
  recognizedSelected.value = recognizedSelected.value.map((x) => (x === oldItem ? next : x))
  // 去重，避免编辑成相同名称
  recognizedCandidates.value = Array.from(new Set(recognizedCandidates.value))
  recognizedSelected.value = Array.from(new Set(recognizedSelected.value))
  cancelEditCandidate()
}

function selectAllRecognized() {
  recognizedSelected.value = [...recognizedCandidates.value]
}

function clearRecognizedSelection() {
  recognizedSelected.value = []
}

function addRecognizedIngredients() {
  const before = ingredients.value.length
  recognizedSelected.value.forEach((item) => addIngredientByName(item))
  const added = ingredients.value.length - before
  recognizedCandidates.value = []
  recognizedSelected.value = []
  cancelEditCandidate()
  uni.showToast({ title: added > 0 ? `已加入 ${added} 个食材` : '没有可加入的新食材', icon: 'none' })
}

function toggleCuisine(id: string) {
  if (selectedCuisines.value.includes(id)) {
    selectedCuisines.value = selectedCuisines.value.filter((x) => x !== id)
  } else {
    selectedCuisines.value.push(id)
  }
}

function getRandomInspiration() {
  const text = randomInspirations[Math.floor(Math.random() * randomInspirations.length)]
  customPrompt.value = customPrompt.value ? `${customPrompt.value}；${text}` : text
}

function applyPreset(prompt: string) {
  if (!prompt) return
  customPrompt.value = customPrompt.value ? `${customPrompt.value}；${prompt}` : prompt
}

async function maybeSaveHistory(res: TodayEatResult, tasteText: string) {
  historyNote.value = ''
  if (res.history_saved === true) {
    msg.toastSaveSuccess()
    return
  }
  if (!isLoggedIn.value) {
    historyNote.value = '未登录：本次未写入历史；登录后可将结果保存到「历史」'
    return
  }
  const title = (res.title || res.recommended_dish || '').trim()
  const body = typeof res.content === 'string' ? res.content.trim() : ''
  if (!title || !body) return
  try {
    await insertRecipeHistoryFromTodayEat({
      title,
      cuisine: res.cuisine ?? null,
      ingredients: Array.isArray(res.ingredients) ? res.ingredients : [],
      response_content: res.content,
      request_payload: {
        source: 'mp-custom-wizard',
        preferences: { taste: tasteText },
        wizard_ingredients: [...ingredients.value],
      },
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
      console.error('[custom-wizard] history insert failed:', err)
    }
  }
}

async function syncFavoriteState() {
  const r = wizardRecipe.value
  if (!r?.title || !r?.content) return
  if (!isLoggedIn.value) {
    isFavorited.value = false
    return
  }
  try {
    const sid = favoriteContentDigest(r.title, r.content)
    isFavorited.value = await isFavoriteRecipe({
      source_type: 'today_eat',
      source_id: sid,
    })
  } catch {
    isFavorited.value = false
  }
}

async function onToggleFavorite() {
  const r = wizardRecipe.value
  if (!r?.title || !r?.content) return
  if (!isLoggedIn.value) {
    uni.navigateTo({
      url: `/pages/login/index?redirect=${encodeURIComponent('/pages/index/index')}`,
    })
    return
  }
  if (favoriteLoading.value) return
  favoriteLoading.value = true
  try {
    const sid = favoriteContentDigest(r.title, r.content)
    const { favorited } = await toggleFavoriteRecipe({
      source_type: 'today_eat',
      source_id: sid,
      title: r.title,
      cuisine: r.cuisine ?? null,
      ingredients: r.ingredients ?? [],
      recipe_content: r.content,
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

async function generateFromWizard() {
  if (wizardLoading.value) {
    return
  }
  if (ingredients.value.length === 0) {
    uni.showToast({ title: '请先添加食材', icon: 'none' })
    return
  }

  wizardLoading.value = true
  wizardProgress.value = 8
  wizardStageText.value = '大师正在挑选食材…'
  wizardError.value = ''
  wizardRecipe.value = null
  wizardImageUrl.value = ''
  historyNote.value = ''
  isFavorited.value = false
  favoriteLoading.value = false
  if (wizardTimer) clearInterval(wizardTimer)
  wizardTimer = setInterval(() => {
    wizardProgress.value = Math.min(92, wizardProgress.value + 7)
    if (wizardProgress.value < 35) wizardStageText.value = '大师正在挑选食材…'
    else if (wizardProgress.value < 70) wizardStageText.value = '大师正在融合口味与菜系…'
    else wizardStageText.value = '大师正在润色步骤与细节…'
  }, 350)
  try {
    await syncAuthFromSupabase()
    const cuisineText = cuisineOptions.filter((x) => selectedCuisines.value.includes(x.id)).map((x) => x.name).join('、')
    const tasteText = [cuisineText ? `偏好菜系：${cuisineText}` : '', customPrompt.value ? `要求：${customPrompt.value}` : '', `食材：${ingredients.value.join('、')}`]
      .filter(Boolean)
      .join('；')

    const res = await requestTodayEat({
      preferences: {
        taste: tasteText || undefined,
      },
      locale: 'zh-CN',
    })

    const title = (res.title || res.recommended_dish || '').trim()
    const contentRaw = res.content
    const content = typeof contentRaw === 'string' ? contentRaw.trim() : ''
    if (!title || !content) {
      throw new Error('接口返回格式异常，缺少菜名或做法正文')
    }

    wizardRecipe.value = {
      title,
      cuisine: res.cuisine,
      content: contentRaw,
      ingredients: Array.isArray(res.ingredients) ? res.ingredients : undefined,
    }
    wizardProgress.value = 100
    wizardStageText.value = '生成完成'
    void maybeSaveHistory(res, tasteText)
    void syncFavoriteState()
  } catch (e: unknown) {
    if (e instanceof HttpError && e.statusCode === 401) {
      wizardError.value = '需要登录后才能生成，或登录已过期'
    } else {
      const err = e as Error
      wizardError.value = err.message || '生成失败，请稍后重试'
    }
  } finally {
    if (wizardTimer) {
      clearInterval(wizardTimer)
      wizardTimer = null
    }
    wizardLoading.value = false
  }
}

async function generateWizardImage() {
  if (!wizardRecipe.value || recipeImageLoading.value) return
  recipeImageLoading.value = true
  try {
    const prompt = [
      `生成一道美食成品图，菜名：${wizardRecipe.value.title}`,
      `菜系：${wizardRecipe.value.cuisine || '家常菜'}`,
      `食材：${ingredients.value.join('、') || '常见家常食材'}`,
      `菜谱：${wizardRecipe.value.content.slice(0, 220)}`,
      '风格：高清写实、美食摄影、干净背景',
    ].join('\n')
    const res = await requestRecipeImage({ prompt, size: '1024x1024' })
    wizardImageUrl.value = res.url || ''
    if (wizardImageUrl.value) {
      const id = favoriteContentDigest(`${wizardRecipe.value.title}:${Date.now()}`, wizardImageUrl.value)
      upsertLocalGalleryItem({
        id,
        url: wizardImageUrl.value,
        recipeName: wizardRecipe.value.title,
        recipeId: id,
        cuisine: wizardRecipe.value.cuisine || '',
        ingredients: [...ingredients.value],
        generatedAt: new Date().toISOString(),
        prompt,
      })
      uni.showToast({ title: '图片已生成并入图鉴', icon: 'success' })
    }
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message || '生成图片失败', icon: 'none' })
  } finally {
    recipeImageLoading.value = false
  }
}

</script>

<style lang="scss" scoped>
@import '@/uni.scss';

/* 顶部 Banner / 头图：居中层级，贴近 Web 运营头图 */
.home__banner,
.home__masthead {
  text-align: center;
}

.home__banner-title,
.home__masthead-title {
  max-width: 620rpx;
  margin-left: auto;
  margin-right: auto;
}

.home__banner-sub,
.home__masthead-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

/* 分区块：左侧轨 + 卡片，层级更清晰 */
.home__section {
  margin-top: 36rpx;
}

.home__section--last {
  margin-bottom: 8rpx;
}

.home__section-rail {
  display: flex;
  flex-direction: row;
  align-items: baseline;
  gap: 12rpx;
  margin-bottom: 16rpx;
  padding-left: 8rpx;
}

.home__section-rail-k {
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.16em;
  color: $mp-text-muted;
  text-transform: uppercase;
}

.home__section-rail-k--accent {
  color: $mp-accent;
}

.home__section-rail-h {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.home__card-kicker {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: $mp-text-muted;
}

.home__card-kicker--muted {
  color: $mp-text-secondary;
  letter-spacing: 0.08em;
}

.home__card-kicker--accent {
  color: $mp-accent;
}

/* 新手 */
.home__newbie-card {
  padding-bottom: 32rpx;
}

.home__steps {
  margin-top: 20rpx;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.home__step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
}

.home__step-num {
  flex-shrink: 0;
  width: 40rpx;
  height: 40rpx;
  line-height: 40rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent;
  background: #fff;
  border-radius: 999rpx;
  border: 1rpx solid #dccdf7;
}

.home__step-text {
  flex: 1;
  font-size: 26rpx;
  line-height: 1.55;
  color: #5a3ba8;
  padding-top: 4rpx;
}

.home__wizard-card {
  border: 1rpx solid $mp-border;
}

.home__wizard-step {
  display: flex;
  gap: 14rpx;
  padding: 14rpx 0;
}

.home__wizard-step + .home__wizard-step {
  border-top: 1rpx dashed $mp-border;
}

.home__wizard-step-num {
  width: 42rpx;
  height: 42rpx;
  line-height: 42rpx;
  text-align: center;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
  background: #fff;
  border: 1rpx solid #dccdf7;
  flex-shrink: 0;
}

.home__wizard-body {
  flex: 1;
}

.home__wizard-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.home__wizard-input,
.home__wizard-textarea {
  margin-top: 10rpx;
  width: 100%;
  border: 1rpx solid $mp-border;
  border-radius: 14rpx;
  background: #fafbfc;
  padding: 16rpx;
  font-size: 24rpx;
}

.home__wizard-photo {
  margin-top: 10rpx;
}

.home__recognized {
  margin-top: 14rpx;
  padding: 16rpx;
  border-radius: 12rpx;
  background: #f5f1ff;
  border: 1rpx solid #e5dbff;
}

.home__recognized-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.home__recognized-actions {
  display: flex;
  gap: 16rpx;
}

.home__recognized-link {
  font-size: 22rpx;
  color: #7c4dff;
}

.home__recognized-list {
  margin-top: 10rpx;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8rpx 12rpx;
}

.home__recognized-item {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.home__recognized-txt {
  margin-left: 8rpx;
  font-size: 24rpx;
  color: $mp-text-primary;
}

.home__recognized-confirm {
  margin-top: 12rpx;
}

.home__recognized-edit {
  margin-left: auto;
  font-size: 22rpx;
  color: #7c4dff;
}

.home__recognized-edit--muted {
  color: #8a8a9b;
}

.home__recognized-input {
  flex: 1;
  min-width: 120rpx;
  height: 52rpx;
  line-height: 52rpx;
  padding: 0 14rpx;
  border-radius: 10rpx;
  border: 1rpx solid $mp-border;
  background: #fff;
  font-size: 24rpx;
}

.home__wizard-picker {
  margin-top: 12rpx;
  padding: 12rpx;
  border-radius: 14rpx;
  border: 1rpx solid $mp-border;
  background: #fff;
}

.home__wizard-picker-title {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
  margin-bottom: 8rpx;
}

.home__wizard-picker-groups {
  display: flex;
  flex-direction: column;
  gap: 10rpx;
}

.home__wizard-picker-k {
  font-size: 22rpx;
  color: $mp-text-secondary;
}

.home__wizard-picker-items {
  margin-top: 6rpx;
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.home__wizard-pick {
  font-size: 21rpx;
  padding: 6rpx 12rpx;
  border-radius: 999rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.home__wizard-tags {
  margin-top: 10rpx;
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
}

.home__wizard-tag {
  font-size: 22rpx;
  color: $mp-accent;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  border-radius: 999rpx;
  padding: 8rpx 14rpx;
}

.home__wizard-cuisines {
  margin-top: 10rpx;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10rpx;
}

.home__wizard-cuisine {
  border: 1rpx solid $mp-border;
  border-radius: 12rpx;
  background: #fafbfc;
  font-size: 22rpx;
  text-align: center;
  padding: 10rpx 8rpx;
}

.home__wizard-cuisine--on {
  background: $mp-accent;
  color: #fff;
  border-color: $mp-accent;
}

.home__wizard-rand {
  margin-top: 10rpx;
}

.home__wizard-presets {
  margin-top: 10rpx;
}

.home__wizard-preset-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
  margin-bottom: 8rpx;
}

.home__wizard-preset {
  font-size: 21rpx;
  padding: 6rpx 12rpx;
  border-radius: 999rpx;
  color: $mp-accent;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.home__wizard-desc,
.home__wizard-empty,
.home__wizard-err {
  display: block;
  margin-top: 10rpx;
  font-size: 23rpx;
  color: $mp-text-secondary;
}

.home__wizard-err {
  color: #d15454;
}

.home__wizard-go {
  margin-top: 10rpx;
}

.home__wizard-loading {
  margin-top: 10rpx;
  padding: 14rpx;
  border: 1rpx solid $mp-border;
  border-radius: 14rpx;
  background: #fafbfc;
  position: relative;
  overflow: hidden;
}

.home__wizard-loading--animated {
  text-align: center;
}

.home__ai-core {
  position: relative;
  width: 112rpx;
  height: 112rpx;
  margin: 6rpx auto 12rpx;
}

.home__ai-glow {
  position: absolute;
  border-radius: 50%;
}

.home__ai-glow--inner {
  width: 76rpx;
  height: 76rpx;
  left: 18rpx;
  top: 18rpx;
  background: radial-gradient(circle at 35% 35%, #f8f5ff 0%, #d8cbff 45%, #8c71ee 100%);
}

.home__ai-glow--outer {
  width: 112rpx;
  height: 112rpx;
  background: radial-gradient(circle, rgba(140, 113, 238, 0.28) 0%, rgba(140, 113, 238, 0.08) 52%, rgba(140, 113, 238, 0) 78%);
  animation: home-halo-pulse 3.2s ease-in-out infinite;
}

.home__ai-orbit {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2rpx solid rgba(123, 87, 228, 0.16);
  border-top-color: rgba(123, 87, 228, 0.45);
}

.home__ai-orbit--a { animation: home-orbit-a 3.4s ease-in-out infinite; }

.home__ai-orbit--b {
  inset: 10rpx;
  border-color: rgba(123, 87, 228, 0.12);
  border-top-color: rgba(123, 87, 228, 0.36);
  border-left-color: rgba(146, 198, 255, 0.34);
  animation: home-orbit-b 2.8s ease-in-out infinite;
}

.home__ai-dot {
  position: absolute;
  width: 8rpx;
  height: 8rpx;
  border-radius: 50%;
  background: rgba(167, 214, 255, 0.95);
  animation: home-dot-float 3.3s ease-in-out infinite;
}

.home__ai-dot--1 { left: 10rpx; top: 18rpx; }
.home__ai-dot--2 { right: 9rpx; top: 34rpx; animation-delay: .55s; }
.home__ai-dot--3 { left: 26rpx; bottom: 9rpx; animation-delay: 1.1s; }
.home__ai-dot--4 { right: 24rpx; bottom: 14rpx; animation-delay: 1.65s; }

.home__wizard-loading-text {
  display: block;
  font-size: 24rpx;
  color: $mp-text-primary;
}

.home__wizard-progress-track {
  margin-top: 10rpx;
  width: 100%;
  height: 14rpx;
  border-radius: 999rpx;
  background: #eceff4;
  overflow: hidden;
}

.home__wizard-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
  width: 40%;
  border-radius: 999rpx;
  animation: home-pulse 1.2s ease-in-out infinite;
}

.home__wizard-progress-val {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

@keyframes home-pulse {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(350%); }
}
@keyframes home-halo-pulse {
  0%,100% { opacity:.66; transform: scale(.94); }
  50% { opacity:.9; transform: scale(1.05); }
}
@keyframes home-orbit-a {
  0% { transform: rotate(0deg); opacity:.72; }
  50% { transform: rotate(140deg); opacity:.95; }
  100% { transform: rotate(360deg); opacity:.72; }
}
@keyframes home-orbit-b {
  0% { transform: rotate(330deg); opacity:.58; }
  50% { transform: rotate(180deg); opacity:.88; }
  100% { transform: rotate(-30deg); opacity:.58; }
}
@keyframes home-dot-float {
  0%,100% { transform: translate3d(0,0,0); opacity:.45; }
  40% { transform: translate3d(0,-7rpx,0); opacity:.9; }
  70% { transform: translate3d(0,2rpx,0); opacity:.62; }
}

.home__wizard-result {
  margin-top: 10rpx;
  border: 1rpx solid $mp-border;
  background: #fafbfc;
  border-radius: 14rpx;
  padding: 14rpx;
}

.home__wizard-r-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.home__wizard-r-meta {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.home__wizard-r-ing {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  line-height: 1.45;
  color: #374151;
}

.home__wizard-note {
  margin-top: 12rpx;
  padding: 12rpx 14rpx;
  border-radius: 12rpx;
  background: #f9fafb;
  border: 1rpx dashed $mp-border;
}

.home__wizard-note-txt {
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.home__wizard-fav-btn {
  width: 100%;
  margin-top: 12rpx;
}

.home__wizard-r-content {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-primary;
  white-space: pre-wrap;
}

.home__wizard-image-btn {
  margin-top: 12rpx;
}

.home__wizard-image {
  margin-top: 12rpx;
  width: 100%;
  border-radius: 12rpx;
  border: 1rpx solid $mp-border;
}
</style>
