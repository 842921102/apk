<template>
  <div
    class="rounded-[20px] bg-app-card px-5 py-8 text-center shadow-app-card ring-1 ring-app-line md:px-8"
    role="alert"
  >
    <div
      class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 ring-1 ring-rose-100"
      aria-hidden="true"
    >
      <slot name="icon">
        <AppStrokeIcon :name="stroke" :size="strokeSize" class="text-rose-600" />
      </slot>
    </div>
    <h3 class="text-[17px] font-bold text-app-fg">{{ title }}</h3>
    <p class="mx-auto mt-2 max-w-md text-[13px] leading-relaxed text-app-muted">
      {{ description }}
    </p>
    <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
      <button
        v-if="showRetry"
        type="button"
        class="app-btn app-btn--primary app-btn--md min-w-[140px]"
        :disabled="retryDisabled"
        @click="$emit('retry')"
      >
        {{ retryLabel }}
      </button>
      <slot name="secondary" />
    </div>
    <div v-if="$slots.extra" class="mt-4">
      <slot name="extra" />
    </div>
  </div>
</template>

<script setup lang="ts">
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'
import type { StrokeIconName } from '@/components/icons/strokeIconPaths'

withDefaults(
  defineProps<{
    title?: string
    description?: string
    stroke?: StrokeIconName
    strokeSize?: number
    retryLabel?: string
    showRetry?: boolean
    retryDisabled?: boolean
  }>(),
  {
    title: '出了一点问题',
    description: '请检查网络或稍后重试。',
    stroke: 'alertTriangle',
    strokeSize: 28,
    retryLabel: '重试',
    showRetry: true,
    retryDisabled: false
  }
)

defineEmits<{
  retry: []
}>()
</script>
