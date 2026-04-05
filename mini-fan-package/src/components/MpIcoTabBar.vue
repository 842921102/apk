<template>
  <view class="wte-ico-tabbar">
    <view
      v-for="t in tabs"
      :key="t.key"
      class="wte-ico-tabbar__item"
      :class="{ 'wte-ico-tabbar__item--on': activeKey === t.key }"
      @click="onTap(t.key)"
    >
      <image
        class="wte-ico-tabbar__icon"
        :src="activeKey === t.key ? t.selectedIconPath : t.iconPath"
        mode="aspectFit"
      />
      <text class="wte-ico-tabbar__label">{{ t.label }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

type TabKey = 'home' | 'inspire' | 'me'

type TabItem = {
  key: TabKey
  label: string
  pagePath: string
  iconPath: string
  selectedIconPath: string
}

const tabs: TabItem[] = [
  {
    key: 'home',
    label: '首页',
    pagePath: '/pages/today-eat/index',
    iconPath: '/static/tabbar/tab-home.png',
    selectedIconPath: '/static/tabbar/tab-home-active.png',
  },
  {
    key: 'inspire',
    label: '灵感',
    pagePath: '/pages/inspiration/index',
    iconPath: '/static/tabbar/tab-inspire.png',
    selectedIconPath: '/static/tabbar/tab-inspire-active.png',
  },
  {
    key: 'me',
    label: '我的',
    pagePath: '/pages/me/index',
    iconPath: '/static/tabbar/tab-me.png',
    selectedIconPath: '/static/tabbar/tab-me-active.png',
  },
]

function getCurrentRoute(): string {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  // @ts-ignore
  const pages = (typeof getCurrentPages === 'function' ? getCurrentPages() : []) as any[]

  const cur = pages?.[pages.length - 1]
  return cur?.route || cur?.$page?.route || cur?.path || ''
}

const currentRoute = ref('')

const activeKey = computed<TabKey>(() => {
  const r = currentRoute.value
  if (!r) return 'home'

  if (r === '/pages/today-eat/index' || r.startsWith('/pages/today-eat/')) return 'home'

  if (r === '/pages/inspiration/index' || r.startsWith('/pages/inspiration/')) return 'inspire'

  if (r === '/pages/me/index') return 'me'

  const meGroup = ['/pages/favorites/index', '/pages/histories/index']
  if (meGroup.includes(r)) return 'me'

  const fromHome = [
    '/pages/table-menu/index',
    '/pages/fortune-cooking/index',
    '/pages/sauce-design/index',
    '/pages/gallery/index',
    '/pages/index/index',
    '/pages/plaza/index',
  ]
  if (fromHome.includes(r) || r.startsWith('/pages/index/')) return 'home'

  return 'home'
})

function refreshRoute() {
  currentRoute.value = getCurrentRoute()
}

onMounted(() => {
  refreshRoute()
})

function onTap(key: TabKey) {
  const t = tabs.find((x) => x.key === key)
  if (!t) return
  uni.switchTab({ url: t.pagePath })
}
</script>

<style lang="scss" scoped>
.wte-ico-tabbar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: 96rpx;
  background: #fdfdfe;
  border-top: 1rpx solid #f3f4f6;
  display: flex;
  flex-direction: row;
  z-index: 950;
  padding-bottom: env(safe-area-inset-bottom);
}

.wte-ico-tabbar__item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-top: 8rpx;
}

.wte-ico-tabbar__icon {
  width: 48rpx;
  height: 48rpx;
}

.wte-ico-tabbar__label {
  margin-top: 4rpx;
  font-size: 20rpx;
  font-weight: 600;
  color: #8e95a3;
}

.wte-ico-tabbar__item--on .wte-ico-tabbar__label {
  color: #7b57e4;
}
</style>
