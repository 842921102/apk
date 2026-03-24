<template>
  <div :class="withPanel ? 'rounded-[20px] bg-app-card p-6 shadow-app-card ring-1 ring-app-line md:p-8' : ''">
    <div class="app-empty py-2">
      <div
        class="app-empty__icon mx-auto !mb-4 flex !h-[4rem] !w-[4rem] items-center justify-center rounded-2xl bg-app-accent-soft ring-1 ring-app-line-soft"
        aria-hidden="true"
      >
        <slot name="icon">
          <AppStrokeIcon
            v-if="stroke"
            :name="stroke"
            :size="strokeSize"
            class="text-app-accent"
          />
          <span v-else class="text-[2rem] leading-none">{{ icon }}</span>
        </slot>
      </div>
      <p class="app-empty__title !text-[17px] !text-app-fg">
        <slot name="title">{{ title }}</slot>
      </p>
      <p v-if="description || $slots.default" class="app-empty__desc !mt-2 !max-w-md !text-[13px]">
        <slot>{{ description }}</slot>
      </p>
      <div v-if="$slots.actions" class="mt-6 flex w-full max-w-xs flex-col items-center gap-3 sm:max-w-sm">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import type { StrokeIconName } from '@/components/icons/strokeIconPaths'

withDefaults(
  defineProps<{
    /** 兼容旧用法：无 stroke 时显示 emoji */
    icon?: string
    /** 统一线性图标（优先于 emoji） */
    stroke?: StrokeIconName
    strokeSize?: number
    title?: string
    description?: string
    /** 外层白卡片容器，适合嵌入列表页 */
    withPanel?: boolean
  }>(),
  {
    icon: '✨',
    stroke: undefined,
    strokeSize: 28,
    title: '',
    description: '',
    withPanel: true
  }
)
</script>
