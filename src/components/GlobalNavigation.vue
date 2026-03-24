<template>
    <nav class="sticky top-0 z-40 border-b border-[#ECEEF2] bg-white/92 backdrop-blur-[10px]">
        <div class="mx-auto w-full max-w-7xl px-4 pb-2 pt-2.5 md:px-6 md:pt-3">
            <div class="flex items-center gap-3">
                <router-link to="/" class="min-w-0 flex items-center gap-2.5" @click="rotateLogo">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#9575E8] to-[#6743BF] text-[17px] font-bold text-white shadow-[0_6px_16px_rgba(122,87,209,0.35)]"
                        :class="{ 'rotate-logo': isLogoRotating }"
                    >
                        饭
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-semibold leading-tight text-[#222222] md:text-[16px]">
                            {{ pageTitle }}
                        </p>
                        <p class="truncate text-[11px] leading-tight text-[#8A8F99] md:text-[12px]">
                            {{ pageSubtitle }}
                        </p>
                    </div>
                </router-link>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

const isLogoRotating = ref(false)

const rotateLogo = () => {
    isLogoRotating.value = true
    setTimeout(() => {
        isLogoRotating.value = false
    }, 500)
}

const route = useRoute()

const pageTitle = computed(() => {
    switch (route.path) {
        case '/':
            return '饭否'
        case '/today-eat':
            return '今天吃什么'
        case '/table-design':
            return '一桌好菜'
        case '/how-to-cook':
            return '菜谱教学'
        case '/sauce-design':
            return '酱料设计'
        case '/fortune-cooking':
            return '玄学厨房'
        case '/plaza':
            return '功能广场'
        case '/collect':
            return route.query.tab === 'histories' ? '历史记录' : '我的收藏'
        case '/favorites':
            return '我的收藏'
        case '/histories':
            return '历史记录'
        case '/me':
            return '我的'
        case '/admin':
            return '管理后台'
        case '/gallery':
            return '封神图鉴'
        case '/about':
            return '关于我们'
        case '/settings':
            return '系统设置'
        case '/login':
            return '登录 / 注册'
        default:
            return '饭否'
    }
})

const pageSubtitle = computed(() => {
    switch (route.path) {
        case '/':
            return '今天也能轻松做顿好饭'
        case '/today-eat':
            return '一键抽取今天的专属推荐'
        case '/table-design':
            return '聚餐菜单一键搭配'
        case '/how-to-cook':
            return '步骤清晰，照着就能做'
        case '/sauce-design':
            return '做一份属于你的灵魂蘸料'
        case '/fortune-cooking':
            return '用趣味方式找灵感'
        case '/plaza':
            return '玩法入口一站直达'
        case '/collect':
            return route.query.tab === 'histories' ? '查看最近生成记录' : '收藏灵感，随时回看'
        case '/favorites':
            return '收藏灵感，随时回看'
        case '/histories':
            return '记录每一次下厨灵感'
        case '/me':
            return '账号与个性设置'
        case '/admin':
            return '运营管理中心'
        case '/gallery':
            return '保存每一次出图成果'
        case '/about':
            return '项目介绍与版本信息'
        case '/settings':
            return '偏好设置与体验配置'
        case '/login':
            return '登录后可同步收藏与历史'
        default:
            return '轻松做饭，认真吃饭'
    }
})

</script>

<style scoped>
@keyframes rotate {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.rotate-logo {
    animation: rotate 0.5s ease-out;
}
</style>