<template>
  <div id="app" class="min-h-screen">
    <div :class="contentPadClass">
      <router-view v-slot="{ Component, route }">
        <transition :name="routeTransitionName(route)" mode="out-in">
          <component :is="Component" :key="routeTransitionKey(route)" />
        </transition>
      </router-view>
    </div>
    <BottomTabBar v-if="showBottomTabBar" />
    <GlobalNoticeModal />
    <AppConfirmHost />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import type { RouteLocationNormalizedLoaded } from 'vue-router'
import AppConfirmHost from './components/AppConfirmHost.vue'
import GlobalNoticeModal from './components/GlobalNoticeModal.vue'
import BottomTabBar from './components/BottomTabBar.vue'
import { shouldShowBottomTabBar } from './utils/bottomTabBar'

const route = useRoute()

const showBottomTabBar = computed(() => shouldShowBottomTabBar(route.path))

/** 移动端为底部栏预留空间（md 及以上不显示底栏，无需 padding） */
const contentPadClass = computed(() =>
  showBottomTabBar.value ? 'pb-[calc(4.25rem+env(safe-area-inset-bottom))] md:pb-0' : ''
)

function routeTransitionName(route: RouteLocationNormalizedLoaded) {
  return shouldShowBottomTabBar(route.path) ? 'app-tab' : 'app-route-none'
}

function routeTransitionKey(route: RouteLocationNormalizedLoaded) {
  return shouldShowBottomTabBar(route.path) ? route.path : route.fullPath
}
</script>
