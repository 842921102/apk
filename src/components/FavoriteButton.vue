<template>
    <button
        @click="toggleFavorite"
        :disabled="isLoading"
        class="favorite-icon p-2 rounded-full transition-all duration-200 hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
        :title="isFavorited ? '取消收藏' : '收藏菜谱'"
    >
        <span class="text-xl transition-transform duration-200" :class="{ 'animate-pulse': isLoading }">
            {{ isFavorited ? '❤️' : '🤍' }}
        </span>
    </button>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import type { Recipe } from '@/types'
import { addFavorite, isFavoriteByTitle, deleteFavoriteByTitle } from '@/services/favoriteService'
import { showAppToast } from '@/utils/showAppToast'

interface Props {
    recipe: Recipe
}

const props = defineProps<Props>()

const emit = defineEmits<{
    favoriteChanged: [isFavorited: boolean]
}>()

const isLoading = ref(false)
const isFavorited = ref(false)

// 把 Recipe 转成文本内容
const buildRecipeContent = (recipe: Recipe) => {
    const title = recipe.name || '未命名菜谱'
    const cuisine = recipe.cuisine || ''
    const ingredients = Array.isArray(recipe.ingredients) ? recipe.ingredients.join('、') : ''

    const steps = Array.isArray(recipe.steps)
        ? recipe.steps.map(item => `${item.step}. ${item.description}`).join('\n')
        : ''

    return [
        `菜名：${title}`,
        cuisine ? `菜系：${cuisine}` : '',
        ingredients ? `食材：${ingredients}` : '',
        steps ? `步骤：\n${steps}` : ''
    ]
        .filter(Boolean)
        .join('\n\n')
}

// 检查收藏状态
const checkFavoriteStatus = async () => {
    try {
        const data = await isFavoriteByTitle(props.recipe.name)
        isFavorited.value = !!data
    } catch (error) {
        console.error('检查收藏状态失败:', error)
        isFavorited.value = false
    }
}

// 切换收藏状态
const toggleFavorite = async () => {
    if (isLoading.value) return

    isLoading.value = true

    try {
        if (isFavorited.value) {
            await deleteFavoriteByTitle(props.recipe.name)
            isFavorited.value = false
            showAppToast('已取消收藏', 'info')
        } else {
            await addFavorite({
                title: props.recipe.name,
                cuisine: props.recipe.cuisine,
                ingredients: props.recipe.ingredients,
                recipe_content: buildRecipeContent(props.recipe),
                image_url: ''
            })

            isFavorited.value = true
            showAppToast('已添加到收藏', 'success')
        }

        emit('favoriteChanged', isFavorited.value)
    } catch (error) {
        console.error('收藏操作失败:', error)
        showAppToast(error instanceof Error ? error.message : '操作失败，请重试', 'error')
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    checkFavoriteStatus()
})

watch(
    () => props.recipe,
    () => {
        checkFavoriteStatus()
    },
    { deep: true }
)
</script>

<style scoped>
.favorite-icon {
    background: transparent;
    border: none;
}

.favorite-icon:hover:not(:disabled) {
    background: rgba(0, 0, 0, 0.05);
}

.favorite-icon:active:not(:disabled) {
    transform: scale(0.95);
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 1s ease-in-out infinite;
}
</style>