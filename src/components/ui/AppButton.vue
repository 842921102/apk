<template>
  <button
    class="app-btn"
    :class="[variantClass, sizeClass, blockClass, { 'is-loading': loading }]"
    :type="nativeType"
    :disabled="disabled || loading"
    :aria-busy="loading ? 'true' : undefined"
  >
    <span v-if="loading" class="app-btn__spinner" aria-hidden="true" />
    <span :class="{ 'opacity-0': loading && hideLabelWhenLoading }">
      <slot />
    </span>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
    size?: 'sm' | 'md' | 'lg' | 'xs'
    block?: boolean
    disabled?: boolean
    loading?: boolean
    /** loading 时是否隐藏文字（仅显示转圈） */
    hideLabelWhenLoading?: boolean
    nativeType?: 'button' | 'submit' | 'reset'
  }>(),
  {
    variant: 'primary',
    size: 'md',
    block: false,
    disabled: false,
    loading: false,
    hideLabelWhenLoading: false,
    nativeType: 'button'
  }
)

const variantClass = computed(() => {
  const m = {
    primary: 'app-btn--primary',
    secondary: 'app-btn--secondary',
    ghost: 'app-btn--ghost',
    danger: 'app-btn--danger'
  }[props.variant]
  return m
})

const sizeClass = computed(() =>
  props.size === 'md' ? 'app-btn--md' : `app-btn--${props.size}`
)

const blockClass = computed(() => (props.block ? 'w-full' : ''))
</script>
