<template>
  <view class="vs">
    <text class="vs__title">补充一下基础体征信息</text>
    <text class="vs__desc">
      用于后续做更贴合的健康饮食建议，例如减脂、轻负担、高蛋白、养胃等推荐。
    </text>

    <view class="mp-card vs__card">
      <view class="vs__row">
        <text class="vs__label">身高（cm）</text>
        <input
          class="vs__input"
          type="number"
          :value="heightText"
          placeholder="如 170"
          placeholder-class="vs__ph"
          @input="onHeightInput"
        />
      </view>
      <view class="vs__row">
        <text class="vs__label">体重（kg）</text>
        <input
          class="vs__input"
          type="digit"
          :value="weightText"
          placeholder="如 62.5"
          placeholder-class="vs__ph"
          @input="onWeightInput"
        />
      </view>
      <view class="vs__row">
        <text class="vs__label">目标体重（kg）<text class="vs__opt">可选</text></text>
        <input
          class="vs__input"
          type="digit"
          :value="targetText"
          placeholder="不填也可继续"
          placeholder-class="vs__ph"
          @input="onTargetInput"
        />
      </view>
    </view>

    <text class="vs__footnote">这里只做轻量饮食建议，不做医疗判断。</text>
  </view>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  heightCm: number | null
  weightKg: number | null
  targetWeightKg: number | null
}>()

const emit = defineEmits<{
  'update:heightCm': [number | null]
  'update:weightKg': [number | null]
  'update:targetWeightKg': [number | null]
}>()

const heightText = ref('')
const weightText = ref('')
const targetText = ref('')

function syncFromProps() {
  heightText.value = props.heightCm == null ? '' : String(props.heightCm)
  weightText.value = props.weightKg == null ? '' : String(props.weightKg)
  targetText.value = props.targetWeightKg == null ? '' : String(props.targetWeightKg)
}

watch(
  () => [props.heightCm, props.weightKg, props.targetWeightKg],
  () => syncFromProps(),
  { immediate: true },
)

function onHeightInput(e: { detail?: { value?: string } }) {
  const raw = String(e.detail?.value ?? '')
  heightText.value = raw
  if (!raw.trim()) {
    emit('update:heightCm', null)
    return
  }
  const n = parseInt(raw, 10)
  emit('update:heightCm', Number.isFinite(n) ? n : null)
}

function onWeightInput(e: { detail?: { value?: string } }) {
  const raw = String(e.detail?.value ?? '')
  weightText.value = raw
  if (!raw.trim()) {
    emit('update:weightKg', null)
    return
  }
  const n = parseFloat(raw)
  emit('update:weightKg', Number.isFinite(n) ? n : null)
}

function onTargetInput(e: { detail?: { value?: string } }) {
  const raw = String(e.detail?.value ?? '')
  targetText.value = raw
  if (!raw.trim()) {
    emit('update:targetWeightKg', null)
    return
  }
  const n = parseFloat(raw)
  emit('update:targetWeightKg', Number.isFinite(n) ? n : null)
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.vs {
  padding: 6rpx 0 28rpx;
}

.vs__title {
  display: block;
  font-size: 34rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.vs__desc {
  display: block;
  margin-top: 12rpx;
  margin-bottom: 22rpx;
  font-size: 23rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.vs__card {
  padding: 22rpx 20rpx 22rpx;
}

.vs__row + .vs__row {
  margin-top: 20rpx;
  padding-top: 20rpx;
  border-top: 1rpx solid #f3f4f6;
}

.vs__label {
  display: flex;
  align-items: center;
  gap: 12rpx;
  font-size: 24rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.vs__opt {
  font-size: 21rpx;
  font-weight: 500;
  color: $mp-text-muted;
}

.vs__input {
  margin-top: 10rpx;
  width: 100%;
  box-sizing: border-box;
  height: 80rpx;
  padding: 0 20rpx;
  background: #f3f4f6;
  border-radius: 14rpx;
  font-size: 24rpx;
  line-height: 80rpx;
  color: $mp-text-primary;
  border: 2rpx solid transparent;
}

.vs__input:focus {
  border-color: $mp-ring-accent;
  background: #fafbfc;
}

.vs__ph {
  color: #c4c8d0;
}

.vs__footnote {
  display: block;
  margin-top: 18rpx;
  font-size: 21rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}
</style>
