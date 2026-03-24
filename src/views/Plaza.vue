<template>
  <UserPageShell max-width-class="max-w-7xl" padding-class="px-4 pt-3 pb-6 md:px-6 md:pt-4 md:pb-8">
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
            background-image: radial-gradient(circle at 18% 35%, #fff 0, transparent 42%),
              radial-gradient(circle at 82% 65%, #fff 0, transparent 38%);
          "
        />
        <div class="relative z-10">
          <p class="text-[12px] font-medium uppercase tracking-[0.2em] text-white/75">扩展玩法</p>
          <h1 class="mt-1.5 text-[24px] font-bold leading-tight tracking-tight md:text-[26px]">功能广场</h1>
          <p class="mt-2 max-w-[320px] text-[13px] leading-relaxed text-white/88 md:max-w-lg">
            {{ pageSubtitle }}
          </p>
        </div>
      </section>

      <!-- 热门玩法：横向卡片 -->
      <section class="mt-6">
        <div class="mb-3 flex items-end justify-between gap-2">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8A8F99]">热门玩法</p>
            <h2 class="text-[17px] font-bold text-[#222222]">值得一试</h2>
          </div>
          <span
            class="rounded-full bg-[#F3ECFF] px-2.5 py-0.5 text-[11px] font-semibold text-[#7A57D1] ring-1 ring-[#E8DDF5]"
          >精选</span>
        </div>

        <div
          class="app-stagger-row -mx-1 flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 pl-1 pr-4 scrollbar-hide md:mx-0 md:overflow-visible md:pb-0 md:pl-0 md:pr-0"
        >
          <router-link
            v-for="card in hotPicks"
            :key="`hot-${card.to}`"
            :to="card.to"
            class="plaza-hot-card app-tap group relative flex min-w-[min(100%,268px)] shrink-0 snap-start flex-col overflow-hidden rounded-[20px] bg-[#FFFFFF] shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2] md:min-w-0 md:flex-1"
          >
            <div
              class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-[#9575E8] via-[#7A57D1] to-[#6743BF]"
            />
            <div class="flex flex-1 flex-col p-4 pl-5">
              <div class="flex items-start justify-between gap-2">
                <span
                  class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3ECFF] text-[#7A57D1] shadow-sm ring-1 ring-[#E8DDF5] transition-transform duration-200 group-hover:scale-105"
                >
                  <AppStrokeIcon :name="card.icon" :size="26" />
                </span>
                <span
                  v-if="card.tag"
                  class="shrink-0 rounded-full bg-[#7A57D1]/10 px-2 py-0.5 text-[10px] font-bold text-[#7A57D1]"
                >{{ card.tag }}</span>
              </div>
              <h3 class="mt-3 text-[16px] font-bold leading-snug text-[#222222]">{{ card.title }}</h3>
              <p class="mt-1 flex-1 text-[12px] leading-relaxed text-[#8A8F99]">{{ card.desc }}</p>
              <div class="mt-3 flex items-center gap-1 text-[12px] font-semibold text-[#7A57D1]">
                <span>立即体验</span>
                <span class="transition-transform group-hover:translate-x-0.5">→</span>
              </div>
            </div>
          </router-link>
        </div>
      </section>

      <!-- 全部功能：宫格 -->
      <section class="mt-8">
        <div class="mb-3">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8A8F99]">功能入口</p>
          <h2 class="text-[17px] font-bold text-[#222222]">全部工具</h2>
        </div>

        <div class="app-stagger-grid grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
          <router-link
            v-for="card in gridCards"
            :key="card.to"
            :to="card.to"
            class="plaza-grid-card app-tap group flex flex-col rounded-[18px] bg-[#FFFFFF] p-4 shadow-[0_2px_16px_rgba(0,0,0,0.04)] ring-1 ring-[#ECEEF2] hover:shadow-[0_8px_28px_rgba(122,87,209,0.1)] hover:ring-[#E8DDF5]"
          >
            <span
              class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-[#F3ECFF] text-[#7A57D1] shadow-sm ring-1 ring-[#E8DDF5] transition-transform duration-200 group-hover:scale-105"
            >
              <AppStrokeIcon :name="card.icon" :size="24" />
            </span>
            <h3 class="text-[14px] font-bold leading-snug text-[#222222]">{{ card.title }}</h3>
            <p class="mt-1 flex-1 text-[11px] leading-relaxed text-[#8A8F99]">{{ card.desc }}</p>
            <div
              class="mt-3 flex items-center justify-end text-[11px] font-semibold text-[#7A57D1] opacity-80 transition-opacity group-hover:opacity-100"
            >
              <span>进入</span>
              <span class="ml-0.5 transition-transform group-hover:translate-x-0.5">→</span>
            </div>
          </router-link>
        </div>
      </section>

      <!-- 功能中心说明 -->
      <section class="mt-8 grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="app-enter-up rounded-[20px] border border-[#ECEEF2] bg-white p-4 shadow-[0_2px_12px_rgba(0,0,0,0.04)] md:col-span-2">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8A8F99]">玩法推荐路线</p>
          <h2 class="mt-1 text-[17px] font-bold text-[#222222]">从灵感到成品，三步就够</h2>
          <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <router-link
              v-for="item in playRoute"
              :key="item.to"
              :to="item.to"
              class="app-tap rounded-xl bg-[#FAFBFC] px-3 py-3 ring-1 ring-[#ECEEF2]"
            >
              <p class="text-[12px] font-semibold text-[#7A57D1]">{{ item.step }}</p>
              <p class="mt-1 text-[13px] font-medium text-[#222222]">{{ item.title }}</p>
              <p class="mt-1 text-[12px] leading-relaxed text-[#8A8F99]">{{ item.desc }}</p>
            </router-link>
          </div>
        </div>
        <div class="app-enter-up app-enter-delay-1 rounded-[20px] border border-[#E8DDF5] bg-[#F3ECFF]/65 p-4">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-[#7A57D1]">新手建议</p>
          <h2 class="mt-1 text-[17px] font-bold text-[#222222]">先从这里开始</h2>
          <ul class="mt-3 space-y-2 text-[13px] leading-relaxed text-[#5A3BA8]">
            <li v-for="tip in starterTips" :key="tip" class="rounded-xl bg-white px-3 py-2 ring-1 ring-[#E8DDF5]">
              {{ tip }}
            </li>
          </ul>
        </div>
      </section>
  </UserPageShell>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import type { StrokeIconName } from '@/components/icons/strokeIconPaths'

type PlazaCard = {
  to: string
  icon: StrokeIconName
  title: string
  desc: string
  tag?: string
}

const gridCards: PlazaCard[] = [
  { to: '/table-design', icon: 'utensils', title: '满汉全席', desc: '一桌好菜，搭配用餐小剧本' },
  { to: '/sauce-design', icon: 'droplet', title: '酱料大师', desc: '调味灵感与酱料设计' },
  { to: '/fortune-cooking', icon: 'moon', title: '玄学厨房', desc: '趣味运势与今日菜色' },
  { to: '/gallery', icon: 'image', title: '封神图鉴', desc: '美食图鉴随心浏览' },
  { to: '/how-to-cook', icon: 'bookOpen', title: '菜谱教学', desc: '步骤清晰，跟做不慌' }
]

/** 横向推荐区：与宫格可重复入口，突出产品感 */
const hotPickTos = ['/table-design', '/fortune-cooking', '/how-to-cook'] as const
const hotPicks = hotPickTos
  .map((to) => gridCards.find((c) => c.to === to))
  .filter((c): c is PlazaCard => !!c)
  .map((c, i) => ({
    ...c,
    tag: i === 0 ? 'HOT' : i === 1 ? '好玩' : '实用'
  }))

const playRoute = [
  { step: 'STEP 1', to: '/today-eat', title: '先定今晚吃什么', desc: '用随机玩法快速做决策。' },
  { step: 'STEP 2', to: '/how-to-cook', title: '查看详细做法', desc: '按步骤直接开做，不怕翻车。' },
  { step: 'STEP 3', to: '/collect', title: '收藏满意方案', desc: '把好吃的留下，下次复刻。' }
] as const

const starterTips = ['想省时：先用「吃什么」再进教学。', '想有亮点：在「酱料大师」加一道灵魂酱。', '想晒图：做完记得去「封神图鉴」保存。'] as const

const heroSubtitles = [
    '选一站入口，把「今天玩什么」交给灵感 ✨',
    '从一桌菜到一味酱，厨房乐趣都在这里',
    '不用翻菜单，先把想玩的都收藏进心里～',
    '轻点一下，解锁下一道生活小惊喜 🎁'
]
const pageSubtitle = ref(heroSubtitles[0])

onMounted(() => {
  pageSubtitle.value = heroSubtitles[Math.floor(Math.random() * heroSubtitles.length)]
})
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.plaza-hot-card:focus-visible,
.plaza-grid-card:focus-visible {
  outline: 2px solid rgba(122, 87, 209, 0.45);
  outline-offset: 2px;
}
</style>
