<template>
  <view class="ocg">
    <view
      v-for="opt in options"
      :key="opt.id"
      class="ocg__chip"
      :class="{ 'ocg__chip--active': selectedSet.has(opt.id) }"
      @click="toggle(opt.id)"
    >
      <text class="ocg__txt">{{ opt.label }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

export type ChipOption = { id: string; label: string }

const props = defineProps<{
  options: ChipOption[]
  modelValue: string[]
  /**
   * 与选项 id 一致（如「暂无」）：点选时仅保留该项并清空同组其它项；
   * 点选其它项时先移除此项；再次点「暂无」可清空。
   */
  mutualExclusiveNoneId?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string[]]
}>()

const selectedSet = computed(() => new Set(props.modelValue))

function toggle(id: string) {
  const noneId = props.mutualExclusiveNoneId
  if (noneId && id === noneId) {
    const cur = props.modelValue
    if (cur.length === 1 && cur[0] === noneId) {
      emit('update:modelValue', [])
      return
    }
    emit('update:modelValue', [noneId])
    return
  }
  if (noneId) {
    const next = new Set(props.modelValue.filter((x) => x !== noneId))
    if (next.has(id)) next.delete(id)
    else next.add(id)
    emit('update:modelValue', [...next])
    return
  }
  const next = new Set(props.modelValue)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  emit('update:modelValue', [...next])
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.ocg {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
}

.ocg__chip {
  padding: 22rpx 36rpx;
  border-radius: 999rpx;
  background: #f3f4f6;
  border: 2rpx solid transparent;
  box-sizing: border-box;
}

.ocg__chip--active {
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
  box-shadow: 0 4rpx 16rpx rgba(122, 87, 209, 0.12);
}

.ocg__txt {
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.ocg__chip--active .ocg__txt {
  color: $mp-accent-deep;
}
</style>
