<template>
  <view class="te-body">
    <view class="mp-card te-body__main-plate">
      <image
        v-if="coverImage"
        class="te-body__main-cover"
        :src="coverImage"
        mode="aspectFill"
      />
      <view class="te-body__main-plate-inner">
        <text class="te-body__main-kicker">今日专属</text>
        <text class="te-body__main-dish">{{ recommendedDish }}</text>
        <text class="te-body__main-tagline">{{ mainTagline }}</text>
      </view>
    </view>

    <view v-if="tags.length" class="te-body__explain-block">
      <text class="te-body__explain-label">推荐标签</text>
      <view class="te-body__tag-row te-body__tag-row--hero">
        <text v-for="(t, i) in tags" :key="i" class="te-body__tag-pill">{{ t }}</text>
      </view>
    </view>

    <view class="te-body__explain-block te-body__explain-block--reason">
      <text class="te-body__explain-title">为什么今天推荐这个？</text>
      <view class="te-body__reason-card">
        <text class="te-body__reason-body">{{ reasonText }}</text>
      </view>
    </view>

    <view v-if="destinyText.trim()" class="te-body__explain-block te-body__explain-block--destiny">
      <text class="te-body__explain-title te-body__explain-title--destiny">今日食命</text>
      <view class="te-body__destiny-card">
        <text class="te-body__destiny-body">{{ destinyText }}</text>
      </view>
    </view>

    <view v-if="alternatives.length" class="te-body__explain-block">
      <text class="te-body__explain-title">如果你想换一种，也可以试试</text>
      <view class="te-body__alt-actions">
        <view
          v-for="(alt, i) in alternatives"
          :key="i"
          class="te-body__alt-chip"
          :class="{ 'te-body__alt-chip--tap': alternativesInteractive }"
          :hover-class="alternativesInteractive ? 'te-body__alt-chip--hover' : ''"
          @click="onAltTap(alt)"
        >
          <text class="te-body__alt-chip-txt">{{ alt }}</text>
        </view>
      </view>
      <text v-if="alternativesInteractive" class="te-body__alt-hint">
        点选备选可将该菜升为主推荐，并生成专属说明；与顶部「换一个推荐」不同
      </text>
      <text v-else class="te-body__alt-hint">以下为当时推荐快照中的备选菜品</text>
    </view>

    <view class="te-body__detail-fold">
      <text class="te-body__detail-fold-k">制作参考</text>
      <view class="te-body__meta-grid te-body__meta-grid--compact">
        <view class="te-body__meta-cell">
          <text class="te-body__meta-label">菜系</text>
          <text class="te-body__meta-value">{{ cuisine || '—' }}</text>
        </view>
        <view class="te-body__meta-cell te-body__meta-cell--wide">
          <text class="te-body__meta-label">食材</text>
          <text class="te-body__meta-value">{{ ingredientsText }}</text>
        </view>
      </view>
      <view class="te-body__body-sheet te-body__body-sheet--muted">
        <text class="te-body__body-text">{{ recipeContent }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    coverImage?: string | null
    recommendedDish: string
    mainTagline: string
    tags: string[]
    reasonText: string
    destinyText?: string
    alternatives: string[]
    alternativesInteractive?: boolean
    cuisine?: string | null
    ingredientsText: string
    recipeContent: string
  }>(),
  {
    coverImage: null,
    destinyText: '',
    cuisine: null,
    alternativesInteractive: false,
  },
)

const emit = defineEmits<{
  selectAlternative: [dish: string]
}>()

function onAltTap(alt: string) {
  if (!props.alternativesInteractive) return
  const name = (alt || '').trim()
  if (!name) return
  emit('selectAlternative', name)
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.te-body {
  display: flex;
  flex-direction: column;
  gap: 28rpx;
}

.te-body__main-plate {
  padding: 0;
  overflow: hidden;
  border-color: $mp-ring-accent;
  box-shadow: 0 16rpx 44rpx rgba(122, 87, 209, 0.14);
}

.te-body__main-cover {
  width: 100%;
  height: 280rpx;
  display: block;
  background: #eceef2;
}

.te-body__main-plate-inner {
  padding: 32rpx 28rpx 36rpx;
  background: linear-gradient(180deg, #fff 0%, rgba(243, 236, 255, 0.4) 100%);
}

.te-body__main-kicker {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: $mp-accent;
  text-transform: uppercase;
}

.te-body__main-dish {
  display: block;
  margin-top: 12rpx;
  font-size: 44rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.25;
  letter-spacing: -0.02em;
  word-break: break-word;
}

.te-body__main-tagline {
  display: block;
  margin-top: 16rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.te-body__explain-block {
  padding: 0 2rpx;
}

.te-body__explain-label {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: $mp-text-muted;
  text-transform: uppercase;
}

.te-body__explain-title {
  display: block;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 16rpx;
  line-height: 1.35;
}

.te-body__explain-title--destiny {
  color: #6b21a8;
}

.te-body__tag-row--hero {
  margin-top: 12rpx;
  gap: 14rpx;
}

.te-body__tag-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.te-body__tag-pill {
  padding: 12rpx 22rpx;
  border-radius: 999rpx;
  font-size: 24rpx;
  font-weight: 600;
  color: $mp-accent;
  background: $mp-accent-soft;
  border: 1rpx solid rgba(122, 87, 209, 0.35);
}

.te-body__reason-card {
  padding: 24rpx 26rpx;
  border-radius: 20rpx;
  background: #f0fdf4;
  border: 1rpx solid #bbf7d0;
}

.te-body__reason-body {
  font-size: 28rpx;
  line-height: 1.65;
  color: #166534;
  white-space: pre-wrap;
  word-break: break-word;
}

.te-body__destiny-card {
  padding: 24rpx 26rpx;
  border-radius: 20rpx;
  background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%);
  border: 1rpx solid #e9d5ff;
}

.te-body__destiny-body {
  font-size: 28rpx;
  line-height: 1.6;
  color: #5b21b6;
  font-weight: 600;
  word-break: break-word;
}

.te-body__alt-actions {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.te-body__alt-chip {
  display: flex;
  align-items: center;
  padding: 22rpx 24rpx;
  border-radius: 16rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.te-body__alt-chip--tap:active {
  opacity: 0.88;
}

.te-body__alt-chip--hover {
  background: rgba(243, 236, 255, 0.65);
  border-color: $mp-ring-accent;
}

.te-body__alt-chip-txt {
  display: block;
  width: 100%;
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-primary;
  line-height: 1.4;
}

.te-body__alt-hint {
  display: block;
  margin-top: 12rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
  line-height: 1.45;
}

.te-body__detail-fold {
  margin-top: 8rpx;
  padding: 24rpx 20rpx;
  border-radius: 20rpx;
  background: #fff;
  border: 1rpx solid $mp-border;
}

.te-body__detail-fold-k {
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: $mp-text-muted;
  text-transform: uppercase;
  margin-bottom: 16rpx;
}

.te-body__meta-grid--compact {
  margin-top: 0;
}

.te-body__meta-grid {
  margin-top: 24rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16rpx;
}

.te-body__meta-cell {
  flex: 1;
  min-width: 200rpx;
  padding: 20rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.te-body__meta-cell--wide {
  flex: 1 1 100%;
  min-width: 0;
}

.te-body__meta-label {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-text-muted;
}

.te-body__meta-value {
  display: block;
  margin-top: 10rpx;
  font-size: 28rpx;
  color: #374151;
  line-height: 1.45;
  word-break: break-all;
}

.te-body__body-sheet--muted {
  margin-top: 16rpx;
  padding: 22rpx 24rpx;
  border-radius: 16rpx;
  background: #f9fafb;
  border: 1rpx solid #e5e7eb;
}

.te-body__body-text {
  font-size: 28rpx;
  line-height: 1.65;
  color: #1f2937;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
