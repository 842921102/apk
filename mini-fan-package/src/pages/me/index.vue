<template>
  <view class="me">
    <!-- 自定义顶栏：居中标题（避让微信胶囊，与系统导航字号一致） -->
    <view class="me__nav" :style="navWrapStyle">
      <view class="me__nav-row" :style="{ height: `${navBarPx}px` }">
        <!-- 与原生导航栏一致：相对屏幕水平居中，非「减去右侧胶囊后的区域居中」 -->
        <view class="me__nav-title-abs">
          <text class="me__nav-title">我的</text>
        </view>
      </view>
    </view>

    <scroll-view scroll-y class="me__scroll" :style="scrollStyle">
      <!-- —— 未登录 —— -->
      <view v-if="!isLoggedIn" class="me__guest-wrap">
        <view class="me__user-shell mp-card">
          <view class="me__user-card me__user-card--guest">
            <view class="me__avatar-ring">
              <image class="me__avatar-img" src="/static/tabbar/me.png" mode="aspectFit" />
            </view>
            <text class="me__guest-lead">立即登录</text>
            <text class="me__guest-sub">登录后同步收藏、最近推荐与个人偏好</text>
            <button
              class="mp-btn-primary me__wx-login"
              :loading="wxLoading"
              :disabled="wxLoading || !apiReady"
              @click="onWeChatLoginInline"
            >
              <text class="me__wx-login-txt">微信一键登录</text>
            </button>
          </view>
        </view>

        <view class="me__guest-newbie">
          <view class="me__newbie-rail-row">
            <text class="me__newbie-rail-k">新手</text>
            <text class="me__newbie-rail-h">新手指引</text>
          </view>
          <view class="mp-card mp-card--accent-soft me__newbie-card">
            <text class="me__newbie-kicker">上手三步</text>
            <view class="me__newbie-steps">
              <view v-for="(s, i) in newbieGuideSteps" :key="i" class="me__newbie-step">
                <text class="me__newbie-num">{{ i + 1 }}</text>
                <text class="me__newbie-txt">{{ s }}</text>
              </view>
            </view>
          </view>
        </view>

        <view class="me__guest-settings-bar">
          <text class="me__guest-settings-tap" @click="onSettingsTap">设置</text>
        </view>
      </view>

      <!-- —— 已登录 —— -->
      <view v-else class="me__logged-wrap">
        <view class="me__user-shell mp-card me__user-shell--accent">
          <view class="me__user-card me__user-card--logged">
            <view class="me__user-main">
              <view class="me__avatar-ring me__avatar-ring--lg" @click.stop="onAvatarTap">
                <image
                  v-if="avatarSrc"
                  class="me__avatar-img me__avatar-img--round"
                  :src="avatarSrc"
                  mode="aspectFill"
                />
                <text v-else class="me__avatar-letter">{{ avatarLetter }}</text>
              </view>
              <view class="me__user-meta">
                <text class="me__nick me__nick--tap" @click.stop="onNicknameTap">{{ displayPrimary }}</text>
                <text class="me__uid">ID {{ displayUserId }}</text>
                <text class="me__user-sub">{{ userCardSubtitle }}</text>
                <view class="me__user-tags">
                  <text class="me__pill me__pill--accent">{{ memberLabel }}</text>
                </view>
              </view>
            </view>
          </view>
        </view>

        <!-- 我的资产：收藏 / 推荐优先 -->
        <view v-if="configured" class="me__block me__block--assets">
          <view class="me__block-title-row">
            <text class="me__block-title">我的资产</text>
            <text class="me__block-sub">你积累的内容</text>
          </view>
          <view class="mp-card me__assets-shell">
            <view class="me__asset-hero" @click="goMenu('/pages/favorites/index')">
              <text class="me__asset-hero-ico">⭐</text>
              <view class="me__asset-hero-mid">
                <text class="me__asset-hero-name">我的收藏</text>
                <text class="me__asset-hero-desc">生成结果与内容收藏</text>
              </view>
              <text class="me__asset-hero-val">{{ favDisplay }}</text>
              <text class="me__asset-hero-arrow">›</text>
            </view>
            <view class="me__asset-hero" @click="goMenu('/pages/recommendation-history/index')">
              <text class="me__asset-hero-ico">📋</text>
              <view class="me__asset-hero-mid">
                <text class="me__asset-hero-name">最近推荐</text>
                <text class="me__asset-hero-desc">每次「吃什么」的推荐记录</text>
              </view>
              <text class="me__asset-hero-val">{{ recDisplay }}</text>
              <text class="me__asset-hero-arrow">›</text>
            </view>
            <view class="me__assets-subgrid">
              <view class="me__asset-sub" @click="goMenu('/pages/histories/index')">
                <text class="me__asset-sub-ico">🕐</text>
                <text class="me__asset-sub-name">我的历史</text>
                <text class="me__asset-sub-val">{{ histDisplay }}</text>
                <text class="me__asset-sub-more">查看 ›</text>
              </view>
              <view class="me__asset-sub" @click="goMenu('/pages/me/recommendation-preferences')">
                <text class="me__asset-sub-ico">🎯</text>
                <text class="me__asset-sub-name">推荐偏好</text>
                <text class="me__asset-sub-val me__asset-sub-val--muted">画像</text>
                <text class="me__asset-sub-more">去管理 ›</text>
              </view>
            </view>
          </view>
        </view>

        <!-- 订单与会员（次要） -->
        <view v-if="configured" class="me__block">
          <view class="me__block-title-row">
            <text class="me__block-title">更多服务</text>
          </view>
          <view class="mp-card me__service-card">
            <view class="me__service-row" @click="goMenu('/pages/mall/orders')">
              <text class="me__service-ico">🧾</text>
              <view class="me__service-mid">
                <text class="me__service-name">我的订单</text>
                <text class="me__service-desc">{{ orderHint }}</text>
              </view>
              <text class="me__service-arrow">›</text>
            </view>
            <view class="me__service-row" @click="onMemberCenterTap">
              <text class="me__service-ico">👑</text>
              <view class="me__service-mid">
                <text class="me__service-name">会员中心</text>
                <text class="me__service-desc">{{ memberShortHint }}</text>
              </view>
              <text class="me__service-arrow">›</text>
            </view>
          </view>
        </view>

        <view v-else class="me__block">
          <view class="mp-card me__hint-card">
            <text class="me__hint-title">完善登录环境</text>
            <text class="me__hint-body">配置小程序 BFF 地址后，可查看云端收藏与历史数量。</text>
          </view>
        </view>

        <!-- 各玩法生成记录 -->
        <view class="me__block">
          <view class="me__block-title-row">
            <text class="me__block-title">生成记录</text>
            <text class="me__block-sub">各玩法最近一次生成</text>
          </view>
          <view class="mp-card me__grid-card">
            <view class="me__record-grid">
              <view
                v-for="tile in recordTiles"
                :key="tile.id"
                class="me__record-cell"
                @click="onRecordTileTap(tile.type)"
              >
                <text class="me__record-ico">{{ tile.icon }}</text>
                <text class="me__record-name">{{ tile.title }}</text>
                <text v-if="latestByType[tile.type]?.title" class="me__record-sum">
                  {{ latestByType[tile.type]?.title }}
                </text>
                <text v-else class="me__record-sum me__record-sum--muted">暂无记录</text>
              </view>
            </view>
          </view>
        </view>

        <!-- 服务中心 -->
        <view class="me__block">
          <view class="me__block-title-row">
            <text class="me__block-title">服务中心</text>
          </view>
          <view class="mp-card me__service-card">
            <view
              v-for="entry in serviceEntries"
              :key="entry.id"
              class="me__service-row"
              @click="onServiceTap(entry.id)"
            >
              <text class="me__service-ico">{{ entry.icon }}</text>
              <view class="me__service-mid">
                <text class="me__service-name">{{ entry.title }}</text>
                <text class="me__service-desc">{{ entry.subtitle }}</text>
              </view>
              <text class="me__service-arrow">›</text>
            </view>
          </view>
        </view>

        <!-- 设置与退出 -->
        <view class="me__block me__block--foot">
          <view class="mp-card me__foot-card">
            <view class="me__foot-row" hover-class="me__foot-row--hover" :hover-stay-time="80" @click="onSettingsTap">
              <text class="me__foot-row-ico">⚙️</text>
              <text class="me__foot-row-txt">设置</text>
              <text class="me__foot-row-arrow">›</text>
            </view>
            <view class="me__foot-divider" />
            <button class="me__foot-logout" @click="onLogoutTap">
              <text class="me__foot-logout-txt">{{ logoutButtonLabel }}</text>
            </button>
            <text class="me__ver me__ver--foot">版本 v{{ appVersion }}</text>
          </view>
        </view>
      </view>
    </scroll-view>

    <!-- 未登录：版本号固定在 TabBar 上方 -->
    <view v-if="!isLoggedIn" class="me__guest-ver-fixed">
      <text class="me__ver-fixed">版本 v{{ appVersion }}</text>
    </view>

    <!-- 微信头像授权（从相册/拍照在 actionSheet 中直接选） -->
    <view
      v-if="wxAvatarPickerVisible"
      class="me__mask"
      @touchmove.stop.prevent
      @click="wxAvatarPickerVisible = false"
    >
      <view class="me__sheet" @click.stop>
        <text class="me__sheet-title">使用微信头像</text>
        <button class="me__sheet-primary" open-type="chooseAvatar" @chooseavatar="onWxChooseAvatar">
          选择微信头像
        </button>
        <button class="me__sheet-cancel" @click="wxAvatarPickerVisible = false">取消</button>
      </view>
    </view>

    <!-- 昵称：支持 type=nickname 同步微信昵称 -->
    <view
      v-if="nickSheetVisible"
      class="me__mask"
      @touchmove.stop.prevent
      @click="closeNickSheet"
    >
      <view class="me__sheet me__sheet--nick" @click.stop>
        <text class="me__sheet-title">修改昵称</text>
        <input
          v-model="nicknameDraft"
          class="me__nick-input"
          type="nickname"
          maxlength="24"
          placeholder="输入昵称或使用微信昵称"
        />
        <view class="me__sheet-actions">
          <button class="me__sheet-cancel me__sheet-cancel--grow" @click="closeNickSheet">取消</button>
          <button class="me__sheet-save" @click="saveNicknameDraft">保存</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { onReady, onShow } from '@dcloudio/uni-app'
import { useAuth } from '@/composables/useAuth'
import { fetchHistories, getFavoritesCount, getHistoriesCount } from '@/api/biz'
import { apiListRecommendationRecords } from '@/api/recommendationRecords'
import { loginWithWechatCode } from '@/api/auth'
import { HttpError } from '@/api/http'
import { shouldAutoOpenOnboardingForCurrentSession } from '@/composables/useOnboardingFlow'
import { isSupabaseConfigured } from '@/lib/supabase'
import { API_BASE_URL } from '@/constants'
import { requestWeChatLoginCode } from '@/services/auth/wechatLoginSkeleton'
import {
  LOGOUT_BUTTON,
  LOGOUT_CONFIRM_TITLE,
  LOGOUT_CONFIRM_CONTENT,
} from '@/config/meCopy'
import { NEWBIE_GUIDE_STEPS } from '@/config/newbieGuide'
import type { HistoryRow } from '@/types/dto'
import { openResultDetail, toDetailPayloadFromHistory, normalizeSourceType, type ResultSourceType } from '@/lib/resultDetail'

const {
  currentUser,
  isLoggedIn,
  syncAuthFromSupabase,
  setToken,
  setCurrentUser,
  logout,
} = useAuth()

const logoutButtonLabel = LOGOUT_BUTTON
const appVersion = '1.0.0'
const newbieGuideSteps = NEWBIE_GUIDE_STEPS

const memberLabel = ref('普通用户')
const orderHint = '全部订单'
const memberShortHint = '权益·订阅'

const recordTiles = [
  { id: 'today_eat_record', type: 'today_eat' as const, title: '吃什么记录', icon: '🍽️' },
  { id: 'table_design_record', type: 'table_design' as const, title: '一桌好菜记录', icon: '🥘' },
  { id: 'fortune_cooking_record', type: 'fortune_cooking' as const, title: '玄学厨房记录', icon: '🔮' },
  { id: 'sauce_design_record', type: 'sauce_design' as const, title: '酱料大师记录', icon: '🧂' },
] as const

const serviceEntries = [
  { id: 'help_center' as const, title: '帮助中心', subtitle: '使用指引与常见问题', icon: '💡' },
  { id: 'about_us' as const, title: '关于我们', subtitle: '饭否小程序', icon: 'ℹ️' },
  { id: 'user_agreement' as const, title: '用户协议', subtitle: '服务条款说明', icon: '📋' },
  { id: 'privacy_policy' as const, title: '隐私政策', subtitle: '个人信息保护说明', icon: '🔐' },
] as const

type ServiceId = (typeof serviceEntries)[number]['id']

const statusBarPx = ref(20)
const navBarPx = ref(44)
const windowWidthPx = ref(375)

const navWrapStyle = computed(() => ({
  paddingTop: `${statusBarPx.value}px`,
}))

function rpxToPx(rpx: number, winW: number): number {
  return (rpx / 750) * winW
}

/**
 * 本页为原生 tabBar（pages.json tabBar.custom=false），windowHeight 已是「导航以下、tabBar 以上」的可视高度，
 * 再按自定义 MpIcoTabBar 去扣 96rpx 会在 scroll-view 下方多出一条无法滚动的空白带，看起来像白条遮挡内容。
 */
/** 未登录：底部固定版本条占位高度（与 .me__guest-ver-fixed 对齐） */
function guestVersionStripPx(): number {
  return isLoggedIn.value ? 0 : rpxToPx(64, windowWidthPx.value)
}

/** 与灵感页一致：用 windowHeight + 实测导航底边算高度；自定义导航页里 fixed+bottom:0 的 scroll-view 易出底白条 */
const meScrollTopPx = ref(88)
const meScrollHeightPx = ref(520)

function measureMeScroll() {
  const sys = uni.getSystemInfoSync()
  const wh = Number(sys.windowHeight) || 667
  const strip = guestVersionStripPx()
  uni.createSelectorQuery()
    .select('.me__nav')
    .boundingClientRect((rect) => {
      const top =
        rect && typeof rect.bottom === 'number' ? rect.bottom : statusBarPx.value + navBarPx.value
      meScrollTopPx.value = top
      meScrollHeightPx.value = Math.max(200, wh - top - strip)
    })
    .exec()
}

const scrollStyle = computed(() => ({
  position: 'fixed' as const,
  left: '0',
  right: '0',
  top: `${meScrollTopPx.value}px`,
  height: `${meScrollHeightPx.value}px`,
  boxSizing: 'border-box' as const,
}))

watch(isLoggedIn, () => {
  nextTick(() => measureMeScroll())
})

function initNavLayout() {
  try {
    const sys = uni.getSystemInfoSync()
    const sb = Number(sys.statusBarHeight) || 20
    statusBarPx.value = sb
    windowWidthPx.value = Number(sys.windowWidth) || 375
    let navH = 44
    // #ifdef MP-WEIXIN
    try {
      const mb = uni.getMenuButtonBoundingClientRect()
      if (mb && typeof mb.top === 'number' && mb.height) {
        navH = mb.height + (mb.top - sb) * 2
      }
    } catch {
      /* ignore */
    }
    // #endif
    navBarPx.value = navH
  } catch {
    statusBarPx.value = 20
    navBarPx.value = 44
  }
  meScrollTopPx.value = statusBarPx.value + navBarPx.value
  nextTick(() => measureMeScroll())
}

const configured = computed(() => isSupabaseConfigured() || Boolean(API_BASE_URL.trim()))

const wxLoading = ref(false)
const isWxDevtools = ref(false)

const apiReady = computed(() => Boolean(API_BASE_URL.trim()))
const apiUsesLoopback = computed(() => {
  const u = API_BASE_URL.trim().toLowerCase()
  return u.includes('localhost') || u.includes('127.0.0.1')
})

const favCount = ref(0)
const histCount = ref(0)
const recommendationRecCount = ref(0)
const statsLoading = ref(false)

const historyRows = ref<HistoryRow[]>([])

const displayPrimary = computed(() => {
  const u = currentUser.value
  if (!u) return '用户'
  if (u.nickname?.trim()) return u.nickname.trim()
  return '用户'
})

const avatarSrc = computed(() => {
  const u = currentUser.value
  const url = u?.avatarUrl?.trim()
  return url || ''
})

const avatarLetter = computed(() => {
  const s = displayPrimary.value.trim()
  return s ? s.slice(0, 1) : '用'
})

const wxAvatarPickerVisible = ref(false)
const nickSheetVisible = ref(false)
const nicknameDraft = ref('')

const favDisplay = computed(() => {
  if (!configured.value) return '—'
  if (statsLoading.value) return '…'
  return String(favCount.value)
})

const histDisplay = computed(() => {
  if (!configured.value) return '—'
  if (statsLoading.value) return '…'
  return String(histCount.value)
})

const recDisplay = computed(() => {
  if (!configured.value) return '—'
  if (statsLoading.value) return '…'
  return String(recommendationRecCount.value)
})

const displayUserId = computed(() => {
  const id = currentUser.value?.id
  if (id == null || String(id).trim() === '') return '—'
  return String(id).trim()
})

const userCardSubtitle = computed(() => {
  if (!configured.value) return '登录环境未完全配置时部分数据仅本地可见'
  return '收藏、最近推荐与偏好云端同步，换设备也能接着用'
})

function inferHistorySourceType(h: HistoryRow): ResultSourceType {
  return normalizeSourceType(h.source_type, h.request_payload)
}

const latestByType = computed(() => {
  const out: Record<ResultSourceType, HistoryRow | null> = {
    today_eat: null,
    table_design: null,
    fortune_cooking: null,
    sauce_design: null,
    gallery: null,
  }

  for (const h of historyRows.value) {
    const t = inferHistorySourceType(h)
    if (out[t] === null) out[t] = h
  }

  return out
})

onMounted(() => {
  initNavLayout()
})

onReady(() => {
  nextTick(() => measureMeScroll())
})

onShow(() => {
  try {
    const p = String(uni.getSystemInfoSync()?.platform || '').toLowerCase()
    isWxDevtools.value = p === 'devtools'
  } catch {
    isWxDevtools.value = false
  }
  initNavLayout()
  void refresh()
})

async function refresh() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value || !configured.value) {
    favCount.value = 0
    histCount.value = 0
    recommendationRecCount.value = 0
    statsLoading.value = false
    historyRows.value = []
    return
  }
  statsLoading.value = true
  try {
    const [fSettled, hSettled, recSettled, rowsSettled] = await Promise.allSettled([
      getFavoritesCount(),
      getHistoriesCount(),
      apiListRecommendationRecords({ per_page: 1, page: 1 }).then((r) => r.meta.pagination.total),
      fetchHistories(),
    ])
    favCount.value = fSettled.status === 'fulfilled' ? fSettled.value : 0
    histCount.value = hSettled.status === 'fulfilled' ? hSettled.value : 0
    recommendationRecCount.value = recSettled.status === 'fulfilled' ? recSettled.value : 0
    historyRows.value = rowsSettled.status === 'fulfilled' ? rowsSettled.value : []
  } finally {
    statsLoading.value = false
  }
}

function toastFromError(e: unknown): string {
  if (e instanceof HttpError) return e.message.slice(0, 240)
  if (e instanceof Error) return e.message.slice(0, 240)
  return '登录失败'
}

function pickAvatarFromUser(wxUser: Record<string, unknown>): string | undefined {
  for (const k of ['avatar_url', 'avatarUrl', 'headimgurl', 'avatar']) {
    const v = wxUser[k]
    if (typeof v === 'string' && v.trim()) return v.trim()
  }
  return undefined
}

async function onWeChatLoginInline() {
  if (!apiReady.value) {
    uni.showToast({ title: '未配置 BFF 地址', icon: 'none' })
    return
  }
  if (apiUsesLoopback.value && !isWxDevtools.value) {
    uni.showToast({
      title: '真机请改用局域网 IP 的 BFF 地址',
      icon: 'none',
      duration: 2600,
    })
    return
  }
  wxLoading.value = true
  try {
    const code = await requestWeChatLoginCode()
    const result = await loginWithWechatCode(code)
    const accessToken = (result.access_token ?? result.accessToken) as string | undefined
    const wxUser = result.user as Record<string, unknown> | undefined

    if (accessToken) {
      setToken(accessToken)
      if (wxUser && wxUser.id != null) {
        const nickname = typeof wxUser.nickname === 'string' ? wxUser.nickname : undefined
        setCurrentUser({
          id: String(wxUser.id),
          nickname,
          avatarUrl: pickAvatarFromUser(wxUser),
          needsOnboarding: wxUser.needs_onboarding === true,
          periodFeatureEnabled: wxUser.period_feature_enabled === true,
        })
      }
      await syncAuthFromSupabase()
      await refresh()
      if (shouldAutoOpenOnboardingForCurrentSession()) {
        uni.redirectTo({
          url: `/pages/onboarding/index?redirect=${encodeURIComponent('/pages/me/index')}`,
        })
        return
      }
      uni.showToast({ title: '登录成功', icon: 'success' })
    } else {
      uni.showToast({ title: '服务端未返回 token', icon: 'none' })
    }
  } catch (e) {
    console.error('[me wx login]', e)
    uni.showToast({ title: toastFromError(e), icon: 'none', duration: 2600 })
  } finally {
    wxLoading.value = false
  }
}

function goMenu(path: string) {
  uni.navigateTo({ url: path })
}

function onAvatarTap() {
  if (!currentUser.value) return
  uni.showActionSheet({
    itemList: ['使用微信头像', '从相册选择', '拍照'],
    success: (res) => {
      if (res.tapIndex === 0) wxAvatarPickerVisible.value = true
      else if (res.tapIndex === 1) pickLocalAvatar(['album'])
      else if (res.tapIndex === 2) pickLocalAvatar(['camera'])
    },
  })
}

function pickLocalAvatar(sourceType: Array<'album' | 'camera'>) {
  const u = currentUser.value
  if (!u) return
  uni.chooseImage({
    count: 1,
    sizeType: ['compressed'],
    sourceType,
    success: (res) => {
      const p = res.tempFilePaths?.[0]
      if (!p) return
      setCurrentUser({ ...u, avatarUrl: p })
      uni.showToast({ title: '头像已更新', icon: 'success' })
    },
  })
}

function onWxChooseAvatar(e: { detail?: { avatarUrl?: string } }) {
  const u = currentUser.value
  const url = e?.detail?.avatarUrl?.trim()
  wxAvatarPickerVisible.value = false
  if (!url || !u) return
  setCurrentUser({ ...u, avatarUrl: url })
  uni.showToast({ title: '头像已更新', icon: 'success' })
}

function onNicknameTap() {
  if (!currentUser.value) return
  nicknameDraft.value = displayPrimary.value === '用户' ? '' : displayPrimary.value
  nickSheetVisible.value = true
}

function closeNickSheet() {
  nickSheetVisible.value = false
}

function saveNicknameDraft() {
  const u = currentUser.value
  if (!u) return
  const v = nicknameDraft.value.trim()
  setCurrentUser({
    ...u,
    nickname: v || undefined,
  })
  closeNickSheet()
  uni.showToast({ title: '昵称已保存', icon: 'success' })
}

function onSettingsTap() {
  uni.showModal({
    title: '设置',
    content: '更多设置能力将陆续开放。',
    showCancel: false,
  })
}

function onMemberCenterTap() {
  uni.showToast({ title: '会员中心即将开放', icon: 'none' })
}

function onRecordTileTap(type: ResultSourceType) {
  const h = latestByType.value[type]
  if (h) {
    openResultDetail(toDetailPayloadFromHistory(h))
    return
  }
  goMenu('/pages/histories/index')
}

function onServiceTap(id: ServiceId) {
  if (id === 'help_center') {
    uni.showModal({
      title: '帮助中心',
      content:
        '「吃什么」生成推荐后，可在「我的资产」查看收藏与最近推荐；生成类玩法记录在下方「生成记录」快速回看。口味与问卷相关入口在「推荐偏好」。',
      showCancel: false,
    })
    return
  }
  if (id === 'about_us') {
    uni.showModal({
      title: '关于我们',
      content: '饭否小程序：美食灵感与生成工具。收藏、历史支持云端同步与回看。',
      showCancel: false,
    })
    return
  }
  if (id === 'user_agreement') {
    uni.showModal({ title: '用户协议', content: '协议正文将由运营侧配置后展示。', showCancel: false })
    return
  }
  if (id === 'privacy_policy') {
    uni.showModal({ title: '隐私政策', content: '隐私说明将由运营侧配置后展示。', showCancel: false })
    return
  }
}

function onLogoutTap() {
  uni.showModal({
    title: LOGOUT_CONFIRM_TITLE,
    content: LOGOUT_CONFIRM_CONTENT,
    confirmText: '退出',
    cancelText: '取消',
    success: (res) => {
      if (res.confirm) void doLogout()
    },
  })
}

async function doLogout() {
  await logout()
  favCount.value = 0
  histCount.value = 0
  recommendationRecCount.value = 0
  historyRows.value = []
  uni.showToast({ title: '已退出登录', icon: 'none' })
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

.me {
  /* min-height:100vh 在 Tab 页易比实际可视区「撑高」，底部多出一条与页面背景同色的空白带 */
  background: $mp-bg-page;
}

.me__nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 200;
  background: rgba($mp-bg-page, 0.92);
  backdrop-filter: blur(12px);
  border-bottom: 1rpx solid rgba($mp-border, 0.85);
}

.me__nav-row {
  position: relative;
  box-sizing: border-box;
}

/* 对齐微信原生导航：标题相对整栏垂直居中，水平为视口中心（与「吃什么」等默认导航一致） */
.me__nav-title-abs {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  max-width: 52%;
  pointer-events: none;
}

.me__nav-title {
  text-align: center;
  font-size: 34rpx;
  font-weight: 500;
  line-height: 1;
  color: #000000;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.me__nav-chip {
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: $mp-accent-soft;
  border: 1rpx solid $mp-ring-accent;
}

.me__nav-chip--ghost {
  background: #fff;
  color: $mp-text-secondary;
  border-color: $mp-border;
}

.me__scroll {
  box-sizing: border-box;
  padding-top: 0;
  /* 避免默认白底在 TabBar 重叠区形成「挡板」 */
  background: $mp-bg-page;
}

.me__block {
  padding: 0 24rpx;
  margin-top: 24rpx;
}

.me__block--foot {
  margin-bottom: 32rpx;
}

.me__guest-settings-bar {
  margin: 28rpx 24rpx 0;
  display: flex;
  flex-direction: row;
  justify-content: center;
}

.me__guest-settings-tap {
  font-size: 26rpx;
  font-weight: 700;
  color: $mp-text-secondary;
  padding: 12rpx 24rpx;
}

.me__guest-newbie {
  margin: 24rpx 24rpx 0;
}

.me__newbie-rail-row {
  display: flex;
  flex-direction: row;
  align-items: baseline;
  gap: 12rpx;
  margin-bottom: 16rpx;
  padding-left: 8rpx;
}

.me__newbie-rail-k {
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: $mp-accent;
  text-transform: uppercase;
}

.me__newbie-rail-h {
  font-size: 30rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__newbie-card {
  padding: 26rpx 22rpx 28rpx;
}

.me__newbie-kicker {
  display: block;
  font-size: 22rpx;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: $mp-accent;
  margin-bottom: 16rpx;
}

.me__newbie-steps {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.me__newbie-step {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16rpx;
}

.me__newbie-num {
  flex-shrink: 0;
  width: 40rpx;
  height: 40rpx;
  line-height: 40rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 700;
  color: $mp-accent;
  background: #fff;
  border-radius: 999rpx;
  border: 1rpx solid #dccdf7;
}

.me__newbie-txt {
  flex: 1;
  font-size: 26rpx;
  line-height: 1.55;
  color: #5a3ba8;
  padding-top: 4rpx;
}

.me__guest-ver-fixed {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 920;
  /* 原生 tabBar 页：可视区底即 tab 上沿，勿再按自定义 TabBar 预留 96rpx */
  bottom: 0;
  min-height: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8rpx 0;
  background: rgba($mp-bg-page, 0.96);
  border-top: 1rpx solid rgba($mp-border, 0.6);
  pointer-events: none;
}

.me__ver-fixed {
  font-size: 22rpx;
  color: $mp-text-muted;
}

.me__block-title-row {
  display: flex;
  flex-direction: row;
  align-items: baseline;
  justify-content: space-between;
  gap: 16rpx;
  margin-bottom: 14rpx;
  padding-left: 6rpx;
}

.me__block-title {
  font-size: 30rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__block-sub {
  font-size: 22rpx;
  color: $mp-text-muted;
}

/* 用户信息卡 */
.me__user-shell {
  margin: 16rpx 24rpx 0;
  overflow: hidden;
  border-radius: $mp-radius-card;
  box-shadow: $mp-shadow-card;
}

.me__user-shell--accent {
  border: 1rpx solid $mp-ring-accent;
  box-shadow: 0 16rpx 48rpx rgba(122, 87, 209, 0.12);
}

.me__user-card {
  position: relative;
  padding: 28rpx 24rpx 32rpx;
}

.me__user-card-actions {
  position: absolute;
  top: 20rpx;
  right: 20rpx;
  display: flex;
  flex-direction: row;
  gap: 10rpx;
  z-index: 2;
}

.me__user-card--guest {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding-top: 36rpx;
}

.me__user-card--logged {
  padding-top: 28rpx;
}

.me__avatar-ring {
  width: 120rpx;
  height: 120rpx;
  border-radius: 36rpx;
  background: linear-gradient(145deg, $mp-accent-soft 0%, #fff 100%);
  border: 2rpx solid $mp-ring-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 8rpx 28rpx rgba(122, 87, 209, 0.12);
}

.me__avatar-ring--lg {
  width: 128rpx;
  height: 128rpx;
  flex-shrink: 0;
}

.me__avatar-img {
  width: 72rpx;
  height: 72rpx;
}

.me__avatar-img--round {
  width: 100%;
  height: 100%;
  border-radius: 36rpx;
}

.me__avatar-letter {
  font-size: 48rpx;
  font-weight: 900;
  color: $mp-accent-deep;
}

.me__guest-lead {
  margin-top: 22rpx;
  font-size: 32rpx;
  font-weight: 900;
  color: $mp-text-primary;
  line-height: 1.35;
  padding: 0 12rpx;
}

.me__guest-sub {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-muted;
  padding: 0 32rpx;
  text-align: center;
}

.me__wx-login {
  margin-top: 36rpx;
  width: 100%;
  max-width: 520rpx;
  padding-top: 28rpx !important;
  padding-bottom: 28rpx !important;
  font-size: 30rpx !important;
  font-weight: 800 !important;
  box-shadow: 0 16rpx 44rpx rgba(122, 87, 209, 0.32);
}

.me__wx-login-txt {
  font-weight: 800;
}

.me__user-main {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 22rpx;
}

.me__user-meta {
  flex: 1;
  min-width: 0;
  padding-top: 6rpx;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10rpx;
}

.me__uid {
  font-size: 24rpx;
  color: $mp-text-muted;
  font-weight: 600;
}

.me__user-sub {
  font-size: 24rpx;
  line-height: 1.45;
  color: $mp-text-secondary;
}

.me__user-tags {
  margin-top: 4rpx;
}

.me__nick {
  display: block;
  font-size: 36rpx;
  font-weight: 900;
  color: $mp-text-primary;
  line-height: 1.25;
}

.me__nick--tap {
  border-bottom: 2rpx dashed rgba(122, 87, 209, 0.35);
  padding-bottom: 4rpx;
}

.me__pill {
  font-size: 20rpx;
  font-weight: 800;
  color: $mp-text-secondary;
  padding: 8rpx 14rpx;
  border-radius: 999rpx;
  background: #f3f4f6;
  border: 1rpx solid $mp-border;
}

.me__pill--accent {
  color: $mp-accent;
  background: $mp-accent-soft;
  border-color: $mp-ring-accent;
}

/* 我的资产：主入口条 + 次宫格 */
.me__assets-shell {
  padding: 20rpx 18rpx 22rpx;
}

.me__asset-hero {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 18rpx;
  padding: 26rpx 20rpx;
  margin-bottom: 14rpx;
  border-radius: 22rpx;
  background: linear-gradient(135deg, rgba(122, 87, 209, 0.08) 0%, #fafbfc 55%, #fff 100%);
  border: 1rpx solid rgba(122, 87, 209, 0.22);
  box-sizing: border-box;
}

.me__asset-hero + .me__assets-subgrid {
  margin-top: 4rpx;
}

.me__asset-hero:active {
  opacity: 0.92;
}

.me__asset-hero-ico {
  font-size: 44rpx;
  line-height: 1;
  flex-shrink: 0;
}

.me__asset-hero-mid {
  flex: 1;
  min-width: 0;
}

.me__asset-hero-name {
  display: block;
  font-size: 32rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__asset-hero-desc {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
  line-height: 1.35;
}

.me__asset-hero-val {
  font-size: 40rpx;
  font-weight: 900;
  color: $mp-accent;
  flex-shrink: 0;
  min-width: 56rpx;
  text-align: right;
}

.me__asset-hero-arrow {
  font-size: 36rpx;
  color: $mp-text-muted;
  flex-shrink: 0;
}

.me__assets-subgrid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14rpx;
}

.me__asset-sub {
  min-height: 168rpx;
  padding: 20rpx 18rpx;
  border-radius: 20rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 6rpx;
}

.me__asset-sub:active {
  background: #f3f4f6;
}

.me__asset-sub-ico {
  font-size: 32rpx;
  line-height: 1;
  margin-bottom: 2rpx;
}

.me__asset-sub-name {
  font-size: 26rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__asset-sub-val {
  font-size: 34rpx;
  font-weight: 900;
  color: $mp-accent;
  line-height: 1.1;
  margin-top: 4rpx;
}

.me__asset-sub-val--muted {
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-text-secondary;
}

.me__asset-sub-more {
  margin-top: auto;
  padding-top: 8rpx;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
}

/* 资产 / 记录 宫格 */
.me__grid-card {
  padding: 22rpx 20rpx 24rpx;
}

.me__asset-grid,
.me__record-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16rpx;
}

.me__asset-cell,
.me__record-cell {
  min-height: 168rpx;
  padding: 20rpx 18rpx;
  border-radius: 20rpx;
  background: #fafbfc;
  border: 1rpx solid $mp-border;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  gap: 6rpx;
}

.me__asset-name,
.me__record-name {
  font-size: 26rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__asset-val {
  font-size: 40rpx;
  font-weight: 900;
  color: $mp-accent;
  line-height: 1.1;
  margin-top: 4rpx;
}

.me__asset-val--sub {
  font-size: 26rpx;
  font-weight: 800;
  color: $mp-text-secondary;
  margin-top: 8rpx;
}

.me__asset-more {
  margin-top: auto;
  padding-top: 8rpx;
  font-size: 22rpx;
  font-weight: 800;
  color: $mp-accent;
}

.me__record-ico {
  font-size: 36rpx;
  line-height: 1;
  margin-bottom: 4rpx;
}

.me__record-sum {
  font-size: 22rpx;
  color: $mp-text-secondary;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.me__record-sum--muted {
  color: $mp-text-muted;
}

/* 服务中心 */
.me__service-card {
  padding: 8rpx 8rpx 12rpx;
}

.me__service-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 22rpx 16rpx;
  border-radius: 16rpx;
}

.me__service-row:active {
  background: #f5f6f8;
}

.me__service-ico {
  font-size: 34rpx;
  width: 48rpx;
  text-align: center;
  flex-shrink: 0;
}

.me__service-mid {
  flex: 1;
  min-width: 0;
}

.me__service-name {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__service-desc {
  display: block;
  margin-top: 6rpx;
  font-size: 22rpx;
  color: $mp-text-muted;
}

.me__service-arrow {
  font-size: 32rpx;
  color: $mp-text-muted;
  flex-shrink: 0;
}

.me__hint-card {
  padding: 28rpx 24rpx;
}

.me__hint-title {
  display: block;
  font-size: 28rpx;
  font-weight: 900;
  color: $mp-text-primary;
}

.me__hint-body {
  display: block;
  margin-top: 10rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: $mp-text-secondary;
}

/* 底部：设置 + 退出 */
.me__foot-card {
  padding: 8rpx 8rpx 20rpx;
  overflow: hidden;
}

.me__foot-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx 18rpx;
  border-radius: 16rpx;
}

.me__foot-row--hover {
  background: #f5f6f8;
}

.me__foot-row-ico {
  font-size: 34rpx;
  width: 48rpx;
  text-align: center;
  flex-shrink: 0;
}

.me__foot-row-txt {
  flex: 1;
  font-size: 30rpx;
  font-weight: 800;
  color: $mp-text-primary;
}

.me__foot-row-arrow {
  font-size: 32rpx;
  color: $mp-text-muted;
}

.me__foot-divider {
  height: 1rpx;
  margin: 0 20rpx;
  background: rgba($mp-border, 0.85);
}

.me__foot-logout {
  margin: 12rpx 12rpx 0;
  width: calc(100% - 24rpx);
  padding: 26rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: #d64556 !important;
  background: transparent !important;
  border: none !important;
  border-radius: 16rpx;
}

.me__foot-logout::after {
  border: none !important;
}

.me__foot-logout-txt {
  font-weight: 800;
}

.me__ver {
  display: block;
  margin-top: 20rpx;
  font-size: 20rpx;
  color: $mp-text-muted;
  text-align: center;
}

.me__ver--foot {
  margin-top: 16rpx;
}

.me__mask {
  position: fixed;
  z-index: 500;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.me__sheet {
  width: 100%;
  padding: 32rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  box-sizing: border-box;
}

.me__sheet-title {
  display: block;
  font-size: 32rpx;
  font-weight: 900;
  color: $mp-text-primary;
  text-align: center;
}

.me__sheet-primary {
  margin-top: 28rpx;
  width: 100%;
  padding: 26rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: #fff !important;
  background: #07c160;
  border-radius: 16rpx;
  border: none;
}

.me__sheet-primary::after {
  border: none;
}

.me__sheet-cancel {
  margin-top: 16rpx;
  width: 100%;
  padding: 22rpx;
  font-size: 28rpx;
  font-weight: 600;
  color: $mp-text-secondary;
  background: #f3f4f6;
  border-radius: 16rpx;
  border: none;
}

.me__sheet-cancel::after {
  border: none;
}

.me__sheet--nick {
  max-height: 70vh;
}

.me__nick-input {
  margin-top: 28rpx;
  width: 100%;
  height: 88rpx;
  padding: 0 20rpx;
  border-radius: 14rpx;
  border: 1rpx solid $mp-border;
  background: #fafbfc;
  font-size: 28rpx;
  color: $mp-text-primary;
  box-sizing: border-box;
}

.me__sheet-actions {
  margin-top: 28rpx;
  display: flex;
  flex-direction: row;
  gap: 16rpx;
}

.me__sheet-save {
  flex: 1;
  padding: 22rpx;
  font-size: 28rpx;
  font-weight: 800;
  color: #fff !important;
  background: $mp-accent;
  border-radius: 16rpx;
  border: none;
}

.me__sheet-save::after {
  border: none;
}

.me__sheet-cancel--grow {
  flex: 1;
  margin-top: 0 !important;
}
</style>
