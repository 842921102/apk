<template>
  <Teleport to="body">
    <div
      class="app-confirm-backdrop app-confirm-backdrop--motion fixed inset-0 z-[100] flex items-end justify-center bg-black/45 p-4 backdrop-blur-[3px] sm:items-center"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      @click.self="emit('cancel')"
    >
      <div
        class="app-confirm-panel w-full max-w-md origin-bottom animate-app-confirm-in rounded-[22px] bg-app-card shadow-[0_16px_48px_rgba(0,0,0,0.12)] ring-1 ring-app-line sm:origin-center"
        @click.stop
      >
        <div class="border-b border-app-line bg-gradient-to-r from-app-accent-soft/90 to-app-page px-5 py-4">
          <h3 :id="titleId" class="text-[17px] font-bold leading-snug text-app-fg">{{ title }}</h3>
          <p v-if="message" class="mt-1.5 text-[13px] leading-relaxed text-app-muted">{{ message }}</p>
        </div>
        <div class="flex gap-3 px-5 py-4">
          <button
            type="button"
            class="app-btn app-btn--secondary app-btn--md flex-1"
            @click="emit('cancel')"
          >
            {{ cancelText }}
          </button>
          <button
            type="button"
            class="app-btn app-btn--md flex-1"
            :class="danger ? 'bg-gradient-to-r from-rose-500 to-rose-600 text-white shadow-md ring-0 hover:opacity-95' : 'app-btn--primary'"
            @click="emit('confirm')"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    title: string
    message?: string
    confirmText?: string
    cancelText?: string
    /** false 时使用紫色主按钮（非危险操作） */
    danger?: boolean
    /** 用于 aria-labelledby */
    titleIdSuffix?: string
  }>(),
  {
    message: '',
    confirmText: '确认',
    cancelText: '取消',
    danger: true,
    titleIdSuffix: 'default'
  }
)

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const titleId = computed(() => `app-confirm-title-${props.titleIdSuffix}`)
</script>

<style scoped>
@keyframes app-confirm-backdrop-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.app-confirm-backdrop--motion {
  animation: app-confirm-backdrop-in 0.32s cubic-bezier(0.33, 1, 0.68, 1) both;
}

@keyframes app-confirm-in {
  from {
    opacity: 0;
    transform: translateY(14px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
.animate-app-confirm-in {
  animation: app-confirm-in 0.38s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@media (min-width: 640px) {
  @keyframes app-confirm-in {
    from {
      opacity: 0;
      transform: translateY(0) scale(0.965);
    }
    to {
      opacity: 1;
      transform: scale(1);
    }
  }
}
</style>
