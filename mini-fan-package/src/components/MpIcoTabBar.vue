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

type TabKey = 'home' | 'eat' | 'plaza' | 'me'

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
    pagePath: '/pages/index/index',
    iconPath: '/static/tabbar/home.png',
    selectedIconPath: '/static/tabbar/home-active.png',
  },
  {
    key: 'eat',
    label: '吃什么',
    pagePath: '/pages/today-eat/index',
    iconPath: '/static/tabbar/eat.png',
    selectedIconPath: '/static/tabbar/eat-active.png',
  },
  {
    key: 'plaza',
    label: '菜单',
    pagePath: '/pages/plaza/index',
    iconPath: '/static/tabbar/plaza.png',
    selectedIconPath: '/static/tabbar/plaza-active.png',
  },
  {
    key: 'me',
    label: '我的',
    pagePath: '/pages/me/index',
    iconPath: '/static/tabbar/me.png',
    selectedIconPath: '/static/tabbar/me-active.png',
  },
]

function getCurrentRoute(): string {
  // uniapp: getCurrentPages 在运行时可用
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  // @ts-ignore
  const pages = (typeof getCurrentPages === 'function' ? getCurrentPages() : []) as any[]

  const cur = pages?.[pages.length - 1]
  return (
    cur?.route ||
    cur?.$page?.route ||
    cur?.path ||
    ''
  )
}

const currentRoute = ref('')

// 非 tab 页面：根据“功能归属”决定哪个 tab 高亮
const activeKey = computed<TabKey>(() => {
  const r = currentRoute.value
  if (!r) return 'home'

  if (
    r === '/pages/index/index' ||
    r.startsWith('/pages/index/')
  )
    return 'home'

  if (r === '/pages/today-eat/index' || r.startsWith('/pages/today-eat/')) return 'eat'

  const plazaGroup = [
    '/pages/plaza/index',
    '/pages/table-menu/index',
    '/pages/fortune-cooking/index',
    '/pages/sauce-design/index',
    '/pages/gallery/index',
  ]
  if (plazaGroup.includes(r)) return 'plaza'

  const meGroup = ['/pages/me/index', '/pages/favorites/index', '/pages/histories/index']
  if (meGroup.includes(r)) return 'me'

  // 默认归属到菜单
  return 'plaza'
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
  width: 44rpx;
  height: 44rpx;
}

.wte-ico-tabbar__label {
  margin-top: 4rpx;
  font-size: 20rpx;
  font-weight: 600;
  color: #8e95a3;
}

.wte-ico-tabbar__item--on .wte-ico-tabbar__label {
  color: #7a57d1;
}
</style>

