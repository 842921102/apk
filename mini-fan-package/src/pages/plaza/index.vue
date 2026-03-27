<template>
  <view class="mp-page plaza">
    <!-- 标题区：与 Web 广场头图一致的信息层级 -->
    <view class="mp-hero plaza__hero">
      <view class="plaza__hero-inner">
        <text class="mp-hero__kicker mp-kicker--on-dark">扩展玩法</text>
        <text class="mp-hero__title plaza__hero-title">{{ config.plaza_title }}</text>
        <text class="mp-hero__sub plaza__hero-sub">{{ config.plaza_subtitle }}</text>
        <view class="plaza__hero-rule" />
      </view>
    </view>

    <!-- 灵感：菜单 Tab 固定主入口（与下方可配置网格区分） -->
    <view class="plaza__circle-wrap">
      <view class="mp-card plaza__circle-card" @click="goInspiration">
        <view class="plaza__circle-top">
          <text class="plaza__circle-emoji">💬</text>
          <view class="plaza__circle-titles">
            <text class="plaza__circle-k">图片流</text>
            <text class="plaza__circle-title">灵感</text>
            <text class="plaza__circle-sub">浏览 AI 美食图与实拍内容（底部 Tab 也可进入）</text>
          </view>
          <text class="plaza__circle-arrow">→</text>
        </view>
      </view>
    </view>

    <!-- 列表区：功能入口 / 全部工具 -->
    <view class="plaza__section">
      <view class="plaza__section-head">
        <view class="plaza__section-titles">
          <text class="plaza__rail-k">功能入口</text>
          <text class="plaza__h2">全部工具</text>
          <text class="plaza__h2-sub">
            以下为远端配置的广场入口；「可进入」已接入小程序路由，「开发中」将在后续版本开放。
          </text>
          <text v-if="visibleEntries.length" class="plaza__h2-meta">
            共 {{ visibleEntries.length }} 项 · 已开放 {{ liveCount }} 项 · 开发中 {{ holdCount }} 项
          </text>
        </view>
        <text class="plaza__pill">精选</text>
      </view>

      <view class="plaza__legend">
        <view class="plaza__legend-item">
          <view class="plaza__legend-bar plaza__legend-bar--live" />
          <text class="plaza__legend-txt">可进入</text>
        </view>
        <view class="plaza__legend-item">
          <view class="plaza__legend-bar plaza__legend-bar--hold" />
          <text class="plaza__legend-txt">开发中</text>
        </view>
      </view>

      <view class="plaza__grid">
        <view
          v-for="item in visibleEntries"
          :key="item.key"
          class="plaza__card"
          :class="isPlaceholder(item) ? 'plaza__card--hold' : 'plaza__card--live'"
          @click="onEntryTap(item)"
        >
          <view class="plaza__card-inner">
            <view class="plaza__card-top">
              <view class="plaza__icon-wrap">
                <image
                  v-if="item.icon"
                  class="plaza__icon-img"
                  :src="item.icon"
                  mode="aspectFit"
                />
                <text v-else class="plaza__icon-emoji">✨</text>
              </view>
              <text
                class="plaza__status"
                :class="isPlaceholder(item) ? 'plaza__status--hold' : 'plaza__status--live'"
              >
                {{ isPlaceholder(item) ? '开发中' : '可进入' }}
              </text>
            </view>
            <text
              class="plaza__card-title"
              :class="{ 'plaza__card-title--hold': isPlaceholder(item) }"
            >{{ item.title }}</text>
            <text
              v-if="item.subtitle"
              class="plaza__card-sub"
              :class="{ 'plaza__card-sub--hold': isPlaceholder(item) }"
            >{{ item.subtitle }}</text>
            <view class="plaza__card-divider" />
            <view class="plaza__card-foot">
              <text
                class="plaza__card-action"
                :class="{ 'plaza__card-action--hold': isPlaceholder(item) }"
              >{{ isPlaceholder(item) ? '暂不可点' : '进入' }}</text>
              <text
                class="plaza__card-arrow"
                :class="{ 'plaza__card-arrow--hold': isPlaceholder(item) }"
              >→</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 说明区 -->
    <view class="plaza__section plaza__section--foot">
      <view class="mp-card mp-card--inset plaza__hint">
        <text class="plaza__rail-k plaza__rail-k--accent">说明</text>
        <text class="plaza__hint-title">小程序版持续补齐</text>
        <text class="plaza__hint-body">
          入口列表由后台「广场配置」下发并与默认配置合并；已配置路由的入口会跳转对应页，未配置或标记即将上线的入口仅作展示。
        </text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppConfig } from '@/composables/useAppConfig'
import { useVisiblePlazaEntries } from '@/composables/useVisiblePlazaEntries'
import { isKnownAppPageRoute, plazaNavTypeForRoute } from '@/lib/plazaNav'
import type { PlazaEntryConfig } from '@/types/plazaConfig'

const { config } = useAppConfig()

const visibleEntries = useVisiblePlazaEntries(config)

function isPlaceholder(item: PlazaEntryConfig): boolean {
  return Boolean(item.coming_soon) || !item.route?.trim()
}

const liveCount = computed(() =>
  visibleEntries.value.filter((e) => !isPlaceholder(e)).length,
)

const holdCount = computed(() =>
  visibleEntries.value.filter((e) => isPlaceholder(e)).length,
)

function goInspiration() {
  uni.switchTab({ url: '/pages/inspiration/index' })
}

function onEntryTap(item: PlazaEntryConfig) {
  if (isPlaceholder(item)) {
    uni.showToast({ title: '小程序版开发中', icon: 'none' })
    return
  }
  const url = item.route.trim()
  if (!url) {
    uni.showToast({ title: '敬请期待', icon: 'none' })
    return
  }
  if (!isKnownAppPageRoute(url)) {
    uni.showToast({ title: '页面未开放', icon: 'none' })
    return
  }
  if (plazaNavTypeForRoute(url) === 'switchTab') {
    uni.switchTab({ url })
    return
  }
  uni.navigateTo({ url })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.plaza__hero-inner {
  text-align: center;
}

.plaza__circle-wrap {
  padding: 0 32rpx 8rpx;
}

.plaza__circle-card {
  padding: 28rpx 28rpx 26rpx;
  border-radius: 20rpx;
  border: 1rpx solid rgba(122, 87, 209, 0.22);
  background: linear-gradient(135deg, rgba(122, 87, 209, 0.1) 0%, rgba(253, 253, 254, 1) 52%, rgba(253, 253, 254, 1) 100%);
}

.plaza__circle-top {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 20rpx;
}

.plaza__circle-emoji {
  font-size: 52rpx;
  line-height: 1;
}

.plaza__circle-titles {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.plaza__circle-k {
  font-size: 20rpx;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #7a57d1;
}

.plaza__circle-title {
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.plaza__circle-sub {
  font-size: 24rpx;
  color: $mp-text-muted;
  line-height: 1.4;
}

.plaza__circle-arrow {
  font-size: 36rpx;
  font-weight: 700;
  color: #7a57d1;
}

.plaza__hero-title {
  max-width: 640rpx;
  margin-left: auto;
  margin-right: auto;
}

.plaza__hero-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

.plaza__hero-rule {
  width: 72rpx;
  height: 6rpx;
  margin: 28rpx auto 0;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.45);
}

.plaza__section {
  margin-top: 8rpx;
}

.plaza__section--foot {
  margin-top: 12rpx;
  margin-bottom: 8rpx;
}

.plaza__section-head {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
  margin-bottom: 20rpx;
  padding: 0 4rpx;
}

.plaza__section-titles {
  flex: 1;
  min-width: 0;
}

.plaza__rail-k {
  display: block;
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: $mp-text-muted;
}

.plaza__rail-k--accent {
  color: $mp-accent;
}

.plaza__h2 {
  display: block;
  margin-top: 8rpx;
  font-size: 34rpx;
  font-weight: 800;
  color: $mp-text-primary;
  letter-spacing: -0.02em;
}

.plaza__h2-sub {
  display: block;
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.plaza__h2-meta {
  display: block;
  margin-top: 14rpx;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}

.plaza__pill {
  flex-shrink: 0;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
  padding: 10rpx 22rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.plaza__legend {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 24rpx;
  margin-bottom: 20rpx;
  padding: 16rpx 20rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.plaza__legend-item {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
}

.plaza__legend-bar {
  width: 8rpx;
  height: 32rpx;
  border-radius: 999rpx;
}

.plaza__legend-bar--live {
  background: linear-gradient(180deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.plaza__legend-bar--hold {
  background: linear-gradient(180deg, #d1d5db 0%, #9ca3af 100%);
}

.plaza__legend-txt {
  font-size: 24rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.plaza__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20rpx;
}

/* 统一卡片骨架：仅通过修饰符区分可用 / 开发中 */
.plaza__card {
  position: relative;
  border-radius: 24rpx;
  overflow: hidden;
  min-height: 260rpx;
}

.plaza__card-inner {
  position: relative;
  z-index: 1;
  height: 100%;
  min-height: 260rpx;
  padding: 24rpx 22rpx 22rpx 26rpx;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  background: $mp-surface;
  border: 1rpx solid $mp-border;
  border-radius: 24rpx;
  box-shadow: $mp-shadow-soft;
}

.plaza__card--live .plaza__card-inner {
  border-color: rgba(232, 221, 245, 0.95);
  box-shadow: 0 8rpx 28rpx rgba(122, 87, 209, 0.1);
}

.plaza__card--live::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 10rpx;
  z-index: 2;
  border-radius: 24rpx 0 0 24rpx;
  background: linear-gradient(180deg, #9575e8 0%, #7a57d1 50%, #6743bf 100%);
}

.plaza__card--hold .plaza__card-inner {
  background: #f9fafb;
  border-style: dashed;
  border-color: #e5e7eb;
  box-shadow: none;
}

.plaza__card--hold::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 10rpx;
  z-index: 2;
  border-radius: 24rpx 0 0 24rpx;
  background: linear-gradient(180deg, #e5e7eb 0%, #d1d5db 100%);
}

.plaza__card-top {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 14rpx;
}

.plaza__icon-wrap {
  width: 64rpx;
  height: 64rpx;
  border-radius: 18rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.plaza__card--live .plaza__icon-wrap {
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
  box-shadow: 0 2rpx 10rpx rgba(122, 87, 209, 0.08);
}

.plaza__card--hold .plaza__icon-wrap {
  background: #f3f4f6;
  border: 1rpx solid #e5e7eb;
}

.plaza__icon-img {
  width: 40rpx;
  height: 40rpx;
}

.plaza__icon-emoji {
  font-size: 32rpx;
  line-height: 1;
}

.plaza__status {
  font-size: 20rpx;
  font-weight: 800;
  padding: 8rpx 14rpx;
  border-radius: 999rpx;
}

.plaza__status--live {
  color: $mp-accent;
  background: rgba(122, 87, 209, 0.1);
  border: 1rpx solid rgba(122, 87, 209, 0.22);
}

.plaza__status--hold {
  color: #6b7280;
  background: #f3f4f6;
  border: 1rpx solid #e5e7eb;
}

.plaza__card-title {
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.35;
}

.plaza__card-title--hold {
  color: #4b5563;
}

.plaza__card-sub {
  margin-top: 8rpx;
  font-size: 22rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
  flex: 1;
}

.plaza__card-sub--hold {
  color: #9ca3af;
}

.plaza__card-divider {
  margin-top: 18rpx;
  margin-bottom: 4rpx;
  height: 1rpx;
  background: #f3f4f6;
}

.plaza__card--hold .plaza__card-divider {
  background: #e5e7eb;
}

.plaza__card-foot {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-end;
  gap: 6rpx;
  padding-top: 12rpx;
}

.plaza__card-action {
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
}

.plaza__card-action--hold {
  color: #9ca3af;
  font-weight: 700;
}

.plaza__card-arrow {
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-accent;
}

.plaza__card-arrow--hold {
  color: #d1d5db;
}

.plaza__hint {
  padding: 28rpx 24rpx;
}

.plaza__hint-title {
  display: block;
  margin-top: 10rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.plaza__hint-body {
  display: block;
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}
</style>
