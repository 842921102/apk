import { ref, computed, type ComputedRef, type Ref } from 'vue'
import type { AuthCurrentUser } from '@/types/auth'
import { API_BASE_URL, STORAGE_ACCESS_TOKEN, STORAGE_CURRENT_USER } from '@/constants'
import { isSupabaseConfigured, supabase } from '@/lib/supabase'

const accessToken = ref('')
const currentUser = ref<AuthCurrentUser | null>(null)

const isLoggedIn: ComputedRef<boolean> = computed(() => Boolean(accessToken.value))

/** 与 Laravel `App\Support\LaravelAccessToken::PREFIX` 一致 */
export const LARAVEL_ACCESS_TOKEN_PREFIX = 'laravel_access_'

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

/** 在已登录时合并更新本地用户摘要（如完成引导后清除 needsOnboarding） */
export function patchCurrentUser(partial: Partial<AuthCurrentUser>) {
  const cur = currentUser.value
  if (!cur) return
  setCurrentUser({ ...cur, ...partial })
}

/**
 * 冷启动/前台恢复时：若持有 Laravel 微信 token，则拉取 `/api/me/profile` 同步
 * `needsOnboarding`、`periodFeatureEnabled`，避免用户长期未重新登录时本地摘要过期。
 * 使用动态 import，避免 useAuth ↔ api/me ↔ http 循环依赖。
 */
export async function syncLaravelMeSummaryIfNeeded(): Promise<void> {
  hydrateFromStorageToState()
  const token = accessToken.value
  if (!token?.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX)) {
    return
  }
  if (!currentUser.value?.id) {
    return
  }
  if (!API_BASE_URL.trim()) {
    return
  }
  try {
    const { fetchMeProfile } = await import('@/api/me')
    const res = await fetchMeProfile()
    patchCurrentUser({
      needsOnboarding: res.needs_onboarding === true,
      periodFeatureEnabled: Boolean(res.profile?.period_feature_enabled),
    })
    if (res.needs_onboarding === false) {
      const { markLocalOnboardingCompleted } = await import('@/composables/useOnboardingFlow')
      markLocalOnboardingCompleted()
    }
  } catch {
    /* 离线、401、未部署接口等：不打断启动 */
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
    patchCurrentUser,
    setSupabaseSession,
    logout,
    syncAuthFromSupabase,
    hydrateFromStorage,
    readTokenFromStorage: hydrateFromStorage,
    syncLaravelMeSummaryIfNeeded,
  }
}

export type UseAuthReturn = ReturnType<typeof useAuth>
