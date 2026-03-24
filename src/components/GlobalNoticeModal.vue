<template>
    <Transition name="app-overlay-fade">
        <div
            v-if="show"
            class="app-modal-backdrop"
            @click.self="handleClose(false)"
        >
            <div class="app-modal-panel app-overlay-fade__panel max-h-[90vh] overflow-y-auto">
            <div class="app-modal-header">
                <h3 class="app-modal-title !flex !items-center !gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/80 text-app-accent ring-1 ring-app-line-soft" aria-hidden="true">
                        <AppStrokeIcon name="bell" :size="20" />
                    </span>
                    重要通知
                </h3>
            </div>
            <div class="app-modal-body">
                <p class="font-medium text-[15px] text-app-fg">感谢使用饭否体验版 🤖</p>
                <p class="leading-relaxed text-app-muted">
                    当前使用的是<span class="font-semibold text-app-accent">智谱清言</span>免费模型（glm-4v-flash / cogview-3-flash），生成效果有限。
                </p>
                <p class="leading-relaxed text-app-muted">
                    💡 <span class="font-semibold text-app-fg">推荐配置更强大的模型：</span><br />
                    前往<span class="font-semibold text-app-accent">设置</span>页面，配置
                    <span class="font-semibold text-app-accent-deep">DeepSeek</span>、<span class="font-semibold text-app-accent-deep">豆包</span>
                    等性价比高的模型，或使用自部署服务，体验更佳。
                </p>
                <p class="border-t border-app-line pt-3 text-sm leading-relaxed text-app-muted">
                    <span>开源地址，欢迎 Star⭐：</span>
                    <a
                        href="https://github.com/liu-ziting/what-to-eat"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-app-accent underline decoration-app-line-soft underline-offset-2 hover:text-app-accent-deep"
                    >
                        github.com/liu-ziting/what-to-eat
                    </a>
                </p>
                <p class="text-sm leading-relaxed text-app-muted">
                    更多 AI 开源项目，请访问：
                    <a
                        href="https://vibecoding.lz-t.top/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-app-accent underline decoration-app-line-soft underline-offset-2 hover:text-app-accent-deep break-all"
                    >
                        vibecoding.lz-t.top
                    </a>
                </p>
            </div>
            <div class="app-modal-footer">
                <button type="button" class="app-btn app-btn--secondary app-btn--md flex-1" @click="handleClose(false)">
                    知道了
                </button>
                <button type="button" class="app-btn app-btn--primary app-btn--md flex-1" @click="handleClose(true)">
                    不再提醒
                </button>
            </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppStrokeIcon from '@/components/icons/AppStrokeIcon.vue'

const show = ref(false)
const NOTICE_KEY = 'global-notice-dismissed'

onMounted(() => {
    const dismissed = localStorage.getItem(NOTICE_KEY)
    if (dismissed !== 'permanent') {
        setTimeout(() => {
            show.value = true
        }, 500)
    }
})

const handleClose = (permanent: boolean) => {
    show.value = false
    if (permanent) {
        localStorage.setItem(NOTICE_KEY, 'permanent')
    }
}
</script>
