/**
 * 首次问卷流程门禁（当前以前端本地态为准，后续可改为与服务端 `needs_onboarding` 对齐）。
 */
import { CURRENT_ONBOARDING_VERSION } from '@/constants/onboarding'
import { getToken, LARAVEL_ACCESS_TOKEN_PREFIX } from '@/composables/useAuth'
import {
  ONBOARDING_DRAFT_STORAGE_KEY,
  readOnboardingDraft,
  writeOnboardingDraft,
} from '@/composables/useOnboardingDraft'
import { getCurrentUser } from '@/composables/useAuth'

export { CURRENT_ONBOARDING_VERSION } from '@/constants/onboarding'

function isLaravelWechatSession(): boolean {
  try {
    const t = getToken()
    return Boolean(t && t.startsWith(LARAVEL_ACCESS_TOKEN_PREFIX))
  } catch {
    return false
  }
}

/**
 * 从旧草稿补全版本号；仅当 `onboarding_version` 已存在且小于 CURRENT 时清除「跳过」，
 * 便于发版再次提醒（不碰 onboarding_completed）。
 * 直接改写 storage，避免 write→read 递归。
 */
export function migrateOnboardingDraftIfNeeded(): void {
  try {
    const raw = uni.getStorageSync(ONBOARDING_DRAFT_STORAGE_KEY)
    if (!raw || typeof raw !== 'string') return
    const p = JSON.parse(raw) as Record<string, unknown>
    if (p.v !== 1) return
    const hadVersionKey = 'onboarding_version' in p && typeof p.onboarding_version === 'number'
    const ver = hadVersionKey ? (p.onboarding_version as number) : 0
    let changed = false
    if (typeof p.onboarding_skipped !== 'boolean') {
      p.onboarding_skipped = false
      changed = true
    }
    if (!hadVersionKey) {
      p.onboarding_version = CURRENT_ONBOARDING_VERSION
      changed = true
    } else if (ver < CURRENT_ONBOARDING_VERSION) {
      p.onboarding_version = CURRENT_ONBOARDING_VERSION
      p.onboarding_skipped = false
      changed = true
    }
    if (changed) {
      uni.setStorageSync(ONBOARDING_DRAFT_STORAGE_KEY, JSON.stringify(p))
    }
  } catch {
    /* ignore */
  }
}

/** 未写完且用户未主动「跳过本次」时，登录/冷启动应进入问卷 */
export function shouldAutoOpenOnboarding(): boolean {
  migrateOnboardingDraftIfNeeded()
  const d = readOnboardingDraft()
  if (d.onboarding_completed === true) return false
  if (d.onboarding_skipped === true) return false
  return true
}

/** Laravel 微信会话下才走本地问卷门禁（避免误伤其它登录体系） */
export function shouldAutoOpenOnboardingForCurrentSession(): boolean {
  if (!isLaravelWechatSession()) {
    return false
  }
  const u = getCurrentUser()
  if (u?.needsOnboarding === false) {
    return false
  }

  return shouldAutoOpenOnboarding()
}

export function openOnboardingWithRedirect(redirectPath: string): void {
  const r = encodeURIComponent(redirectPath.trim() || '/pages/today-eat/index')
  uni.redirectTo({ url: `/pages/onboarding/index?redirect=${r}` })
}

/**
 * 冷启动时：已登录且应门禁则进入问卷欢迎页。
 * @returns 是否已发起跳转
 */
export function tryOnboardingGateOnColdStart(): boolean {
  migrateOnboardingDraftIfNeeded()
  if (!shouldAutoOpenOnboardingForCurrentSession()) return false
  try {
    const pages = getCurrentPages()
    const last = pages[pages.length - 1] as { route?: string }  | undefined
    if (last?.route && String(last.route).includes('onboarding')) return false
  } catch {
    /* ignore */
  }
  openOnboardingWithRedirect('/pages/today-eat/index')
  return true
}

/** 用户主动完成整套问卷时调用（可与 API 提交并排） */
export function markLocalOnboardingCompleted(): void {
  writeOnboardingDraft({
    onboarding_completed: true,
    onboarding_skipped: false,
    screen: 'welcome',
  })
}

/** 顶栏「跳过」：本次放行，本地记下跳过，后续仍可通过版本迁移或产品入口再次拉起 */
export function markLocalOnboardingSkipped(): void {
  writeOnboardingDraft({ onboarding_skipped: true })
}
