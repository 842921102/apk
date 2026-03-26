<template>
  <view class="page">
    <text class="title">{{ config.login_prompt_title }}</text>
    <text v-if="config.login_prompt_subtitle" class="login-prompt-sub">{{
      config.login_prompt_subtitle
    }}</text>

    <view class="section">
      <text class="section-title">邮箱登录（与 Web 同一 Supabase 账号）</text>
      <text class="hint">用于加载收藏 / 历史真实数据；微信一键登录待 BFF 接入后替换。</text>
      <input
        v-model="email"
        class="input"
        type="text"
        placeholder="邮箱"
        :disabled="loading"
      />
      <input
        v-model="password"
        class="input"
        type="password"
        password
        placeholder="密码"
        :disabled="loading"
      />
      <button type="primary" class="btn-primary" :loading="loading" :disabled="loading" @click="onEmailLogin">
        {{ config.login_button_text }}
      </button>
    </view>

    <view class="section section-wx">
      <text class="section-title">微信登录（骨架）</text>
      <button class="btn-wx" type="default" :loading="wxLoading" :disabled="wxLoading" @click="onWeChatLogin">
        微信登录
      </button>
      <text v-if="lastCodePreview" class="code-preview">最近 code：{{ lastCodePreview }}</text>
    </view>

    <button type="default" class="btn-back" @click="goBack">返回</button>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useAppConfig } from '@/composables/useAppConfig'
import { useAuth } from '@/composables/useAuth'
import { API_BASE_URL } from '@/constants'
import { signInWithEmailPassword } from '@/services/auth/supabaseEmailAuth'
import { requestWeChatLoginCode } from '@/services/auth/wechatLoginSkeleton'
import { loginWithWechatCode } from '@/api/auth'

const { config } = useAppConfig()
const { syncAuthFromSupabase, setToken, setCurrentUser } = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const wxLoading = ref(false)
const lastCodePreview = ref('')
const redirectPath = ref('')

onLoad((options) => {
  if (options?.redirect) {
    try {
      redirectPath.value = decodeURIComponent(String(options.redirect))
    } catch {
      redirectPath.value = ''
    }
  }
})

async function onEmailLogin() {
  if (!email.value.trim() || !password.value) {
    uni.showToast({ title: '请输入邮箱和密码', icon: 'none' })
    return
  }
  loading.value = true
  try {
    await signInWithEmailPassword(email.value, password.value)
    await syncAuthFromSupabase()
    uni.showToast({ title: '登录成功', icon: 'success' })
    setTimeout(() => navigateAfterLogin(), 400)
  } catch (e: unknown) {
    const err = e as Error
    uni.showToast({ title: err.message?.slice(0, 24) || '登录失败', icon: 'none' })
  } finally {
    loading.value = false
  }
}

async function onWeChatLogin() {
  wxLoading.value = true
  lastCodePreview.value = ''
  try {
    if (!API_BASE_URL.trim()) {
      uni.showToast({
        title: '未配置 VITE_API_BASE_URL（BFF 地址）',
        icon: 'none',
        duration: 2800,
      })
      return
    }
    const code = await requestWeChatLoginCode()
    console.log('[wx login] code:', code)
    const result = await loginWithWechatCode(code)
    const openid = (result.openid ?? result.unionid) as string | undefined

    const accessToken = (result.access_token ?? result.accessToken) as string | undefined
    const wxUser = result.user as { id?: string; nickname?: string } | undefined

    // 展示 openid（用于你确认“确实拿到了 openid”）
    if (openid) {
      lastCodePreview.value = openid.length > 12 ? `${openid.slice(0, 12)}…` : openid
    } else {
      lastCodePreview.value = code.length > 12 ? `${code.slice(0, 12)}…` : code
    }

    // 若 BFF 返回了后端 access_token，则直接写入小程序登录态
    if (accessToken) {
      setToken(accessToken)
      if (wxUser?.id) {
        setCurrentUser({ id: String(wxUser.id), nickname: wxUser.nickname })
      }
      uni.showToast({ title: '微信登录成功', icon: 'success' })
      setTimeout(() => navigateAfterLogin(), 400)
    } else {
      uni.showToast({
        title: '已获取微信凭证（openid），但未返回会话 token',
        icon: 'none',
        duration: 2800,
      })
    }
  } catch (e) {
    console.error('[wx login] failed:', e)
    const msg = e instanceof Error ? e.message : String(e)
    if (msg.includes('VITE_API_BASE_URL')) {
      uni.showToast({
        title: '未配置 VITE_API_BASE_URL（BFF 地址）',
        icon: 'none',
        duration: 2800,
      })
    } else {
      uni.showToast({ title: '获取登录凭证失败', icon: 'none' })
    }
  } finally {
    wxLoading.value = false
  }
}

const TAB_PATHS = [
  '/pages/index/index',
  '/pages/today-eat/index',
  '/pages/plaza/index',
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
  padding: 48rpx 32rpx;
  display: flex;
  flex-direction: column;
}
.page .section,
.page .section-wx,
.page .btn-back {
  margin-top: 40rpx;
}
.title {
  font-size: 36rpx;
  font-weight: 600;
  color: #111827;
}
.login-prompt-sub {
  margin-top: 16rpx;
  font-size: 26rpx;
  color: #6b7280;
  line-height: 1.5;
}
.section {
  display: flex;
  flex-direction: column;
}
.section .hint,
.section .input,
.section .btn-primary,
.section .btn-wx,
.section .code-preview {
  margin-top: 16rpx;
}
.section-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #374151;
}
.hint {
  font-size: 24rpx;
  color: #6b7280;
  line-height: 1.5;
}
.input {
  border: 1rpx solid #e5e7eb;
  border-radius: 12rpx;
  padding: 20rpx 24rpx;
  font-size: 28rpx;
  background: #fff;
}
.section-wx {
  padding-top: 8rpx;
  border-top: 1rpx solid #e5e7eb;
}
.code-preview {
  font-size: 22rpx;
  color: #9ca3af;
  word-break: break-all;
}
</style>
