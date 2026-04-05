<template>
  <view class="mp-page rp">
    <view class="mp-card rp__lead">
      <text class="rp__lead-k">推荐偏好</text>
      <text class="rp__lead-body">
        长期画像与「吃什么」推荐直接挂钩，可随时修改。主入口为下方「编辑推荐画像」。
      </text>
    </view>

    <view class="mp-card rp__hero" @click="go('/pages/me/recommendation-profile-edit')">
      <view class="rp__hero-mid">
        <text class="rp__hero-name">编辑推荐画像</text>
        <text class="rp__hero-desc">基础信息、口味、忌口、目标、生理期与推荐风格</text>
      </view>
      <text class="rp__hero-arrow">›</text>
    </view>

    <view class="mp-card rp__card">
      <text class="rp__sec-k">更多入口</text>
      <view class="rp__row" @click="goOnboarding">
        <text class="rp__row-ico">✨</text>
        <view class="rp__row-mid">
          <text class="rp__row-name">重新了解口味</text>
          <text class="rp__row-desc">再走一遍口味问卷（与首次引导相同流程）</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
      <view class="rp__row" @click="go('/pages/me/diet-preferences')">
        <text class="rp__row-ico">🥗</text>
        <view class="rp__row-mid">
          <text class="rp__row-name">饮食偏好</text>
          <text class="rp__row-desc">口味、忌口、饮食类型与健康目标</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
      <view class="rp__row" @click="go('/pages/me/basic-profile')">
        <text class="rp__row-ico">👤</text>
        <view class="rp__row-mid">
          <text class="rp__row-name">基础资料</text>
          <text class="rp__row-desc">生日与性别等基础信息</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
      <view class="rp__row rp__row--last" @click="go('/pages/me/recommend-settings')">
        <text class="rp__row-ico">⚙️</text>
        <view class="rp__row-mid">
          <text class="rp__row-name">推荐设置</text>
          <text class="rp__row-desc">食命推荐、特殊时期等能力开关</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
    </view>

    <view class="mp-card rp__card">
      <text class="rp__sec-k">菜谱</text>
      <view class="rp__row rp__row--last" @click="go('/pages/recipe-favorites/index')">
        <text class="rp__row-ico">📖</text>
        <view class="rp__row-mid">
          <text class="rp__row-name">标准菜谱收藏</text>
          <text class="rp__row-desc">已收藏的标准做法，与「我的收藏」中的生成结果不同</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
    </view>

    <view class="rp__placeholder mp-card mp-card--inset">
      <text class="rp__ph-k">说明</text>
      <text class="rp__ph-txt">上方「编辑推荐画像」已覆盖问卷核心字段；分栏入口适合只改单项时使用。</text>
    </view>
  </view>
</template>

<script setup lang="ts">
function go(url: string) {
  uni.navigateTo({ url })
}

function goOnboarding() {
  uni.navigateTo({
    url: `/pages/onboarding/index?redirect=${encodeURIComponent('/pages/me/recommendation-preferences')}`,
  })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.rp {
  padding: 24rpx 24rpx 48rpx;
  box-sizing: border-box;
  min-height: 100vh;
  background: linear-gradient(180deg, #f6f4fc 0%, #f9fafb 120rpx);
}

.rp__lead {
  padding: 26rpx 22rpx 28rpx;
  margin-bottom: 20rpx;
  border-color: $mp-ring-accent;
}

.rp__lead-k {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: $mp-accent;
  text-transform: uppercase;
  margin-bottom: 12rpx;
}

.rp__lead-body {
  display: block;
  font-size: 26rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.rp__hero {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 28rpx 24rpx;
  margin-bottom: 20rpx;
  border-color: $mp-ring-accent;
  background: linear-gradient(135deg, rgba(122, 87, 209, 0.12) 0%, #fff 55%);
}

.rp__hero:active {
  opacity: 0.92;
}

.rp__hero-mid {
  flex: 1;
  min-width: 0;
}

.rp__hero-name {
  display: block;
  font-size: 32rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.rp__hero-desc {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $mp-text-muted;
  line-height: 1.4;
}

.rp__hero-arrow {
  font-size: 40rpx;
  color: $mp-accent;
  flex-shrink: 0;
}

.rp__card {
  padding: 12rpx 8rpx 16rpx;
  margin-bottom: 20rpx;
}

.rp__sec-k {
  display: block;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-text-muted;
  padding: 12rpx 16rpx 8rpx;
}

.rp__row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 22rpx 16rpx;
  border-radius: 16rpx;
}

.rp__row:active {
  background: #f5f6f8;
}

.rp__row--last {
  margin-bottom: 0;
}

.rp__row-ico {
  font-size: 34rpx;
  width: 48rpx;
  text-align: center;
  flex-shrink: 0;
}

.rp__row-mid {
  flex: 1;
  min-width: 0;
}

.rp__row-name {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.rp__row-desc {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
  line-height: 1.4;
}

.rp__row-arrow {
  font-size: 32rpx;
  color: $mp-text-muted;
  flex-shrink: 0;
}

.rp__placeholder {
  padding: 22rpx 20rpx;
}

.rp__ph-k {
  display: block;
  font-size: 24rpx;
  font-weight: 800;
  color: $mp-text-secondary;
  margin-bottom: 8rpx;
}

.rp__ph-txt {
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-muted;
}
</style>
