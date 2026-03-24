<template>
  <div class="w-full">
    <label v-if="label" class="app-label" :for="inputId">{{ label }}</label>
    <input
      :id="inputId"
      class="app-input"
      :class="{ 'app-input--invalid': !!errorMessage }"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :aria-invalid="errorMessage ? 'true' : undefined"
      :aria-describedby="errorMessage ? `${inputId}-err` : undefined"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="errorMessage" :id="`${inputId}-err`" class="app-form-error" role="alert">
      {{ errorMessage }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    placeholder?: string
    type?: string
    disabled?: boolean
    autocomplete?: string
    id?: string
    /** 校验错误文案，展示为统一错误块 */
    errorMessage?: string
  }>(),
  {
    label: '',
    placeholder: '',
    type: 'text',
    disabled: false,
    autocomplete: 'off',
    id: '',
    errorMessage: ''
  }
)

defineEmits<{
  'update:modelValue': [value: string]
}>()

const stableId = `app-input-${Math.random().toString(36).slice(2, 11)}`
const inputId = computed(() => props.id || stableId)
</script>
