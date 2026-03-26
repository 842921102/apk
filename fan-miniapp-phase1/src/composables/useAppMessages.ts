import { useAppConfig } from '@/composables/useAppConfig'

function clipToastTitle(s: string, max = 48): string {
  const t = s.trim()
  if (!t) return ''
  return t.length <= max ? t : `${t.slice(0, max - 1)}…`
}

/**
 * 统一 Toast：文案来自远端配置 + 本地默认兜底；调用失败不应影响业务流程。
 */
export function useAppMessages() {
  const { config } = useAppConfig()

  function toastNone(title: string, duration = 2000) {
    const t = clipToastTitle(title)
    if (!t) return
    try {
      uni.showToast({ title: t, icon: 'none', duration })
    } catch {
      /* ignore */
    }
  }

  function toastSuccess(title: string) {
    const t = clipToastTitle(title, 32)
    if (!t) return
    try {
      uni.showToast({ title: t, icon: 'success' })
    } catch {
      /* ignore */
    }
  }

  return {
    toastLoadFailed(detail?: string) {
      const base = config.value.toast_load_failed || '加载失败'
      if (detail?.trim()) {
        toastNone(`${base}：${clipToastTitle(detail.trim(), 20)}`)
      } else {
        toastNone(base)
      }
    },
    toastFavoriteDeleted() {
      toastSuccess(config.value.toast_favorite_deleted)
    },
    toastHistoryDeleted() {
      toastSuccess(config.value.toast_history_deleted)
    },
    toastSaveSuccess() {
      toastSuccess(config.value.toast_save_success)
    },
    toastSaveFailed(detail?: string) {
      const base = config.value.toast_save_failed || '保存失败'
      if (detail?.trim()) {
        toastNone(`${base}：${clipToastTitle(detail.trim(), 20)}`)
      } else {
        toastNone(base)
      }
    },
    toastFavoriteSuccess() {
      toastSuccess(config.value.toast_favorite_success)
    },
    toastFavoriteCancel() {
      toastNone(config.value.toast_favorite_cancel)
    },
  }
}
