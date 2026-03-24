<template>
  <div class="min-h-screen bg-[#F5F6F8]">
    <GlobalNavigation />
    <main :class="mainClass">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import GlobalNavigation from '@/components/GlobalNavigation.vue'

const props = withDefaults(
  defineProps<{
    maxWidthClass?: string
    paddingClass?: string
  }>(),
  {
    maxWidthClass: 'max-w-7xl',
    paddingClass: 'px-3 pt-3 pb-6 md:px-6 md:pt-4 md:pb-8'
  }
)

const mainClass = computed(() => `mx-auto w-full ${props.maxWidthClass} ${props.paddingClass}`)
</script>
