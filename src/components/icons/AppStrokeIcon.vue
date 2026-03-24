<template>
  <svg
    v-if="paths.length"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    class="shrink-0 text-current"
    aria-hidden="true"
    focusable="false"
  >
    <path
      v-for="(d, i) in paths"
      :key="i"
      :d="d"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { STROKE_PATHS, type StrokeIconName } from './strokeIconPaths'

const props = withDefaults(
  defineProps<{
    name: StrokeIconName
    size?: number | string
  }>(),
  {
    size: 24
  }
)

const paths = computed(() => STROKE_PATHS[props.name] ?? [])
</script>
