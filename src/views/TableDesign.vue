<template>
    <UserPageShell max-width-class="max-w-7xl" padding-class="px-3 pt-3 pb-6 md:px-5 md:pt-4 md:pb-8">
        <div>
            <!-- 步骤1和2: 左右布局 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- 左侧: 步骤1 菜品配置 -->
                <div class="">
                    <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                        1. 菜品配置
                    </div>
                    <div class="mt-2 h-full rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2]">
                        <!-- 生成模式选择 - 紧凑布局 -->
                        <div class="mb-4">
                            <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-[#222222]">
                                <AppStrokeIcon name="utensils" :size="20" class="text-[#7A57D1]" />
                                <span>选择生成模式</span>
                            </h3>
                            <div class="grid grid-cols-1 gap-3">
                                <button
                                    @click="config.flexibleCount = false"
                                    :class="[
                                        'flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold transition-all duration-200 ring-1',
                                        !config.flexibleCount ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-[#FAFBFC] text-[#8A8F99] ring-[#ECEEF2] hover:bg-white'
                                    ]"
                                >
                                    <AppStrokeIcon name="zap" :size="18" class="text-current" />
                                    <div>
                                        <div class="font-bold text-sm">固定数量模式</div>
                                        <div class="text-xs opacity-75">指定确切菜品数量</div>
                                    </div>
                                </button>
                                <button
                                    @click="config.flexibleCount = true"
                                    :class="[
                                        'flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold transition-all duration-200 ring-1',
                                        config.flexibleCount ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-[#FAFBFC] text-[#8A8F99] ring-[#ECEEF2] hover:bg-white'
                                    ]"
                                >
                                    <AppStrokeIcon name="sparkles" :size="18" class="text-current" />
                                    <div>
                                        <div class="font-bold text-sm">智能搭配模式</div>
                                        <div class="text-xs opacity-75">AI智能决定数量和搭配</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- 配置内容 - 紧凑布局 -->
                        <div class="space-y-4">
                            <!-- 固定数量模式配置 -->
                            <div v-if="!config.flexibleCount">
                                <!-- 数量选择 -->
                                <div class="rounded-xl bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]">
                                    <h5 class="text-sm font-bold text-gray-700 mb-2 flex items-center gap-1"><AppStrokeIcon name="utensils" :size="16" class="text-[#7A57D1]" /> 菜品数量</h5>
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <div class="flex gap-2">
                                            <button
                                                v-for="count in [2, 4, 6, 8]"
                                                :key="count"
                                                @click="config.dishCount = count"
                                                :class="[
                                                    'rounded-lg px-3 py-1 text-sm font-semibold transition-all duration-200 ring-1',
                                                    config.dishCount === count ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-white text-[#8A8F99] ring-[#ECEEF2] hover:bg-[#FAFBFC]'
                                                ]"
                                            >
                                                {{ count }}道
                                            </button>
                                        </div>
                                        <div class="h-4 w-px bg-gray-300"></div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600">自定义</span>
                                            <input
                                                v-model.number="config.dishCount"
                                                class="app-input app-input--compact w-14 text-center font-bold"
                                                type="number"
                                                min="1"
                                                max="20"
                                                @input="validateDishCount"
                                            />
                                            <span class="text-sm text-gray-600">道</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 可选菜品 -->
                                <div class="rounded-xl bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]">
                                    <h5 class="text-sm font-bold text-gray-700 mb-2 flex items-center gap-1"><AppStrokeIcon name="clipboardList" :size="16" class="text-[#7A57D1]" /> 指定菜品（可选）</h5>
                                    <div v-if="config.customDishes.length > 0" class="mb-3">
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="dish in config.customDishes"
                                                :key="dish"
                                                class="inline-flex items-center gap-1 rounded-full bg-[#F3ECFF] px-2 py-1 text-sm font-medium text-[#5A3BA8] ring-1 ring-[#E8DDF5]"
                                            >
                                                {{ dish }}
                                                <button @click="removeCustomDish(dish)" class="hover:bg-yellow-500 rounded-full p-1 transition-colors">
                                                    <span class="text-xs">✕</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input
                                            v-model="currentCustomDish"
                                            class="app-input app-input--compact pr-[4.25rem] text-sm font-medium"
                                            placeholder="输入菜品名称，按回车添加..."
                                            @keyup.enter="addCustomDish"
                                        />
                                        <button
                                            type="button"
                                            class="app-btn app-btn--primary app-btn--xs absolute right-2 top-1/2 -translate-y-1/2"
                                            :disabled="!currentCustomDish.trim() || config.customDishes.length >= 10"
                                            @click="addCustomDish"
                                        >
                                            添加
                                        </button>
                                    </div>
                                    <div class="flex justify-between items-center mt-1 text-xs text-gray-500">
                                        <span>💡 例如：红烧肉、清蒸鱼</span>
                                        <span>{{ config.customDishes.length }}/10</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 智能搭配模式配置 -->
                            <div v-else>
                                <div class="rounded-xl bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]">
                                    <h5 class="text-sm font-bold text-gray-700 mb-2 flex items-center gap-1"><AppStrokeIcon name="clipboardList" :size="16" class="text-[#7A57D1]" /> 输入想要的菜品</h5>
                                    <div v-if="config.customDishes.length === 0" class="mb-3 rounded-lg bg-[#FFF8F1] p-2 ring-1 ring-[#F6D9B4]">
                                        <p class="text-xs text-orange-700">
                                            <span class="font-medium">请先输入至少一道菜品</span>
                                        </p>
                                    </div>
                                    <div v-if="config.customDishes.length > 0" class="mb-3">
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="dish in config.customDishes"
                                                :key="dish"
                                                class="inline-flex items-center gap-1 rounded-full bg-[#EEF8F1] px-2 py-1 text-sm font-medium text-[#2F7A4C] ring-1 ring-[#CFECD9]"
                                            >
                                                {{ dish }}
                                                <button @click="removeCustomDish(dish)" class="hover:bg-green-500 rounded-full p-1 transition-colors">
                                                    <span class="text-xs">✕</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input
                                            v-model="currentCustomDish"
                                            class="app-input app-input--compact pr-[4.25rem] text-sm font-medium"
                                            placeholder="输入菜品名称，按回车添加..."
                                            @keyup.enter="addCustomDish"
                                        />
                                        <button
                                            type="button"
                                            class="app-btn app-btn--primary app-btn--xs absolute right-2 top-1/2 -translate-y-1/2"
                                            :disabled="!currentCustomDish.trim() || config.customDishes.length >= 10"
                                            @click="addCustomDish"
                                        >
                                            添加
                                        </button>
                                    </div>
                                    <div class="flex justify-between items-center mt-1 text-xs text-gray-500">
                                        <span>💡 例如：包菜、娃娃菜、土豆</span>
                                        <span>{{ config.customDishes.length }}/10</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 右侧: 步骤2 偏好设置（可选） -->
                <div class="mt-10">
                    <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                        2. 偏好设置（可选）
                    </div>
                    <div class="mt-2 h-full rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2]">
                        <!-- 提示信息 -->
                        <div class="mb-4 rounded-lg bg-[#F3ECFF]/55 p-3 ring-1 ring-[#E8DDF5]">
                            <p class="text-sm text-blue-700">
                                <span class="font-medium">💡 可选配置：</span>
                                以下设置为可选项，不设置也能生成精彩菜单。
                            </p>
                        </div>

                        <!-- 可折叠的配置选项 -->
                        <div class="space-y-4">
                            <!-- 口味和风格设置 -->
                            <div class="rounded-lg ring-1 ring-[#ECEEF2]">
                                <button
                                    @click="showTasteSettings = !showTasteSettings"
                                    class="flex w-full items-center justify-between rounded-lg bg-[#FAFBFC] px-4 py-3 text-left font-medium text-[#222222] transition-colors hover:bg-white"
                                >
                                    <div class="flex items-center gap-2">
                                        <AppStrokeIcon name="utensils" :size="18" class="text-[#7A57D1]" />
                                        <span class="font-bold text-gray-800 text-sm">口味和风格设置</span>
                                    </div>
                                    <span class="text-gray-500 transform transition-transform" :class="{ 'rotate-180': showTasteSettings }">▼</span>
                                </button>

                                <Transition name="collapse">
                                    <div v-show="showTasteSettings" class="space-y-6 border-t border-[#ECEEF2] p-4">
                                        <!-- 口味偏好 -->
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-1"><AppStrokeIcon name="droplet" :size="16" class="text-[#7A57D1]" /> 口味偏好</h5>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                <button
                                                    v-for="taste in tasteOptions"
                                                    :key="taste.id"
                                                    @click="toggleTaste(taste.id)"
                                                    :class="[
                                                        'flex items-center justify-center gap-1 rounded-lg p-2 text-xs font-medium transition-all duration-200 ring-1',
                                                        config.tastes.includes(taste.id) ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-[#FAFBFC] text-[#8A8F99] ring-[#ECEEF2] hover:bg-white'
                                                    ]"
                                                >
                                                    <AppStrokeIcon :name="taste.icon" :size="14" />
                                                    <span>{{ taste.name }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 菜系风格 -->
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-1"><AppStrokeIcon name="layoutGrid" :size="16" class="text-[#7A57D1]" /> 菜系风格</h5>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                                <button
                                                    v-for="style in cuisineStyles"
                                                    :key="style.id"
                                                    @click="config.cuisineStyle = style.id"
                                                    :class="[
                                                        'flex items-center justify-center gap-1 rounded-lg p-2 text-xs font-medium transition-all duration-200 ring-1',
                                                        config.cuisineStyle === style.id ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-[#FAFBFC] text-[#8A8F99] ring-[#ECEEF2] hover:bg-white'
                                                    ]"
                                                >
                                                    <AppStrokeIcon :name="style.icon" :size="14" />
                                                    <span>{{ style.name }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 用餐场景 -->
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-1"><AppStrokeIcon name="users" :size="16" class="text-[#7A57D1]" /> 用餐场景</h5>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                <button
                                                    v-for="scene in diningScenes"
                                                    :key="scene.id"
                                                    @click="config.diningScene = scene.id"
                                                    :class="[
                                                        'flex items-center justify-center gap-1 rounded-lg p-2 text-xs font-medium transition-all duration-200 ring-1',
                                                        config.diningScene === scene.id ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-[#FAFBFC] text-[#8A8F99] ring-[#ECEEF2] hover:bg-white'
                                                    ]"
                                                >
                                                    <AppStrokeIcon :name="scene.icon" :size="14" />
                                                    <span>{{ scene.name }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- 营养和特殊要求设置 -->
                            <div class="rounded-lg ring-1 ring-[#ECEEF2]">
                                <button
                                    @click="showNutritionSettings = !showNutritionSettings"
                                    class="flex w-full items-center justify-between rounded-lg bg-[#FAFBFC] px-4 py-3 text-left font-medium text-[#222222] transition-colors hover:bg-white"
                                >
                                    <div class="flex items-center gap-2">
                                        <AppStrokeIcon name="chartBar" :size="18" class="text-[#7A57D1]" />
                                        <span class="font-bold text-gray-800 text-sm">营养和特殊要求</span>
                                    </div>
                                    <span class="text-gray-500 transform transition-transform" :class="{ 'rotate-180': showNutritionSettings }">▼</span>
                                </button>

                                <Transition name="collapse">
                                    <div v-show="showNutritionSettings" class="space-y-6 border-t border-[#ECEEF2] p-4">
                                        <!-- 营养搭配 -->
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-1"><AppStrokeIcon name="chartBar" :size="16" class="text-[#7A57D1]" /> 营养搭配</h5>
                                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                                                <button
                                                    v-for="nutrition in nutritionOptions"
                                                    :key="nutrition.id"
                                                    @click="config.nutritionFocus = nutrition.id"
                                                    :class="[
                                                        'flex items-center justify-center gap-1 rounded-lg p-2 text-xs font-medium transition-all duration-200 ring-1',
                                                        config.nutritionFocus === nutrition.id ? 'bg-[#F3ECFF] text-[#5A3BA8] ring-[#E8DDF5]' : 'bg-[#FAFBFC] text-[#8A8F99] ring-[#ECEEF2] hover:bg-white'
                                                    ]"
                                                >
                                                    <AppStrokeIcon :name="nutrition.icon" :size="14" />
                                                    <span>{{ nutrition.name }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 特殊要求 -->
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-1"><AppStrokeIcon name="scrollText" :size="16" class="text-[#7A57D1]" /> 特殊要求</h5>
                                            <textarea
                                                v-model="config.customRequirement"
                                                placeholder="例如：不要太油腻，适合老人小孩，有一道汤..."
                                                class="app-input text-sm"
                                                rows="3"
                                                maxlength="200"
                                            ></textarea>
                                            <div class="text-xs text-gray-500 mt-1 text-right">{{ config.customRequirement.length }}/200</div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- 当前配置预览（简化版） -->
                        <div class="mt-6 rounded-lg bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]">
                            <h6 class="font-bold text-sm text-gray-700 mb-2 flex items-center gap-2">
                                <AppStrokeIcon name="clipboardList" :size="16" class="text-[#7A57D1]" />
                                <span>当前配置</span>
                            </h6>
                            <div class="text-xs text-gray-600 space-y-1">
                                <div>生成模式：{{ config.flexibleCount ? '智能搭配' : '固定数量' }}</div>
                                <div v-if="!config.flexibleCount">菜品数量：{{ config.dishCount }}道菜</div>
                                <div v-if="config.customDishes.length > 0">{{ config.flexibleCount ? '输入菜品' : '指定菜品' }}：{{ config.customDishes.join('、') }}</div>
                                <div v-if="config.tastes.length > 0">口味：{{ config.tastes.map(t => tasteOptions.find(opt => opt.id === t)?.name).join('、') }}</div>
                                <div v-if="config.cuisineStyle !== 'mixed'">风格：{{ cuisineStyles.find(s => s.id === config.cuisineStyle)?.name }}</div>
                                <div v-if="config.diningScene !== 'family'">场景：{{ diningScenes.find(s => s.id === config.diningScene)?.name }}</div>
                                <div v-if="config.nutritionFocus !== 'balanced'">营养：{{ nutritionOptions.find(n => n.id === config.nutritionFocus)?.name }}</div>
                                <div v-if="config.customRequirement">特殊要求：{{ config.customRequirement }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 步骤2: 生成一桌菜 -->
            <div class="mb-6 mt-16">
                <div class="inline-flex rounded-full bg-[#F3ECFF] px-3 py-1 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]">
                    3. 生成一桌菜
                </div>
                <div class="mt-2 rounded-[20px] bg-white p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:p-6">
                    <!-- 生成按钮区域 -->
                    <div v-if="!isGenerating && generatedDishes.length === 0" class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <AppStrokeIcon name="utensils" :size="28" class="text-white" />
                        </div>
                        <h2 class="mb-2 text-xl font-bold text-[#222222]">准备生成一桌菜</h2>
                        <p class="text-gray-600 mb-6 text-sm">AI大师已准备就绪，点击按钮开始设计您的专属菜单</p>

                        <div class="space-y-3">
                            <button
                                @click="generateTableMenuAction"
                                :disabled="isGenerating || (config.flexibleCount && config.customDishes.length === 0)"
                                class="app-btn app-btn--primary app-btn--lg disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <span class="flex items-center gap-2 justify-center">
                                    <AppStrokeIcon name="sparkles" :size="18" class="text-white" />
                                    <span>交给大师</span>
                                </span>
                            </button>

                            <!-- 智能搭配模式提示 -->
                            <div v-if="config.flexibleCount && config.customDishes.length === 0" class="mt-3 rounded-lg bg-[#FFF8F1] p-3 ring-1 ring-[#F6D9B4]">
                                <p class="text-sm text-orange-700 text-center">
                                    <span class="font-medium">请先在步骤1中输入至少一道菜品</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 生成中状态 -->
                    <div v-if="isGenerating" class="text-center py-8">
                        <div class="animate-spin w-16 h-16 border-4 border-orange-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ generatingText }}</h3>
                        <p class="text-gray-600">AI大师正在为您精心搭配...</p>
                    </div>

                    <!-- 生成结果 -->
                    <div v-if="!isGenerating && generatedDishes.length > 0">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <AppStrokeIcon name="sparkles" :size="18" class="text-[#7A57D1]" />
                                <span>您的专属一桌菜</span>
                            </h3>
                            <button @click="resetConfig" class="app-btn app-btn--secondary app-btn--sm">
                                重新生成
                            </button>
                        </div>

                        <!-- 菜品列表 -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div
                                v-for="(dish, index) in generatedDishes"
                                :key="index"
                                class="rounded-[16px] bg-white p-4 transition-colors ring-1 ring-[#ECEEF2] hover:bg-[#FAFBFC]"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-lg font-bold text-[#222222]">{{ dish.name }}</h4>
                                    <span class="rounded-full bg-[#F3ECFF] px-2 py-1 text-xs font-medium text-[#7A57D1] ring-1 ring-[#E8DDF5]">{{ dish.category }}</span>
                                </div>
                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ dish.description }}</p>
                                <div class="flex justify-between items-center">
                                    <div class="flex gap-1 flex-wrap">
                                        <span v-for="tag in dish.tags" :key="tag" class="rounded bg-[#F3ECFF] px-2 py-1 text-xs font-medium text-[#5A3BA8]">
                                            {{ tag }}
                                        </span>
                                    </div>
                                    <button
                                        @click="generateDishRecipeAction(dish, index)"
                                        :disabled="dish.isGeneratingRecipe"
                                        :class="[
                                            'rounded-lg px-3 py-2 text-sm font-semibold text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed',
                                            dish.recipe
                                                ? 'bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600'
                                                : 'bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600'
                                        ]"
                                    >
                                        <span v-if="dish.isGeneratingRecipe" class="flex items-center gap-1">
                                            <div class="w-3 h-3 border border-white border-t-transparent rounded-full animate-spin"></div>
                                            <span>生成中</span>
                                        </span>
                                        <span v-else-if="dish.recipe">查看菜谱</span>
                                        <span v-else>生成菜谱</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </UserPageShell>

    <!-- 菜谱弹窗 -->
    <Teleport to="body">
        <div v-if="selectedRecipe" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4 modal-overlay" @click="closeRecipeModal">
            <div
                class="modal-content w-full max-h-[90vh] max-w-4xl overflow-hidden rounded-[22px] bg-white shadow-2xl ring-1 ring-[#ECEEF2] transition-all duration-300"
                @click.stop
            >
                <!-- 弹窗头部 -->
                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <AppStrokeIcon name="bookOpen" :size="22" class="text-white" />
                        <h3 class="text-xl font-bold">{{ selectedRecipe.name }}</h3>
                    </div>
                    <button
                        @click="closeRecipeModal"
                        class="w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110"
                    >
                        <span class="text-white text-lg font-bold">✕</span>
                    </button>
                </div>

                <!-- 弹窗内容 -->
                <div class="max-h-[calc(90vh-80px)] overflow-y-auto scrollbar-hide">
                    <div class="">
                        <RecipeCard :recipe="selectedRecipe" />
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, Teleport, Transition, onMounted, onUnmounted } from 'vue'
import type { Recipe } from '@/types'
import RecipeCard from '@/components/RecipeCard.vue'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import type { StrokeIconName } from '@/components/icons/strokeIconPaths'
import { generateTableMenu, generateDishRecipe } from '@/services/aiService'

// 配置选项
interface TableConfig {
    dishCount: number
    flexibleCount: boolean
    tastes: string[]
    cuisineStyle: string
    diningScene: string
    nutritionFocus: string
    customRequirement: string
    customDishes: string[]
}

// 菜品信息
interface DishInfo {
    name: string
    description: string
    category: string
    tags: string[]
    recipe?: Recipe
    isGeneratingRecipe?: boolean
}

// 状态管理
const isGenerating = ref(false)
const generatingText = ref('正在生成菜单...')
const generatedDishes = ref<DishInfo[]>([])
const selectedRecipe = ref<Recipe | null>(null)

// 折叠状态管理
const showTasteSettings = ref(false)
const showNutritionSettings = ref(false)

// 配置
const config = reactive<TableConfig>({
    dishCount: 6,
    flexibleCount: true, // 默认开启智能搭配模式
    tastes: [],
    cuisineStyle: 'mixed',
    diningScene: 'family',
    nutritionFocus: 'balanced',
    customRequirement: '',
    customDishes: []
})

// 自定义菜品输入
const currentCustomDish = ref('')

// 配置选项数据
const tasteOptions: { id: string; name: string; icon: StrokeIconName }[] = [
    { id: 'spicy', name: '麻辣', icon: 'zap' },
    { id: 'sweet', name: '甜味', icon: 'sparkles' },
    { id: 'sour', name: '酸味', icon: 'droplet' },
    { id: 'salty', name: '咸鲜', icon: 'utensils' },
    { id: 'light', name: '清淡', icon: 'moon' },
    { id: 'rich', name: '浓郁', icon: 'heart' }
]

const cuisineStyles: { id: string; name: string; icon: StrokeIconName }[] = [
    { id: 'mixed', name: '混合菜系', icon: 'layoutGrid' },
    { id: 'chinese', name: '中式为主', icon: 'utensils' },
    { id: 'western', name: '西式为主', icon: 'bookOpen' },
    { id: 'japanese', name: '日式为主', icon: 'moon' }
]

const diningScenes: { id: string; name: string; icon: StrokeIconName }[] = [
    { id: 'family', name: '家庭聚餐', icon: 'users' },
    { id: 'friends', name: '朋友聚会', icon: 'sparkles' },
    { id: 'romantic', name: '浪漫晚餐', icon: 'heart' },
    { id: 'business', name: '商务宴请', icon: 'clipboardList' },
    { id: 'festival', name: '节日庆祝', icon: 'calendar' },
    { id: 'casual', name: '日常用餐', icon: 'utensils' }
]

const nutritionOptions: { id: string; name: string; icon: StrokeIconName }[] = [
    { id: 'balanced', name: '营养均衡', icon: 'chartBar' },
    { id: 'protein', name: '高蛋白', icon: 'zap' },
    { id: 'vegetarian', name: '素食为主', icon: 'moon' },
    { id: 'low_fat', name: '低脂健康', icon: 'clock' },
    { id: 'comfort', name: '滋补养生', icon: 'heart' }
]

// 切换口味选择
const toggleTaste = (tasteId: string) => {
    const index = config.tastes.indexOf(tasteId)
    if (index > -1) {
        config.tastes.splice(index, 1)
    } else {
        config.tastes.push(tasteId)
    }
}

// 增加菜品数量 - 暂时未使用
// const increaseDishCount = () => {
//     if (config.dishCount < 20) {
//         config.dishCount++
//     }
// }

// 减少菜品数量 - 暂时未使用
// const decreaseDishCount = () => {
//     if (config.dishCount > 1) {
//         config.dishCount--
//     }
// }

// 验证菜品数量输入
const validateDishCount = (event: Event) => {
    const target = event.target as HTMLInputElement
    let value = parseInt(target.value)

    if (isNaN(value) || value < 1) {
        config.dishCount = 1
    } else if (value > 20) {
        config.dishCount = 20
    } else {
        config.dishCount = value
    }
}

// 添加自定义菜品
const addCustomDish = () => {
    const dish = currentCustomDish.value.trim()
    if (dish && !config.customDishes.includes(dish) && config.customDishes.length < 10) {
        config.customDishes.push(dish)
        currentCustomDish.value = ''
    }
}

// 移除自定义菜品
const removeCustomDish = (dish: string) => {
    const index = config.customDishes.indexOf(dish)
    if (index > -1) {
        config.customDishes.splice(index, 1)
    }
}

// 测试AI连接 - 暂时未使用
// const testConnection = async () => {
//     try {
//         const isConnected = await testAIConnection()
//         if (isConnected) {
//             alert('AI连接测试成功！')
//         } else {
//             alert('大厨暂时不在厨房，请稍后再试~')
//         }
//     } catch (error) {
//         alert('大厨暂时不在厨房：' + error)
//     }
// }

// 生成一桌菜
const generateTableMenuAction = async () => {
    isGenerating.value = true
    generatingText.value = '正在生成菜单...'

    try {
        // 调用AI服务生成菜单
        const aiDishes = await generateTableMenu(config)

        // 转换为本地格式
        const dishes: DishInfo[] = aiDishes.map(dish => ({
            name: dish.name,
            description: dish.description,
            category: dish.category,
            tags: dish.tags,
            isGeneratingRecipe: false
        }))

        generatedDishes.value = dishes
    } catch (error) {
        console.error('生成菜单失败:', error)
        // 显示错误提示
        alert('大厨表示这个菜单搭配太有挑战性了，哈哈！调整一下要求试试吧~')
    } finally {
        isGenerating.value = false
    }
}

// 阻止背景滚动
const disableBodyScroll = () => {
    document.body.style.overflow = 'hidden'
}

// 恢复背景滚动
const enableBodyScroll = () => {
    document.body.style.overflow = ''
}

// 生成单个菜品的菜谱
const generateDishRecipeAction = async (dish: DishInfo, _index: number) => {
    if (dish.recipe) {
        selectedRecipe.value = dish.recipe
        disableBodyScroll()
        return
    }

    dish.isGeneratingRecipe = true

    try {
        // 调用AI服务生成菜谱
        const recipe = await generateDishRecipe(dish.name, dish.description, dish.category)

        dish.recipe = recipe
        // 移除自动弹出，让用户手动点击查看
        // selectedRecipe.value = recipe
        // disableBodyScroll()
    } catch (error) {
        console.error('生成菜谱失败:', error)
        // 显示友好的错误提示
        alert(`大厨表示"${dish.name}"这道菜太有挑战性了，哈哈！换个菜试试吧~`)
    } finally {
        dish.isGeneratingRecipe = false
    }
}

// 关闭菜谱弹窗
const closeRecipeModal = () => {
    selectedRecipe.value = null
    enableBodyScroll()
}

// 测试弹窗功能 - 暂时未使用
// const testModal = () => {
//     // 创建一个测试菜谱
//     const testRecipe: Recipe = {
//         id: 'test-recipe',
//         name: '测试菜谱 - 红烧肉',
//         cuisine: '中式',
//         ingredients: ['五花肉 500g', '生抽 2勺', '老抽 1勺', '冰糖 30g', '料酒 1勺', '葱段 适量', '姜片 适量'],
//         steps: [
//             { step: 1, description: '五花肉切块，冷水下锅焯水去腥', time: 5 },
//             { step: 2, description: '热锅下油，放入冰糖炒糖色', time: 3 },
//             { step: 3, description: '下入肉块翻炒上色', time: 5 },
//             { step: 4, description: '加入生抽、老抽、料酒调色调味', time: 2 },
//             { step: 5, description: '加入开水没过肉块，大火烧开转小火炖煮', time: 45 }
//         ],
//         cookingTime: 60,
//         difficulty: 'medium',
//         tips: ['糖色要炒到微微冒烟', '炖煮时要小火慢炖', '最后大火收汁']
//     }

//     selectedRecipe.value = testRecipe
// }

// 键盘事件处理
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && selectedRecipe.value) {
        closeRecipeModal()
    }
}

// 组件挂载时添加键盘事件监听
onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

// 组件卸载时移除键盘事件监听
onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
    enableBodyScroll() // 确保组件销毁时恢复滚动
})

// 重置配置
const resetConfig = () => {
    // 只清除生成的结果，保留用户的配置选择
    generatedDishes.value = []
    selectedRecipe.value = null
    // 不重置用户的配置选择，让用户可以基于当前配置重新生成
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* 弹窗动画 */
.modal-overlay {
    animation: fadeIn 0.3s ease-out;
}

.modal-content {
    animation: slideUp 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* 确保弹窗在最顶层 */
.modal-overlay {
    backdrop-filter: blur(4px);
}

/* 隐藏滚动条但保持滚动功能 */
.scrollbar-hide {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none; /* Chrome, Safari and Opera */
}

/* 折叠动画 */
.collapse-enter-active,
.collapse-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
    max-height: 0;
    opacity: 0;
}

.collapse-enter-to,
.collapse-leave-from {
    max-height: 500px;
    opacity: 1;
}
</style>
