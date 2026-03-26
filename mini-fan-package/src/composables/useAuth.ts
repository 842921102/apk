import { ref, computed, type ComputedRef, type Ref } from 'vue'
import type { AuthCurrentUser } from '@/types/auth'
import { STORAGE_ACCESS_TOKEN, STORAGE_CURRENT_USER } from '@/constants'
import { isSupabaseConfigured, supabase } from '@/lib/supabase'

const accessToken = ref('')
const currentUser = ref<AuthCurrentUser | null>(null)

const isLoggedIn: ComputedRef<boolean> = computed(() => Boolean(accessToken.value))

const LARAVEL_ACCESS_TOKEN_PREFIX = 'laravel_access_'

function hydrateFromStorageToState() {
  try {
    const token = uni.getStorageSync(STORAGE_ACCESS_TOKEN)
    if (typeof token === 'string' && token) {
      accessToken.value = token
    }
  } catch {
    /* ignore */
  }

  try {
    const raw = uni.getStorageSync(STORAGE_CURRENT_USER)
    if (typeof raw === 'string' && raw) {
      const parsed = JSON.parse(raw) as AuthCurrentUser
      if (parsed?.id) currentUser.value = parsed
    }
  } catch {
    /* ignore */
  }
}

function clearLegacyMirror() {
  try {
    uni.removeStorageSync(STORAGE_ACCESS_TOKEN)
    uni.removeStorageSync(STORAGE_CURRENT_USER)
  } catch {
    /* ignore */
  }
}

/**
 * 从 Supabase 会话同步到内存（与 getToken / getCurrentUser 同源）
 * 业务请求走 supabase-js，RLS 依赖该会话中的 JWT。
 */
export async function syncAuthFromSupabase(): Promise<void> {
  try {
    // 如果是后端自建 token（非 supabase 体系），避免调用 supabase.auth.getSession 覆盖清空登录态。
    // 这是“微信登录→Larvel 签发 token”模式的兼容保护。
    if (
      accessToken.value?.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX) ||
      (function () {
        try {
          const t = uni.getStorageSync(STORAGE_ACCESS_TOKEN)
          return typeof t === 'string' && t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)
        } catch {
          return false
        }
      })()
    ) {
      hydrateFromStorageToState()
      return
    }

    const { data } = await supabase.auth.getSession()
    const session = data.session
    if (session) {
      accessToken.value = session.access_token
      currentUser.value = {
        id: session.user.id,
        nickname: session.user.email ?? undefined,
      }
    } else {
      accessToken.value = ''
      currentUser.value = null
    }
  } catch {
    accessToken.value = ''
    currentUser.value = null
  }
}

export function getToken(): string {
  return accessToken.value
}

/** 预留：BFF 返回 access + refresh 时调用，再 syncAuthFromSupabase */
export async function setSupabaseSession(accessTokenStr: string, refreshToken?: string) {
  // 避免 TS 把 `undefined` 传给 refresh_token（supabase-js 的类型可能不接受可选值）
  const payload: { access_token: string; refresh_token?: string } = {
    access_token: accessTokenStr,
  }
  if (refreshToken) {
    payload.refresh_token = refreshToken
  }

  const { error } = await supabase.auth.setSession(payload as any)
  if (error) throw error
  await syncAuthFromSupabase()
}

export function setToken(token: string) {
  accessToken.value = token
  try {
    uni.setStorageSync(STORAGE_ACCESS_TOKEN, token)
  } catch {
    /* ignore */
  }
}

export function clearToken() {
  accessToken.value = ''
  try {
    uni.removeStorageSync(STORAGE_ACCESS_TOKEN)
  } catch {
    /* ignore */
  }
}

export function getCurrentUser(): AuthCurrentUser | null {
  return currentUser.value
}

export function setCurrentUser(user: AuthCurrentUser | null) {
  currentUser.value = user
  try {
    if (user) {
      uni.setStorageSync(STORAGE_CURRENT_USER, JSON.stringify(user))
    } else {
      uni.removeStorageSync(STORAGE_CURRENT_USER)
    }
  } catch {
    /* ignore */
  }
}

export async function logout() {
  if (isSupabaseConfigured()) {
    try {
      await supabase.auth.signOut()
    } catch (e) {
      console.warn('[wte-mp][auth] signOut 失败（忽略，仍清理本地态）:', e)
    }
  }
  accessToken.value = ''
  currentUser.value = null
  clearLegacyMirror()
}

/** @deprecated 请使用 syncAuthFromSupabase */
export function hydrateFromStorage() {
  hydrateFromStorageToState()
}

export function useAuth() {
  return {
    accessToken,
    currentUser,
    isLoggedIn,
    getToken,
    setToken,
    clearToken,
    getCurrentUser,
    setCurrentUser,
    setSupabaseSession,
    logout,
    syncAuthFromSupabase,
    hydrateFromStorage,
    readTokenFromStorage: hydrateFromStorage,
  }
}

export type UseAuthReturn = ReturnType<typeof useAuth>
