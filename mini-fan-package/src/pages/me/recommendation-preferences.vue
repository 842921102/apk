<template>
  <view class="mp-page rp">
    <view class="mp-card rp__lead">
      <text class="rp__lead-k">推荐偏好</text>
      <text class="rp__lead-body">
        推荐画像会持续影响「今天吃什么」结果。建议优先维护下方「编辑推荐画像」，其余入口用于单项调整。
      </text>
    </view>

    <view class="mp-card rp__hero" @click="go('/pages/me/recommendation-profile-edit')">
      <view class="rp__hero-mid">
        <text class="rp__hero-name">编辑推荐画像</text>
        <text class="rp__hero-desc">统一维护基础信息、口味偏好、忌口与推荐风格</text>
      </view>
      <text class="rp__hero-arrow">›</text>
    </view>

    <view class="mp-card rp__card">
      <text class="rp__sec-k">更多入口</text>
      <view class="rp__row" @click="goOnboarding">
        <view class="rp__row-ico rp__row-ico--spark">
          <view class="rp__spark-star rp__spark-star--main" />
          <view class="rp__spark-star rp__spark-star--sub" />
        </view>
        <view class="rp__row-mid">
          <text class="rp__row-name">重新完成口味问卷</text>
          <text class="rp__row-desc">按完整流程重新评估口味偏好与饮食习惯</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
      <view class="rp__row" @click="go('/pages/me/diet-preferences')">
        <view class="rp__row-ico rp__row-ico--diet">
          <view class="rp__diet-bowl" />
          <view class="rp__diet-leaf rp__diet-leaf--l" />
          <view class="rp__diet-leaf rp__diet-leaf--r" />
        </view>
        <view class="rp__row-mid">
          <text class="rp__row-name">饮食偏好</text>
          <text class="rp__row-desc">管理口味倾向、忌口项与饮食目标</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
      <view class="rp__row" @click="go('/pages/me/basic-profile')">
        <view class="rp__row-ico rp__row-ico--profile">
          <view class="rp__profile-head" />
          <view class="rp__profile-body" />
        </view>
        <view class="rp__row-mid">
          <text class="rp__row-name">基础资料</text>
          <text class="rp__row-desc">维护年龄段、生日等基础信息</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
      <view class="rp__row rp__row--last" @click="go('/pages/me/recommend-settings')">
        <view class="rp__row-ico rp__row-ico--setting">
          <view class="rp__setting-core" />
          <view class="rp__setting-arm rp__setting-arm--v" />
          <view class="rp__setting-arm rp__setting-arm--h" />
        </view>
        <view class="rp__row-mid">
          <text class="rp__row-name">推荐设置</text>
          <text class="rp__row-desc">配置推荐能力开关与场景策略</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
    </view>

    <view class="mp-card rp__card">
      <text class="rp__sec-k">菜谱</text>
      <view class="rp__row rp__row--last" @click="go('/pages/recipe-favorites/index')">
        <view class="rp__row-ico rp__row-ico--book">
          <view class="rp__book-cover" />
          <view class="rp__book-line" />
        </view>
        <view class="rp__row-mid">
          <text class="rp__row-name">标准菜谱收藏</text>
          <text class="rp__row-desc">查看已收藏的标准菜谱，与生成结果收藏分开管理</text>
        </view>
        <text class="rp__row-arrow">›</text>
      </view>
    </view>

    <view class="rp__placeholder mp-card mp-card--inset">
      <text class="rp__ph-k">说明</text>
      <text class="rp__ph-txt">为保证推荐准确性，建议定期更新推荐画像；仅调整单项时可使用分项入口快速修改。</text>
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
  width: 56rpx;
  height: 56rpx;
  border-radius: 16rpx;
  background: linear-gradient(165deg, #f5f0ff 0%, #ebe4ff 48%, #e2d9fc 100%);
  border: 1rpx solid rgba(123, 87, 228, 0.18);
  box-shadow: 0 6rpx 18rpx rgba(123, 87, 228, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  position: relative;
}

.rp__spark-star {
  position: absolute;
  background: linear-gradient(145deg, #966fec, #7b57e4);
}

.rp__spark-star--main {
  width: 16rpx;
  height: 16rpx;
  transform: rotate(45deg);
  border-radius: 4rpx;
}

.rp__spark-star--sub {
  width: 8rpx;
  height: 8rpx;
  right: 10rpx;
  top: 10rpx;
  transform: rotate(45deg);
  border-radius: 2rpx;
}

.rp__diet-bowl {
  width: 24rpx;
  height: 10rpx;
  border-radius: 0 0 12rpx 12rpx;
  background: linear-gradient(90deg, #8b5cf6, #6d3df5);
  position: absolute;
  bottom: 12rpx;
}

.rp__diet-leaf {
  position: absolute;
  width: 10rpx;
  height: 14rpx;
  border-radius: 10rpx 10rpx 10rpx 0;
  background: #8d72ea;
  transform: rotate(-25deg);
  top: 12rpx;
}

.rp__diet-leaf--l {
  left: 18rpx;
}

.rp__diet-leaf--r {
  left: 28rpx;
  transform: rotate(18deg);
}

.rp__profile-head {
  position: absolute;
  top: 12rpx;
  width: 14rpx;
  height: 14rpx;
  border-radius: 50%;
  background: #7b57e4;
}

.rp__profile-body {
  position: absolute;
  bottom: 10rpx;
  width: 24rpx;
  height: 14rpx;
  border-radius: 12rpx 12rpx 8rpx 8rpx;
  background: rgba(123, 87, 228, 0.9);
}

.rp__setting-core {
  width: 14rpx;
  height: 14rpx;
  border-radius: 50%;
  background: #7b57e4;
}

.rp__setting-arm {
  position: absolute;
  background: #9b82ed;
  border-radius: 4rpx;
}

.rp__setting-arm--v {
  width: 6rpx;
  height: 24rpx;
}

.rp__setting-arm--h {
  width: 24rpx;
  height: 6rpx;
}

.rp__book-cover {
  width: 24rpx;
  height: 28rpx;
  border-radius: 6rpx;
  background: linear-gradient(160deg, #8f68ea, #6d3df5);
}

.rp__book-line {
  position: absolute;
  left: 24rpx;
  width: 2rpx;
  height: 22rpx;
  background: rgba(255, 255, 255, 0.8);
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
