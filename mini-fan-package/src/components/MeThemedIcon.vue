<script setup lang="ts">
import { computed } from 'vue'
import { type MeThemedIconName, getMeThemedIconSrc } from '@/lib/meThemedIcons'

const props = withDefaults(
  defineProps<{
    name: MeThemedIconName
    /** 宽高（rpx） */
    sizeRpx?: number
  }>(),
  { sizeRpx: 36 },
)

const src = computed(() => getMeThemedIconSrc(props.name))
const wh = computed(() => ({
  width: `${props.sizeRpx}rpx`,
  height: `${props.sizeRpx}rpx`,
}))
</script>

<template>
  <image class="me-themed-icon" :src="src" mode="aspectFit" :style="wh" />
</template>

<style scoped lang="scss">
.me-themed-icon {
  display: block;
  flex-shrink: 0;
}
</style>
