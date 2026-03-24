<template>
  <AppStateEmpty
    :icon="icon"
    :stroke="stroke"
    :title="title"
    :description="description"
    :with-panel="withPanel"
  >
    <slot />
    <template #icon><slot name="icon" /></template>
    <template #title><slot name="title" /></template>
    <template #actions><slot name="actions" /></template>
  </AppStateEmpty>
</template>

<script setup lang="ts">
import AppStateEmpty from './AppStateEmpty.vue'
import type { StrokeIconName } from '@/components/icons/strokeIconPaths'

withDefaults(
  defineProps<{
    icon?: string
    stroke?: StrokeIconName
    title?: string
    description?: string
    withPanel?: boolean
  }>(),
  {
    icon: '✨',
    stroke: undefined,
    title: '',
    description: '',
    withPanel: true
  }
)
</script>
