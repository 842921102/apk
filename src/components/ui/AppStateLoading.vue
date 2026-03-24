<template>
  <!-- 骨架列表 -->
  <div
    v-if="variant === 'skeleton'"
    class="rounded-[20px] bg-app-card p-5 shadow-app-card ring-1 ring-app-line md:p-6"
    role="status"
    :aria-label="title || '加载中'"
  >
    <div v-if="title" class="mb-4 flex items-center gap-3">
      <div class="h-10 w-10 shrink-0 animate-pulse rounded-xl bg-app-line" />
      <div class="min-w-0 flex-1 space-y-2">
        <div class="h-4 w-[40%] max-w-[180px] animate-pulse rounded-lg bg-app-line" />
        <div class="h-3 w-[55%] max-w-[240px] animate-pulse rounded-lg bg-app-page" />
      </div>
    </div>
    <div class="space-y-3">
      <div
        v-for="i in rows"
        :key="i"
        class="h-[52px] animate-pulse rounded-xl bg-gradient-to-r from-app-page via-app-line/80 to-app-page bg-[length:200%_100%]"
        :style="{ animationDelay: `${i * 80}ms` }"
        :class="i % 2 === 0 ? 'opacity-90' : 'opacity-70'"
      />
    </div>
    <p v-if="hint" class="mt-4 text-center text-[12px] text-app-muted">{{ hint }}</p>
  </div>

  <!-- 卡片式加载（图标 + 文案 + 转圈） -->
  <div
    v-else
    class="rounded-[20px] bg-app-card px-5 py-10 text-center shadow-app-card ring-1 ring-app-line md:px-8 md:py-12"
    role="status"
    :aria-label="title || '加载中'"
  >
    <div
      class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-app-accent-soft text-2xl ring-1 ring-app-line-soft"
    >
      <span class="asl-pulse">{{ emoji }}</span>
    </div>
    <h3 class="text-[16px] font-bold text-app-fg md:text-lg">{{ title }}</h3>
    <p v-if="subtitle" class="mt-2 text-[13px] leading-relaxed text-app-muted">{{ subtitle }}</p>
    <div class="mt-5 flex justify-center">
      <div
        class="h-9 w-9 animate-spin rounded-full border-2 border-app-line border-t-app-accent"
        aria-hidden="true"
      />
    </div>
    <div v-if="dots" class="mt-4 flex justify-center gap-1 text-app-muted">
      <span class="animate-bounce text-xs" style="animation-delay: 0ms">●</span>
      <span class="animate-bounce text-xs" style="animation-delay: 150ms">●</span>
      <span class="animate-bounce text-xs" style="animation-delay: 300ms">●</span>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'card' | 'skeleton'
    title?: string
    subtitle?: string
    hint?: string
    emoji?: string
    /** skeleton 模式下的行数 */
    rows?: number
    dots?: boolean
  }>(),
  {
    variant: 'card',
    title: '加载中…',
    subtitle: '',
    hint: '',
    emoji: '✨',
    rows: 4,
    dots: false
  }
)
</script>

<style scoped>
@keyframes asl-soft-pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.06);
    opacity: 0.85;
  }
}
.asl-pulse {
  display: inline-block;
  animation: asl-soft-pulse 1.4s ease-in-out infinite;
}
</style>
