<template>
  <nav
    class="bottom-tab-bar fixed bottom-0 left-0 right-0 z-40 md:hidden"
    aria-label="主导航"
  >
    <div
      class="border-t border-[#E8EAEF] bg-[#FDFDFE]/98 shadow-[0_-4px_24px_rgba(15,23,42,0.06),0_-1px_0_rgba(255,255,255,0.9)_inset] backdrop-blur-[12px]"
    >
      <div
        class="mx-auto flex max-w-lg items-stretch justify-between gap-1 px-2.5 pt-2.5"
        :style="{
          paddingBottom: 'max(0.5rem, calc(0.35rem + env(safe-area-inset-bottom, 0px)))'
        }"
      >
        <router-link
          v-for="item in tabs"
          :key="item.path"
          :to="item.path"
          class="tab-item app-tap flex min-w-0 flex-1 flex-col items-center justify-center gap-0.5 rounded-2xl px-1 py-1.5 text-center outline-none focus-visible:ring-2 focus-visible:ring-[#7A57D1]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FDFDFE]"
          :class="
            isActive(item)
              ? 'tab-item--active'
              : 'tab-item--inactive'
          "
        >
          <span
            class="relative flex h-[1.625rem] flex-col items-center justify-end"
            aria-hidden="true"
          >
            <AppStrokeIcon
              :name="item.icon"
              :size="22"
              class="tab-icon transition-all duration-200 ease-out"
              :class="
                isActive(item)
                  ? 'scale-105 text-[#7A57D1] drop-shadow-[0_2px_10px_rgba(122,87,209,0.28)]'
                  : 'scale-100 text-[#8E95A3] opacity-[0.85]'
              "
            />
            <span
              class="mt-0.5 h-1 w-1 shrink-0 rounded-full transition-all duration-200 ease-out"
              :class="
                isActive(item)
                  ? 'scale-100 bg-[#7A57D1] opacity-100 shadow-[0_0_8px_rgba(122,87,209,0.55)]'
                  : 'scale-75 bg-[#7A57D1] opacity-0'
              "
            />
          </span>
          <span
            class="max-w-full truncate text-[11px] leading-tight tracking-wide transition-colors duration-200"
            :class="
              isActive(item)
                ? 'font-semibold text-[#7A57D1]'
                : 'font-medium text-[#8E95A3]'
            "
          >{{ item.label }}</span>
        </router-link>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import type { StrokeIconName } from '@/components/icons/strokeIconPaths'

const route = useRoute()

const tabs: readonly {
  path: string
  label: string
  icon: StrokeIconName
  match: (p: string) => boolean
}[] = [
  { path: '/', label: '首页', icon: 'home', match: (p: string) => p === '/' },
  { path: '/today-eat', label: '吃什么', icon: 'zap', match: (p: string) => p === '/today-eat' },
  { path: '/plaza', label: '菜单', icon: 'layoutGrid', match: (p: string) => p === '/plaza' },
  { path: '/me', label: '我的', icon: 'user', match: (p: string) => p === '/me' }
] as const

function isActive(item: (typeof tabs)[number]) {
  return item.match(route.path)
}
</script>

<style scoped>
.bottom-tab-bar {
  -webkit-tap-highlight-color: transparent;
}

.tab-item--active {
  background: linear-gradient(180deg, rgba(122, 87, 209, 0.12) 0%, rgba(122, 87, 209, 0.06) 100%);
  box-shadow: 0 1px 0 rgba(255, 255, 255, 0.85) inset, 0 0 0 1px rgba(122, 87, 209, 0.12);
}

.tab-item--inactive:hover {
  background-color: rgba(15, 23, 42, 0.04);
}

.tab-item--inactive:active {
  background-color: rgba(15, 23, 42, 0.06);
}
</style>
