<template>
  <view class="mp-page mol">
    <view v-if="orders.length === 0" class="mp-card"><text class="mol__muted">暂无订单</text></view>
    <view v-for="order in orders" :key="order.id" class="mp-card mol__item" @click="openDetail(order.id)">
      <view class="mol__top">
        <text class="mol__no">{{ order.orderNo }}</text>
        <text class="mol__status">{{ orderStatusText(order.orderStatus) }}</text>
      </view>
      <view class="mol__body">
        <image class="mol__img" :src="order.productImage" mode="aspectFill" />
        <view class="mol__info">
          <text class="mol__name">{{ order.productName }}</text>
          <text class="mol__meta">x{{ order.quantity }} · {{ formatPrice(order.totalAmount) }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyMallOrders } from '@/api/mall'
import type { MallOrder } from '@/types/mall'

const orders = ref<MallOrder[]>([])

onShow(async () => {
  orders.value = await getMyMallOrders()
})

function formatPrice(fen: number): string {
  return `¥${(fen / 100).toFixed(2)}`
}

function openDetail(id: string) {
  uni.navigateTo({ url: `/pages/mall/order-detail?id=${encodeURIComponent(id)}` })
}

function orderStatusText(status: string): string {
  if (status === 'pending') return '待处理'
  if (status === 'paid') return '已支付'
  if (status === 'shipping') return '配送中'
  if (status === 'completed') return '已完成'
  if (status === 'cancelled') return '已取消'
  return status
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.mol__item{padding:20rpx}
.mol__top{display:flex;justify-content:space-between;align-items:center;gap:12rpx}
.mol__no{font-size:22rpx;color:$mp-text-muted}
.mol__status{font-size:24rpx;color:$mp-accent;font-weight:700}
.mol__body{margin-top:10rpx;display:flex;gap:12rpx}
.mol__img{width:120rpx;height:120rpx;border-radius:10rpx;background:#f3f4f6;flex-shrink:0}
.mol__name{display:block;font-size:26rpx;font-weight:700;color:$mp-text-primary}
.mol__meta{display:block;margin-top:8rpx;font-size:23rpx;color:$mp-text-secondary}
.mol__muted{display:block;padding:16rpx 0;font-size:24rpx;color:$mp-text-muted}
</style>
