<template>
  <AppConfirmDialog
    :title="title"
    :message="message"
    confirm-text="确认"
    cancel-text="取消"
    :danger="true"
    title-id-suffix="confirm-modal"
    @confirm="$emit('confirm')"
    @cancel="$emit('cancel')"
  />
</template>

<script setup lang="ts">
import AppConfirmDialog from './AppConfirmDialog.vue'

interface Props {
  title: string
  message: string
}

defineProps<Props>()
defineEmits<{
  confirm: []
  cancel: []
}>()
</script>
