<template>
  <view class="me">
    <!-- 自定义顶栏占位：全透明、无标题，保留状态栏+导航条高度；渐变贴片铺到顶，上滑后白底透过 -->
    <view class="me__nav" :style="navWrapStyle">
      <view class="me__nav-row" :style="{ height: `${navBarPx}px` }"></view>
    </view>

    <scroll-view scroll-y class="me__scroll" :style="scrollStyle">
      <!-- —— 未登录 —— -->
      <view v-if="!isLoggedIn" class="me__guest-wrap">
        <view class="me__hero me__hero--guest" :style="{ paddingTop: `${guestHeroPadTopPx}px` }">
          <view class="me__hero-bg" aria-hidden="true" />
          <view class="me__hero-card me__hero-card--guest">
            <view class="me__guest-identity">
              <view class="me__guest-avatar-ring">
                <image class="me__guest-avatar-img" src="/static/tabbar/tab-me.png" mode="aspectFit" />
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
        </view>

        <view class="me__guest-newbie">
          <view class="me__section-head me__section-head--guest">
            <text class="me__section-kicker">新手</text>
            <text class="me__section-title">新手指引</text>
          </view>
          <view class="me__card me__card--lg me__newbie-card me__newbie-card--soft">
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

      <!-- —— 已登录：参考「头部氛围 → 主权益卡 → 数据概览 → 主功能卡 → 生成记录 → 服务」骨架 —— -->
      <view v-else class="me__logged-wrap">
        <!-- 1 顶部完整氛围区：身份 + 右上入口（非白底资料卡） -->
        <view class="me__atmos" :style="{ paddingTop: `${atmosPadTopPx}px` }">
          <view class="me__atmos-bg" aria-hidden="true" />
          <view class="me__atmos-inner">
            <view class="me__atmos-head">
              <view class="me__atmos-user">
                <view class="me__atmos-avatar" @click.stop="onAvatarTap">
                  <view class="me__avatar-ring me__avatar-ring--atmos">
                    <image
                      v-if="avatarSrc"
                      class="me__avatar-img me__avatar-img--round"
                      :src="avatarSrc"
                      mode="aspectFill"
                    />
                    <text v-else class="me__avatar-letter">{{ avatarLetter }}</text>
                  </view>
                </view>
                <view class="me__atmos-meta">
                  <text class="me__atmos-name me__atmos-name--tap" @click.stop="onNicknameTap">{{ displayPrimary }}</text>
                  <view class="me__atmos-tag-row">
                    <text class="me__pill me__pill--accent">{{ memberLabel }}</text>
                  </view>
                </view>
              </view>
              <view class="me__atmos-actions">
                <view
                  class="me__atmos-act me__atmos-act--icon-only"
                  hover-class="me__atmos-act--hover"
                  :hover-stay-time="80"
                  @click="onSettingsTap"
                >
                  <MeThemedIcon name="settings" :size-rpx="40" />
                </view>
              </view>
            </view>
          </view>
        </view>

        <!-- 2 核心主卡片（权益卡位）：推荐偏好画像 -->
        <view class="me__block me__block--tight">
          <view class="me__card me__card--lg me__benefit" @click="goMenu('/pages/me/recommendation-preferences')">
            <view class="me__benefit-main">
              <text class="me__benefit-title">推荐偏好画像</text>
              <text class="me__benefit-sub">{{ userCardSubtitle }}</text>
              <view class="me__benefit-metrics">
                <text class="me__benefit-metric">收藏 {{ favDisplay }}</text>
                <text class="me__benefit-dot">·</text>
                <text class="me__benefit-metric">最近推荐 {{ recDisplay }}</text>
                <text class="me__benefit-dot">·</text>
                <text class="me__benefit-metric">历史 {{ histDisplay }}</text>
              </view>
            </view>
            <view class="me__benefit-cta-wrap" @click.stop="goMenu('/pages/me/recommendation-preferences')">
              <text class="me__benefit-cta">去管理</text>
            </view>
          </view>
        </view>

        <!-- 3 数据概览：订单 + 收藏 / 最近推荐 / 历史 -->
        <view class="me__block">
          <view class="me__card me__card--lg me__stats-bar">
            <view class="me__stat-cell" hover-class="me__stat-cell--hover" :hover-stay-time="80" @click="goMenu('/pages/mall/orders')">
              <MeThemedIcon name="order" :size-rpx="44" />
              <text class="me__stat-label">订单</text>
            </view>
            <view class="me__stat-gap" />
            <view class="me__stat-cell" hover-class="me__stat-cell--hover" :hover-stay-time="80" @click="goMenu('/pages/favorites/index')">
              <text class="me__stat-num">{{ favDisplay }}</text>
              <text class="me__stat-label">收藏</text>
            </view>
            <view class="me__stat-gap" />
            <view class="me__stat-cell" hover-class="me__stat-cell--hover" :hover-stay-time="80" @click="goMenu('/pages/recommendation-history/index')">
              <text class="me__stat-num">{{ recDisplay }}</text>
              <text class="me__stat-label">最近推荐</text>
            </view>
            <view class="me__stat-gap" />
            <view class="me__stat-cell" hover-class="me__stat-cell--hover" :hover-stay-time="80" @click="goMenu('/pages/histories/index')">
              <text class="me__stat-num">{{ histDisplay }}</text>
              <text class="me__stat-label">我的历史</text>
            </view>
          </view>
          <view v-if="!configured" class="me__env-tip">
            <text class="me__env-tip-txt">配置小程序 BFF 地址后，可查看云端收藏与历史数量。</text>
          </view>
        </view>

        <!-- 4 玩法记录：与数据概览同款的横向四格 -->
        <view class="me__block me__block--muted">
          <view class="me__card me__card--lg me__stats-bar">
            <block v-for="(tile, idx) in recordTiles" :key="tile.id">
              <view v-if="idx > 0" class="me__stat-gap" />
              <view
                class="me__stat-cell"
                hover-class="me__stat-cell--hover"
                :hover-stay-time="80"
                @click="onRecordTileTap(tile.type)"
              >
                <MeThemedIcon :name="tile.icon" :size-rpx="44" />
                <text class="me__stat-label">{{ tile.label }}</text>
              </view>
            </block>
          </view>
        </view>

        <!-- 5 服务中心：帮助 / 协议等 + 设置 / 退出 -->
        <view class="me__block me__block--foot">
          <view class="me__card me__card--lg me__list-sheet">
            <view
              v-for="(entry, idx) in serviceEntries"
              :key="entry.id"
            >
              <view class="me__list-row" hover-class="me__list-row--hover" :hover-stay-time="80" @click="onServiceTap(entry.id)">
                <view class="me__list-ico">
                  <MeThemedIcon :name="entry.icon" :size-rpx="36" />
                </view>
                <view class="me__list-mid">
                  <text class="me__list-title">{{ entry.title }}</text>
                </view>
                <view class="me__list-chev">
                  <MeThemedIcon name="chevronRight" :size-rpx="28" />
                </view>
              </view>
              <template v-if="entry.id === 'about_us'">
                <view class="me__list-rule" />
                <view class="me__list-row" hover-class="me__list-row--hover" :hover-stay-time="80" @click="onServiceTap('help_center')">
                  <view class="me__list-ico">
                    <MeThemedIcon name="chat" :size-rpx="36" />
                  </view>
                  <view class="me__list-mid">
                    <text class="me__list-title">我的客服</text>
                  </view>
                  <view class="me__list-chev">
                    <MeThemedIcon name="chevronRight" :size-rpx="28" />
                  </view>
                </view>
              </template>
              <view v-if="idx < serviceEntries.length - 1" class="me__list-rule" />
            </view>
          </view>
          <view class="me__card me__card--lg me__list-sheet me__list-sheet--account">
            <view class="me__list-row" hover-class="me__list-row--hover" :hover-stay-time="80" @click="onSettingsTap">
              <view class="me__list-ico me__list-ico--muted">
                <MeThemedIcon name="settings" :size-rpx="36" />
              </view>
              <view class="me__list-mid">
                <text class="me__list-title">设置</text>
              </view>
              <view class="me__list-chev">
                <MeThemedIcon name="chevronRight" :size-rpx="28" />
              </view>
            </view>
            <view class="me__list-rule" />
            <button class="me__logout-btn" @click="onLogoutTap">
              <text class="me__logout-txt">{{ logoutButtonLabel }}</text>
            </button>
            <text class="me__ver-line">版本 v{{ appVersion }}</text>
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

    <!-- 帮助中心（Q&A 折叠）/ 关于我们 / 协议与政策：自底部升起约 2/3 屏 -->
    <view
      v-if="legalSheetKind"
      class="me__mask me__mask--ua"
      @touchmove.stop.prevent
      @click="closeLegalSheet"
    >
      <view class="me__ua-panel" @click.stop>
        <view class="me__ua-head">
          <text class="me__ua-title">{{ legalSheetTitle }}</text>
          <view
            class="me__ua-close"
            hover-class="me__ua-close--hover"
            :hover-stay-time="80"
            @click="closeLegalSheet"
          >
            <text class="me__ua-close-icon">×</text>
          </view>
        </view>
        <scroll-view
          v-if="legalSheetKind === 'help_center'"
          scroll-y
          class="me__ua-scroll"
          :show-scrollbar="true"
          :enable-flex="true"
        >
          <view class="me__hc-body">
            <text class="me__hc-intro">以下为常见问题，点击问题可展开或收起说明。</text>
            <view v-for="(item, i) in HELP_CENTER_QA" :key="i" class="me__hc-item">
              <view
                class="me__hc-q"
                hover-class="me__hc-q--hover"
                :hover-stay-time="80"
                @click="toggleHelpQa(i)"
              >
                <text class="me__hc-q-txt">{{ item.q }}</text>
                <text class="me__hc-q-arrow" :class="{ 'me__hc-q-arrow--open': helpQaOpen[i] }">›</text>
              </view>
              <view v-show="helpQaOpen[i]" class="me__hc-a">
                <text v-for="(line, j) in item.a" :key="j" class="me__hc-a-line">{{ line }}</text>
              </view>
            </view>
          </view>
        </scroll-view>
        <scroll-view v-else scroll-y class="me__ua-scroll" :show-scrollbar="true" :enable-flex="true">
          <view class="me__ua-body">
            <view v-for="(sec, i) in legalSheetSections" :key="i" class="me__ua-sec">
              <text v-if="sec.h" class="me__ua-sec-h">{{ sec.h }}</text>
              <text v-for="(para, j) in sec.p" :key="j" class="me__ua-sec-p">{{ para }}</text>
            </view>
          </view>
        </scroll-view>
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
import { USER_AGREEMENT_SECTIONS } from '@/config/userAgreement'
import { PRIVACY_POLICY_SECTIONS } from '@/config/privacyPolicy'
import { ABOUT_US_SECTIONS } from '@/config/aboutUs'
import { HELP_CENTER_QA } from '@/config/helpCenter'
import type { HistoryRow } from '@/types/dto'
import { openResultDetail, toDetailPayloadFromHistory, normalizeSourceType, type ResultSourceType } from '@/lib/resultDetail'
import type { MeThemedIconName } from '@/lib/meThemedIcons'
import MeThemedIcon from '@/components/MeThemedIcon.vue'

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

type LegalSheetKind = 'help_center' | 'about_us' | 'user_agreement' | 'privacy_policy'

const legalSheetKind = ref<LegalSheetKind | null>(null)

/** 帮助中心 Q&A 展开状态（可多题同时展开） */
const helpQaOpen = ref<Record<number, boolean>>({})

function toggleHelpQa(index: number) {
  const cur = helpQaOpen.value[index] === true
  helpQaOpen.value = { ...helpQaOpen.value, [index]: !cur }
}

const legalSheetTitle = computed(() => {
  const k = legalSheetKind.value
  if (k === 'help_center') return '帮助中心'
  if (k === 'privacy_policy') return '隐私政策'
  if (k === 'about_us') return '关于我们'
  return '用户协议'
})

const legalSheetSections = computed(() => {
  const k = legalSheetKind.value
  if (k === 'privacy_policy') return PRIVACY_POLICY_SECTIONS
  if (k === 'about_us') return ABOUT_US_SECTIONS
  return USER_AGREEMENT_SECTIONS
})

function closeLegalSheet() {
  legalSheetKind.value = null
  helpQaOpen.value = {}
}

const recordTiles: readonly {
  id: string
  type: ResultSourceType
  label: string
  icon: MeThemedIconName
}[] = [
  { id: 'today_eat_record', type: 'today_eat', label: '吃什么', icon: 'eat' },
  { id: 'table_design_record', type: 'table_design', label: '一桌好菜', icon: 'table' },
  { id: 'fortune_cooking_record', type: 'fortune_cooking', label: '玄学厨房', icon: 'fortune' },
  { id: 'sauce_design_record', type: 'sauce_design', label: '酱料', icon: 'sauce' },
]

const serviceEntries: readonly {
  id: 'help_center' | 'about_us' | 'user_agreement' | 'privacy_policy'
  title: string
  subtitle: string
  icon: MeThemedIconName
}[] = [
  { id: 'help_center', title: '帮助中心', subtitle: '使用指引与常见问题', icon: 'help' },
  { id: 'about_us', title: '关于我们', subtitle: '饭否小程序', icon: 'about' },
  { id: 'user_agreement', title: '用户协议', subtitle: '服务条款说明', icon: 'agreement' },
  { id: 'privacy_policy', title: '隐私政策', subtitle: '个人信息保护说明', icon: 'privacy' },
]

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

/**
 * 顶栏占位高度（状态栏 + 导航条）：用于把用户区内容上推，渐变背景铺到物理顶部。
 * scroll-view 自身从 top:0 全高，顶栏透明叠在上面。
 */
const navBleedBottomPx = ref(88)
const meScrollHeightPx = ref(520)

/** 已登录氛围区顶内边距：顶栏高度 + 原 16rpx */
const atmosPadTopPx = computed(() => navBleedBottomPx.value + rpxToPx(16, windowWidthPx.value))

/** 未登录 hero 顶内边距：顶栏高度 + 原 8rpx */
const guestHeroPadTopPx = computed(() => navBleedBottomPx.value + rpxToPx(8, windowWidthPx.value))

function measureMeScroll() {
  const sys = uni.getSystemInfoSync()
  const wh = Number(sys.windowHeight) || 667
  const strip = guestVersionStripPx()
  uni.createSelectorQuery()
    .select('.me__nav')
    .boundingClientRect((rect) => {
      const inset =
        rect && typeof rect.bottom === 'number' ? rect.bottom : statusBarPx.value + navBarPx.value
      navBleedBottomPx.value = inset
      meScrollHeightPx.value = Math.max(200, wh - strip)
    })
    .exec()
}

const scrollStyle = computed(() => ({
  position: 'fixed' as const,
  left: '0',
  right: '0',
  top: '0',
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
  navBleedBottomPx.value = statusBarPx.value + navBarPx.value
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
  uni.navigateTo({ url: '/pages/me/personal-info' })
}

function onRecordTileTap(type: ResultSourceType) {
  if (type === 'today_eat') {
    goMenu('/pages/recommendation-history/index')
    return
  }
  const h = latestByType.value[type]
  if (h) {
    openResultDetail(toDetailPayloadFromHistory(h))
    return
  }
  goMenu('/pages/histories/index')
}

function onServiceTap(id: ServiceId) {
  if (id === 'help_center') {
    helpQaOpen.value = {}
    legalSheetKind.value = 'help_center'
    return
  }
  if (id === 'about_us') {
    legalSheetKind.value = 'about_us'
    return
  }
  if (id === 'user_agreement') {
    legalSheetKind.value = 'user_agreement'
    return
  }
  if (id === 'privacy_policy') {
    legalSheetKind.value = 'privacy_policy'
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

/* 本页主题（与全局 uni 并存，仅作用域内使用） */
$me-bg: #f6f7fb;
$me-card: #ffffff;
$me-purple: #8b5cf6;
$me-purple-2: #a78bfa;
$me-purple-soft: #f3eeff;
$me-text: #1f2329;
$me-text-2: #6b7280;
$me-text-3: #9ca3af;
$me-radius-lg: 28rpx;
$me-radius-sm: 24rpx;
$me-shadow: 0 4rpx 20rpx rgba(31, 35, 41, 0.03);
$me-line: rgba(139, 92, 246, 0.09);

.me {
  background: $me-bg;
  position: relative;
  isolation: isolate;
}

.me__nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 200;
  background: transparent;
  border-bottom: none;
}

.me__nav-row {
  position: relative;
  box-sizing: border-box;
}

.me__scroll {
  position: relative;
  z-index: 50;
  box-sizing: border-box;
  padding-top: 0;
  /* 避免默认白底在 TabBar 重叠区形成「挡板」 */
  background: $me-bg;
}

.me__guest-wrap,
.me__logged-wrap {
  padding: 0 24rpx 8rpx;
  box-sizing: border-box;
}

/* —— 页面通用白卡片 —— */
.me__card {
  background: $me-card;
  box-sizing: border-box;
  border: 1rpx solid $me-line;
  box-shadow: $me-shadow;
}

.me__card--lg {
  border-radius: $me-radius-lg;
}

.me__card--sm {
  border-radius: $me-radius-sm;
}

/* —— 未登录：顶部氛围 + 白卡 —— */
.me__hero {
  position: relative;
  margin: 0 -24rpx 8rpx;
  padding: 0 24rpx 36rpx;
  box-sizing: border-box;
}

.me__hero--guest {
  padding-bottom: 28rpx;
}

.me__hero-bg {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  border-radius: 0 0 40rpx 40rpx;
  background: linear-gradient(
    165deg,
    #ddd6fe 0%,
    rgba(196, 181, 253, 0.55) 45%,
    rgba(246, 247, 251, 0.35) 100%
  );
  pointer-events: none;
}

.me__hero-card {
  position: relative;
  z-index: 1;
  background: $me-card;
  border-radius: $me-radius-lg;
  border: 1rpx solid $me-line;
  box-shadow: $me-shadow;
  padding: 36rpx 28rpx 40rpx;
  box-sizing: border-box;
}

.me__hero-card--guest {
  padding: 44rpx 32rpx 40rpx;
}

.me__guest-identity {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.me__guest-avatar-ring {
  width: 144rpx;
  height: 144rpx;
  border-radius: 36rpx;
  background: linear-gradient(150deg, $me-purple-soft, #fff);
  border: 2rpx solid rgba(167, 139, 250, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 28rpx rgba(139, 92, 246, 0.1);
}

.me__guest-avatar-img {
  width: 80rpx;
  height: 80rpx;
}

/* —— 已登录：顶部完整氛围区（非白底资料卡） —— */
.me__atmos {
  position: relative;
  z-index: 0;
  margin: 0 -24rpx;
  /* 用户模块整体加高 60rpx（相对原底部留白）；顶边距由 :style 叠加导航占位 */
  padding: 0 24rpx 108rpx;
  box-sizing: border-box;
}

.me__atmos-bg {
  position: absolute;
  inset: 0;
  border-radius: 0 0 36rpx 36rpx;
  /* 到顶的「贴片」：顶部略饱和，与透明导航下的观感一致 */
  background: linear-gradient(
    168deg,
    #d4c4fc 0%,
    #ddd6fe 22%,
    rgba(167, 139, 250, 0.45) 48%,
    rgba(233, 213, 255, 0.55) 72%,
    rgba(246, 247, 251, 0.2) 100%
  );
  pointer-events: none;
}

.me__atmos-inner {
  position: relative;
  z-index: 1;
}

.me__atmos-head {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.me__atmos-user {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 20rpx;
  flex: 1;
  min-width: 0;
  margin-left: 5rpx;
  margin-top: 10rpx;
}

.me__atmos-avatar {
  flex-shrink: 0;
}

.me__avatar-ring--atmos {
  width: 120rpx;
  height: 120rpx;
  border-radius: 28rpx;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, $me-purple-soft 100%);
  border: 2rpx solid rgba(255, 255, 255, 0.85);
  box-shadow: 0 8rpx 24rpx rgba(139, 92, 246, 0.12);
}

.me__avatar-ring--atmos .me__avatar-img--round {
  border-radius: 24rpx;
}

.me__avatar-ring--atmos .me__avatar-letter {
  font-size: 52rpx;
}

.me__atmos-meta {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8rpx;
}

.me__atmos-name {
  font-size: 40rpx;
  font-weight: 800;
  color: $me-text;
  line-height: 1.2;
  letter-spacing: -0.02em;
  text-shadow: 0 1rpx 0 rgba(255, 255, 255, 0.65);
}

.me__atmos-name--tap {
  border-bottom: 2rpx dashed rgba(139, 92, 246, 0.35);
  padding-bottom: 4rpx;
  align-self: flex-start;
}

.me__atmos-actions {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: center;
  gap: 10rpx;
}

.me__atmos-act {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8rpx;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.55);
  border: 1rpx solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 2rpx 12rpx rgba(139, 92, 246, 0.06);
}

.me__atmos-act--icon-only {
  padding: 14rpx 16rpx;
  border-radius: 20rpx;
}

.me__atmos-act--icon-only .me__atmos-act-ico {
  font-size: 40rpx;
  line-height: 1;
}

.me__atmos-act--hover {
  background: rgba(255, 255, 255, 0.88);
}

/* —— 内容区块间距 —— */
.me__block {
  margin-top: 28rpx;
}

/* 推荐偏好画像：叠在氛围渐变之上，避免被下层渐变/圆角区域盖住 */
.me__block--tight {
  position: relative;
  z-index: 20;
  margin-top: -28rpx;
}

.me__block--muted {
  margin-top: 32rpx;
}

.me__block--foot {
  margin-top: 28rpx;
  margin-bottom: 40rpx;
}

/* —— 主权益卡 —— */
.me__benefit {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 20rpx;
  padding: 28rpx 26rpx;
}

.me__benefit-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.me__benefit-title {
  font-size: 32rpx;
  font-weight: 800;
  color: $me-text;
}

.me__benefit-sub {
  font-size: 24rpx;
  line-height: 1.45;
  color: $me-text-2;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.me__benefit-metrics {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  gap: 4rpx 0;
  margin-top: 4rpx;
}

.me__benefit-metric {
  font-size: 22rpx;
  color: $me-text-3;
  font-weight: 500;
}

.me__benefit-dot {
  font-size: 22rpx;
  color: $me-text-3;
  margin: 0 10rpx;
}

.me__benefit-cta-wrap {
  flex-shrink: 0;
}

.me__benefit-cta {
  display: inline-block;
  padding: 16rpx 28rpx;
  font-size: 24rpx;
  font-weight: 700;
  color: $me-purple;
  background: $me-purple-soft;
  border-radius: 999rpx;
  border: 1rpx solid rgba(167, 139, 250, 0.35);
}

/* —— 数据概览三列 —— */
.me__stats-bar {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  padding: 28rpx 12rpx 32rpx;
}

.me__stat-cell {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  min-width: 0;
}

.me__stat-cell--hover {
  opacity: 0.88;
}

.me__stat-num {
  font-size: 44rpx;
  font-weight: 800;
  color: $me-purple;
  line-height: 1;
}

.me__stat-label {
  font-size: 22rpx;
  color: $me-text-3;
  font-weight: 500;
}

.me__stat-gap {
  width: 1rpx;
  align-self: stretch;
  margin: 8rpx 0;
  background: rgba(31, 35, 41, 0.06);
}

.me__env-tip {
  margin-top: 16rpx;
  padding: 0 8rpx;
}

.me__env-tip-txt {
  font-size: 22rpx;
  line-height: 1.5;
  color: $me-text-2;
}

/* —— 底部列表（生成记录 / 服务 + 账号） —— */
.me__list-sheet {
  padding: 8rpx 0 12rpx;
  overflow: hidden;
}

.me__list-sheet--account {
  margin-top: 20rpx;
}

.me__list-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 22rpx;
  padding: 28rpx 28rpx;
  box-sizing: border-box;
}

.me__list-row--hover {
  background: rgba($me-purple-soft, 0.35);
}

.me__list-ico {
  width: 64rpx;
  height: 64rpx;
  border-radius: 18rpx;
  background: rgba(139, 92, 246, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.me__list-ico--muted {
  background: rgba(139, 92, 246, 0.08);
}

.me__list-mid {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6rpx;
}

.me__list-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $me-text;
}

.me__list-chev {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  opacity: 0.92;
}

.me__list-rule {
  height: 1rpx;
  margin: 0 28rpx;
  background: rgba(31, 35, 41, 0.04);
}

.me__logout-btn {
  margin: 0;
  width: 100%;
  padding: 22rpx 24rpx 10rpx;
  font-size: 28rpx;
  font-weight: 600;
  color: rgba(220, 80, 90, 0.76) !important;
  background: transparent !important;
  border: none !important;
  line-height: 1.45;
  text-align: center;
}

.me__logout-btn::after {
  border: none !important;
}

.me__logout-txt {
  font-weight: 600;
}

.me__ver-line {
  display: block;
  padding: 4rpx 24rpx 12rpx;
  font-size: 22rpx;
  color: $me-text-3;
  text-align: center;
}

/* —— 未登录章节标题 —— */
.me__section-head--guest {
  display: flex;
  flex-direction: row;
  align-items: baseline;
  gap: 12rpx;
  margin-bottom: 14rpx;
  padding-left: 6rpx;
}

.me__section-kicker {
  font-size: 20rpx;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: $me-purple;
  text-transform: uppercase;
}

.me__section-title {
  font-size: 30rpx;
  font-weight: 800;
  color: $me-text;
}

.me__newbie-card--soft {
  background: rgba($me-purple-soft, 0.72);
  border-color: rgba(167, 139, 250, 0.22);
}

.me__guest-settings-bar {
  margin: 28rpx 0 0;
  display: flex;
  flex-direction: row;
  justify-content: center;
}

.me__guest-settings-tap {
  font-size: 26rpx;
  font-weight: 600;
  color: $me-text-2;
  padding: 12rpx 24rpx;
}

.me__guest-newbie {
  margin-top: 28rpx;
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
  color: $me-purple;
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
  color: $me-purple;
  background: #fff;
  border-radius: 999rpx;
  border: 1rpx solid rgba(167, 139, 250, 0.45);
}

.me__newbie-txt {
  flex: 1;
  font-size: 26rpx;
  line-height: 1.55;
  color: #5b4a9e;
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
  background: rgba($me-bg, 0.96);
  border-top: 1rpx solid rgba(139, 92, 246, 0.1);
  pointer-events: none;
}

.me__ver-fixed {
  font-size: 22rpx;
  color: $me-text-3;
}

.me__avatar-ring {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.me__avatar-img {
  width: 72rpx;
  height: 72rpx;
}

.me__avatar-img--round {
  width: 100%;
  height: 100%;
}

.me__avatar-letter {
  font-weight: 800;
  color: $me-purple;
}

.me__guest-lead {
  margin-top: 28rpx;
  font-size: 36rpx;
  font-weight: 800;
  color: $me-text;
  line-height: 1.3;
}

.me__guest-sub {
  margin-top: 14rpx;
  font-size: 26rpx;
  line-height: 1.55;
  color: $me-text-2;
  padding: 0 16rpx;
}

.me__wx-login {
  margin-top: 40rpx;
  width: 100%;
  max-width: 520rpx;
  padding-top: 28rpx !important;
  padding-bottom: 28rpx !important;
  font-size: 30rpx !important;
  font-weight: 800 !important;
  box-shadow: 0 12rpx 36rpx rgba(139, 92, 246, 0.22);
}

.me__wx-login-txt {
  font-weight: 800;
}

.me__pill {
  font-size: 22rpx;
  font-weight: 700;
  padding: 10rpx 20rpx;
  border-radius: 999rpx;
}

.me__pill--accent {
  color: $me-purple;
  background: $me-purple-soft;
  border: 1rpx solid rgba(167, 139, 250, 0.35);
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

/* 用户协议底栏：高度约为视口下 2/3 */
.me__mask--ua {
  z-index: 520;
}

.me__ua-panel {
  width: 100%;
  height: 66.666vh;
  max-height: 66.666vh;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding-bottom: env(safe-area-inset-bottom);
  box-shadow: 0 -8rpx 40rpx rgba(31, 35, 41, 0.12);
}

.me__ua-head {
  flex-shrink: 0;
  position: relative;
  padding: 28rpx 96rpx 22rpx 32rpx;
  border-bottom: 1rpx solid rgba(31, 35, 41, 0.06);
}

.me__ua-title {
  display: block;
  font-size: 32rpx;
  font-weight: 800;
  color: $me-text;
  text-align: center;
  line-height: 1.3;
}

.me__ua-close {
  position: absolute;
  right: 20rpx;
  top: 50%;
  transform: translateY(-50%);
  width: 56rpx;
  height: 56rpx;
  border-radius: 999rpx;
  background: rgba(139, 92, 246, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.me__ua-close--hover {
  background: rgba(139, 92, 246, 0.18);
}

.me__ua-close-icon {
  font-size: 40rpx;
  line-height: 1;
  color: $me-text-2;
  font-weight: 300;
  margin-top: -4rpx;
}

.me__ua-scroll {
  flex: 1;
  height: 0;
  min-height: 200rpx;
  box-sizing: border-box;
}

.me__ua-body {
  padding: 24rpx 32rpx 40rpx;
  box-sizing: border-box;
}

.me__ua-sec {
  margin-bottom: 28rpx;
}

.me__ua-sec:last-child {
  margin-bottom: 8rpx;
}

.me__ua-sec-h {
  display: block;
  font-size: 28rpx;
  font-weight: 800;
  color: $me-text;
  margin-bottom: 12rpx;
  line-height: 1.4;
}

.me__ua-sec-p {
  display: block;
  font-size: 26rpx;
  line-height: 1.65;
  color: $me-text-2;
  margin-bottom: 16rpx;
  text-align: justify;
}

.me__ua-sec-p:last-child {
  margin-bottom: 0;
}

/* —— 帮助中心：折叠 Q&A —— */
.me__hc-body {
  padding: 12rpx 24rpx 40rpx;
  box-sizing: border-box;
}

.me__hc-intro {
  display: block;
  font-size: 24rpx;
  line-height: 1.5;
  color: $me-text-3;
  margin-bottom: 20rpx;
  padding: 0 8rpx;
}

.me__hc-item {
  border-bottom: 1rpx solid rgba(31, 35, 41, 0.06);
}

.me__hc-item:last-child {
  border-bottom: none;
}

.me__hc-q {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 26rpx 12rpx 26rpx 8rpx;
  box-sizing: border-box;
}

.me__hc-q--hover {
  background: rgba(139, 92, 246, 0.06);
}

.me__hc-q-txt {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  font-weight: 700;
  color: $me-text;
  line-height: 1.45;
}

.me__hc-q-arrow {
  flex-shrink: 0;
  font-size: 36rpx;
  line-height: 1;
  color: $me-purple;
  opacity: 0.65;
  transform: rotate(0deg);
  transition: transform 0.2s ease;
}

.me__hc-q-arrow--open {
  transform: rotate(90deg);
}

.me__hc-a {
  padding: 0 12rpx 24rpx 8rpx;
  box-sizing: border-box;
}

.me__hc-a-line {
  display: block;
  font-size: 26rpx;
  line-height: 1.65;
  color: $me-text-2;
  margin-bottom: 14rpx;
  text-align: justify;
}

.me__hc-a-line:last-child {
  margin-bottom: 0;
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
  color: $me-text;
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
  color: $me-text-2;
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
  border-radius: 16rpx;
  border: 1rpx solid rgba(139, 92, 246, 0.2);
  background: #fafbfc;
  font-size: 28rpx;
  color: $me-text;
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
  background: $me-purple;
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
