<template>
  <UserPageShell max-width-class="max-w-7xl" padding-class="px-4 pt-3 pb-8 md:px-6 md:pt-4 md:pb-10">
      <!-- 顶部氛围区 -->
      <section class="me-hero relative mt-2 overflow-hidden rounded-[24px] px-5 pb-10 pt-8 text-white shadow-[0_12px_40px_rgba(122,87,209,0.22)]">
        <div class="me-hero__glow pointer-events-none absolute -right-16 -top-24 h-56 w-56 rounded-full bg-white/25 blur-3xl" />
        <div class="me-hero__glow2 pointer-events-none absolute -bottom-20 -left-10 h-48 w-48 rounded-full bg-[#F3ECFF]/35 blur-3xl" />
        <div
          class="pointer-events-none absolute inset-0 opacity-[0.12]"
          style="
            background-image: radial-gradient(circle at 20% 30%, #fff 0, transparent 45%),
              radial-gradient(circle at 80% 70%, #fff 0, transparent 40%);
          "
        />

        <div class="relative z-10">
          <p class="text-[13px] font-medium text-white/85">{{ welcomePhrase }}</p>
          <h1 class="mt-2 text-[22px] font-bold leading-snug tracking-tight text-white md:text-2xl">
            {{ loggedIn ? userLine : '开启你的美食生活' }}
          </h1>
          <p v-if="loggedIn" class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[12px] text-white/90 backdrop-blur-sm">
            <span class="opacity-80">身份</span>
            <span class="font-semibold text-white">{{ roleDisplay }}</span>
          </p>
          <p v-else class="mt-3 max-w-[280px] text-[13px] leading-relaxed text-white/80">
            登录后可同步收藏与历史记录，跨设备不丢失。
          </p>
        </div>
      </section>

      <!-- 功能入口：大圆角白卡片 -->
      <section class="relative z-20 -mt-7 app-enter-up">
        <div
          class="rounded-[22px] bg-[#FFFFFF] p-4 shadow-[0_4px_24px_rgba(0,0,0,0.06)] md:p-6"
        >
          <template v-if="loggedIn">
            <p class="mb-3 text-[13px] font-semibold text-[#222222]">常用功能</p>
            <div class="app-stagger-grid grid grid-cols-2 gap-3">
              <router-link
                to="/collect"
                class="me-tile app-tap group flex flex-col gap-2.5 rounded-[18px] border border-[#ECEEF2] bg-[#FAFBFC] p-4"
              >
                <span
                  class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3ECFF] text-[#7A57D1] shadow-sm transition-transform duration-200 group-active:scale-95"
                  aria-hidden="true"
                >
                  <AppStrokeIcon name="bookmark" :size="26" />
                </span>
                <span class="text-[15px] font-semibold text-[#222222]">我的收藏</span>
                <span class="text-[11px] leading-snug text-[#8A8F99]">菜谱与灵感</span>
              </router-link>

              <router-link
                to="/histories"
                class="me-tile app-tap group flex flex-col gap-2.5 rounded-[18px] border border-[#ECEEF2] bg-[#FAFBFC] p-4"
              >
                <span
                  class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3ECFF] text-[#7A57D1] shadow-sm transition-transform duration-200 group-active:scale-95"
                  aria-hidden="true"
                >
                  <AppStrokeIcon name="clock" :size="26" />
                </span>
                <span class="text-[15px] font-semibold text-[#222222]">我的历史</span>
                <span class="text-[11px] leading-snug text-[#8A8F99]">生成记录</span>
              </router-link>

              <router-link
                to="/settings"
                class="me-tile app-tap group flex flex-col gap-2.5 rounded-[18px] border border-[#ECEEF2] bg-[#FAFBFC] p-4"
              >
                <span
                  class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3ECFF] text-[#7A57D1] shadow-sm transition-transform duration-200 group-active:scale-95"
                  aria-hidden="true"
                >
                  <AppStrokeIcon name="sliders" :size="26" />
                </span>
                <span class="text-[15px] font-semibold text-[#222222]">系统设置</span>
                <span class="text-[11px] leading-snug text-[#8A8F99]">偏好与体验</span>
              </router-link>

              <router-link
                v-if="showAdminEntry"
                to="/admin"
                class="me-tile me-tile--admin app-tap group flex flex-col gap-2.5 rounded-[18px] border border-[#E8DDF5] bg-gradient-to-br from-[#F3ECFF] to-[#FAFBFC] p-4"
              >
                <span
                  class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#7A57D1]/12 text-[#7A57D1] shadow-sm ring-1 ring-[#7A57D1]/15 transition-transform duration-200 group-active:scale-95"
                  aria-hidden="true"
                >
                  <AppStrokeIcon name="wrench" :size="26" />
                </span>
                <span class="text-[15px] font-semibold text-[#7A57D1]">进入管理后台</span>
                <span class="text-[11px] leading-snug text-[#8A8F99]">管理员入口</span>
              </router-link>

              <button
                type="button"
                class="app-tap col-span-2 flex w-full items-center justify-center gap-2 rounded-[18px] border border-[#FDE2E4] bg-[#FFF5F5] py-3.5 text-[15px] font-semibold text-[#D64556] active:bg-[#FFECEC]"
                @click="handleSignOut"
              >
                <AppStrokeIcon name="logOut" :size="20" class="text-[#D64556]" aria-hidden="true" />
                退出登录
              </button>
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col items-center py-4 text-center">
              <div
                class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#F3ECFF] text-[#7A57D1] shadow-sm ring-1 ring-[#E8DDF5]"
                aria-hidden="true"
              >
                <AppStrokeIcon name="sparkles" :size="34" />
              </div>
              <p class="text-[15px] font-semibold text-[#222222]">登录体验完整功能</p>
              <p class="mt-1.5 max-w-xs text-[13px] leading-relaxed text-[#8A8F99]">
                同步收藏、历史记录与个性化设置。
              </p>
              <router-link
                to="/login"
                class="app-tap mt-6 w-full rounded-[18px] bg-[#7A57D1] py-3.5 text-center text-[15px] font-semibold text-white shadow-[0_8px_24px_rgba(122,87,209,0.35)] active:bg-[#6846BC]"
              >
                去登录
              </router-link>

              <div class="mt-4 w-full rounded-[16px] border border-[#E8DDF5] bg-[#F3ECFF]/60 p-3 text-left">
                <p class="text-[12px] font-semibold text-[#7A57D1]">访客模式可先体验</p>
                <ul class="mt-1.5 space-y-1 text-[12px] leading-relaxed text-[#5A3BA8]">
                  <li>• 首页生成菜谱与玩法推荐</li>
                  <li>• 菜单广场浏览全部工具</li>
                  <li>• 吃什么随机决策体验</li>
                </ul>
              </div>
            </div>
          </template>
        </div>
      </section>

      <!-- 个人运营区：最近记录 / 身份提示 -->
      <section v-if="loggedIn" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="app-enter-up rounded-[20px] border border-[#ECEEF2] bg-white p-4 shadow-[0_2px_12px_rgba(0,0,0,0.04)] md:col-span-2">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-[16px] font-bold text-[#222222]">最近足迹</h3>
            <router-link to="/collect" class="text-[12px] font-semibold text-[#7A57D1]">查看全部 →</router-link>
          </div>
          <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="rounded-xl bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]">
              <p class="text-[11px] font-semibold tracking-wider text-[#8A8F99]">最近收藏</p>
              <p class="mt-1 text-[13px] font-medium text-[#222222]">
                {{ latestFavoriteTitle || '还没有收藏，看到喜欢的菜谱可以先点亮爱心' }}
              </p>
            </div>
            <div class="rounded-xl bg-[#FAFBFC] p-3 ring-1 ring-[#ECEEF2]">
              <p class="text-[11px] font-semibold tracking-wider text-[#8A8F99]">最近生成</p>
              <p class="mt-1 text-[13px] font-medium text-[#222222]">
                {{ latestHistoryTitle || '还没有生成记录，去首页做一道今日菜谱吧' }}
              </p>
            </div>
          </div>
        </div>

        <div class="app-enter-up app-enter-delay-1 rounded-[20px] border border-[#E8DDF5] bg-[#F3ECFF]/70 p-4">
          <p class="text-[11px] font-semibold tracking-wider text-[#7A57D1]">身份提示</p>
          <h3 class="mt-1 text-[16px] font-bold text-[#222222]">{{ roleHintTitle }}</h3>
          <p class="mt-2 text-[13px] leading-relaxed text-[#5A3BA8]">{{ roleHintDesc }}</p>
          <router-link
            v-if="showAdminEntry"
            to="/admin"
            class="app-tap mt-3 inline-flex items-center gap-1 rounded-full bg-white px-3 py-1.5 text-[12px] font-semibold text-[#7A57D1] ring-1 ring-[#DCCDF7]"
          >
            打开管理后台
            <span>→</span>
          </router-link>
        </div>
      </section>
  </UserPageShell>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import { supabase } from '@/lib/supabase'
import { getCurrentUserRole } from '@/services/userProfileService'
import { getFavorites } from '@/services/favoriteService'
import { getHistories } from '@/services/historyService'
import { canAccessAdmin, normalizeRole, roleLabelCN } from '@/lib/appRoles'

const router = useRouter()
const loggedIn = ref(false)
const emailDisplay = ref('—')
const userDisplay = ref('—')
const showAdminEntry = ref(false)
const rawRole = ref<string | null>(null)
const latestFavoriteTitle = ref('')
const latestHistoryTitle = ref('')

const roleDisplay = computed(() => {
  if (!loggedIn.value) return '—'
  return roleLabelCN(normalizeRole(rawRole.value))
})

const welcomePhrase = computed(() => {
  if (!loggedIn.value) return '你好呀'
  const h = new Date().getHours()
  if (h < 12) return '早上好'
  if (h < 18) return '下午好'
  return '晚上好'
})

const userLine = computed(() => {
  if (!loggedIn.value) return ''
  const u = userDisplay.value
  if (u && u !== '—') return u
  return emailDisplay.value
})

const roleHintTitle = computed(() => {
  if (!showAdminEntry.value) return '普通用户模式'
  return normalizeRole(rawRole.value) === 'super_admin' ? '超级管理员模式' : '运营管理模式'
})

const roleHintDesc = computed(() => {
  if (!showAdminEntry.value) return '你可以专注收藏与历史记录，我们会持续为你优化日常做饭体验。'
  return '你拥有后台访问能力，可查看运营数据与配置项；前台体验仍与普通用户一致。'
})

let authSub: { unsubscribe: () => void } | null = null

async function syncProfile() {
  const { data } = await supabase.auth.getUser()
  const user = data.user
  loggedIn.value = !!user
  emailDisplay.value = user?.email ?? '—'
  const meta = user?.user_metadata as Record<string, unknown> | undefined
  const fromMeta =
    (typeof meta?.full_name === 'string' && meta.full_name) ||
    (typeof meta?.name === 'string' && meta.name) ||
    ''
  userDisplay.value = fromMeta || user?.email || '—'
  if (!user) {
    rawRole.value = null
    showAdminEntry.value = false
    latestFavoriteTitle.value = ''
    latestHistoryTitle.value = ''
    return
  }
  const role = await getCurrentUserRole()
  rawRole.value = role
  showAdminEntry.value = canAccessAdmin(role)
  await loadRecentCards()
}

async function loadRecentCards() {
  try {
    const [favorites, histories] = await Promise.all([getFavorites(), getHistories()])
    latestFavoriteTitle.value = favorites?.[0]?.title || ''
    latestHistoryTitle.value = histories?.[0]?.title || ''
  } catch {
    latestFavoriteTitle.value = ''
    latestHistoryTitle.value = ''
  }
}

async function handleSignOut() {
  await supabase.auth.signOut()
  await syncProfile()
  router.push('/login')
}

onMounted(() => {
  syncProfile()
  const { data } = supabase.auth.onAuthStateChange(() => {
    syncProfile()
  })
  authSub = data.subscription
})

onBeforeUnmount(() => {
  authSub?.unsubscribe()
})
</script>

<style scoped>
.me-hero {
  background: linear-gradient(135deg, #7a57d1 0%, #9575e8 42%, #b8a3ef 100%);
}

.me-tile:focus-visible {
  outline: 2px solid rgba(122, 87, 209, 0.45);
  outline-offset: 2px;
}
</style>
