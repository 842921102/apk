<template>
  <AppConfirmDialog
    v-if="payload"
    :title="payload.title"
    :message="payload.message"
    :confirm-text="payload.confirmText"
    :cancel-text="payload.cancelText"
    :danger="payload.danger"
    :title-id-suffix="String(payload.id)"
    @confirm="onConfirm"
    @cancel="onCancel"
  />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import AppConfirmDialog from './AppConfirmDialog.vue'

export type AppConfirmOpenDetail = {
  id: number
  title: string
  message: string
  confirmText: string
  cancelText: string
  danger: boolean
}

const payload = ref<AppConfirmOpenDetail | null>(null)

function onOpen(e: Event) {
  const d = (e as CustomEvent<AppConfirmOpenDetail>).detail
  if (!d?.id) return
  payload.value = d
}

function respond(confirmed: boolean) {
  if (!payload.value) return
  const id = payload.value.id
  payload.value = null
  window.dispatchEvent(new CustomEvent('app-confirm-response', { detail: { id, confirmed } }))
}

function onConfirm() {
  respond(true)
}

function onCancel() {
  respond(false)
}

onMounted(() => {
  window.addEventListener('app-confirm-open', onOpen as EventListener)
})

onUnmounted(() => {
  window.removeEventListener('app-confirm-open', onOpen as EventListener)
})
</script>
