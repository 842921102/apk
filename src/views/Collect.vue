<template>
  <UserPageShell max-width-class="max-w-6xl" padding-class="px-3 pt-3 pb-6 md:px-5 md:pt-4 md:pb-8">
    <div class="mx-auto w-full">
      <div class="mb-4 rounded-[20px] bg-white p-1.5 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-[#ECEEF2]">
        <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="flex min-w-[140px] flex-1 items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-all"
          :class="[
            activeTab === 'favorites'
              ? 'bg-[#F3ECFF] text-[#7A57D1] ring-1 ring-[#E8DDF5]'
              : 'bg-[#FAFBFC] text-[#8A8F99] ring-1 ring-[#ECEEF2] hover:bg-white'
          ]"
          @click="setTab('favorites')"
        >
          <AppStrokeIcon name="heart" :size="20" class="shrink-0" />
          我的收藏
        </button>
        <button
          type="button"
          class="flex min-w-[140px] flex-1 items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-all"
          :class="[
            activeTab === 'histories'
              ? 'bg-[#F3ECFF] text-[#7A57D1] ring-1 ring-[#E8DDF5]'
              : 'bg-[#FAFBFC] text-[#8A8F99] ring-1 ring-[#ECEEF2] hover:bg-white'
          ]"
          @click="setTab('histories')"
        >
          <AppStrokeIcon name="scrollText" :size="20" class="shrink-0" />
          历史记录
        </button>
      </div>
      </div>

      <Favorites v-show="activeTab === 'favorites'" embedded />
      <Histories v-show="activeTab === 'histories'" embedded />
    </div>
  </UserPageShell>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserPageShell from '@/components/layout/UserPageShell.vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import Favorites from '@/views/Favorites.vue'
import Histories from '@/views/Histories.vue'

const route = useRoute()
const router = useRouter()
const activeTab = ref<'favorites' | 'histories'>('favorites')

function applyQueryTab() {
  activeTab.value = route.query.tab === 'histories' ? 'histories' : 'favorites'
}

watch(
  () => route.query.tab,
  () => applyQueryTab(),
  { immediate: true }
)

function setTab(tab: 'favorites' | 'histories') {
  activeTab.value = tab
  router.replace({ path: '/collect', query: tab === 'histories' ? { tab: 'histories' } : {} })
}
</script>
