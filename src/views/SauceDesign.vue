<template>
    <UserPageShell max-width-class="max-w-7xl" padding-class="px-3 pt-3 pb-6 md:px-6 md:pt-4 md:pb-8">
        <div>
            <!-- 智能推荐配置区域 -->
            <div class="mb-8">
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    1. 智能推荐
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- 口味偏好 -->
                        <div>
                            <h3 class="font-bold text-lg mb-4 text-gray-800">口味偏好</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center justify-between">
                                        <span>🌶️ 辣度</span>
                                        <span class="text-red-600 font-bold">{{ preferences.spiceLevel }}</span>
                                    </label>
                                    <input
                                        v-model="preferences.spiceLevel"
                                        type="range"
                                        min="1"
                                        max="5"
                                        class="app-range"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center justify-between">
                                        <span>🍯 甜度</span>
                                        <span class="text-yellow-600 font-bold">{{ preferences.sweetLevel }}</span>
                                    </label>
                                    <input
                                        v-model="preferences.sweetLevel"
                                        type="range"
                                        min="1"
                                        max="5"
                                        class="app-range"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center justify-between">
                                        <span>🧂 咸度</span>
                                        <span class="text-blue-600 font-bold">{{ preferences.saltLevel }}</span>
                                    </label>
                                    <input
                                        v-model="preferences.saltLevel"
                                        type="range"
                                        min="1"
                                        max="5"
                                        class="app-range"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center justify-between">
                                        <span>🍋 酸度</span>
                                        <span class="text-green-600 font-bold">{{ preferences.sourLevel }}</span>
                                    </label>
                                    <input
                                        v-model="preferences.sourLevel"
                                        type="range"
                                        min="1"
                                        max="5"
                                        class="app-range"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 使用场景和食材 -->
                        <div>
                            <h3 class="font-bold text-lg mb-4 text-gray-800">使用场景</h3>
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <button
                                    v-for="useCase in useCaseOptions"
                                    :key="useCase.id"
                                    @click="toggleUseCase(useCase.id)"
                                    :class="[
                                        'p-3 rounded-lg border-2 border-[#0A0910] text-sm font-medium transition-all duration-200',
                                        preferences.useCase.includes(useCase.id) ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    ]"
                                >
                                    <span class="mr-1">{{ useCase.icon }}</span>
                                    {{ useCase.name }}
                                </button>
                            </div>

                            <h3 class="font-bold text-lg mb-2 text-gray-800">现有食材</h3>
                            <div class="relative">
                                <input
                                    v-model="ingredientInput"
                                    class="app-input text-sm"
                                    placeholder="输入食材名称，回车添加..."
                                    @keyup.enter="addIngredient"
                                />
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span
                                    v-for="ingredient in preferences.availableIngredients"
                                    :key="ingredient"
                                    class="inline-flex items-center px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full"
                                >
                                    {{ ingredient }}
                                    <button @click="removeIngredient(ingredient)" class="ml-1 text-orange-600 hover:text-orange-800">×</button>
                                </span>
                            </div>

                            <button
                                @click="getRecommendations"
                                :disabled="isLoadingRecommendations"
                                class="w-full mt-4 bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 disabled:from-gray-400 disabled:to-gray-400 text-white px-4 py-3 rounded-lg font-bold text-sm border-2 border-[#0A0910] transition-all duration-300 disabled:cursor-not-allowed"
                            >
                                <span class="flex items-center gap-2 justify-center">
                                    <template v-if="isLoadingRecommendations">
                                        <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                                        <span>AI推荐中...</span>
                                    </template>
                                    <template v-else>
                                        <span>🤖</span>
                                        <span>获取智能推荐</span>
                                    </template>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 推荐结果区域 -->
            <div v-if="recommendations.length > 0 || isLoadingRecommendations" class="mb-8" data-recommendations>
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    为您推荐
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <!-- 推荐加载状态 -->
                    <AppStateLoading
                        v-if="isLoadingRecommendations"
                        title="AI 正在为您精选酱料…"
                        subtitle="根据口味偏好匹配灵感配方"
                        emoji="🤖"
                        :dots="true"
                    />

                    <!-- 推荐列表 -->
                    <div v-else-if="recommendations.length > 0" class="space-y-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">🎯 根据您的偏好，推荐以下酱料：</h3>
                            <span class="text-sm text-gray-500">共{{ recommendations.length }}种</span>
                        </div>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <button
                                v-for="(sauceName, index) in recommendations"
                                :key="sauceName"
                                @click="selectRecommendedSauce(sauceName)"
                                class="group p-4 bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-[#0A0910] rounded-lg hover:from-purple-100 hover:to-pink-100 transition-all duration-200 transform hover:scale-105 text-left relative overflow-hidden"
                            >
                                <div class="absolute top-2 right-2 w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                                    {{ index + 1 }}
                                </div>
                                <div class="font-bold text-gray-800 mb-2 pr-8">{{ sauceName }}</div>
                                <div class="text-sm text-gray-600 mb-2">点击查看详细制作方法</div>
                                <div class="flex items-center text-xs text-purple-600 group-hover:text-purple-700">
                                    <span class="mr-1">👨‍🍳</span>
                                    <span>AI推荐</span>
                                </div>
                            </button>
                        </div>
                        <div class="text-center pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-500">💡 不满意推荐结果？试试调整上方的口味偏好或使用场景</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 酱料搜索区域 -->
            <div class="mb-8">
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    2. 酱料搜索
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                        <AppSearch
                            v-model="searchQuery"
                            class="min-w-0 flex-1"
                            placeholder="输入酱料名称"
                            input-class="py-4 text-base font-medium md:text-sm"
                            @keyup.enter="searchSauce"
                        />
                        <AppButton
                            variant="primary"
                            size="lg"
                            class="shrink-0 sm:self-stretch"
                            :disabled="!searchQuery.trim() || isLoadingSearch"
                            :loading="isLoadingSearch"
                            @click="searchSauce"
                        >
                            搜索
                        </AppButton>
                    </div>
                </div>
            </div>

            <!-- 制作教程区域 -->
            <div class="mb-8" data-results>
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    3. 制作教程
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <!-- 空状态 -->
                    <AppStateEmpty
                        v-if="!currentSauce && !isLoadingSearch"
                        stroke="droplet"
                        title="开始你的酱料之旅"
                        :with-panel="false"
                    >
                        <div class="mx-auto mt-2 max-w-md space-y-2 text-[13px] leading-relaxed text-app-muted">
                            <p class="flex items-center justify-center gap-2">
                                <AppStrokeIcon name="sliders" :size="16" class="shrink-0 text-app-accent" />
                                配置口味偏好，获取个性化推荐
                            </p>
                            <p class="flex items-center justify-center gap-2">
                                <AppStrokeIcon name="search" :size="16" class="shrink-0 text-app-accent" />
                                或直接搜索想做的酱料名称
                            </p>
                        </div>
                    </AppStateEmpty>

                    <AppStateLoading
                        v-if="isLoadingSearch"
                        title="酱料大师正在调配配方…"
                        subtitle="请稍等片刻，美味秘方即将呈现"
                        emoji="👨‍🍳"
                        :dots="true"
                    />

                    <!-- 酱料详情 -->
                    <div v-if="currentSauce" class="max-w-4xl mx-auto">
                        <SauceRecipeComponent :sauce="currentSauce" />
                    </div>
                </div>
            </div>

            <!-- 历史记录 -->
            <div v-if="searchHistory.length > 0" class="mb-8">
                <div class="bg-gray-600 text-white px-4 py-2 rounded-t-lg border-2 border-[#0A0910] border-b-0 inline-block">
                    <span class="font-bold">📚 最近查看</span>
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2]">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="historyItem in searchHistory.slice(0, 8)"
                            :key="historyItem"
                            @click="selectHistoryItem(historyItem)"
                            class="px-3 py-2 text-sm bg-orange-100 text-orange-700 rounded-full border border-orange-300 hover:bg-orange-200 hover:border-orange-400 transition-all duration-200"
                        >
                            {{ historyItem }}
                        </button>
                        <button @click="clearHistory" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 underline">清除历史</button>
                    </div>
                </div>
            </div>
        </div>

    </UserPageShell>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { generateSauceRecipe, recommendSauces } from '@/services/aiService'
import type { SauceRecipe, SaucePreference } from '@/types'
import { useCaseOptions } from '@/config/sauces'
import SauceRecipeComponent from '@/components/SauceRecipe.vue'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import AppStateEmpty from '@/components/ui/AppStateEmpty.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import AppStateLoading from '@/components/ui/AppStateLoading.vue'
import { showAppToast } from '@/utils/showAppToast'
import AppSearch from '@/components/ui/AppSearch.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { addSauceRecord } from '@/services/sauceRecordService'

// 响应式数据
const searchQuery = ref('')
const ingredientInput = ref('')
const currentSauce = ref<SauceRecipe | null>(null)
const recommendations = ref<string[]>([])
const searchHistory = ref<string[]>([])
const isLoadingSearch = ref(false)
const isLoadingRecommendations = ref(false)

// 用户偏好配置
const preferences = ref<SaucePreference>({
    spiceLevel: 3,
    sweetLevel: 2,
    saltLevel: 3,
    sourLevel: 2,
    useCase: [],
    availableIngredients: []
})

// 页面加载时恢复历史记录
onMounted(() => {
    const saved = localStorage.getItem('sauceDesign_history')
    if (saved) {
        try {
            searchHistory.value = JSON.parse(saved)
        } catch (e) {
            console.error('恢复搜索历史失败:', e)
        }
    }
})

// 切换使用场景
const toggleUseCase = (useCaseId: string) => {
    const index = preferences.value.useCase.indexOf(useCaseId)
    if (index > -1) {
        preferences.value.useCase.splice(index, 1)
    } else {
        preferences.value.useCase.push(useCaseId)
    }
}

// 添加食材
const addIngredient = () => {
    const ingredient = ingredientInput.value.trim()
    if (ingredient && !preferences.value.availableIngredients.includes(ingredient)) {
        preferences.value.availableIngredients.push(ingredient)
        ingredientInput.value = ''
    }
}

// 移除食材
const removeIngredient = (ingredient: string) => {
    const index = preferences.value.availableIngredients.indexOf(ingredient)
    if (index > -1) {
        preferences.value.availableIngredients.splice(index, 1)
    }
}

// 获取智能推荐
const getRecommendations = async () => {
    isLoadingRecommendations.value = true
    currentSauce.value = null

    try {
        const result = await recommendSauces(preferences.value)
        recommendations.value = result

        if (result.length === 0) {
            showErrorMessage('暂时没有找到合适的推荐，请尝试调整偏好设置')
        } else {
            // 滚动到推荐区域
            setTimeout(() => {
                const recommendationsElement = document.querySelector('[data-recommendations]')
                if (recommendationsElement) {
                    recommendationsElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
                }
            }, 100)
        }
    } catch (error) {
        console.error('获取推荐失败:', error)
        showErrorMessage('获取推荐失败，请检查网络连接后重试')
    } finally {
        isLoadingRecommendations.value = false
    }
}

// 搜索酱料
const searchSauce = async () => {
    if (!searchQuery.value.trim() || isLoadingSearch.value) return

    const sauceName = searchQuery.value.trim()
    addToHistory(sauceName)

    isLoadingSearch.value = true
    recommendations.value = []
    currentSauce.value = null

    try {
        const result = await generateSauceRecipe(sauceName)
        currentSauce.value = result
        await addSauceRecord(result)

        // 滚动到结果区域
        setTimeout(() => {
            const resultsElement = document.querySelector('[data-results]')
            if (resultsElement) {
                resultsElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }
        }, 100)
    } catch (error) {
        console.error('搜索酱料失败:', error)
        showErrorMessage('酱料大师表示这个配方太有挑战性了，哈哈！换个搭配试试吧~')
    } finally {
        isLoadingSearch.value = false
    }
}

// 选择推荐的酱料
const selectRecommendedSauce = async (sauceName: string) => {
    addToHistory(sauceName)

    isLoadingSearch.value = true
    currentSauce.value = null

    try {
        const result = await generateSauceRecipe(sauceName)
        currentSauce.value = result
        await addSauceRecord(result)

        // 滚动到结果区域
        setTimeout(() => {
            const resultsElement = document.querySelector('[data-results]')
            if (resultsElement) {
                resultsElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }
        }, 100)
    } catch (error) {
        console.error('获取酱料配方失败:', error)
        showErrorMessage('酱料大师挠了挠头说："这个配方我还没学会呢！"换个试试吧~')
    } finally {
        isLoadingSearch.value = false
    }
}

// 选择历史记录项
const selectHistoryItem = (sauceName: string) => {
    searchQuery.value = sauceName
    searchSauce()
}

// 添加到历史记录
const addToHistory = (sauceName: string) => {
    if (!searchHistory.value.includes(sauceName)) {
        searchHistory.value.unshift(sauceName)
        if (searchHistory.value.length > 20) {
            searchHistory.value = searchHistory.value.slice(0, 20)
        }
        localStorage.setItem('sauceDesign_history', JSON.stringify(searchHistory.value))
    }
}

// 清除历史记录
const clearHistory = () => {
    searchHistory.value = []
    localStorage.removeItem('sauceDesign_history')
}

const showErrorMessage = (message: string) => {
    showAppToast(`⚠️ ${message}`, 'error')
}
</script>

<style scoped>
/* 自定义滑块样式 */
.slider-red::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #ef4444;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider-yellow::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #eab308;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider-blue::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider-green::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #22c55e;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider-red::-moz-range-thumb,
.slider-yellow::-moz-range-thumb,
.slider-blue::-moz-range-thumb,
.slider-green::-moz-range-thumb {
    height: 20px;
    width: 20px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
