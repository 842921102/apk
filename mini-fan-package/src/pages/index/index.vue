<template>
  <view class="mp-page home">
    <!-- 1. 顶部标题区：运营 Banner（配置）或默认品牌头图 -->
    <view v-if="config.show_home_banner" class="mp-hero home__banner">
      <text class="mp-hero__kicker mp-kicker--on-dark">生活厨房</text>
      <text class="mp-hero__title home__banner-title">{{ config.home_banner_title }}</text>
      <text class="mp-hero__sub home__banner-sub">{{ config.home_banner_subtitle }}</text>
    </view>

    <view v-else class="mp-hero home__masthead">
      <text class="mp-hero__kicker mp-kicker--on-dark">饭否</text>
      <text class="mp-hero__title home__masthead-title">{{ config.home_title }}</text>
      <text class="mp-hero__sub home__masthead-sub">{{ config.home_subtitle }}</text>
    </view>

    <!-- 2. 核心 CTA：全页最突出操作，文案来自 homeCopy + 配置副文案 -->
    <view class="home__primary mp-card">
      <view class="home__primary-tag">
        <text class="home__primary-tag-txt">今日吃什么</text>
      </view>
      <template v-if="config.show_home_banner">
        <text class="home__primary-head">{{ config.home_title }}</text>
        <text class="home__primary-lead">{{ config.home_subtitle }}</text>
      </template>
      <template v-else>
        <text class="home__primary-head">三步搞定今天吃什么</text>
        <text class="home__primary-lead">填偏好、生成方案，登录后还能记进历史</text>
      </template>
      <button class="mp-btn-primary home__cta-hero" @click="goEat">
        <text class="home__cta-hero-label">{{ ctaLabel }}</text>
        <text class="home__cta-hero-go">→</text>
      </button>
      <text class="home__primary-foot">由自有 BFF 代理，不直连第三方 AI</text>
    </view>

    <!-- 2b. 运营推荐位次卡（对齐 Web 首页 secondary banner） -->
    <view class="mp-card home__secondary-card" @click="goPlaza">
      <view class="home__secondary-tag">
        <text>灵感补给站</text>
      </view>
      <text class="home__secondary-title">去功能广场逛逛</text>
      <text class="home__secondary-desc">满汉全席、酱料大师、图鉴教学都在这里。</text>
      <view class="home__secondary-action">
        <button type="button" class="mp-btn-ghost home__secondary-btn" @click.stop="goPlaza">
          <text>去看看</text>
          <text class="home__secondary-go">→</text>
        </button>
      </view>
    </view>

    <!-- 3. 快捷入口：与底栏能力对齐的产品入口区 -->
    <view class="home__section">
      <view class="home__section-rail">
        <text class="home__section-rail-k">入口</text>
        <text class="home__section-rail-h">一键直达</text>
      </view>

      <view class="home__quick-grid">
        <view
          v-for="item in quickEntries"
          :key="item.key"
          class="home__quick-tile"
          @click="onQuickEntryTap(item.key)"
        >
          <text class="home__quick-ico">{{ item.icon }}</text>
          <text class="home__quick-label">{{ item.label }}</text>
        </view>
      </view>

      <view class="home__quick-me-row">
        <button class="mp-btn-secondary home__quick-me-btn" @click="goMe">我的</button>
      </view>
    </view>

    <!-- 4. 今日推荐 / 运营说明（配置开关，结构对齐 Web 推荐卡） -->
    <view v-if="config.show_home_recommend" class="home__section">
      <view class="home__section-rail">
        <text class="home__section-rail-k">推荐</text>
        <text class="home__section-rail-h">今日灵感</text>
      </view>
      <view class="mp-card home__recommend-card">
        <text class="home__card-kicker">{{ config.home_recommend_title }}</text>
        <text class="home__card-title">{{ config.home_recommend_subtitle }}</text>
        <view class="home__tag-grid">
          <view v-for="t in todayRecommendations" :key="t" class="home__tag" @click="goEat">
            <text class="home__tag-txt">{{ t }}</text>
          </view>
        </view>
        <button class="mp-btn-secondary home__recommend-btn" @click="goEat">去体验</button>
      </view>
    </view>

    <!-- 5. 热门玩法：列表式入口，直接跳到大功能页 -->
    <view v-if="config.show_home_hot" class="home__section">
      <view class="home__section-rail">
        <text class="home__section-rail-k">热门</text>
        <text class="home__section-rail-h">值得一试</text>
      </view>
      <view class="mp-card home__hot-card">
        <text class="home__card-kicker home__card-kicker--muted">{{ config.home_hot_title }}</text>
        <text class="home__card-title">{{ config.home_hot_subtitle }}</text>
        <view class="home__hot-list">
          <view class="home__hot-row" @click="goTableMenu">
            <view class="home__hot-left">
              <text class="home__hot-ico">🍽️</text>
              <text class="home__hot-name">一桌好菜</text>
            </view>
            <text class="home__hot-arrow">→</text>
          </view>
          <view class="home__hot-row" @click="goFortuneCooking">
            <view class="home__hot-left">
              <text class="home__hot-ico">🔮</text>
              <text class="home__hot-name">玄学厨房</text>
            </view>
            <text class="home__hot-arrow">→</text>
          </view>
          <view class="home__hot-row" @click="goSauceDesign">
            <view class="home__hot-left">
              <text class="home__hot-ico">🥣</text>
              <text class="home__hot-name">酱料大师</text>
            </view>
            <text class="home__hot-arrow">→</text>
          </view>
          <view class="home__hot-row" @click="goGallery">
            <view class="home__hot-left">
              <text class="home__hot-ico">🖼️</text>
              <text class="home__hot-name">图鉴</text>
            </view>
            <text class="home__hot-arrow">→</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 6. 新手引导（固定模块，与 Web 新手卡一致） -->
    <view class="home__section home__section--last">
      <view class="home__section-rail">
        <text class="home__section-rail-k home__section-rail-k--accent">新手</text>
        <text class="home__section-rail-h">第一次来这样玩</text>
      </view>
      <view class="mp-card mp-card--accent-soft home__newbie-card">
        <text class="home__card-kicker home__card-kicker--accent">上手三步</text>
        <view class="home__steps">
          <view v-for="(s, i) in newbieSteps" :key="s" class="home__step">
            <text class="home__step-num">{{ i + 1 }}</text>
            <text class="home__step-text">{{ s }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 7. 满汉全席：首页步骤预览（仅体验入口，不发起生成请求） -->
    <view class="home__section">
      <view class="home__section-rail">
        <text class="home__section-rail-k home__section-rail-k--accent">满汉全席</text>
        <text class="home__section-rail-h">一桌好菜 · 生成预览</text>
      </view>

      <view class="mp-card home__tablePreview-card">
        <view class="home__tablePreview-steps">
          <view class="home__tablePreview-step">
            <text class="home__tablePreview-step-num">1</text>
            <view class="home__tablePreview-step-body">
              <text class="home__tablePreview-step-title">添加食材</text>
              <text class="home__tablePreview-step-desc">在“一桌好菜”里自定义或快速选择</text>
            </view>
          </view>

          <view class="home__tablePreview-step">
            <text class="home__tablePreview-step-num">2</text>
            <view class="home__tablePreview-step-body">
              <text class="home__tablePreview-step-title">选择菜品/配置</text>
              <text class="home__tablePreview-step-desc">配置数量、参考菜与偏好（可选）</text>
            </view>
          </view>

          <view class="home__tablePreview-step">
            <text class="home__tablePreview-step-num">3</text>
            <view class="home__tablePreview-step-body">
              <text class="home__tablePreview-step-title">交给大师</text>
              <text class="home__tablePreview-step-desc">服务端通过 BFF 代理生成整桌菜单</text>
            </view>
          </view>

          <view class="home__tablePreview-step">
            <text class="home__tablePreview-step-num">4</text>
            <view class="home__tablePreview-step-body">
              <text class="home__tablePreview-step-title">查看结果</text>
              <text class="home__tablePreview-step-desc">生成完成后可继续点开单道菜谱</text>
            </view>
          </view>
        </view>

        <button class="mp-btn-primary home__tablePreview-start" @click="goTableMenu">
          <text class="home__tablePreview-start-txt">进入一桌好菜</text>
          <text class="home__tablePreview-start-go">→</text>
        </button>
        <text class="home__tablePreview-foot">首页预览不请求网络，仅用于体验和入口引导。</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { HOME_CTA_LABEL } from '@/config/homeCopy'
import { useAppConfig } from '@/composables/useAppConfig'

const { config } = useAppConfig()
const ctaLabel = HOME_CTA_LABEL

type QuickEntryKey = 'today-eat' | 'favorites' | 'histories' | 'plaza'

const quickEntries: Array<{ key: QuickEntryKey; icon: string; label: string }> = [
  { key: 'today-eat', icon: '🎲', label: '吃什么' },
  { key: 'favorites', icon: '⭐', label: '收藏' },
  { key: 'histories', icon: '📜', label: '历史' },
  { key: 'plaza', icon: '✨', label: '功能广场' },
]

/** 与 Web 首页“今日推荐”对齐：静态示例，不新增接口 */
const todayRecommendations = ['番茄牛腩', '蒜蓉西兰花', '酸辣土豆丝'] as const

const newbieSteps = [
  '打开「吃什么」，填写口味与忌口',
  '生成推荐后可在收藏里留存',
  '到「功能广场」探索更多玩法',
]

function goEat() {
  uni.switchTab({ url: '/pages/today-eat/index' })
}

function goPlaza() {
  uni.switchTab({ url: '/pages/plaza/index' })
}

function goMe() {
  uni.switchTab({ url: '/pages/me/index' })
}

function goFavorites() {
  uni.navigateTo({ url: '/pages/favorites/index' })
}

function goHistories() {
  uni.navigateTo({ url: '/pages/histories/index' })
}

function goTableMenu() {
  uni.navigateTo({ url: '/pages/table-menu/index' })
}

function goFortuneCooking() {
  uni.navigateTo({ url: '/pages/fortune-cooking/index' })
}

function goSauceDesign() {
  uni.navigateTo({ url: '/pages/sauce-design/index' })
}

function goGallery() {
  uni.navigateTo({ url: '/pages/gallery/index' })
}

function onQuickEntryTap(key: QuickEntryKey) {
  switch (key) {
    case 'today-eat':
      goEat()
      break
    case 'favorites':
      goFavorites()
      break
    case 'histories':
      goHistories()
      break
    case 'plaza':
      goPlaza()
      break
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

/* 顶部 Banner / 头图：居中层级，贴近 Web 运营头图 */
.home__banner,
.home__masthead {
  text-align: center;
}

.home__banner-title,
.home__masthead-title {
  max-width: 620rpx;
  margin-left: auto;
  margin-right: auto;
}

.home__banner-sub,
.home__masthead-sub {
  max-width: 600rpx;
  margin-left: auto;
  margin-right: auto;
}

/* 主 CTA 卡片：紫色顶条 + 更醒目的按钮 */
.home__primary {
  position: relative;
  padding-top: 36rpx;
  overflow: hidden;
}

.home__primary::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 8rpx;
  background: linear-gradient(90deg, #9575e8 0%, #7a57d1 45%, #6743bf 100%);
}

.home__primary-tag {
  align-self: flex-start;
  margin-bottom: 16rpx;
}

.home__primary-tag-txt {
  font-size: 22rpx;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: $mp-accent;
  padding: 10rpx 20rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.home__primary-head {
  font-size: 38rpx;
  font-weight: 800;
  color: $mp-text-primary;
  line-height: 1.3;
  letter-spacing: -0.02em;
}

.home__primary-lead {
  display: block;
  margin-top: 16rpx;
  font-size: 28rpx;
  line-height: 1.55;
  color: $mp-text-secondary;
}

.home__cta-hero {
  margin-top: 36rpx;
  display: flex !important;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding-top: 32rpx !important;
  padding-bottom: 32rpx !important;
  font-size: 32rpx !important;
  box-shadow: 0 20rpx 56rpx rgba(122, 87, 209, 0.38);
}

.home__cta-hero-label {
  font-weight: 700;
}

.home__cta-hero-go {
  font-size: 34rpx;
  font-weight: 700;
  opacity: 0.95;
}

.home__primary-foot {
  display: block;
  margin-top: 20rpx;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
  text-align: center;
}

/* 分区块：左侧轨 + 卡片，层级更清晰 */
.home__section {
  margin-top: 36rpx;
}

.home__section--last {
  margin-bottom: 8rpx;
}

.home__section-rail {
  display: flex;
  flex-direction: row;
  align-items: baseline;
  gap: 12rpx;
  margin-bottom: 16rpx;
  padding-left: 8rpx;
}

.home__section-rail-k {
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.16em;
  color: $mp-text-muted;
  text-transform: uppercase;
}

.home__section-rail-k--accent {
  color: $mp-accent;
}

.home__section-rail-h {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.home__card-kicker {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: $mp-text-muted;
}

.home__card-kicker--muted {
  color: $mp-text-secondary;
  letter-spacing: 0.08em;
}

.home__card-kicker--accent {
  color: $mp-accent;
}

.home__card-title {
  display: block;
  margin-top: 10rpx;
  font-size: 30rpx;
  font-weight: 700;
  color: $mp-text-primary;
  line-height: 1.35;
}

.home__card-desc {
  display: block;
  margin-top: 12rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

/* 推荐卡：示例行 */
.home__preview-list {
  margin-top: 24rpx;
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.home__preview-row {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  padding: 18rpx 20rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.home__preview-dot {
  flex-shrink: 0;
  width: 28rpx;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-accent;
  line-height: 1.2;
}

.home__preview-txt {
  flex: 1;
  font-size: 26rpx;
  line-height: 1.45;
  color: $mp-text-primary;
}

.home__recommend-btn {
  margin-top: 28rpx;
  width: 100%;
}

/* 快捷入口宫格 */
.home__quick-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16rpx;
  padding-left: 8rpx;
  padding-right: 8rpx;
}

.home__quick-tile {
  width: calc(50% - 8rpx);
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  padding: 24rpx 0;
  background: #fff;
  border: 1rpx solid $mp-border;
  border-radius: 18rpx;
  box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.03);
}

.home__quick-ico {
  font-size: 44rpx;
  line-height: 1;
}

.home__quick-label {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.home__quick-me-row {
  margin-top: 20rpx;
  padding: 0 8rpx;
}

.home__quick-me-btn {
  width: 100%;
}

/* 推荐标签 */
.home__tag-grid {
  margin-top: 24rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 14rpx;
}

.home__tag {
  padding: 14rpx 18rpx;
  border-radius: 999rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.home__tag-txt {
  font-size: 26rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

/* 热门：行列表 */
.home__hot-list {
  margin-top: 24rpx;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.home__hot-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 22rpx 20rpx;
  border-radius: 16rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
}

.home__hot-left {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
}

.home__hot-ico {
  font-size: 36rpx;
  line-height: 1;
}

.home__hot-name {
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-primary;
}

.home__hot-arrow {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-accent;
}

/* 新手 */
.home__newbie-card {
  padding-bottom: 32rpx;
}

.home__steps {
  margin-top: 20rpx;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.home__step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
}

.home__step-num {
  flex-shrink: 0;
  width: 40rpx;
  height: 40rpx;
  line-height: 40rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent;
  background: #fff;
  border-radius: 999rpx;
  border: 1rpx solid #dccdf7;
}

.home__step-text {
  flex: 1;
  font-size: 26rpx;
  line-height: 1.55;
  color: #5a3ba8;
  padding-top: 4rpx;
}

/* 满汉全席首页步骤预览 */
.home__tablePreview-card {
  padding: 26rpx 22rpx;
}

.home__tablePreview-steps {
  display: flex;
  flex-direction: column;
  gap: 18rpx;
  margin-bottom: 22rpx;
}

.home__tablePreview-step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
}

.home__tablePreview-step-num {
  flex-shrink: 0;
  width: 44rpx;
  height: 44rpx;
  line-height: 44rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
  background: #fff;
  border-radius: 999rpx;
  border: 1rpx solid #dccdf7;
}

.home__tablePreview-step-body {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.home__tablePreview-step-title {
  font-size: 28rpx;
  font-weight: 700;
  color: $mp-text-primary;
}

.home__tablePreview-step-desc {
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.home__tablePreview-start {
  width: 100%;
}

.home__tablePreview-start-txt {
  font-weight: 800;
}

.home__tablePreview-start-go {
  font-size: 34rpx;
  font-weight: 800;
  opacity: 0.95;
  margin-left: 12rpx;
}

.home__tablePreview-foot {
  display: block;
  margin-top: 16rpx;
  text-align: center;
  font-size: 22rpx;
  line-height: 1.45;
  color: $mp-text-muted;
}

/* 运营推荐位次卡 */
.home__secondary-card {
  margin-top: 24rpx;
  padding: 26rpx 22rpx;
  overflow: hidden;
}

.home__secondary-tag {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 14rpx;
}

.home__secondary-title {
  display: block;
  font-size: 32rpx;
  font-weight: 800;
  color: $mp-text-primary;
  margin-bottom: 12rpx;
}

.home__secondary-desc {
  display: block;
  font-size: 24rpx;
  color: $mp-text-secondary;
  line-height: 1.5;
}

.home__secondary-action {
  margin-top: 22rpx;
}

.home__secondary-btn {
  width: 100%;
}

.home__secondary-go {
  font-size: 34rpx;
  font-weight: 800;
  margin-left: 16rpx;
  opacity: 0.95;
}
</style>
