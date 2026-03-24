<template>
  <div>
    <AppNav />

    <div class="app-page flex min-h-screen items-center justify-center px-4 py-10">
      <div
        class="w-full max-w-[520px] rounded-[22px] bg-app-card p-8 shadow-[0_18px_50px_rgba(0,0,0,0.08)] ring-1 ring-app-line md:p-10"
      >
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-app-fg md:text-[1.75rem]">登录 / 注册</h1>
          <p class="mt-2 text-sm leading-relaxed text-app-muted">
            先登录账号，后面收藏和历史记录都会跟账号绑定
          </p>
        </div>

        <div class="space-y-5">
          <div>
            <label class="app-label" for="login-email">邮箱</label>
            <input
              id="login-email"
              v-model="email"
              type="email"
              autocomplete="email"
              placeholder="请输入邮箱"
              class="app-input"
            />
          </div>

          <div>
            <label class="app-label" for="login-password">密码</label>
            <input
              id="login-password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              placeholder="请输入密码"
              class="app-input"
            />
          </div>
        </div>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
          <AppButton class="sm:flex-1" variant="primary" @click="handleSignIn">登录</AppButton>
          <AppButton class="sm:flex-1" variant="secondary" @click="handleSignUp">注册</AppButton>
          <AppButton class="sm:flex-1" variant="danger" @click="handleSignOut">退出</AppButton>
        </div>

        <div
          class="mt-8 rounded-[18px] border border-app-line-soft bg-app-accent-soft/60 px-4 py-4 ring-1 ring-app-line/80"
        >
          <div class="text-xs font-semibold text-app-accent-deep">当前状态</div>
          <div class="mt-1 break-all text-base font-bold text-app-fg">
            {{ userEmail || '未登录' }}
          </div>
        </div>

        <p
          v-if="message"
          :class="messageType === 'success' ? 'app-form-success mt-6' : 'app-form-error mt-6'"
          role="alert"
        >
          {{ message }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { signUpWithEmail, signInWithEmail, signOut, getCurrentUser } from '../services/authService'
import AppNav from '../components/AppNav.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { showAppToast } from '@/utils/showAppToast'

const email = ref('')
const password = ref('')
const userEmail = ref('')
const message = ref('')
const messageType = ref<'success' | 'error'>('success')

const route = useRoute()
const router = useRouter()

async function refreshUser() {
  const user = await getCurrentUser()
  userEmail.value = user?.email || ''
}

async function handleSignUp() {
  try {
    message.value = ''
    await signUpWithEmail(email.value, password.value)
    messageType.value = 'success'
    message.value = '注册成功，请继续登录'
    await refreshUser()
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err.message || '注册失败'
  }
}

async function handleSignIn() {
  try {
    message.value = ''
    await signInWithEmail(email.value, password.value)
    messageType.value = 'success'
    message.value = '登录成功'
    await refreshUser()

    const redirect = route.query.redirect
    if (typeof redirect === 'string' && redirect.startsWith('/')) {
      router.push(redirect)
    } else {
      router.push('/me')
    }
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err.message || '登录失败'
  }
}

async function handleSignOut() {
  try {
    message.value = ''
    await signOut()
    userEmail.value = ''
    messageType.value = 'success'
    message.value = '已退出登录'
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err.message || '退出失败'
  }
}

onMounted(() => {
  refreshUser()
  if (route.query.reason === 'auth') {
    showAppToast('请先登录后再继续', 'info')
  }
})
</script>
