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
            <h1 class="app-page-hero__title">最近生成记录</h1>
            <p class="app-page-hero__subtitle">每一次生成都会保留在这里，方便你回看、复刻和优化。</p>
          </div>
        </section>

        <section class="app-card app-card--pad mb-4 app-enter-up">
          <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-wider text-app-muted">回访页</p>
              <h1 class="mt-1 text-[24px] font-bold text-app-fg">最近生成记录</h1>
              <p class="mt-1 text-sm text-app-muted">这里会保存你生成过的菜谱，方便回看、复刻与调整。</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="app-btn app-btn--secondary app-btn--sm" @click="loadHistories">刷新历史</button>
              <button type="button" class="app-btn app-btn--primary app-btn--sm" @click="addDemoHistory">新增测试历史</button>
            </div>
          </div>
          <p v-if="message" :class="messageType === 'success' ? 'app-form-success !mt-3' : 'app-form-error !mt-3'">
            {{ message }}
          </p>
        </section>

        <AppStateLoading
          v-if="loading"
          variant="skeleton"
          title="正在同步历史记录"
          hint="从云端拉取你的生成记录…"
          :rows="5"
        />

        <template v-else>
          <AppStateEmpty
            v-if="authRequired"
            stroke="user"
            title="先登录再查看历史"
            description="登录后会自动同步你的生成记录，换设备也能继续接着做。"
            :with-panel="true"
          >
            <template #actions>
              <router-link :to="loginTarget" class="app-btn app-btn--primary app-btn--md w-full sm:w-auto">
                去登录
              </router-link>
              <router-link to="/" class="app-btn app-btn--secondary app-btn--md w-full sm:w-auto">
                先去首页创作
              </router-link>
            </template>
          </AppStateEmpty>

          <AppStateError
            v-else-if="fetchError"
            class="mb-6"
            title="历史记录加载失败"
            :description="fetchError"
            retry-label="重新加载"
            @retry="loadHistories"
          />

          <AppStateEmpty
            v-else-if="histories.length === 0"
            stroke="scrollText"
            title="暂无历史记录"
            description="去首页添加食材并生成菜谱，每一次创作都会自动同步到这里，方便你回看与复用。"
            :with-panel="true"
          >
            <template #actions>
              <router-link to="/" class="app-btn app-btn--primary app-btn--md w-full sm:w-auto">
                去首页创作
              </router-link>
              <button type="button" class="app-btn app-btn--secondary app-btn--md w-full sm:w-auto" @click="addDemoHistory">
                新增一条测试记录
              </button>
            </template>
          </AppStateEmpty>

          <section v-else class="grid gap-3 md:gap-4">
            <article
              v-for="item in histories"
              :key="item.id"
              class="app-card app-card--sm p-4 md:p-5 app-enter-up"
            >
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                  <h3 class="truncate text-[18px] font-bold text-app-fg">{{ item.title || '未命名菜谱' }}</h3>
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="app-tag app-tag--accent">{{ item.cuisine || '未填写菜系' }}</span>
                    <span class="app-tag app-tag--muted">{{ formatTime(item.created_at) }}</span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button type="button" class="app-btn app-btn--secondary app-btn--xs" @click="selectedHistory = item">查看详情</button>
                  <button type="button" class="app-btn app-btn--danger app-btn--xs" @click="handleDelete(item.id)">删除</button>
                </div>
              </div>
              <div class="mt-3 grid gap-2 md:grid-cols-2">
                <div class="rounded-[14px] bg-app-page p-3 ring-1 ring-app-line">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-app-muted">食材</p>
                  <p class="mt-1 line-clamp-3 text-[13px] leading-relaxed text-app-fg">{{ formatIngredients(item.ingredients) }}</p>
                </div>
                <div class="rounded-[14px] bg-app-page p-3 ring-1 ring-app-line">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-app-muted">内容摘要</p>
                  <p class="mt-1 line-clamp-3 text-[13px] leading-relaxed text-app-fg">{{ summarize(item.response_content) }}</p>
                </div>
              </div>
            </article>
          </section>
        </template>
      </div>
    </div>

    <div
      v-if="selectedHistory"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/45 p-4 backdrop-blur-[2px] sm:items-center"
      @click.self="selectedHistory = null"
    >
      <div class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-[22px] bg-app-card shadow-[0_20px_60px_rgba(0,0,0,0.15)] ring-1 ring-app-line" @click.stop>
        <div class="flex items-center justify-between border-b border-app-line bg-gradient-to-r from-app-accent-soft/95 to-app-page px-5 py-4">
          <h3 class="text-[17px] font-bold text-app-fg">历史详情</h3>
          <button type="button" class="app-btn app-btn--secondary app-btn--xs" @click="selectedHistory = null">关闭</button>
        </div>
        <div class="max-h-[calc(90vh-4.5rem)] overflow-y-auto p-5 md:p-6">
          <h4 class="text-xl font-bold text-app-fg">{{ selectedHistory.title || '未命名菜谱' }}</h4>
          <div class="mt-2 flex flex-wrap gap-2">
            <span class="app-tag app-tag--accent">{{ selectedHistory.cuisine || '未填写菜系' }}</span>
            <span class="app-tag app-tag--muted">{{ formatTime(selectedHistory.created_at) }}</span>
          </div>
          <div class="mt-4 rounded-[14px] bg-app-page p-4 ring-1 ring-app-line">
            <p class="text-xs font-semibold uppercase tracking-wider text-app-muted">食材</p>
            <p class="mt-2 text-sm leading-relaxed text-app-fg">{{ formatIngredients(selectedHistory.ingredients) }}</p>
          </div>
          <div class="mt-4 rounded-[14px] bg-app-page p-4 ring-1 ring-app-line">
            <p class="text-xs font-semibold uppercase tracking-wider text-app-muted">完整内容</p>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-app-fg">{{ selectedHistory.response_content }}</p>
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
import { addHistory, getHistories, deleteHistory } from '../services/historyService'

const props = withDefaults(
  defineProps<{
    embedded?: boolean
  }>(),
  { embedded: false }
)
const { embedded } = props
const route = useRoute()

interface HistoryItem {
  id: number
  title?: string
  cuisine?: string
  ingredients?: string[] | string
  response_content: string
  created_at: string
}

const histories = ref<HistoryItem[]>([])
const loading = ref(false)
const fetchError = ref('')
const message = ref('')
const messageType = ref<'success' | 'error'>('success')
const selectedHistory = ref<HistoryItem | null>(null)
const authRequired = computed(() => /请先登录/i.test(fetchError.value))
const loginTarget = computed(() => ({
  path: '/login',
  query: { redirect: route.fullPath || '/collect?tab=histories' }
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

async function loadHistories() {
  try {
    loading.value = true
    message.value = ''
    fetchError.value = ''
    const list = await getHistories()
    histories.value = (list || []) as HistoryItem[]
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err.message || '读取历史失败'
    fetchError.value = err.message || '读取历史失败'
  } finally {
    loading.value = false
  }
}

async function addDemoHistory() {
  try {
    message.value = ''
    await addHistory({
      title: '番茄炒蛋',
      cuisine: '家常菜',
      ingredients: ['番茄', '鸡蛋'],
      request_payload: { ingredients: ['番茄', '鸡蛋'] },
      response_content: '1. 番茄切块。2. 鸡蛋打散。3. 先炒鸡蛋，再炒番茄，最后混合。',
      image_url: ''
    })
    messageType.value = 'success'
    message.value = '新增测试历史成功'
    await loadHistories()
  } catch (err: any) {
    if (/请先登录/i.test(err?.message || '')) {
      fetchError.value = '请先登录'
    }
    messageType.value = 'error'
    message.value = err.message || '新增历史失败'
  }
}

async function handleDelete(id: number) {
  try {
    message.value = ''
    await deleteHistory(id)
    messageType.value = 'success'
    message.value = '删除成功'
    await loadHistories()
  } catch (err: any) {
    if (/请先登录/i.test(err?.message || '')) {
      fetchError.value = '请先登录'
    }
    messageType.value = 'error'
    message.value = err.message || '删除失败'
  }
}

onMounted(() => {
  loadHistories()
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