<template>
    <UserPageShell max-width-class="max-w-7xl" padding-class="px-3 pt-3 pb-6 md:px-5 md:pt-4 md:pb-8">
        <div>
            <!-- 输入区域 -->
            <div class="mb-6">
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    1. 输入菜名
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <div class="space-y-4">
                        <AppSearch
                            v-model="dishName"
                            placeholder="例如：红烧肉、宫保鸡丁、麻婆豆腐..."
                            input-class="py-3.5 text-[15px] font-medium md:text-sm"
                            @keyup.enter="searchRecipe"
                        />

                        <AppButton
                            variant="primary"
                            block
                            size="lg"
                            class="w-full"
                            :disabled="!dishName.trim() || isLoading"
                            :loading="isLoading"
                            @click="searchRecipe"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <AppStrokeIcon v-if="!isLoading" name="search" :size="18" />
                                <span>{{ isLoading ? 'AI大师思考中...' : '开始学做菜' }}</span>
                            </span>
                        </AppButton>
                    </div>
                </div>
            </div>

            <!-- 菜谱结果 -->
            <div class="mb-6" data-results>
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    2. 制作教程
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <!-- 空状态提示 -->
                    <div v-if="!recipe && !isLoading" class="text-center py-12">
                        <div class="w-16 h-16 bg-gradient-to-br from-gray-300 to-gray-400 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <AppStrokeIcon name="utensils" :size="26" class="text-white" />
                        </div>
                        <h3 class="mb-3 text-[18px] font-bold text-gray-600">等待您的菜名...</h3>
                        <div class="mx-auto max-w-md space-y-2 text-[13px] text-gray-500">
                            <p class="flex items-center justify-center gap-2">
                                <AppStrokeIcon name="sparkles" :size="14" class="text-[#7A57D1]" />
                                <span>输入具体菜名效果更好，如"红烧肉"</span>
                            </p>
                            <p class="flex items-center justify-center gap-2">
                                <AppStrokeIcon name="sparkles" :size="14" class="text-[#7A57D1]" />
                                <span>支持各种菜系：川菜、粤菜、湘菜等</span>
                            </p>
                            <p class="flex items-center justify-center gap-2">
                                <AppStrokeIcon name="scrollText" :size="14" class="text-[#7A57D1]" />
                                <span>包含详细步骤、用料和烹饪技巧</span>
                            </p>
                        </div>
                    </div>

                    <!-- 加载状态 -->
                    <div v-if="isLoading && !recipe" class="text-center py-12">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-lg flex items-center justify-center mx-auto mb-4 animate-pulse">
                            <AppStrokeIcon name="utensils" :size="26" class="text-white" />
                        </div>
                        <h3 class="mb-2 text-[20px] font-bold text-gray-700">AI大师正在为您准备教程...</h3>
                        <p class="text-[13px] text-gray-500">请稍等片刻，精彩内容即将呈现</p>
                        <div class="mt-4">
                            <div class="animate-spin w-8 h-8 border-4 border-pink-400 border-t-transparent rounded-full mx-auto"></div>
                        </div>
                    </div>

                    <!-- 菜谱内容 -->
                    <div v-if="recipe" class="mx-auto max-w-2xl overflow-hidden rounded-[18px] bg-[#FAFBFC] p-1 ring-1 ring-[#ECEEF2]">
                        <RecipeCard :recipe="recipe" :show-actions="true" />
                    </div>
                </div>
          
            </div>

            <!-- 历史记录 -->
            <div v-if="searchHistory.length > 0" class="mb-6">
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    最近搜索
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2]">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="historyItem in searchHistory.slice(0, 8)"
                            :key="historyItem"
                            @click="selectDish(historyItem)"
                            class="rounded-full border border-[#E8DDF5] bg-[#F3ECFF] px-3 py-2 text-[13px] font-medium text-[#5A3BA8] transition-all duration-200 hover:bg-[#ECE0FF]"
                        >
                            {{ historyItem }}
                        </button>
                        <button
                            v-if="searchHistory.length > 0"
                            @click="clearHistory"
                            class="px-3 py-2 text-[13px] text-red-600 hover:text-red-700 underline"
                        >
                            清除历史
                        </button>
                    </div>
                </div>
            </div>


        </div>
    </UserPageShell>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { generateDishRecipeByName } from '@/services/aiService'
import type { Recipe } from '@/types'
import RecipeCard from '@/components/RecipeCard.vue'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import AppSearch from '@/components/ui/AppSearch.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'

// 响应式数据
const dishName = ref('')
const recipe = ref<Recipe | null>(null)
const isLoading = ref(false)
const searchHistory = ref<string[]>([])

// 页面加载时恢复历史记录
onMounted(() => {
    const saved = localStorage.getItem('howToCook_history')
    if (saved) {
        try {
            searchHistory.value = JSON.parse(saved)
        } catch (e) {
            console.error('恢复搜索历史失败:', e)
        }
    }
})

// 选择菜品
const selectDish = (dish: string) => {
    dishName.value = dish
    searchRecipe()
}

// 搜索菜谱
const searchRecipe = async () => {
    if (!dishName.value.trim() || isLoading.value) return

    const searchTerm = dishName.value.trim()
    
    // 添加到历史记录
    if (!searchHistory.value.includes(searchTerm)) {
        searchHistory.value.unshift(searchTerm)
        if (searchHistory.value.length > 20) {
            searchHistory.value = searchHistory.value.slice(0, 20)
        }
        localStorage.setItem('howToCook_history', JSON.stringify(searchHistory.value))
    }

    isLoading.value = true
    recipe.value = null

    try {
        const result = await generateDishRecipeByName(searchTerm)
        recipe.value = result
        
        // 滚动到结果区域
        setTimeout(() => {
            const resultsElement = document.querySelector('[data-results]')
            if (resultsElement) {
                resultsElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }
        }, 100)
    } catch (error) {
        console.error('搜索菜谱失败:', error)
        // 这里可以添加错误提示
    } finally {
        isLoading.value = false
    }
}

// 清除历史记录
const clearHistory = () => {
    searchHistory.value = []
    localStorage.removeItem('howToCook_history')
}
</script>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.6s ease-out;
}
</style>