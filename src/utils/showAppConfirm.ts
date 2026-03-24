/** 与 AppConfirmHost + AppConfirmDialog 配合使用（在 App.vue 挂载 Host） */

export type AppConfirmOptions = {
  title: string
  message?: string
  confirmText?: string
  cancelText?: string
  /** 默认 true（红按钮）；设为 false 用紫色主按钮 */
  danger?: boolean
}

let idSeq = 0

export function showAppConfirm(options: AppConfirmOptions): Promise<boolean> {
  const id = ++idSeq
  return new Promise((resolve) => {
    const onResponse = (e: Event) => {
      const d = (e as CustomEvent<{ id: number; confirmed: boolean }>).detail
      if (!d || d.id !== id) return
      window.removeEventListener('app-confirm-response', onResponse)
      resolve(!!d.confirmed)
    }
    window.addEventListener('app-confirm-response', onResponse)
    window.dispatchEvent(
      new CustomEvent('app-confirm-open', {
        detail: {
          id,
          title: options.title,
          message: options.message ?? '',
          confirmText: options.confirmText ?? '确认',
          cancelText: options.cancelText ?? '取消',
          danger: options.danger !== false
        }
      })
    )
  })
}
