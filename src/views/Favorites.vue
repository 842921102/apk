<template>
  <div :class="embedded ? 'recipe-page-embedded' : ''">
    <GlobalNavigation v-if="!embedded" />
    <div class="page-wrap">
      <div class="page-shell">
        <section
          v-if="!embedded"
          class="app-page-hero mb-4 app-enter-up"
        >
          <div class="app-page-hero__bg" />
          <div class="app-page-hero__glow-a" />
          <div class="app-page-hero__glow-b" />
          <div class="app-page-hero__noise" />
          <div class="app-page-hero__body">
            <p class="app-page-hero__eyebrow">回访页</p>
            <h1 class="app-page-hero__title">我的收藏</h1>
            <p class="app-page-hero__subtitle">喜欢的菜谱会保存在这里，随时可以回看和复刻。</p>
          </div>
        </section>

        <section class="app-card app-card--pad mb-4 app-enter-up">
          <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-wider text-app-muted">收藏总览</p>
              <h2 class="mt-1 text-[22px] font-bold text-app-fg">共 {{ favorites.length }} 条收藏</h2>
              <p class="mt-1 text-sm text-app-muted">按时间倒序展示，支持快速查看详情与删除。</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="app-btn app-btn--secondary app-btn--sm" @click="loadFavorites">刷新收藏</button>
              <button type="button" class="app-btn app-btn--primary app-btn--sm" @click="addDemoFavorite">新增测试收藏</button>
            </div>
          </div>
          <p v-if="message" :class="messageType === 'success' ? 'app-form-success !mt-3' : 'app-form-error !mt-3'">
            {{ message }}
          </p>
        </section>

        <AppStateLoading
          v-if="loading"
          variant="skeleton"
          title="正在同步收藏"
          hint="从云端拉取你的收藏菜谱…"
          :rows="5"
        />

        <template v-else>
          <AppStateEmpty
            v-if="authRequired"
            stroke="user"
            title="先登录再查看收藏"
            description="访客模式下你可以先逛首页和功能广场。登录后，收藏会自动跟随账号同步。"
            :with-panel="true"
          >
            <template #actions>
              <router-link :to="loginTarget" class="app-btn app-btn--primary app-btn--md w-full sm:w-auto">
                去登录
              </router-link>
              <router-link to="/" class="app-btn app-btn--secondary app-btn--md w-full sm:w-auto">
                先去首页逛逛
              </router-link>
            </template>
          </AppStateEmpty>

          <AppStateError
            v-else-if="fetchError"
            class="mb-6"
            title="收藏列表加载失败"
            :description="fetchError"
            retry-label="重新加载"
            @retry="loadFavorites"
          />

          <AppStateEmpty
            v-else-if="favorites.length === 0"
            stroke="heart"
            title="暂无收藏"
            description="在首页或生成结果里点亮爱心，菜谱会同步保存在这里，随时打开回顾。"
            :with-panel="true"
          >
            <template #actions>
              <router-link to="/" class="app-btn app-btn--primary app-btn--md w-full sm:w-auto">
                去首页找菜谱
              </router-link>
              <button type="button" class="app-btn app-btn--secondary app-btn--md w-full sm:w-auto" @click="addDemoFavorite">
                新增一条测试收藏
              </button>
            </template>
          </AppStateEmpty>

          <section v-else class="grid gap-3 md:gap-4">
            <article
              v-for="item in favorites"
              :key="item.id"
              class="app-card app-card--sm p-4 md:p-5 app-enter-up"
            >
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                  <h3 class="truncate text-[18px] font-bold text-app-fg">{{ item.title || '未命名菜谱' }}</h3>
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="app-tag app-tag--accent">{{ item.cuisine || '未填写菜系' }}</span>
                    <span class="app-tag app-tag--muted">收藏于 {{ formatTime(item.created_at) }}</span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button type="button" class="app-btn app-btn--secondary app-btn--xs" @click="selectedFavorite = item">查看详情</button>
                  <button type="button" class="app-btn app-btn--danger app-btn--xs" @click="handleDelete(item.id)">删除</button>
                </div>
              </div>
              <div class="mt-3 grid gap-2 md:grid-cols-2">
                <div class="rounded-[14px] bg-app-page p-3 ring-1 ring-app-line">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-app-muted">食材</p>
                  <p class="mt-1 line-clamp-3 text-[13px] leading-relaxed text-app-fg">{{ formatIngredients(item.ingredients) }}</p>
                </div>
                <div class="rounded-[14px] bg-app-page p-3 ring-1 ring-app-line">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-app-muted">做法摘要</p>
                  <p class="mt-1 line-clamp-3 text-[13px] leading-relaxed text-app-fg">{{ summarize(item.recipe_content) }}</p>
                </div>
              </div>
            </article>
          </section>
        </template>
      </div>
    </div>

    <div
      v-if="selectedFavorite"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/45 p-4 backdrop-blur-[2px] sm:items-center"
      @click.self="selectedFavorite = null"
    >
      <div class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-[22px] bg-app-card shadow-[0_20px_60px_rgba(0,0,0,0.15)] ring-1 ring-app-line" @click.stop>
        <div class="flex items-center justify-between border-b border-app-line bg-gradient-to-r from-app-accent-soft/95 to-app-page px-5 py-4">
          <h3 class="text-[17px] font-bold text-app-fg">收藏详情</h3>
          <button type="button" class="app-btn app-btn--secondary app-btn--xs" @click="selectedFavorite = null">关闭</button>
        </div>
        <div class="max-h-[calc(90vh-4.5rem)] overflow-y-auto p-5 md:p-6">
          <h4 class="text-xl font-bold text-app-fg">{{ selectedFavorite.title || '未命名菜谱' }}</h4>
          <div class="mt-2 flex flex-wrap gap-2">
            <span class="app-tag app-tag--accent">{{ selectedFavorite.cuisine || '未填写菜系' }}</span>
            <span class="app-tag app-tag--muted">{{ formatTime(selectedFavorite.created_at) }}</span>
          </div>
          <div class="mt-4 rounded-[14px] bg-app-page p-4 ring-1 ring-app-line">
            <p class="text-xs font-semibold uppercase tracking-wider text-app-muted">食材</p>
            <p class="mt-2 text-sm leading-relaxed text-app-fg">{{ formatIngredients(selectedFavorite.ingredients) }}</p>
          </div>
          <div class="mt-4 rounded-[14px] bg-app-page p-4 ring-1 ring-app-line">
            <p class="text-xs font-semibold uppercase tracking-wider text-app-muted">完整做法</p>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-app-fg">{{ selectedFavorite.recipe_content }}</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import GlobalNavigation from '@/components/GlobalNavigation.vue'
import AppStateEmpty from '@/components/ui/AppStateEmpty.vue'
import AppStateError from '@/components/ui/AppStateError.vue'
import AppStateLoading from '@/components/ui/AppStateLoading.vue'
import { ref, onMounted, withDefaults, defineProps, computed } from 'vue'
import { useRoute } from 'vue-router'
import { getFavorites, addFavorite, deleteFavorite } from '../services/favoriteService'

const props = withDefaults(
  defineProps<{
    /** 嵌入「收藏」聚合页时不展示 AppNav、压缩外边距 */
    embedded?: boolean
  }>(),
  { embedded: false }
)
const { embedded } = props
const route = useRoute()

interface FavoriteItem {
  id: number
  title: string
  cuisine?: string
  ingredients?: string[] | string
  recipe_content: string
  image_url?: string
  created_at: string
}

const favorites = ref<FavoriteItem[]>([])
const loading = ref(false)
const fetchError = ref('')
const message = ref('')
const messageType = ref<'success' | 'error'>('success')
const selectedFavorite = ref<FavoriteItem | null>(null)
const authRequired = computed(() => /请先登录/i.test(fetchError.value))
const loginTarget = computed(() => ({
  path: '/login',
  query: { redirect: route.fullPath || '/collect' }
}))

function formatIngredients(value: any) {
  if (Array.isArray(value)) return value.join('、')
  if (!value) return '未填写'
  return String(value)
}

function formatTime(value: string) {
  if (!value) return ''
  return new Date(value).toLocaleString()
}

function summarize(content: string) {
  if (!content) return '暂无内容'
  return content.replace(/\s+/g, ' ').slice(0, 80) + (content.length > 80 ? '…' : '')
}

async function loadFavorites() {
  try {
    loading.value = true
    message.value = ''
    fetchError.value = ''
    const list = await getFavorites()
    favorites.value = (list || []) as FavoriteItem[]
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err.message || '读取收藏失败'
    fetchError.value = err.message || '读取收藏失败'
  } finally {
    loading.value = false
  }
}

async function addDemoFavorite() {
  try {
    message.value = ''
    await addFavorite({
      title: '红烧排骨',
      cuisine: '家常菜',
      ingredients: ['排骨', '葱', '姜', '酱油'],
      recipe_content: '1. 排骨焯水。2. 热锅下油炒香葱姜。3. 加入排骨翻炒，加酱油和清水，小火焖煮。',
      image_url: ''
    })
    messageType.value = 'success'
    message.value = '新增测试收藏成功'
    await loadFavorites()
  } catch (err: any) {
    if (/请先登录/i.test(err?.message || '')) {
      fetchError.value = '请先登录'
    }
    messageType.value = 'error'
    message.value = err.message || '新增收藏失败'
  }
}

async function handleDelete(id: number) {
  try {
    message.value = ''
    await deleteFavorite(id)
    messageType.value = 'success'
    message.value = '删除成功'
    await loadFavorites()
  } catch (err: any) {
    if (/请先登录/i.test(err?.message || '')) {
      fetchError.value = '请先登录'
    }
    messageType.value = 'error'
    message.value = err.message || '删除失败'
  }
}

onMounted(() => {
  loadFavorites()
})
</script>

<style scoped>
.page-wrap {
  min-height: 100vh;
  padding: 20px 16px 24px;
}
.page-shell {
  width: 100%;
  max-width: 980px;
  margin: 0 auto;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (max-width: 640px) {
  .page-wrap {
    padding: 12px 12px 20px;
  }
}

.recipe-page-embedded .page-wrap {
  min-height: auto;
  padding: 8px 0 16px;
}
</style>