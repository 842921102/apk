<template>
  <view class="mp-page sp">
    <scroll-view scroll-y class="sp__scroll" :show-scrollbar="false">
      <view class="sp__top">
        <image class="sp__illu" src="/static/home/sponsorship-hero.svg" mode="aspectFit" />
        <text class="sp__title">赞助饭否</text>
        <text class="sp__one">谢谢你愿意支持我们</text>
      </view>

      <view v-if="isSponsor" class="sp__status">
        <text class="sp__status-badge">当前为赞助用户</text>
        <text v-if="sponsorUntilText" class="sp__status-until">{{ sponsorUntilText }}</text>
        <view class="sp__cancel" hover-class="sp__cancel--hover" :hover-stay-time="80" @click="onCancelSponsor">
          <text class="sp__cancel-txt">取消赞助身份</text>
        </view>
        <text class="sp__cancel-hint">取消后个人中心显示「普通用户」，不涉及退款</text>
      </view>

      <view class="sp__panel">
        <view class="sp__row-head sp__row-head--love">
          <text class="sp__row-name">爱心赞助</text>
          <image class="sp__heart-ico" src="/static/home/sponsorship-heart.svg" mode="aspectFit" />
        </view>
        <text class="sp__row-tip">金额随意，多少都是心意</text>
        <text class="sp__row-tip sp__row-tip--sub">支付成功后 1 个月内展示「赞助用户」；再次赞助可顺延有效期</text>
        <view class="sp__input-wrap">
          <text class="sp__input-prefix">¥</text>
          <input
            :value="loveAmount"
            class="sp__input"
            type="text"
            placeholder="输入金额"
            placeholder-class="sp__input-ph"
            confirm-type="done"
            :disabled="payLoading"
            @input="onLoveInput"
          />
        </view>
        <button
          class="sp__btn sp__btn--primary"
          hover-class="sp__btn--hover"
          :disabled="payLoading"
          @click="onLoveTap"
        >
          <text class="sp__btn-txt">{{ payLoading ? '处理中…' : '立即赞助' }}</text>
        </button>
      </view>

      <view class="sp__history">
        <text class="sp__history-title">赞助记录</text>
        <text class="sp__history-sub">仅展示支付成功的爱心赞助</text>
        <view v-if="!isLoggedIn || !configured" class="sp__history-empty">登录后可查看赞助订单</view>
        <view v-else-if="historyLoading" class="sp__history-empty">加载中…</view>
        <view v-else-if="historyRows.length === 0" class="sp__history-empty">暂无支付成功的赞助记录</view>
        <view v-else class="sp__history-list">
          <view v-for="row in historyRows" :key="row.id" class="sp__history-row">
            <view class="sp__history-row-top">
              <text class="sp__history-type">爱心赞助</text>
              <text class="sp__history-amount">¥{{ formatYuanFromFen(row.amount_fen) }}</text>
            </view>
            <text class="sp__history-time sp__history-time--full">{{ paidAtLabel(row) }}</text>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
  createPayOrder,
  createWechatPrepay,
  getPayOrder,
  listSponsorPayOrders,
  type PayOrder,
} from '@/api/pay'
import { fetchMeProfile, postMeSponsorCancel } from '@/api/me'
import { HttpError } from '@/api/http'
import { useAuth } from '@/composables/useAuth'
import { API_BASE_URL } from '@/constants'
import { isSupabaseConfigured } from '@/lib/supabase'
import { requestWechatPayment, waitForPayOrderPaid, yuanStringToFen } from '@/lib/wechatPay'

const { isLoggedIn, syncAuthFromSupabase } = useAuth()

const configured = computed(() => isSupabaseConfigured() || Boolean(API_BASE_URL.trim()))

const MAX_LOVE_FEN = 300000

const loveAmount = ref('')
const isSponsor = ref(false)
const sponsorUntilText = ref('')
const payLoading = ref(false)
const historyRows = ref<PayOrder[]>([])
const historyLoading = ref(false)

onShow(() => {
  void loadSponsorStatus()
  void loadHistory()
})

async function loadSponsorStatus() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value) {
    isSponsor.value = false
    sponsorUntilText.value = ''
    return
  }
  try {
    const me = await fetchMeProfile()
    isSponsor.value = me.is_sponsor === true
    sponsorUntilText.value = formatSponsorUntilLabel(me.sponsor_until)
  } catch {
    isSponsor.value = false
    sponsorUntilText.value = ''
  }
}

function formatSponsorUntilLabel(iso: string | null | undefined): string {
  if (!iso || typeof iso !== 'string') return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = d.getMonth() + 1
  const day = d.getDate()
  return `有效期至 ${y}年${m}月${day}日`
}

async function loadHistory() {
  await syncAuthFromSupabase()
  if (!isLoggedIn.value || !configured.value) {
    historyRows.value = []
    return
  }
  historyLoading.value = true
  try {
    historyRows.value = await listSponsorPayOrders()
  } catch {
    historyRows.value = []
  } finally {
    historyLoading.value = false
  }
}

function formatYuanFromFen(fen: number): string {
  const n = Number(fen)
  if (!Number.isFinite(n)) return '0.00'
  return (n / 100).toFixed(2)
}

function paidAtLabel(row: PayOrder): string {
  if (row.paid_at) {
    return `支付时间 ${formatShortTime(row.paid_at)}`
  }
  return ''
}

function formatShortTime(iso: string): string {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso.slice(0, 16)
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const h = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  return `${y}-${m}-${day} ${h}:${min}`
}

function onCancelSponsor() {
  uni.showModal({
    title: '取消赞助身份',
    content: '取消后个人中心将显示「普通用户」。已支付的赞助款项不会因此退款。',
    confirmText: '确认取消',
    cancelText: '算了',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await postMeSponsorCancel()
        isSponsor.value = false
        sponsorUntilText.value = ''
        uni.showToast({ title: '已取消', icon: 'success' })
      } catch (e) {
        const msg = e instanceof HttpError ? e.message.slice(0, 80) : '操作失败'
        uni.showToast({ title: msg, icon: 'none' })
      }
    },
  })
}

function onLoveInput(e: { detail?: { value?: string } }) {
  let v = String(e.detail?.value ?? '')
  v = v.replace(/[^\d.]/g, '')
  const dot = v.indexOf('.')
  if (dot !== -1) {
    v = v.slice(0, dot + 1) + v.slice(dot + 1).replace(/\./g, '')
    const [a, b = ''] = v.split('.')
    v = a + '.' + b.slice(0, 2)
  }
  if (v !== '' && v !== '.') {
    const fen = yuanStringToFen(v)
    if (fen > MAX_LOVE_FEN) {
      loveAmount.value = '3000'
      return
    }
  }
  loveAmount.value = v
}

function payErrorMessage(e: unknown): string {
  const maybe = e as { errMsg?: string }
  if (maybe?.errMsg?.includes('cancel')) return '已取消支付'
  if (e instanceof HttpError) return e.message.slice(0, 72)
  if (e instanceof Error) return e.message.slice(0, 72)
  return '支付失败，请稍后重试'
}

async function runLoveSponsorPay(amountFen: number) {
  if (payLoading.value) return
  await syncAuthFromSupabase()
  if (!isLoggedIn.value) {
    uni.showToast({ title: '请先登录', icon: 'none' })
    return
  }
  if (!configured.value) {
    uni.showToast({ title: '未配置服务端地址', icon: 'none' })
    return
  }

  payLoading.value = true
  try {
    const order = await createPayOrder({
      business_type: 'sponsor_love',
      title: '饭否 · 爱心赞助',
      amount_fen: amountFen,
      description: '随心赞助',
    })
    const payParams = await createWechatPrepay(order.id)
    await requestWechatPayment(payParams)

    uni.showLoading({ title: '确认支付结果…', mask: true })
    let outcome: 'paid' | 'failed' | 'timeout'
    try {
      outcome = await waitForPayOrderPaid(order.id)
    } finally {
      uni.hideLoading()
    }

    if (outcome === 'paid') {
      uni.showToast({ title: '支付成功，感谢支持', icon: 'success' })
      await loadSponsorStatus()
      await loadHistory()
      return
    }
    if (outcome === 'failed') {
      uni.showToast({ title: '订单未成功', icon: 'none' })
      return
    }
    const late = await getPayOrder(order.id)
    if (late.status === 'paid') {
      uni.showToast({ title: '支付成功，感谢支持', icon: 'success' })
    } else {
      uni.showToast({
        title: '若已扣款，请稍后在「我的」下拉刷新',
        icon: 'none',
        duration: 3200,
      })
    }
    await loadSponsorStatus()
    await loadHistory()
  } catch (e: unknown) {
    uni.hideLoading()
    uni.showToast({ title: payErrorMessage(e), icon: 'none', duration: 2600 })
  } finally {
    payLoading.value = false
  }
}

async function onLoveTap() {
  const fen = yuanStringToFen(loveAmount.value)
  if (fen < 1) {
    uni.showToast({ title: '金额至少 0.01 元', icon: 'none' })
    return
  }
  if (fen > MAX_LOVE_FEN) {
    uni.showToast({ title: '单笔金额不能超过 3000 元', icon: 'none' })
    return
  }
  await runLoveSponsorPay(fen)
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';

$sp-purple: #8b5cf6;
$sp-text: #1f2329;
$sp-text-2: #6b7280;
$sp-text-3: #9ca3af;

.sp {
  min-height: 100vh;
  background: #f6f7fb;
  box-sizing: border-box;
}

.sp__scroll {
  height: 100vh;
  box-sizing: border-box;
  padding: 20rpx 24rpx calc(28rpx + env(safe-area-inset-bottom));
}

.sp__top {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16rpx 0 36rpx;
}

.sp__illu {
  width: 280rpx;
  height: 168rpx;
  margin-bottom: 8rpx;
}

.sp__title {
  font-size: 40rpx;
  font-weight: 800;
  color: $sp-text;
  letter-spacing: -0.02em;
}

.sp__one {
  margin-top: 12rpx;
  font-size: 26rpx;
  color: $sp-text-2;
}

.sp__status {
  width: 100%;
  margin-bottom: 24rpx;
  padding: 24rpx 22rpx 28rpx;
  border-radius: 24rpx;
  background: linear-gradient(145deg, #fffbeb 0%, #ffffff 55%);
  border: 1rpx solid rgba(251, 191, 36, 0.35);
  box-sizing: border-box;
}

.sp__status-badge {
  display: block;
  font-size: 26rpx;
  font-weight: 800;
  color: #b45309;
}

.sp__status-until {
  display: block;
  margin-top: 10rpx;
  font-size: 24rpx;
  color: #92400e;
  line-height: 1.45;
}

.sp__cancel {
  margin-top: 20rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 80rpx;
  border-radius: 40rpx;
  border: 2rpx solid rgba(220, 38, 38, 0.45);
  background: rgba(255, 255, 255, 0.9);
  box-sizing: border-box;
}

.sp__cancel--hover {
  opacity: 0.88;
}

.sp__cancel-txt {
  font-size: 28rpx;
  font-weight: 700;
  color: #dc2626;
}

.sp__cancel-hint {
  display: block;
  margin-top: 16rpx;
  font-size: 22rpx;
  line-height: 1.5;
  color: $sp-text-3;
}

.sp__panel {
  background: #fff;
  border-radius: 28rpx;
  padding: 28rpx 26rpx 32rpx;
  margin-bottom: 24rpx;
  border: 1rpx solid rgba(139, 92, 246, 0.1);
  box-shadow: 0 4rpx 20rpx rgba(31, 35, 41, 0.04);
}

.sp__row-head {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.sp__row-head--love {
  justify-content: flex-start;
  gap: 12rpx;
}

.sp__row-name {
  font-size: 32rpx;
  font-weight: 800;
  color: $sp-text;
}

.sp__heart-ico {
  width: 44rpx;
  height: 44rpx;
}

.sp__row-tip {
  display: block;
  margin-top: 10rpx;
  font-size: 24rpx;
  color: $sp-text-3;
  line-height: 1.4;
}

.sp__row-tip--sub {
  margin-top: 12rpx;
  font-size: 22rpx;
  line-height: 1.45;
}

.sp__input-wrap {
  margin-top: 20rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  min-height: 80rpx;
  padding: 12rpx 22rpx;
  border-radius: 20rpx;
  background: #f3f4f6;
  border: 1rpx solid rgba(139, 92, 246, 0.12);
  box-sizing: border-box;
}

.sp__input-prefix {
  font-size: 28rpx;
  font-weight: 700;
  color: $sp-text;
  margin-right: 6rpx;
  line-height: 1.2;
}

.sp__input {
  flex: 1;
  min-width: 0;
  min-height: 56rpx;
  font-size: 28rpx;
  font-weight: 600;
  color: $sp-text;
  line-height: 1.35;
}

.sp__input-ph {
  color: #b8bec9;
  font-weight: 500;
  font-size: 26rpx;
}

.sp__btn {
  margin-top: 24rpx;
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  border-radius: 44rpx;
  border: none;
  padding: 0;
}

.sp__btn::after {
  border: none;
}

.sp__btn--primary {
  background: linear-gradient(90deg, #a78bfa 0%, #7c3aed 100%);
}

.sp__btn-txt {
  font-size: 30rpx;
  font-weight: 800;
  color: #fff;
}

.sp__btn--hover {
  opacity: 0.9;
}

.sp__btn[disabled] {
  opacity: 0.55;
}

.sp__history {
  margin-top: 8rpx;
  padding: 28rpx 26rpx 32rpx;
  background: #fff;
  border-radius: 28rpx;
  border: 1rpx solid rgba(139, 92, 246, 0.1);
  box-shadow: 0 4rpx 20rpx rgba(31, 35, 41, 0.04);
  box-sizing: border-box;
}

.sp__history-title {
  display: block;
  font-size: 30rpx;
  font-weight: 800;
  color: $sp-text;
  margin-bottom: 8rpx;
}

.sp__history-sub {
  display: block;
  font-size: 22rpx;
  color: $sp-text-3;
  line-height: 1.45;
  margin-bottom: 16rpx;
}

.sp__history-empty {
  font-size: 24rpx;
  color: $sp-text-3;
  line-height: 1.5;
  padding: 12rpx 0 4rpx;
}

.sp__history-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.sp__history-row {
  padding: 22rpx 0;
  border-top: 1rpx solid rgba(31, 35, 41, 0.06);
}

.sp__history-row:first-of-type {
  border-top: none;
  padding-top: 0;
}

.sp__history-row-top {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.sp__history-type {
  font-size: 28rpx;
  font-weight: 700;
  color: $sp-text;
}

.sp__history-amount {
  font-size: 30rpx;
  font-weight: 800;
  color: $sp-purple;
}

.sp__history-time {
  font-size: 22rpx;
  color: $sp-text-3;
}

.sp__history-time--full {
  display: block;
  margin-top: 10rpx;
}
</style>
