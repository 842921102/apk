<template>
  <view class="page">
    <view class="hero">
      <text class="hero-kicker">饭否</text>
      <text class="hero-title">小程序登录</text>
      <text class="hero-sub">使用微信账号登录，与后台用户体系共用同一套账号数据。</text>
    </view>

    <view class="card">
      <button
        class="btn-wx"
        :loading="wxLoading"
        :disabled="wxLoading || !apiReady"
        @click="onWeChatLogin"
      >
        <text class="btn-wx-text">微信一键登录</text>
      </button>

      <text v-if="!apiReady" class="warn">未检测到 BFF 地址，请先配置 `VITE_API_BASE_URL`。</text>

      <view v-else-if="apiUsesLoopback" class="warn-card">
        <text class="warn-card-title">当前为 localhost，真机不可访问</text>
        <text class="warn-card-sub">请改成电脑局域网 IP（例如 `http://192.168.1.12:8800`）后重新编译。</text>
      </view>

      <view class="helper">
        <text class="helper-title" @click="showTechDetail = !showTechDetail">
          {{ showTechDetail ? '收起连接信息' : '展开连接信息' }}
        </text>
        <view v-if="showTechDetail" class="helper-content">
          <text class="hint">当前 BFF：{{ apiBaseUrlDisplay }}</text>
          <text class="hint">需确保 BFF 已配置 `ADMIN_BACKEND_BASE_URL`。</text>
          <text class="hint">Laravel 需配置 `WECHAT_APP_ID` 与 `WECHAT_APP_SECRET`。</text>
        </view>
      </view>
    </view>

    <view class="footer">
      <text class="footer-line">运营人员请使用电脑端打开后台管理登录，不在此页操作。</text>
      <button class="btn-back" @click="goBack">返回</button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { API_BASE_URL } from '@/constants'
import { useAuth } from '@/composables/useAuth'
import { requestWeChatLoginCode } from '@/services/auth/wechatLoginSkeleton'
import { loginWithWechatCode } from '@/api/auth'
import { HttpError } from '@/api/http'

const { setToken, setCurrentUser } = useAuth()

const wxLoading = ref(false)
const redirectPath = ref('')
const isWxDevtools = ref(false)
const showTechDetail = ref(false)

const apiReady = computed(() => Boolean(API_BASE_URL.trim()))
const apiBaseUrlDisplay = computed(() => API_BASE_URL.trim() || '(未配置)')

/** 真机请求 localhost / 127.0.0.1 会失败且报「url not in domain list:localhost」 */
const apiUsesLoopback = computed(() => {
  const u = API_BASE_URL.trim().toLowerCase()
  return u.includes('localhost') || u.includes('127.0.0.1')
})

onLoad((options) => {
  try {
    const p = String(uni.getSystemInfoSync()?.platform || '').toLowerCase()
    isWxDevtools.value = p === 'devtools'
  } catch {
    isWxDevtools.value = false
  }
  if (options?.redirect) {
    try {
      redirectPath.value = decodeURIComponent(String(options.redirect))
    } catch {
      redirectPath.value = ''
    }
  }
})

function toastFromError(e: unknown): string {
  if (e instanceof HttpError) {
    return e.message.slice(0, 48)
  }
  if (e instanceof Error) {
    return e.message.slice(0, 48)
  }
  return '登录失败'
}

async function onWeChatLogin() {
  if (!apiReady.value) {
    uni.showToast({ title: '未配置 BFF 地址', icon: 'none' })
    return
  }
  if (apiUsesLoopback.value && !isWxDevtools.value) {
    uni.showToast({
      title: '真机请改用局域网 IP 的 BFF 地址',
      icon: 'none',
      duration: 2600,
    })
    return
  }
  wxLoading.value = true
  try {
    const code = await requestWeChatLoginCode()
    const result = await loginWithWechatCode(code)

    const accessToken = (result.access_token ?? result.accessToken) as string | undefined
    const wxUser = result.user as { id?: string; nickname?: string } | undefined

    if (accessToken) {
      setToken(accessToken)
      if (wxUser?.id) {
        setCurrentUser({ id: String(wxUser.id), nickname: wxUser.nickname })
      }
      uni.showToast({ title: '登录成功', icon: 'success' })
      setTimeout(() => navigateAfterLogin(), 400)
    } else {
      uni.showToast({ title: '服务端未返回 token', icon: 'none' })
    }
  } catch (e) {
    console.error('[wx login]', e)
    uni.showToast({ title: toastFromError(e), icon: 'none', duration: 2600 })
  } finally {
    wxLoading.value = false
  }
}

const TAB_PATHS = [
  '/pages/today-eat/index',
  '/pages/index/index',
  '/pages/plaza/index',
  '/pages/inspiration/index',
  '/pages/me/index',
]

function navigateAfterLogin() {
  const target = redirectPath.value.trim()
  if (target && target.startsWith('/pages/')) {
    if (TAB_PATHS.includes(target)) {
      uni.switchTab({ url: target })
      return
    }
    uni.redirectTo({ url: target })
    return
  }
  uni.navigateBack({
    fail: () => {
      uni.switchTab({ url: '/pages/me/index' })
    },
  })
}

function goBack() {
  uni.navigateBack({
    fail: () => {
      uni.switchTab({ url: '/pages/me/index' })
    },
  })
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  padding: 56rpx 40rpx 80rpx;
  box-sizing: border-box;
  background: linear-gradient(180deg, #ecfdf5 0%, #f9fafb 38%, #ffffff 100%);
  display: flex;
  flex-direction: column;
}

.hero {
  margin-bottom: 48rpx;
}
.hero-kicker {
  font-size: 24rpx;
  color: #059669;
  font-weight: 600;
  letter-spacing: 0.08em;
}
.hero-title {
  display: block;
  margin-top: 16rpx;
  font-size: 44rpx;
  font-weight: 700;
  color: #111827;
}
.hero-sub {
  display: block;
  margin-top: 20rpx;
  font-size: 28rpx;
  line-height: 1.55;
  color: #6b7280;
}

.card {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 40rpx 36rpx;
  box-shadow: 0 12rpx 40rpx rgba(17, 24, 39, 0.06);
}

.btn-wx {
  width: 100%;
  height: 96rpx;
  line-height: 96rpx;
  background: #07c160;
  color: #ffffff;
  border-radius: 16rpx;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
}
.btn-wx::after {
  border: none;
}
.btn-wx-text {
  color: #ffffff;
}

.warn {
  display: block;
  margin-top: 24rpx;
  font-size: 26rpx;
  line-height: 1.5;
  color: #b45309;
}

.warn-card {
  margin-top: 22rpx;
  padding: 18rpx 20rpx;
  border-radius: 14rpx;
  background: #fff7ed;
  border: 1rpx solid #fed7aa;
}

.warn-card-title {
  display: block;
  font-size: 24rpx;
  color: #b45309;
  font-weight: 700;
}

.warn-card-sub {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  line-height: 1.5;
  color: #9a3412;
}

.hint {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: #9ca3af;
}

.helper {
  margin-top: 18rpx;
}

.helper-title {
  font-size: 24rpx;
  color: #4b5563;
  font-weight: 600;
}

.helper-content {
  margin-top: 8rpx;
  padding-top: 6rpx;
}

.footer {
  margin-top: auto;
  padding-top: 48rpx;
}
.footer-line {
  display: block;
  font-size: 24rpx;
  color: #9ca3af;
  line-height: 1.5;
  text-align: center;
}
.btn-back {
  margin-top: 28rpx;
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  background: #f3f4f6;
  color: #374151;
  border-radius: 16rpx;
  font-size: 28rpx;
  border: none;
}
.btn-back::after {
  border: none;
}
</style>
