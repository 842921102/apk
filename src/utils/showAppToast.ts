/** 全站统一 Toast（样式见 src/styles/app-ui.css） */

export type AppToastType = 'success' | 'error' | 'warning' | 'info'

const typeClass: Record<AppToastType, string> = {
    success: 'app-toast app-toast--success',
    error: 'app-toast app-toast--error',
    warning: 'app-toast app-toast--warning',
    info: 'app-toast app-toast--info'
}

const typeIcon: Record<AppToastType, string> = {
    success: '✓',
    error: '!',
    warning: '!',
    info: 'i'
}

function buildToastEl(message: string, type: AppToastType): HTMLDivElement {
    const toast = document.createElement('div')
    toast.className = typeClass[type]
    toast.setAttribute('role', 'status')

    const row = document.createElement('div')
    row.className = 'app-toast__row'

    const icon = document.createElement('span')
    icon.className = 'app-toast__icon'
    icon.setAttribute('aria-hidden', 'true')
    icon.textContent = typeIcon[type]

    const text = document.createElement('span')
    text.className = 'app-toast__text'
    text.textContent = message

    row.appendChild(icon)
    row.appendChild(text)
    toast.appendChild(row)

    return toast
}

export function showAppToast(message: string, type: AppToastType = 'info', durationMs = 2600) {
    const toast = buildToastEl(message, type)
    document.body.appendChild(toast)

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.classList.add('is-visible')
        })
    })

    const remove = () => {
        toast.classList.remove('is-visible')
        let finished = false
        const done = () => {
            if (finished) return
            finished = true
            toast.removeEventListener('transitionend', onEnd)
            if (document.body.contains(toast)) {
                document.body.removeChild(toast)
            }
        }
        const onEnd = (e: TransitionEvent) => {
            if (e.propertyName === 'opacity' || e.propertyName === 'transform') {
                done()
            }
        }
        toast.addEventListener('transitionend', onEnd)
        setTimeout(done, 440)
    }

    setTimeout(remove, durationMs)
}
