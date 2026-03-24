<template>
    <UserPageShell max-width-class="max-w-7xl" padding-class="px-4 pt-3 pb-6 md:px-6 md:pt-4 md:pb-8">
        <main class="te-main mx-auto w-full max-w-lg space-y-5 md:max-w-7xl md:space-y-6">
            <!-- 顶部氛围头部 -->
            <section
                class="relative mt-2 overflow-hidden rounded-[24px] px-5 pb-7 pt-7 text-white shadow-[0_12px_40px_rgba(122,87,209,0.2)]"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#7A57D1] via-[#9575E8] to-[#B8A3EF]"
                />
                <div
                    class="pointer-events-none absolute -right-12 -top-16 h-44 w-44 rounded-full bg-white/20 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-16 -left-8 h-40 w-40 rounded-full bg-[#F3ECFF]/30 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.14]"
                    style="
                        background-image: radial-gradient(circle at 15% 40%, #fff 0, transparent 42%),
                            radial-gradient(circle at 85% 60%, #fff 0, transparent 38%);
                    "
                />
                <div class="relative z-10">
                    <p class="text-[12px] font-medium uppercase tracking-[0.2em] text-white/75">今日灵感</p>
                    <h1 class="mt-1.5 text-[24px] font-bold leading-tight tracking-tight md:text-[26px]">吃什么</h1>
                    <p class="mt-2 max-w-[300px] text-[13px] leading-relaxed text-white/88 md:max-w-md">
                        {{ pageSubtitle }}
                    </p>
                </div>
            </section>

            <!-- 开始随机：主视觉卡片（第一主操作区，紧跟 Banner） -->
            <div v-if="!isSelecting && selectedDishes.length === 0" class="text-center app-enter-up">
                <div
                    class="rounded-[24px] border border-[#E8DDF5] bg-[#FFFFFF] p-6 shadow-[0_12px_36px_rgba(122,87,209,0.14)] md:p-8"
                >
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-[#7A57D1]">主操作区</p>
                    <div
                        class="mx-auto mb-5 flex h-[4.75rem] w-[4.75rem] items-center justify-center rounded-2xl bg-[#F3ECFF] text-[2.9rem] shadow-sm ring-1 ring-[#E8DDF5] md:h-[5.25rem] md:w-[5.25rem] md:text-[3.15rem]"
                    >
                        🎲
                    </div>
                    <h2 class="mb-2 text-[21px] font-bold text-[#222222] md:text-[24px]">开始今日推荐</h2>
                    <p class="mb-6 text-[13px] leading-relaxed text-[#8A8F99]">点一下，马上给你一份可执行的晚餐方案</p>

                    <button
                        type="button"
                        class="app-tap te-primary-btn mx-auto flex w-full max-w-[300px] items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#7A57D1] to-[#6743BF] py-4 text-[16px] font-semibold text-white shadow-[0_12px_32px_rgba(122,87,209,0.42)] md:max-w-xs"
                        @click="startRandomSelection"
                    >
                        <span class="text-xl leading-none">{{ randomDice }}</span>
                            <span>立即开始今日推荐</span>
                    </button>

                    <!-- 偏好设置 -->
                    <div class="mt-8 border-t border-[#ECEEF2] pt-6">
                        <button
                            type="button"
                            class="mx-auto flex items-center justify-center gap-1.5 text-[13px] font-medium text-[#8A8F99] transition-colors hover:text-[#7A57D1]"
                            @click="showPreference = !showPreference"
                        >
                            <span>偏好设置</span>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 transition-transform duration-200"
                                :class="{ 'rotate-180': showPreference }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            v-if="showPreference"
                            class="mt-4 rounded-[18px] bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]"
                        >
                            <div class="grid grid-cols-2 gap-2 md:flex md:flex-row md:flex-wrap md:justify-center md:gap-2">
                                <button
                                    v-for="opt in preferenceOptions"
                                    :key="opt.value"
                                    type="button"
                                    class="te-pref-pill rounded-full px-3 py-2.5 text-[13px] font-semibold transition-all duration-200 md:flex-1 md:max-w-[140px]"
                                    :class="
                                        preference === opt.value
                                            ? 'bg-[#7A57D1] text-white shadow-md shadow-[#7A57D1]/25'
                                            : 'bg-white/80 text-[#222222] ring-1 ring-[#ECEEF2] hover:bg-[#F3ECFF]/60'
                                    "
                                    @click="preference = opt.value"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="mt-6 text-left text-[12px] leading-relaxed text-[#8A8F99] italic md:text-center">
                        {{ randomFoodComment }}
                    </p>
                </div>
            </div>

            <!-- 选择过程 -->
            <div
                v-if="isSelecting"
                class="app-enter-up rounded-[22px] bg-[#FFFFFF] p-5 shadow-[0_4px_24px_rgba(0,0,0,0.06)] md:p-7"
            >
                <div class="mx-auto mb-6 max-w-xl text-center">
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-[#8A8F99]">盲盒匹配中</p>
                    <h3 class="text-lg font-bold text-[#222222] md:text-xl">{{ selectionStatus }}</h3>
                    <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-[#ECEEF2] p-0.5 shadow-inner">
                        <div
                            class="te-progress-bar h-full rounded-full bg-gradient-to-r from-[#9575E8] via-[#7A57D1] to-[#6743BF] shadow-sm transition-[width] duration-300 ease-out"
                            :style="{ width: `${selectionProgress}%` }"
                        />
                    </div>
                </div>

                <div
                    v-if="currentSelection"
                    class="te-reveal relative overflow-hidden rounded-[20px] border border-[#ECEEF2] bg-gradient-to-b from-[#F3ECFF]/90 to-white px-5 py-8 text-center shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] md:px-8 md:py-10"
                >
                    <div class="te-reveal-shimmer pointer-events-none absolute inset-0 opacity-40" />
                    <p class="relative z-10 mb-3 text-[11px] font-semibold tracking-wide text-[#7A57D1]">
                        {{ currentSelection.type === 'dish' ? '候选食材闪现' : '主厨正在路过…' }}
                    </p>
                    <div class="relative z-10 te-slot-emoji text-[3rem] leading-none md:text-[3.25rem]">
                        {{ currentSelection.type === 'dish' ? '🍽️' : currentSelection.avatar }}
                    </div>
                    <div class="relative z-10 mt-3 text-lg font-bold text-[#222222] md:text-xl">
                        {{ currentSelection.name }}
                    </div>
                    <div
                        v-if="currentSelection.specialty"
                        class="relative z-10 mt-1.5 text-[13px] text-[#8A8F99]"
                    >
                        {{ currentSelection.specialty }}
                    </div>
                </div>
            </div>

            <!-- 推荐结果 -->
            <div
                v-if="!isSelecting && selectedDishes.length > 0"
                class="app-enter-up rounded-[22px] bg-[#FFFFFF] p-5 shadow-[0_4px_24px_rgba(0,0,0,0.06)] md:p-7"
            >
                <div class="mb-6 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8A8F99]">今日专属推荐已生成</p>
                    <h3 class="mt-1 text-xl font-bold text-[#222222] md:text-2xl">你的晚餐灵感，已经就位</h3>
                    <p class="mt-2 text-[13px] text-[#8A8F99]">保存这份搭配，今晚照着做就行。</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 md:gap-5">
                    <!-- 菜品 -->
                    <div
                        class="rounded-[18px] border border-[#ECEEF2] bg-[#FAFBFC] p-4 shadow-sm md:p-5"
                    >
                        <div class="mb-3 flex items-center gap-2">
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F3ECFF] text-lg ring-1 ring-[#E8DDF5]"
                            >🥗</span>
                            <h4 class="text-[15px] font-bold text-[#222222]">推荐菜品</h4>
                            <span
                                class="ml-auto rounded-full bg-[#7A57D1]/10 px-2 py-0.5 text-[11px] font-semibold text-[#7A57D1]"
                            >{{ selectedDishes.length }} 道</span>
                        </div>
                        <div class="app-stagger-grid grid grid-cols-2 gap-2">
                            <div
                                v-for="dish in selectedDishes"
                                :key="dish"
                                class="app-tap rounded-xl border border-[#ECEEF2] bg-[#FFFFFF] px-2 py-2.5 text-center shadow-sm hover:border-[#E8DDF5] hover:shadow-md"
                            >
                                <div class="text-[13px] font-semibold leading-snug text-[#222222]">{{ dish }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 主厨 -->
                    <div
                        class="rounded-[18px] border border-[#ECEEF2] bg-gradient-to-br from-[#F3ECFF]/80 to-white p-4 shadow-sm ring-1 ring-[#E8DDF5]/60 md:p-5"
                    >
                        <div class="mb-3 flex items-center gap-2">
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-lg shadow-sm ring-1 ring-[#ECEEF2]"
                            >👨‍🍳</span>
                            <h4 class="text-[15px] font-bold text-[#222222]">推荐主厨</h4>
                        </div>
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 ring-1 ring-[#ECEEF2]">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#F3ECFF] text-3xl shadow-inner ring-1 ring-[#E8DDF5]"
                            >
                                {{ selectedMaster?.avatar }}
                            </div>
                            <div class="min-w-0 text-left">
                                <div class="truncate text-[16px] font-bold text-[#222222]">
                                    {{ selectedMaster?.name }}
                                </div>
                                <div class="mt-0.5 text-[13px] leading-snug text-[#8A8F99]">
                                    {{ selectedMaster?.specialty }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <button
                        type="button"
                        class="app-tap te-primary-btn flex flex-1 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#7A57D1] to-[#6743BF] py-3.5 text-[15px] font-semibold text-white shadow-[0_8px_24px_rgba(122,87,209,0.32)] sm:flex-none sm:min-w-[180px]"
                        :disabled="isGenerating"
                        @click="generateRecipeFromSelection"
                    >
                        <span v-if="isGenerating" class="flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            />
                            <span>{{ generatingText }}</span>
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <span>✨</span>
                            <span>生成菜谱</span>
                        </span>
                    </button>

                    <button
                        type="button"
                        class="flex flex-1 items-center justify-center gap-2 rounded-2xl border border-[#ECEEF2] bg-white py-3.5 text-[15px] font-semibold text-[#222222] shadow-sm transition-all duration-200 hover:bg-[#FAFBFC] sm:flex-none sm:min-w-[160px]"
                        :disabled="isGenerating"
                        @click="resetSelection"
                    >
                        再来一轮
                    </button>
                </div>
            </div>

            <!-- 菜谱结果 -->
            <div
                v-if="recipe"
                class="rounded-[22px] bg-[#FFFFFF] p-5 shadow-[0_4px_24px_rgba(0,0,0,0.06)] md:p-7"
            >
                <div class="mb-5 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8A8F99]">今日成就已解锁</p>
                    <h3 class="mt-1 flex items-center justify-center gap-2 text-xl font-bold text-[#222222] md:text-2xl">
                        <span>📖</span>
                        <span>你的专属菜谱</span>
                    </h3>
                </div>

                <!-- 成就感 / 截图感信息卡 -->
                <div class="mx-auto mb-5 max-w-2xl overflow-hidden rounded-[18px] border border-[#E8DDF5] bg-gradient-to-br from-[#F3ECFF] via-white to-white p-4 shadow-[0_6px_24px_rgba(122,87,209,0.08)] md:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#7A57D1]">今日推荐卡</p>
                            <h4 class="mt-1 truncate text-[20px] font-bold text-[#222222]">{{ recipe.name }}</h4>
                        </div>
                        <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-[#7A57D1] ring-1 ring-[#DCCDF7]">
                            可截图保存
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-[#7A57D1]/10 px-2.5 py-1 text-[11px] font-semibold text-[#7A57D1]">{{ selectedMaster?.name }}</span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-[#5A3BA8] ring-1 ring-[#E8DDF5]">难度：{{ difficultyText(recipe.difficulty) }}</span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-[#5A3BA8] ring-1 ring-[#E8DDF5]">用时：{{ formatCookingTime(recipe.cookingTime) }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div
                            v-for="dish in selectedDishes"
                            :key="dish"
                            class="rounded-xl bg-white px-2.5 py-2 text-center text-[12px] font-medium text-[#222222] ring-1 ring-[#ECEEF2]"
                        >
                            {{ dish }}
                        </div>
                    </div>
                </div>

                <div
                    class="mx-auto max-w-2xl overflow-hidden rounded-[18px] bg-[#FAFBFC] p-1 shadow-inner ring-1 ring-[#ECEEF2]"
                >
                    <div class="overflow-hidden rounded-[14px] bg-[#FFFFFF] ring-1 ring-[#ECEEF2]/80">
                        <RecipeCard :recipe="recipe" :show-actions="false" />
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <button
                        type="button"
                        class="app-tap te-primary-btn flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#7A57D1] to-[#6743BF] py-3.5 text-[15px] font-semibold text-white shadow-[0_8px_24px_rgba(122,87,209,0.32)]"
                        @click="handleAddFavorite"
                    >
                        收藏这份推荐
                    </button>
                    <button
                        type="button"
                        class="app-btn app-btn--secondary app-btn--md"
                        @click="resetSelection"
                    >
                        再来一次
                    </button>
                    <router-link to="/plaza" class="app-btn app-btn--secondary app-btn--md">
                        继续探索玩法
                    </router-link>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <router-link to="/collect" class="rounded-xl bg-[#FAFBFC] px-3 py-2 text-center text-[13px] font-medium text-[#5A3BA8] ring-1 ring-[#ECEEF2]">
                        去看我的收藏
                    </router-link>
                    <router-link to="/how-to-cook" class="rounded-xl bg-[#FAFBFC] px-3 py-2 text-center text-[13px] font-medium text-[#5A3BA8] ring-1 ring-[#ECEEF2]">
                        看更多菜谱教学
                    </router-link>
                </div>
            </div>

            <!-- 玩法引导区（固定放在页面下方） -->
            <section class="grid grid-cols-1 gap-3 pt-1 md:grid-cols-3">
                <div class="app-enter-up rounded-[20px] border border-[#E8DDF5] bg-[#F3ECFF]/70 p-4 md:col-span-2">
                    <p class="text-[11px] font-semibold tracking-wider text-[#7A57D1]">核心玩法</p>
                    <h3 class="mt-1 text-[16px] font-bold text-[#222222]">30 秒完成今日菜单决策</h3>
                    <p class="mt-1 text-[13px] leading-relaxed text-[#6A5A95]">
                        先随机出「菜品 + 主厨」，再一键生成完整菜谱。你负责点按钮，厨房灵感交给我们。
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="item in playHighlights"
                            :key="item"
                            class="rounded-full bg-white px-3 py-1 text-[12px] font-medium text-[#7A57D1] ring-1 ring-[#DCCDF7]"
                        >
                            {{ item }}
                        </span>
                    </div>
                </div>
                <div class="app-enter-up app-enter-delay-1 rounded-[20px] border border-[#ECEEF2] bg-white p-4 shadow-[0_2px_12px_rgba(0,0,0,0.04)]">
                    <p class="text-[11px] font-semibold tracking-wider text-[#8A8F99]">新手入口</p>
                    <h3 class="mt-1 text-[16px] font-bold text-[#222222]">第一次玩？</h3>
                    <div class="mt-3 space-y-2">
                        <router-link
                            v-for="item in helperLinks"
                            :key="item.to"
                            :to="item.to"
                            class="app-tap flex items-center justify-between rounded-xl bg-[#FAFBFC] px-3 py-2 ring-1 ring-[#ECEEF2]"
                        >
                            <span class="text-[13px] text-[#222222]">{{ item.label }}</span>
                            <span class="text-[12px] text-[#7A57D1]">→</span>
                        </router-link>
                    </div>
                </div>
            </section>
        </main>

    </UserPageShell>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { cuisines } from '@/config/cuisines'
import { ingredientCategories } from '@/config/ingredients'
import type { Recipe, CuisineType } from '@/types'
import { generateRecipe } from '@/services/aiService'
import { addHistory } from '../services/historyService'
import { addFavorite } from '../services/favoriteService'
import RecipeCard from '@/components/RecipeCard.vue'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import { showAppToast } from '@/utils/showAppToast'

// 状态管理
const isSelecting = ref(false)
const isGenerating = ref(false)
const selectedDishes = ref<string[]>([])
const selectedMaster = ref<CuisineType | null>(null)
const recipe = ref<Recipe | null>(null)
const preference = ref<string | null>(null)
const showPreference = ref(false)

// 选择过程状态
const selectionStatus = ref('')
const selectionProgress = ref(0)
const currentSelection = ref<any>(null)

// 文字轮播
const generatingText = ref('正在生成菜谱...')
const generatingTexts = ['正在生成菜谱...', '大师正在创作...', '调配独特配方...', '完善制作步骤...']

// 随机筛子表情
const diceEmojis = ['🎯']
const randomDice = ref('🎯')

const heroSubtitles = [
    '扭一扭随机键，给今天一点小期待～',
    '不知道吃什么？交给运气和主厨吧 ✨',
    '六样食材 + 一位大师 = 今日专属灵感',
    '像抽盲盒一样，打开下一顿饭的惊喜 🎁'
]
const pageSubtitle = ref(heroSubtitles[0])

const playHighlights = ['随机不盲目', '自动配主厨', '可直接生成菜谱'] as const

const helperLinks = [
    { to: '/', label: '去首页添加食材后再来' },
    { to: '/how-to-cook', label: '想学做法可看菜谱教学' },
    { to: '/collect', label: '收藏过的菜可在这里回看' }
] as const

const preferenceOptions = [
    { value: 'meat-heavy' as const, label: '🥩 荤菜多' },
    { value: 'veg-heavy' as const, label: '🥬 素菜多' },
    { value: 'veg-only' as const, label: '🌱 纯素' },
    { value: 'meat-only' as const, label: '🍖 纯荤' }
]

// 美食评论
const foodComments = [
    "💬 鲁菜大师看到我的五花肉，直接拍案而起：'今天必须教你什么叫真正的把子肉！' 🐷🔥",
    "💬 川菜大师盯着我的鸡胸肉冷笑：'莫得问题，马上让你体验什么叫麻辣鸡丝怀疑人生' 🌶️😭",
    '💬 给粤菜大师一根白萝卜，他能还你一桌国宴级开水白菜...而我只会凉拌 🦢🤷',
    '💬 日料大师处理我的三文鱼时，刀光闪过，鱼生薄得能当手机贴膜 🍣📱',
    "💬 湘菜大师的炒锅起火三米高：'辣椒放少了！你这是对湖南人的侮辱！' 🔥🌶️",
    "💬 当法餐大师看到我用速冻牛排：'亲爱的，这需要先做个SPA再按摩48小时' 🥩💆",
    '💬 闽菜大师的海鲜汤里，虾兵蟹将都在跳佛跳墙 🦐🙏',
    '💬 意大利面在真正意厨手里旋转的样子，比我前任还会绕 🍝💔',
    '💬 徽菜大师的火腿吊汤术，香得邻居以为我家在炼丹 🐷☁️',
    '💬 泰式大师的冬阴功里，柠檬草、香茅、南姜正在开演唱会 🎤🌿'
]

const currentFoodComment = ref(foodComments[0])
const randomFoodComment = computed(() => currentFoodComment.value)

let commentInterval: number | undefined

onMounted(() => {
    commentInterval = window.setInterval(() => {
        currentFoodComment.value = foodComments[Math.floor(Math.random() * foodComments.length)]
    }, 3000)

    allDishes.value = ingredientCategories.flatMap(category => category.items)
    randomDice.value = diceEmojis[Math.floor(Math.random() * diceEmojis.length)]
    pageSubtitle.value = heroSubtitles[Math.floor(Math.random() * heroSubtitles.length)]
})

onUnmounted(() => {
    if (commentInterval) {
        clearInterval(commentInterval)
    }
})

// 所有菜品数据
const allDishes = ref<string[]>([])

const difficultyText = (difficulty: Recipe['difficulty']) => {
    const m = {
        easy: '简单',
        medium: '中等',
        hard: '稍有挑战'
    } as const
    return m[difficulty] ?? '中等'
}

const formatCookingTime = (minutes?: number) => {
    if (!minutes || minutes <= 0) return '未标注'
    if (minutes < 60) return `${minutes}分钟`
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}小时${m}分钟` : `${h}小时`
}

function buildRecipeContent(recipeData: Recipe | null) {
    if (!recipeData) return ''

    const title = recipeData.name || '未命名菜谱'
    const cuisine = recipeData.cuisine || ''
    const ingredients = Array.isArray(recipeData.ingredients)
        ? recipeData.ingredients.join('、')
        : ''

    const steps = Array.isArray(recipeData.steps)
        ? recipeData.steps
              .map((item) => `${item.step}. ${item.description}`)
              .join('\n')
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

// 开始随机选择
const startRandomSelection = async () => {
    isSelecting.value = true
    selectedDishes.value = []
    selectedMaster.value = null
    recipe.value = null
    selectionProgress.value = 0

    // 第一阶段：选择菜品
    selectionStatus.value = '正在随机选择菜品...'
    await selectRandomDishes()

    // 第二阶段：选择大师
    selectionStatus.value = '正在匹配主厨大师...'
    await selectRandomMaster()

    // 完成
    selectionStatus.value = '选择完成！'
    selectionProgress.value = 100

    setTimeout(() => {
        isSelecting.value = false
    }, 1000)
}

// 随机选择菜品
const selectRandomDishes = async () => {
    const dishCount = 6
    let filteredDishes = [...allDishes.value]

    // 根据偏好过滤菜品
    if (preference.value) {
        const meatCategories = ['meat', 'seafood']
        const vegCategories = ['vegetables', 'mushrooms', 'beans']

        if (preference.value === 'meat-heavy') {
            filteredDishes = filteredDishes.filter(dish =>
                ingredientCategories.some(cat => meatCategories.includes(cat.id) && cat.items.includes(dish))
            )
        } else if (preference.value === 'veg-heavy') {
            filteredDishes = filteredDishes.filter(dish =>
                ingredientCategories.some(cat => vegCategories.includes(cat.id) && cat.items.includes(dish))
            )
        } else if (preference.value === 'meat-only') {
            filteredDishes = filteredDishes.filter(dish =>
                ingredientCategories.some(cat => meatCategories.includes(cat.id) && cat.items.includes(dish))
            )
        } else if (preference.value === 'veg-only') {
            filteredDishes = filteredDishes.filter(dish =>
                ingredientCategories.some(cat => vegCategories.includes(cat.id) && cat.items.includes(dish))
            )
        }
    }

    const shuffledDishes = [...filteredDishes].sort(() => 0.5 - Math.random())
    const uniqueDishes = new Set<string>()

    while (uniqueDishes.size < dishCount && shuffledDishes.length > 0) {
        const dish = shuffledDishes.pop()
        if (dish) uniqueDishes.add(dish)
    }

    for (let i = 0; i < 5; i++) {
        const randomDish = [...uniqueDishes][Math.floor(Math.random() * uniqueDishes.size)]
        currentSelection.value = {
            type: 'dish',
            name: randomDish
        }
        selectionProgress.value = (i / 5) * 50
        await new Promise(resolve => setTimeout(resolve, 100))
    }

    selectedDishes.value = [...uniqueDishes]
    currentSelection.value = {
        type: 'dish',
        name: selectedDishes.value[0]
    }

    await new Promise(resolve => setTimeout(resolve, 300))
}

// 随机选择大师
const selectRandomMaster = async () => {
    for (let i = 0; i < 10; i++) {
        const randomMaster = cuisines[Math.floor(Math.random() * cuisines.length)]
        currentSelection.value = {
            type: 'master',
            name: randomMaster.name,
            avatar: randomMaster.avatar,
            specialty: randomMaster.specialty
        }
        selectionProgress.value = 50 + (i / 10) * 50
        await new Promise(resolve => setTimeout(resolve, 80))
    }

    const finalMaster = cuisines[Math.floor(Math.random() * cuisines.length)]
    selectedMaster.value = finalMaster
    currentSelection.value = {
        type: 'master',
        name: finalMaster.name,
        avatar: finalMaster.avatar,
        specialty: finalMaster.specialty
    }

    await new Promise(resolve => setTimeout(resolve, 500))
}

// 生成菜谱
const generateRecipeFromSelection = async () => {
    if (!selectedMaster.value || selectedDishes.value.length === 0 || isGenerating.value) return

    isGenerating.value = true

    let textIndex = 0
    const textInterval = setInterval(() => {
        generatingText.value = generatingTexts[textIndex]
        textIndex = (textIndex + 1) % generatingTexts.length
    }, 1000)

    try {
        const cuisineInfo = {
            id: selectedMaster.value.id,
            name: selectedMaster.value.name,
            description: selectedMaster.value.description || '',
            avatar: selectedMaster.value.avatar,
            specialty: selectedMaster.value.specialty,
            prompt: selectedMaster.value.specialty
        }

        const generatedRecipe = await generateRecipe(selectedDishes.value, cuisineInfo)
        recipe.value = generatedRecipe

        await addHistory({
            title: generatedRecipe.name || '今日吃什么',
            cuisine: selectedMaster.value.name,
            ingredients: selectedDishes.value,
            request_payload: {
                dishes: selectedDishes.value,
                master: selectedMaster.value.name,
                masterSpecialty: selectedMaster.value.specialty,
                preference: preference.value
            },
            response_content: buildRecipeContent(generatedRecipe),
            image_url: ''
        })

        showAppToast('今日专属菜谱已生成，已为你保存到历史', 'success')
    } catch (error: any) {
        console.error('生成菜谱失败:', error)

        const realMessage =
            error?.message ||
            error?.response?.data?.error?.message ||
            error?.response?.data?.message ||
            '生成失败，请查看控制台'

        showAppToast(`生成失败：${realMessage}`, 'error')
    } finally {
        clearInterval(textInterval)
        isGenerating.value = false
    }
}

// 加入收藏
const handleAddFavorite = async () => {
    if (!recipe.value || !selectedMaster.value || selectedDishes.value.length === 0) return

    try {
        await addFavorite({
            title: recipe.value.name || '今日吃什么',
            cuisine: selectedMaster.value.name,
            ingredients: selectedDishes.value,
            recipe_content: buildRecipeContent(recipe.value),
            image_url: ''
        })

        showAppToast('已收藏这份今日推荐，回访时可在「我的收藏」查看', 'success')
    } catch (error) {
        console.error('加入收藏失败:', error)
        showAppToast('加入收藏失败，请稍后再试', 'error')
    }
}

// 重置选择
const resetSelection = () => {
    selectedDishes.value = []
    selectedMaster.value = null
    recipe.value = null
    currentSelection.value = null
    selectionProgress.value = 0
}
</script>

<style scoped>
@keyframes te-shimmer-move {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

@keyframes te-slot-pulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.06);
    }
}

.te-reveal-shimmer::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        transparent 0%,
        rgba(255, 255, 255, 0.55) 45%,
        transparent 70%
    );
    animation: te-shimmer-move 2.2s ease-in-out infinite;
}

.te-slot-emoji {
    animation: te-slot-pulse 1.25s ease-in-out infinite;
}

.te-primary-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.te-primary-btn:not(:disabled):active {
    transform: scale(0.98);
}

.te-primary-btn:not(:disabled) {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        opacity 0.2s ease;
}

.te-primary-btn:not(:disabled):hover {
    box-shadow: 0 12px 32px rgba(122, 87, 209, 0.42);
}

.te-pref-pill:active:not(:disabled) {
    transform: scale(0.97);
}
</style>
