<template>
  <label
    class="app-switch"
    :class="{ 'is-disabled': disabled }"
    :for="switchId"
  >
    <input
      :id="switchId"
      class="app-switch__native"
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span class="app-switch__ui" aria-hidden="true">
      <span class="app-switch__thumb" />
    </span>
    <span v-if="label || $slots.default" class="app-switch__text">
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    label?: string
    disabled?: boolean
    id?: string
  }>(),
  {
    label: '',
    disabled: false,
    id: ''
  }
)

defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const stableId = `app-switch-${Math.random().toString(36).slice(2, 11)}`
const switchId = computed(() => props.id || stableId)
</script>
