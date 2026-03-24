<template>
  <div class="app-search">
    <span class="app-search__icon" aria-hidden="true">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
        />
      </svg>
    </span>
    <input
      :id="inputId"
      class="app-input app-search__input"
      :class="inputClass"
      type="search"
      autocomplete="off"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      v-bind="filteredAttrs"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    placeholder?: string
    disabled?: boolean
    id?: string
    inputClass?: string
  }>(),
  {
    placeholder: '',
    disabled: false,
    id: '',
    inputClass: ''
  }
)

defineEmits<{
  'update:modelValue': [value: string]
}>()

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()

const filteredAttrs = computed(() => {
  const { class: _c, style: _s, ...rest } = attrs as Record<string, unknown>
  return rest
})

const stableId = `app-search-${Math.random().toString(36).slice(2, 11)}`
const inputId = computed(() => props.id || stableId)
</script>
