<template>
    <UserPageShell max-width-class="max-w-7xl" padding-class="px-3 pt-3 pb-6 md:px-5 md:pt-4 md:pb-8">
        <div>
            <!-- 页面标题 -->
            <div class="mb-5">
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    封神图鉴
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3ECFF] text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                                <AppStrokeIcon name="image" :size="24" class="text-[#7A57D1]" />
                            </div>
                            <div>
                                <h1 class="text-[18px] font-bold text-gray-800">AI厨艺的视觉宝典</h1>
                                <p class="text-[13px] text-gray-600">共生成了 {{ images.length }} 张菜品图片</p>
                            </div>
                        </div>

                        <!-- 操作按钮 -->
                        <div class="flex items-center gap-2">
                            <button
                                v-if="images.length > 0"
                                @click="showClearConfirm = true"
                                class="app-btn app-btn--secondary app-btn--sm"
                            >
                                清空
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 搜索和筛选 -->
            <div v-if="false" class="mb-6">
                <div class="bg-white border-2 border-[#0A0910] rounded-lg p-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- 搜索框 -->
                        <div class="flex-1">
                            <input
                                v-model="searchQuery"
                                class="app-input text-sm"
                                placeholder="搜索菜谱名称、菜系或食材..."
                            />
                        </div>

                        <!-- 菜系筛选 -->
                        <div class="md:w-48">
                            <select v-model="selectedCuisine" class="app-select text-sm">
                                <option value="">全部菜系</option>
                                <option v-for="cuisine in availableCuisines" :key="cuisine" :value="cuisine">
                                    {{ cuisine }}
                                </option>
                            </select>
                        </div>

                        <!-- 排序 -->
                        <div class="md:w-48">
                            <select v-model="sortBy" class="app-select text-sm">
                                <option value="date-desc">最新生成</option>
                                <option value="date-asc">最早生成</option>
                                <option value="name-asc">菜名 A-Z</option>
                                <option value="name-desc">菜名 Z-A</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 图片网格 -->
            <div v-if="filteredImages.length > 0" class="rounded-[20px] bg-white p-3 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="image in filteredImages"
                        :key="image.id"
                        class="group overflow-hidden rounded-[16px] bg-white ring-1 ring-[#ECEEF2] transition-all duration-200 hover:shadow-lg"
                    >
                        <!-- 图片 -->
                        <div class="relative aspect-[4/3] overflow-hidden cursor-pointer" @click="openImageModal(image)">
                            <img
                                :src="image.url"
                                :alt="image.recipeName"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                @error="handleImageError(image.id)"
                            />

                            <!-- 悬浮信息层 -->
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                            >
                                <!-- 顶部操作按钮 -->
                                <div class="absolute top-3 right-3 flex gap-2 pointer-events-auto">
                                    <button
                                        @click.stop="downloadImage(image)"
                                        class="p-2 bg-blue-500/80 hover:bg-blue-500 text-white rounded-full text-sm transition-colors backdrop-blur-sm"
                                        title="下载图片"
                                    >
                                        <AppStrokeIcon name="arrowLeft" :size="14" class="rotate-[-90deg]" />
                                    </button>
                                    <button
                                        @click.stop="confirmDeleteImage(image.id)"
                                        class="p-2 bg-red-500/80 hover:bg-red-500 text-white rounded-full text-sm transition-colors backdrop-blur-sm"
                                        title="删除图片"
                                    >
                                        <AppStrokeIcon name="inbox" :size="14" />
                                    </button>
                                </div>
                            </div>

                            <!-- 底部信息 -->
                            <div class="absolute bottom-0 left-0 right-0 p-4">
                                <h3 class="mb-2 line-clamp-1 text-[18px] font-bold text-white">{{ image.recipeName }}</h3>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[13px] text-white/90">{{ image.cuisine }}</span>
                                    <span class="text-[12px] text-white/80">{{ formatDate(image.generatedAt) }}</span>
                                </div>

                                <!-- 食材标签 -->
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="ingredient in image.ingredients.slice(0, 4)"
                                        :key="ingredient"
                                        class="bg-white/20 backdrop-blur-sm text-white px-2 py-1 rounded text-xs border border-white/30"
                                    >
                                        {{ ingredient }}
                                    </span>
                                    <span v-if="image.ingredients.length > 4" class="bg-white/10 backdrop-blur-sm text-white/80 px-2 py-1 rounded text-xs border border-white/20">
                                        +{{ image.ingredients.length - 4 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 空状态 / 搜索无结果 -->
            <div v-else-if="images.length === 0" class="py-2">
                <AppStateEmpty
                    stroke="image"
                    title="图库还是空的"
                    description="先在首页生成带图菜谱并保存，成品图会陆续出现在这里，形成你的美食图鉴。"
                    :with-panel="true"
                >
                    <template #actions>
                        <router-link to="/" class="app-btn app-btn--primary app-btn--md inline-flex w-full max-w-xs justify-center sm:w-auto">
                            去首页生成菜谱
                        </router-link>
                    </template>
                </AppStateEmpty>
            </div>

            <div v-else class="py-2">
                <AppStateEmpty
                    stroke="search"
                    title="没有找到匹配的图片"
                    description="试试更换关键词或菜系筛选；若曾关闭筛选区，可先清空条件查看全部作品。"
                    :with-panel="true"
                >
                    <template #actions>
                        <button type="button" class="app-btn app-btn--primary app-btn--md w-full max-w-xs sm:w-auto" @click="clearFilters">
                            清除筛选条件
                        </button>
                    </template>
                </AppStateEmpty>
            </div>
        </div>

        <!-- 图片详情弹窗 -->
        <ImageModal v-if="selectedImage" :image="selectedImage" @close="selectedImage = null" />

        <!-- 删除确认弹窗 -->
        <ConfirmModal v-if="deletingImageId" title="确认删除图片" message="确定要删除这张图片吗？此操作不可恢复。" @confirm="deleteImage" @cancel="deletingImageId = null" />

        <!-- 清空确认弹窗 -->
        <ConfirmModal v-if="showClearConfirm" title="确认清空图库" message="确定要清空所有图片吗？此操作不可恢复。" @confirm="clearAllImages" @cancel="showClearConfirm = false" />

    </UserPageShell>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { GalleryService, type GalleryImage } from '@/services/galleryService'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import ImageModal from '@/components/ImageModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'
import AppStateEmpty from '@/components/ui/AppStateEmpty.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import { showAppToast } from '@/utils/showAppToast'

// 响应式数据
const images = ref<GalleryImage[]>([])
const searchQuery = ref('')
const selectedCuisine = ref('')
const sortBy = ref('date-desc')
const selectedImage = ref<GalleryImage | null>(null)
const deletingImageId = ref<string | null>(null)
const showClearConfirm = ref(false)

// 可用菜系列表
const availableCuisines = computed(() => {
    const cuisines = new Set(images.value.map(img => img.cuisine))
    return Array.from(cuisines).sort()
})

// 筛选后的图片列表
const filteredImages = computed(() => {
    let filtered = [...images.value]

    // 搜索筛选
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(
            img =>
                img.recipeName.toLowerCase().includes(query) ||
                img.cuisine.toLowerCase().includes(query) ||
                img.ingredients.some(ingredient => ingredient.toLowerCase().includes(query))
        )
    }

    // 菜系筛选
    if (selectedCuisine.value) {
        filtered = filtered.filter(img => img.cuisine === selectedCuisine.value)
    }

    // 排序
    filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'date-desc':
                return new Date(b.generatedAt).getTime() - new Date(a.generatedAt).getTime()
            case 'date-asc':
                return new Date(a.generatedAt).getTime() - new Date(b.generatedAt).getTime()
            case 'name-asc':
                return a.recipeName.localeCompare(b.recipeName)
            case 'name-desc':
                return b.recipeName.localeCompare(a.recipeName)
            default:
                return 0
        }
    })

    return filtered
})

// 格式化日期
const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = now.getTime() - date.getTime()
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays === 0) {
        return '今天'
    } else if (diffDays === 1) {
        return '昨天'
    } else if (diffDays < 7) {
        return `${diffDays}天前`
    } else {
        return date.toLocaleDateString('zh-CN', {
            month: 'short',
            day: 'numeric'
        })
    }
}

// 刷新图库
const refreshGallery = () => {
    images.value = GalleryService.getGalleryImages()
}

// 打开图片详情弹窗
const openImageModal = (image: GalleryImage) => {
    selectedImage.value = image
}

// 确认删除图片
const confirmDeleteImage = (imageId: string) => {
    deletingImageId.value = imageId
    selectedImage.value = null
}

// 删除图片
const deleteImage = () => {
    if (!deletingImageId.value) return

    const success = GalleryService.removeFromGallery(deletingImageId.value)
    if (success) {
        refreshGallery()
        showAppToast('图片已删除', 'info')
    } else {
        showAppToast('删除失败', 'error')
    }
    deletingImageId.value = null
}

// 清空所有图片
const clearAllImages = () => {
    const success = GalleryService.clearGallery()
    if (success) {
        refreshGallery()
        showAppToast('图库已清空', 'info')
    } else {
        showAppToast('清空失败', 'error')
    }
    showClearConfirm.value = false
}

// 下载图片
const downloadImage = (image: GalleryImage) => {
    try {
        window.open(image.url, '_blank')
        showAppToast('正在打开图片', 'info')
    } catch (error) {
        console.error('打开图片失败:', error)
        showAppToast('打开失败', 'error')
    }
}

// 处理图片加载错误
const handleImageError = (imageId: string) => {
    console.warn(`图片加载失败: ${imageId}`)
}

// 清除筛选条件
const clearFilters = () => {
    searchQuery.value = ''
    selectedCuisine.value = ''
    sortBy.value = 'date-desc'
}

// 初始化
onMounted(() => {
    refreshGallery()
})
</script>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* 响应式调整 */
@media (max-width: 640px) {
    .grid-cols-1 {
        gap: 1rem;
    }
}
</style>
