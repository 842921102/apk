<template>
  <Teleport to="body">
    <div
      class="image-modal-backdrop fixed inset-0 z-[90] flex items-end justify-center bg-black/50 p-2 backdrop-blur-[2px] sm:items-center md:p-4"
      @click.self="$emit('close')"
    >
      <div
        class="flex max-h-[96vh] w-full max-w-4xl flex-col overflow-hidden rounded-[22px] bg-app-card shadow-[0_20px_60px_rgba(0,0,0,0.2)] ring-1 ring-app-line animate-image-modal-in sm:max-h-[92vh]"
        @click.stop
      >
        <!-- 标题区 -->
        <div
          class="flex shrink-0 items-start justify-between gap-3 border-b border-app-line bg-gradient-to-r from-app-accent-soft/95 to-app-page px-4 py-4 md:px-6"
        >
          <div class="min-w-0">
            <h3 class="text-[17px] font-bold leading-snug text-app-fg">{{ image.recipeName }}</h3>
            <p class="mt-1 text-[13px] text-app-muted">
              {{ image.cuisine }} · {{ formatDate(image.generatedAt) }}
            </p>
          </div>
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/90 text-xl leading-none text-app-muted ring-1 ring-app-line transition-colors hover:bg-app-page hover:text-app-fg"
            aria-label="关闭"
            @click="$emit('close')"
          >
            ×
          </button>
        </div>

        <!-- 图片 -->
        <div class="relative flex min-h-[40vh] flex-1 items-center justify-center bg-[#0a0a0a] md:min-h-[50vh]">
          <img :src="image.url" :alt="image.recipeName" class="max-h-[min(70vh,720px)] max-w-full object-contain" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import type { GalleryImage } from '@/services/galleryService'

interface Props {
  image: GalleryImage
}

defineProps<Props>()

defineEmits<{
  close: []
}>()

function formatDate(dateString: string) {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 0) {
    return '今天'
  }
  if (diffDays === 1) {
    return '昨天'
  }
  if (diffDays < 7) {
    return `${diffDays}天前`
  }
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>

<style scoped>
@keyframes image-modal-backdrop-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.image-modal-backdrop {
  animation: image-modal-backdrop-in 0.32s cubic-bezier(0.33, 1, 0.68, 1) both;
}

@keyframes image-modal-in {
  from {
    opacity: 0;
    transform: translateY(12px) scale(0.985);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
.animate-image-modal-in {
  animation: image-modal-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@media (min-width: 640px) {
  @keyframes image-modal-in {
    from {
      opacity: 0;
      transform: scale(0.965);
    }
    to {
      opacity: 1;
      transform: scale(1);
    }
  }
}
</style>
