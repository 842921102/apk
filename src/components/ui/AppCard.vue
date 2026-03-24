<template>
  <div
    class="app-card"
    :class="[
      smallRadius && 'app-card--sm',
      pad === 'md' && 'app-card--pad',
      pad === 'sm' && 'app-card--pad-sm'
    ]"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    smallRadius?: boolean
    pad?: 'none' | 'sm' | 'md'
  }>(),
  {
    smallRadius: false,
    pad: 'none'
  }
)
</script>
