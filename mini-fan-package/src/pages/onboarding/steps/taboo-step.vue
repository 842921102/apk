<template>
  <view class="ts">
    <text class="ts__title">有没有你不吃或必须避开的食材？</text>
    <text class="ts__lead">
      下面分成「不喜欢吃」和「过敏 / 必须避免」两类，请分别选择。后续推荐会尽量避开这些食材。
    </text>

    <view class="mp-card ts__module">
      <view class="ts__module-head">
        <text class="ts__module-k">口味习惯</text>
        <text class="ts__module-title">不喜欢吃</text>
        <text class="ts__module-hint">偏主观偏好，可按日常口味勾选</text>
      </view>
      <OptionChipGroup
        v-model="dislikeInner"
        mutual-exclusive-none-id="暂无"
        :options="dislikeOptions"
      />
    </view>

    <view class="mp-card ts__module ts__module--accent">
      <view class="ts__module-head">
        <text class="ts__module-k ts__module-k--emph">健康与安全</text>
        <text class="ts__module-title">过敏 / 必须避免</text>
        <text class="ts__module-hint">涉及过敏或不耐受，请务必如实勾选</text>
      </view>
      <OptionChipGroup
        v-model="allergyInner"
        mutual-exclusive-none-id="暂无"
        :options="allergyOptions"
      />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import OptionChipGroup from '@/components/onboarding/OptionChipGroup.vue'

const props = defineProps<{
  dislikeIngredients: string[]
  allergyIngredients: string[]
}>()

const emit = defineEmits<{
  'update:dislikeIngredients': [string[]]
  'update:allergyIngredients': [string[]]
}>()

const dislikeInner = computed({
  get: () => props.dislikeIngredients,
  set: (v) => emit('update:dislikeIngredients', v),
})

const allergyInner = computed({
  get: () => props.allergyIngredients,
  set: (v) => emit('update:allergyIngredients', v),
})

const dislikeOptions = [
  { id: '牛肉', label: '牛肉' },
  { id: '羊肉', label: '羊肉' },
  { id: '海鲜', label: '海鲜' },
  { id: '香菜', label: '香菜' },
  { id: '辣椒', label: '辣椒' },
  { id: '葱姜蒜', label: '葱姜蒜' },
  { id: '内脏', label: '内脏' },
  { id: '暂无', label: '暂无' },
]

const allergyOptions = [
  { id: '海鲜过敏', label: '海鲜过敏' },
  { id: '花生过敏', label: '花生过敏' },
  { id: '乳糖不耐', label: '乳糖不耐' },
  { id: '贝类敏感', label: '贝类敏感' },
  { id: '酒精敏感', label: '酒精敏感' },
  { id: '其他待补充', label: '其他待补充' },
  { id: '暂无', label: '暂无' },
]
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.ts {
  padding: 6rpx 0 28rpx;
}

.ts__title {
  display: block;
  font-size: 34rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
  letter-spacing: -0.02em;
}

.ts__lead {
  display: block;
  margin-top: 12rpx;
  margin-bottom: 22rpx;
  font-size: 23rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

.ts__module {
  padding: 22rpx 20rpx 22rpx;
}

.ts__module + .ts__module {
  margin-top: 16rpx;
}

.ts__module--accent {
  background: rgba(253, 250, 255, 0.95);
  border-color: $mp-ring-accent;
}

.ts__module-head {
  margin-bottom: 16rpx;
  padding-bottom: 14rpx;
  border-bottom: 1rpx solid #f0f2f5;
}

.ts__module-k {
  display: block;
  font-size: 20rpx;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: $mp-text-muted;
  text-transform: uppercase;
}

.ts__module-k--emph {
  color: $mp-accent;
}

.ts__module-title {
  display: block;
  margin-top: 8rpx;
  font-size: 27rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.ts__module-hint {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}
</style>
