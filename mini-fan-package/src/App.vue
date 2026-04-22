<template>
  <!-- 根节点占位：不占布局，避免 App 无 DOM 时部分基础库下 Vue patch 异常 -->
  <view class="uni-app-host" />
</template>

<script setup lang="ts">
import { onLaunch, onShow, onHide } from '@dcloudio/uni-app'
import { syncLaravelMeSummaryIfNeeded } from '@/composables/useAuth'
import { logMiniappEnvOnLaunch } from '@/utils/env'

onLaunch(async () => {
  logMiniappEnvOnLaunch()
  console.log('App Launch')
  await syncLaravelMeSummaryIfNeeded()
  try {
    const { tryOnboardingGateOnColdStart } = await import('@/composables/useOnboardingFlow')
    tryOnboardingGateOnColdStart()
  } catch (e) {
    console.warn('[wte-mp][onboarding] cold gate failed:', e)
  }
})
onShow(() => {
  console.log('App Show')
})
onHide(() => {
  console.log('App Hide')
})
</script>

<style lang="scss">
@import './styles/mp-ui.scss';

.uni-app-host {
  position: fixed;
  width: 0;
  height: 0;
  overflow: hidden;
  pointer-events: none;
}
</style>
