<template>
    <div class="recipe-card bg-app-card">
        <!-- 头部：菜名 + 元信息 -->
        <div class="recipe-head border-b border-app-line bg-gradient-to-r from-app-accent-soft/90 to-app-page px-4 py-4 md:px-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="line-clamp-2 text-[20px] font-bold leading-snug text-app-fg md:text-[24px]">{{ recipe.name }}</h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="app-tag app-tag--accent">{{ recipe.cuisine }}</span>
                        <span class="app-tag app-tag--muted">难度：{{ difficultyText }}</span>
                        <span class="app-tag app-tag--muted">用时：{{ formatTime(recipe.cookingTime) }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <FavoriteButton v-if="showFavoriteButton" :recipe="recipe" @favorite-changed="onFavoriteChanged" />
                </div>
            </div>
        </div>

        <div class="space-y-4 p-3 md:space-y-5 md:p-6">
            <!-- 食材 -->
            <section class="rounded-[16px] bg-app-page p-4 ring-1 ring-app-line">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-app-fg">所需食材</h4>
                    <span class="text-xs font-medium text-app-muted">{{ recipe.ingredients.length }} 项</span>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <div
                        v-for="ingredient in recipe.ingredients"
                        :key="ingredient"
                        class="rounded-xl bg-app-card px-3 py-2 text-[13px] font-medium text-app-fg ring-1 ring-app-line"
                    >
                        {{ ingredient }}
                    </div>
                </div>
            </section>

            <!-- 步骤 -->
            <section class="rounded-[16px] bg-app-page p-4 ring-1 ring-app-line">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-app-fg">制作步骤</h4>
                    <button type="button" class="app-btn app-btn--secondary app-btn--xs" @click="toggleExpanded">
                        {{ isExpanded ? '收起步骤' : '展开详情' }}
                    </button>
                </div>

                <div class="space-y-2.5">
                    <article
                        v-for="step in isExpanded ? recipe.steps : recipe.steps.slice(0, 3)"
                        :key="step.step"
                        class="rounded-[14px] bg-app-card p-3 ring-1 ring-app-line"
                    >
                        <div class="flex items-start gap-3">
                            <div class="step-index flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-app-accent text-xs font-bold text-white">
                                {{ step.step }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-relaxed text-app-fg">{{ step.description }}</p>
                                <div v-if="step.time || step.temperature" class="mt-2 flex flex-wrap gap-2">
                                    <span v-if="step.time" class="app-tag app-tag--muted">时间 {{ formatTime(step.time) }}</span>
                                    <span v-if="step.temperature" class="app-tag app-tag--muted">火候 {{ step.temperature }}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <p v-if="!isExpanded && recipe.steps.length > 3" class="mt-2 text-center text-xs text-app-muted">
                    还有 {{ recipe.steps.length - 3 }} 个步骤，点击“展开详情”查看完整教程
                </p>
            </section>

            <!-- 小贴士 -->
            <section v-if="recipe.tips && recipe.tips.length > 0 && isExpanded" class="rounded-[16px] bg-app-page p-4 ring-1 ring-app-line">
                <h4 class="mb-2 text-sm font-bold text-app-fg">小贴士</h4>
                <ul class="space-y-2">
                    <li v-for="tip in recipe.tips" :key="tip" class="rounded-xl bg-app-card px-3 py-2 text-sm leading-relaxed text-app-fg ring-1 ring-app-line">
                        {{ tip }}
                    </li>
                </ul>
            </section>

            <!-- 营养分析 -->
            <section v-if="isExpanded" class="rounded-[16px] bg-app-page p-4 ring-1 ring-app-line">
                <h4 class="mb-3 text-sm font-bold text-app-fg">营养分析</h4>

                <div v-if="isFetchingNutrition" class="rounded-xl bg-app-card p-6 text-center ring-1 ring-app-line">
                    <div class="mx-auto mb-3 h-12 w-12 animate-spin rounded-full border-4 border-app-line border-t-emerald-500"></div>
                    <h5 class="mb-1 text-sm font-bold text-app-fg">营养师正在分析中...</h5>
                    <p class="text-xs text-app-muted">{{ nutritionLoadingText }}</p>
                </div>

                <div v-else-if="nutritionError" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                    {{ nutritionError }}
                </div>

                <NutritionAnalysis v-if="recipe.nutritionAnalysis" :nutritionAnalysis="recipe.nutritionAnalysis" />

                <div v-else-if="!isFetchingNutrition" class="rounded-xl border border-dashed border-app-line bg-app-card p-6 text-center">
                    <p class="mb-4 text-xs text-app-muted">暂无营养分析数据</p>
                    <button
                        @click="fetchNutritionAnalysis"
                        :disabled="isFetchingNutrition"
                        class="app-btn app-btn--secondary app-btn--sm"
                    >
                        <span class="flex items-center gap-1.5">
                            <template v-if="isFetchingNutrition">
                                <div class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent"></div>
                                获取中...
                            </template>
                            <template v-else>获取营养分析</template>
                        </span>
                    </button>
                </div>
            </section>

            <!-- 饮品搭配 -->
            <section v-if="isExpanded" class="rounded-[16px] bg-app-page p-4 ring-1 ring-app-line">
                <h4 class="mb-3 text-sm font-bold text-app-fg">饮品搭配</h4>

                <div v-if="isFetchingWine" class="rounded-xl bg-app-card p-6 text-center ring-1 ring-app-line">
                    <div class="mx-auto mb-3 h-12 w-12 animate-spin rounded-full border-4 border-app-line border-t-purple-500"></div>
                    <h5 class="mb-1 text-sm font-bold text-app-fg">饮品师正在推荐中...</h5>
                    <p class="text-xs text-app-muted">{{ wineLoadingText }}</p>
                </div>

                <div v-else-if="wineError" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                    {{ wineError }}
                </div>

                <WinePairing v-if="recipe.winePairing" :winePairing="recipe.winePairing" />

                <div v-else-if="!isFetchingWine" class="rounded-xl border border-dashed border-app-line bg-app-card p-6 text-center">
                    <p class="mb-4 text-xs text-app-muted">暂无饮品搭配推荐</p>
                    <button
                        @click="fetchWinePairing"
                        :disabled="isFetchingWine"
                        class="app-btn app-btn--secondary app-btn--sm"
                    >
                        <span class="flex items-center gap-1.5">
                            <template v-if="isFetchingWine">
                                <div class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent"></div>
                                获取中...
                            </template>
                            <template v-else>获取饮品搭配</template>
                        </span>
                    </button>
                </div>
            </section>

            <!-- 效果图区域 -->
            <section class="rounded-[16px] bg-app-page p-4 ring-1 ring-app-line">
                <h4 class="mb-3 text-sm font-bold text-app-fg">菜品效果图</h4>

                <!-- 加载状态 -->
                <div v-if="isGeneratingImage" class="rounded-xl bg-app-card p-6 text-center ring-1 ring-app-line">
                    <div class="mx-auto mb-3 h-12 w-12 animate-spin rounded-full border-4 border-app-line border-t-blue-500"></div>
                    <h5 class="mb-1 text-sm font-bold text-app-fg">AI画师正在创作中...</h5>
                    <p class="text-xs text-app-muted">{{ imageLoadingText }}</p>
                </div>

                <!-- 错误提示 -->
                <div v-else-if="imageError" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                    {{ imageError }}
                </div>

                <!-- 生成的图片 -->
                <div v-else-if="generatedImage" class="mb-3">
                    <img
                        :src="generatedImage.url"
                        :alt="`${recipe.name}效果图`"
                        class="w-full cursor-pointer rounded-[14px] object-cover ring-1 ring-app-line transition-all duration-300 hover:brightness-110 hover:scale-[1.01]"
                        @error="handleImageError"
                        @click="openImageModal"
                    />
                </div>

                <div v-else class="rounded-xl border border-dashed border-app-line bg-app-card p-10 text-center">
                    <p class="mb-4 text-xs text-app-muted">暂无菜品效果图</p>
                    <button
                        @click="generateImage"
                        :disabled="isGeneratingImage"
                        class="app-btn app-btn--secondary app-btn--sm"
                    >
                        <span class="flex items-center gap-1.5">
                            <template v-if="isGeneratingImage">
                                <div class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent"></div>
                                生成中...
                            </template>
                            <template v-else>生成效果图</template>
                        </span>
                    </button>
                </div>

                <div v-if="generatedImage && !isGeneratingImage" class="mt-3 text-center">
                    <button
                        @click="generateImage"
                        class="app-btn app-btn--secondary app-btn--xs"
                    >
                        重新生成
                    </button>
                </div>
            </section>

            <!-- 底部操作区 -->
            <section v-if="showActions" class="sticky bottom-0 z-[5] -mx-3 bg-app-card/95 px-3 pb-1 pt-2 backdrop-blur md:-mx-6 md:px-6">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <button
                        type="button"
                        class="app-btn app-btn--primary app-btn--sm"
                        @click="emit('regenerate')"
                    >
                        再来一份
                    </button>
                    <button
                        type="button"
                        class="app-btn app-btn--secondary app-btn--sm"
                        @click="generateImage"
                    >
                        生成效果图
                    </button>
                    <router-link to="/plaza" class="app-btn app-btn--secondary app-btn--sm">
                        继续探索
                    </router-link>
                </div>
            </section>
        </div>
    </div>

    <!-- 图片弹窗 -->
    <ImageModal v-if="showImageModal && generatedImage" :image="getModalImageData()!" @close="closeImageModal" />
</template>

<script setup lang="ts">
import { computed, ref, onUnmounted } from 'vue'
import type { Recipe } from '@/types'
import { generateRecipeImage, type GeneratedImage } from '@/services/imageService'
import { getNutritionAnalysis, getWinePairing } from '@/services/aiService'
import type { GalleryImage } from '@/services/galleryService'
import FavoriteButton from './FavoriteButton.vue'
import NutritionAnalysis from './NutritionAnalysis.vue'
import WinePairing from './WinePairing.vue'
import ImageModal from './ImageModal.vue'

interface Props {
    recipe: Recipe
    showFavoriteButton?: boolean
    showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    showFavoriteButton: true,
    showActions: true
})

const emit = defineEmits<{
    favoriteChanged: [isFavorited: boolean]
    regenerate: []
}>()
const isExpanded = ref(false)
const isGeneratingImage = ref(false)
const generatedImage = ref<GeneratedImage | null>(null)
const imageError = ref<string>('')
const imageLoadingText = ref('正在构思画面布局...')
const nutritionLoadingText = ref('营养师正在分析中...')
const wineLoadingText = ref('侍酒师正在推荐中...')
const isFetchingNutrition = ref(false)
const nutritionError = ref('')
const isFetchingWine = ref(false)
const wineError = ref('')
const showImageModal = ref(false)

// 图片生成加载文字轮播
const imageLoadingTexts = [
    '正在构思画面布局...',
    '正在调配色彩搭配...',
    '正在绘制食材细节...',
    '正在优化光影效果...',
    '正在精修画面质感...',
    '正在添加最后润色...',
    '精美效果图即将完成...'
]

// 营养分析加载文字轮播
const nutritionLoadingTexts = [
    '营养师正在分析中...',
    '正在计算卡路里...',
    '正在分析蛋白质含量...',
    '正在评估维生素含量...',
    '正在生成健康建议...',
    '正在准备饮食建议...',
    '营养分析即将完成...'
]

// 饮品搭配加载文字轮播
const wineLoadingTexts = [
    '饮品师正在推荐中...',
    '正在匹配口味特征...',
    '正在考虑饮品平衡...',
    '正在评估搭配效果...',
    '正在选择最佳温度...',
    '正在准备搭配理由...',
    '完美搭配即将呈现...'
]

let imageLoadingInterval: ReturnType<typeof setTimeout> | null = null

const difficultyText = computed(() => {
    const difficultyMap = {
        easy: '简单',
        medium: '中等',
        hard: '困难'
    }
    return difficultyMap[props.recipe.difficulty] || '中等'
})

// 格式化时间显示
const formatTime = (minutes: number): string => {
    if (minutes < 60) {
        return `${minutes}分钟`
    }

    const days = Math.floor(minutes / (24 * 60))
    const hours = Math.floor((minutes % (24 * 60)) / 60)
    const remainingMinutes = minutes % 60

    let result = ''

    if (days > 0) {
        result += `${days}天`
    }

    if (hours > 0) {
        result += `${hours}小时`
    }

    if (remainingMinutes > 0) {
        result += `${remainingMinutes}分钟`
    }

    return result || '0分钟'
}

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value
}

// 处理收藏状态变化
const onFavoriteChanged = (isFavorited: boolean) => {
    emit('favoriteChanged', isFavorited)
}

const generateImage = async () => {
    if (isGeneratingImage.value) return

    isGeneratingImage.value = true
    imageError.value = ''

    // 开始图片生成加载文字轮播
    let textIndex = 0
    imageLoadingInterval = setInterval(() => {
        imageLoadingText.value = imageLoadingTexts[textIndex]
        textIndex = (textIndex + 1) % imageLoadingTexts.length
    }, 2000)

    try {
        const image = await generateRecipeImage(props.recipe)
        generatedImage.value = image

        // 将生成的图片添加到图库
        const { GalleryService } = await import('@/services/galleryService')
        const prompt = `一道精美的${props.recipe.cuisine.replace('大师', '').replace('菜', '')}菜肴：${props.recipe.name}`
        GalleryService.addToGallery(props.recipe, image.url, image.id, prompt)
    } catch (error) {
        console.error('生成图片失败:', error)
        imageError.value = 'AI画师表示这道菜太有艺术挑战性了，哈哈！'
    } finally {
        isGeneratingImage.value = false
        if (imageLoadingInterval) {
            clearInterval(imageLoadingInterval)
            imageLoadingInterval = null
        }
    }
}

const handleImageError = () => {
    imageError.value = '图片加载失败'
    generatedImage.value = null
}

const fetchNutritionAnalysis = async () => {
    if (isFetchingNutrition.value) return

    isFetchingNutrition.value = true
    nutritionError.value = ''

    let textIndex = 0
    const interval = setInterval(() => {
        nutritionLoadingText.value = nutritionLoadingTexts[textIndex]
        textIndex = (textIndex + 1) % nutritionLoadingTexts.length
    }, 2000)

    try {
        const analysis = await getNutritionAnalysis(props.recipe)
        props.recipe.nutritionAnalysis = analysis
    } catch (error) {
        console.error('获取营养分析失败:', error)
        nutritionError.value = '获取营养分析失败，请稍后重试'
    } finally {
        isFetchingNutrition.value = false
        clearInterval(interval)
    }
}

const fetchWinePairing = async () => {
    if (isFetchingWine.value) return

    isFetchingWine.value = true
    wineError.value = ''

    let textIndex = 0
    const interval = setInterval(() => {
        wineLoadingText.value = wineLoadingTexts[textIndex]
        textIndex = (textIndex + 1) % wineLoadingTexts.length
    }, 2000)

    try {
        const pairing = await getWinePairing(props.recipe)
        props.recipe.winePairing = pairing
    } catch (error) {
        console.error('获取饮品搭配失败:', error)
        wineError.value = '获取饮品搭配失败，请稍后重试'
    } finally {
        isFetchingWine.value = false
        clearInterval(interval)
    }
}

// 打开图片弹窗
const openImageModal = () => {
    if (generatedImage.value) {
        showImageModal.value = true
    }
}

// 关闭图片弹窗
const closeImageModal = () => {
    showImageModal.value = false
}

// 创建适配ImageModal的图片数据
const getModalImageData = (): GalleryImage | null => {
    if (!generatedImage.value) return null

    return {
        id: generatedImage.value.id,
        url: generatedImage.value.url,
        recipeName: props.recipe.name,
        recipeId: props.recipe.id,
        cuisine: props.recipe.cuisine,
        ingredients: props.recipe.ingredients,
        prompt: `一道精美的${props.recipe.cuisine.replace('大师', '').replace('菜', '')}菜肴：${props.recipe.name}`,
        generatedAt: new Date().toISOString()
    }
}

onUnmounted(() => {
    if (imageLoadingInterval) {
        clearInterval(imageLoadingInterval)
    }
})
</script>

<style scoped>
.recipe-card {
    @apply h-full overflow-hidden rounded-[22px] ring-1 ring-app-line;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.recipe-head {
    box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.55);
}
</style>
