<template>
  <view class="ord">
    <view class="ord__tabs-wrap">
      <view class="ord__search-wrap">
        <view class="ord__search">
          <text class="ord__search-ico">🔎</text>
          <input
            v-model.trim="searchKeyword"
            class="ord__search-input"
            type="text"
            confirm-type="search"
            placeholder="搜索订单号 / 商品名称"
            placeholder-class="ord__search-placeholder"
          />
          <view v-if="searchKeyword" class="ord__search-clear" @click="searchKeyword = ''">清空</view>
        </view>
      </view>
      <!-- 状态 Tab -->
      <scroll-view scroll-x class="ord__tabs-scroll" :show-scrollbar="false" :enable-flex="true">
        <view class="ord__tabs">
          <view
            v-for="t in tabs"
            :key="t.key"
            class="ord__tab"
            :class="{ 'ord__tab--active': activeTab === t.key }"
            @click="activeTab = t.key"
          >
            <text class="ord__tab-txt">{{ t.label }}</text>
            <text
              v-if="t.key !== 'all' && tabCounts[t.key] > 0"
              class="ord__tab-badge"
            >{{ tabCounts[t.key] > 99 ? '99+' : tabCounts[t.key] }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <view class="ord__main">
      <view v-if="loading" class="ord__state">
        <text class="ord__state-txt">加载中…</text>
      </view>
      <view v-else-if="loadError" class="ord__state">
        <text class="ord__state-txt">{{ loadError }}</text>
        <view class="ord__retry" @click="reload">重新加载</view>
      </view>
      <template v-else>
        <view v-if="filteredOrders.length === 0" class="ord__empty">
          <text class="ord__empty-ico">📦</text>
          <text class="ord__empty-title">暂无相关订单</text>
          <text class="ord__empty-sub">去商城逛逛，发现心仪好物</text>
          <view class="ord__empty-btn" @click="goMall">去逛逛</view>
        </view>
        <view
          v-for="order in filteredOrders"
          :key="order.id"
          class="ord__card"
          @click="openDetail(order.id)"
        >
          <view class="ord__card-head">
            <view class="ord__shop">
              <text class="ord__shop-ico">🛒</text>
              <text class="ord__shop-name">饭否商城</text>
            </view>
            <text class="ord__status" :class="statusClass(order)">{{ displayStatus(order) }}</text>
          </view>
          <view class="ord__card-meta">
            <text class="ord__meta-no">订单号 {{ order.orderNo }}</text>
            <text class="ord__meta-time">{{ formatTime(order.createdAt) }}</text>
          </view>
          <view class="ord__goods">
            <image class="ord__goods-img" :src="order.productImage" mode="aspectFill" />
            <view class="ord__goods-mid">
              <text class="ord__goods-name">{{ order.productName }}</text>
              <text class="ord__goods-spec">默认规格</text>
            </view>
            <view class="ord__goods-end">
              <text class="ord__goods-price">{{ formatPrice(order.productPrice) }}</text>
              <text class="ord__goods-qty">x{{ order.quantity }}</text>
            </view>
          </view>
          <view class="ord__sum">
            <text class="ord__sum-txt">共{{ order.quantity }}件</text>
            <text class="ord__sum-txt ord__sum-strong">实付 </text>
            <text class="ord__sum-money">{{ formatPrice(order.totalAmount) }}</text>
          </view>
          <view class="ord__actions" @click.stop>
            <template v-if="isUnpaid(order)">
              <view class="ord__btn ord__btn--ghost" @click.stop="onCancelOrder(order)">取消订单</view>
              <view class="ord__btn ord__btn--primary" @click.stop="openDetail(order.id)">去付款</view>
            </template>
            <template v-else-if="order.orderStatus === 'paid'">
              <view class="ord__btn ord__btn--ghost" @click.stop="toastSoon('已提醒商家尽快发货')">提醒发货</view>
              <view class="ord__btn ord__btn--primary" @click.stop="openDetail(order.id)">查看详情</view>
            </template>
            <template v-else-if="order.orderStatus === 'shipping'">
              <view class="ord__btn ord__btn--ghost" @click.stop="onViewLogistics(order)">查看物流</view>
              <view class="ord__btn ord__btn--primary" @click.stop="toastSoon('确认收货功能即将上线')">确认收货</view>
            </template>
            <template v-else-if="order.orderStatus === 'completed'">
              <view class="ord__btn ord__btn--ghost" @click.stop="toastSoon('售后/退款请联系客服')">申请售后</view>
              <view class="ord__btn ord__btn--primary" @click.stop="toastSoon('再次购买即将上线')">再次购买</view>
            </template>
            <template v-else-if="order.orderStatus === 'cancelled'">
              <view class="ord__btn ord__btn--ghost" @click.stop="openDetail(order.id)">查看详情</view>
            </template>
            <template v-else>
              <view class="ord__btn ord__btn--primary" @click.stop="openDetail(order.id)">查看详情</view>
            </template>
          </view>
        </view>
      </template>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import { getMyMallOrders } from '@/api/mall'
import type { MallOrder } from '@/types/mall'

type OrderTabKey = 'all' | 'unpaid' | 'to_ship' | 'to_receive' | 'done'

const tabs: { key: OrderTabKey; label: string }[] = [
  { key: 'all', label: '全部' },
  { key: 'unpaid', label: '待付款' },
  { key: 'to_ship', label: '待发货' },
  { key: 'to_receive', label: '待收货' },
  { key: 'done', label: '已完成' },
]

const activeTab = ref<OrderTabKey>('all')
const searchKeyword = ref('')
const orders = ref<MallOrder[]>([])
const loading = ref(true)
const loadError = ref('')

function isUnpaid(o: MallOrder): boolean {
  if (o.orderStatus === 'cancelled') return false
  if (o.payStatus === 'unpaid') return true
  return o.orderStatus === 'pending' && o.payStatus !== 'paid'
}

function matchesTab(o: MallOrder, tab: OrderTabKey): boolean {
  if (tab === 'all') return true
  if (o.orderStatus === 'cancelled') return false
  if (tab === 'unpaid') return isUnpaid(o)
  if (tab === 'to_ship') return o.orderStatus === 'paid'
  if (tab === 'to_receive') return o.orderStatus === 'shipping'
  if (tab === 'done') return o.orderStatus === 'completed'
  return false
}

const normalizedKeyword = computed(() => searchKeyword.value.trim().toLowerCase())

const filteredOrders = computed(() => {
  return orders.value.filter((o) => {
    if (!matchesTab(o, activeTab.value)) return false
    const kw = normalizedKeyword.value
    if (!kw) return true
    const orderNo = (o.orderNo || '').toLowerCase()
    const productName = (o.productName || '').toLowerCase()
    return orderNo.includes(kw) || productName.includes(kw)
  })
})

const tabCounts = computed(() => {
  const c: Record<OrderTabKey, number> = {
    all: orders.value.length,
    unpaid: 0,
    to_ship: 0,
    to_receive: 0,
    done: 0,
  }
  for (const o of orders.value) {
    if (matchesTab(o, 'unpaid')) c.unpaid += 1
    if (matchesTab(o, 'to_ship')) c.to_ship += 1
    if (matchesTab(o, 'to_receive')) c.to_receive += 1
    if (matchesTab(o, 'done')) c.done += 1
  }
  return c
})

async function reload() {
  loading.value = true
  loadError.value = ''
  try {
    orders.value = await getMyMallOrders()
  } catch {
    loadError.value = '加载失败，请检查网络或稍后重试'
    orders.value = []
  } finally {
    loading.value = false
  }
}

onShow(() => void reload())

onPullDownRefresh(async () => {
  await reload()
  uni.stopPullDownRefresh()
})

function formatPrice(fen: number): string {
  const n = Number(fen) || 0
  return `¥${(n / 100).toFixed(2)}`
}

function formatTime(iso: string): string {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso.slice(0, 16)
  const y = d.getFullYear()
  const m = `${d.getMonth() + 1}`.padStart(2, '0')
  const day = `${d.getDate()}`.padStart(2, '0')
  const h = `${d.getHours()}`.padStart(2, '0')
  const min = `${d.getMinutes()}`.padStart(2, '0')
  return `${y}-${m}-${day} ${h}:${min}`
}

function displayStatus(o: MallOrder): string {
  if (o.orderStatus === 'cancelled') return '已取消'
  if (isUnpaid(o)) return '待付款'
  if (o.orderStatus === 'paid') return '待发货'
  if (o.orderStatus === 'shipping') return '待收货'
  if (o.orderStatus === 'completed') return '已完成'
  if (o.orderStatus === 'pending') return '待处理'
  return o.orderStatus
}

function statusClass(o: MallOrder): string {
  if (o.orderStatus === 'cancelled') return 'ord__status--muted'
  if (isUnpaid(o)) return 'ord__status--warn'
  if (o.orderStatus === 'shipping') return 'ord__status--accent'
  if (o.orderStatus === 'completed') return 'ord__status--muted'
  return 'ord__status--accent'
}

function openDetail(id: string) {
  uni.navigateTo({ url: `/pages/mall/order-detail?id=${encodeURIComponent(id)}` })
}

function goMall() {
  uni.navigateBack({
    fail: () => {
      uni.switchTab({ url: '/pages/index/index' })
    },
  })
}

function toastSoon(title: string) {
  uni.showToast({ title, icon: 'none', duration: 2000 })
}

function onCancelOrder(order: MallOrder) {
  uni.showModal({
    title: '取消订单',
    content: '确定要取消该订单吗？',
    success: (res) => {
      if (res.confirm) {
        toastSoon('已提交取消申请（示例）')
      }
    },
  })
}

function onViewLogistics(order: MallOrder) {
  const no = order.logisticsNo?.trim()
  const com = order.logisticsCompany?.trim()
  if (no) {
    uni.setClipboardData({
      data: `${com ? `${com} ` : ''}${no}`,
      success: () => uni.showToast({ title: '物流信息已复制', icon: 'none' }),
    })
    return
  }
  toastSoon('暂无物流单号，请稍后在详情中查看')
}
</script>

<style lang="scss" scoped>
$ord-bg: #f5f6f8;
$ord-card: #ffffff;
$ord-text: #1f2329;
$ord-sub: #6b7280;
$ord-muted: #9ca3af;
$ord-line: rgba(31, 35, 41, 0.06);
$ord-purple: #8b5cf6;
$ord-warn: #ea580c;
$ord-red: #dc2626;

.ord {
  min-height: 100vh;
  background: $ord-bg;
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
  box-sizing: border-box;
}

.ord__tabs-wrap {
  position: sticky;
  top: 0;
  z-index: 20;
  background: $ord-card;
  border-bottom: 1rpx solid $ord-line;
}

.ord__search-wrap {
  padding: 16rpx 24rpx 8rpx;
}

.ord__search {
  height: 72rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  background: linear-gradient(180deg, rgba(139, 92, 246, 0.08) 0%, rgba(139, 92, 246, 0.04) 100%);
  border: 1rpx solid rgba(139, 92, 246, 0.18);
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
  box-sizing: border-box;
}

.ord__search-ico {
  font-size: 24rpx;
  line-height: 1;
  opacity: 0.85;
}

.ord__search-input {
  flex: 1;
  min-width: 0;
  font-size: 26rpx;
  color: $ord-text;
}

.ord__search-placeholder {
  color: $ord-muted;
}

.ord__search-clear {
  flex-shrink: 0;
  padding: 6rpx 16rpx;
  font-size: 22rpx;
  color: $ord-purple;
  background: rgba(139, 92, 246, 0.12);
  border-radius: 999rpx;
}

.ord__tabs-scroll {
  width: 100%;
  white-space: nowrap;
}

.ord__tabs {
  display: flex;
  flex-direction: row;
  padding: 0 8rpx;
  box-sizing: border-box;
}

.ord__tab {
  position: relative;
  flex-shrink: 0;
  padding: 24rpx 20rpx 20rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 6rpx;
}

.ord__tab-txt {
  font-size: 28rpx;
  color: $ord-sub;
  font-weight: 500;
}

.ord__tab--active .ord__tab-txt {
  color: $ord-text;
  font-weight: 800;
}

.ord__tab--active::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: 8rpx;
  transform: translateX(-50%);
  width: 48rpx;
  height: 6rpx;
  border-radius: 999rpx;
  background: $ord-purple;
}

.ord__tab-badge {
  min-width: 32rpx;
  height: 32rpx;
  padding: 0 8rpx;
  border-radius: 999rpx;
  background: rgba(220, 38, 38, 0.12);
  color: $ord-red;
  font-size: 20rpx;
  font-weight: 700;
  line-height: 32rpx;
  text-align: center;
}

.ord__main {
  padding: 20rpx 24rpx 0;
  box-sizing: border-box;
}

.ord__state {
  padding: 120rpx 32rpx;
  text-align: center;
}

.ord__state-txt {
  font-size: 28rpx;
  color: $ord-sub;
}

.ord__retry {
  margin: 28rpx auto 0;
  display: inline-block;
  padding: 16rpx 40rpx;
  font-size: 26rpx;
  font-weight: 700;
  color: $ord-purple;
  border: 1rpx solid rgba(139, 92, 246, 0.35);
  border-radius: 999rpx;
}

.ord__empty {
  padding: 100rpx 40rpx 60rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.ord__empty-ico {
  font-size: 88rpx;
  line-height: 1;
  opacity: 0.35;
}

.ord__empty-title {
  margin-top: 24rpx;
  font-size: 30rpx;
  font-weight: 800;
  color: $ord-text;
}

.ord__empty-sub {
  margin-top: 12rpx;
  font-size: 26rpx;
  color: $ord-muted;
}

.ord__empty-btn {
  margin-top: 36rpx;
  padding: 18rpx 48rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #a78bfa 0%, $ord-purple 100%);
  border-radius: 999rpx;
}

.ord__card {
  background: $ord-card;
  border-radius: 20rpx;
  padding: 24rpx 24rpx 20rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 24rpx rgba(31, 35, 41, 0.04);
  border: 1rpx solid $ord-line;
  box-sizing: border-box;
}

.ord__card-head {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.ord__shop {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10rpx;
  min-width: 0;
}

.ord__shop-ico {
  font-size: 28rpx;
}

.ord__shop-name {
  font-size: 28rpx;
  font-weight: 800;
  color: $ord-text;
}

.ord__status {
  flex-shrink: 0;
  font-size: 26rpx;
  font-weight: 700;
}

.ord__status--accent {
  color: $ord-purple;
}

.ord__status--warn {
  color: $ord-warn;
}

.ord__status--muted {
  color: $ord-muted;
}

.ord__card-meta {
  margin-top: 12rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 8rpx 20rpx;
}

.ord__meta-no,
.ord__meta-time {
  font-size: 22rpx;
  color: $ord-muted;
}

.ord__goods {
  margin-top: 20rpx;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 20rpx;
}

.ord__goods-img {
  width: 160rpx;
  height: 160rpx;
  border-radius: 12rpx;
  background: #f3f4f6;
  flex-shrink: 0;
}

.ord__goods-mid {
  flex: 1;
  min-width: 0;
}

.ord__goods-name {
  font-size: 28rpx;
  font-weight: 700;
  color: $ord-text;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.ord__goods-spec {
  display: block;
  margin-top: 10rpx;
  font-size: 24rpx;
  color: $ord-muted;
}

.ord__goods-end {
  flex-shrink: 0;
  text-align: right;
}

.ord__goods-price {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: $ord-text;
}

.ord__goods-qty {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: $ord-muted;
}

.ord__sum {
  margin-top: 20rpx;
  padding-top: 20rpx;
  border-top: 1rpx solid $ord-line;
  display: flex;
  flex-direction: row;
  align-items: baseline;
  justify-content: flex-end;
  gap: 6rpx;
}

.ord__sum-txt {
  font-size: 24rpx;
  color: $ord-sub;
}

.ord__sum-strong {
  font-weight: 600;
  color: $ord-text;
}

.ord__sum-money {
  font-size: 32rpx;
  font-weight: 800;
  color: $ord-text;
}

.ord__actions {
  margin-top: 20rpx;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 16rpx;
}

.ord__btn {
  padding: 14rpx 28rpx;
  font-size: 26rpx;
  font-weight: 600;
  border-radius: 999rpx;
  box-sizing: border-box;
}

.ord__btn--ghost {
  color: $ord-sub;
  background: #f3f4f6;
  border: 1rpx solid rgba(31, 35, 41, 0.06);
}

.ord__btn--primary {
  color: #fff;
  background: linear-gradient(135deg, #a78bfa 0%, $ord-purple 100%);
  border: none;
}
</style>
