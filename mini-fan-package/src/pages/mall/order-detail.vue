<template>
  <view class="mp-page mod">
    <view v-if="!order" class="mp-card"><text class="mod__muted">订单不存在</text></view>
    <template v-else>
      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">订单信息</text>
        <text class="mod__line">订单号：{{ order.orderNo }}</text>
        <text class="mod__line">状态：{{ orderStatusText(order.orderStatus) }}</text>
        <text class="mod__line">支付：{{ payStatusText(order.payStatus) }}</text>
        <text class="mod__line">下单时间：{{ order.createdAt }}</text>
      </view>
      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">商品快照</text>
        <view class="mod__product">
          <image class="mod__img" :src="order.productImage" mode="aspectFill" />
          <view class="mod__body">
            <text class="mod__name">{{ order.productName }}</text>
            <text class="mod__line">单价：{{ formatPrice(order.productPrice) }}</text>
            <text class="mod__line">数量：{{ order.quantity }}</text>
            <text class="mod__total">合计：{{ formatPrice(order.totalAmount) }}</text>
          </view>
        </view>
      </view>
      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">收货信息</text>
        <text class="mod__line">收货人：{{ order.consigneeName }}</text>
        <text class="mod__line">手机：{{ order.consigneePhone }}</text>
        <text class="mod__line">地址：{{ order.consigneeAddress }}</text>
        <text class="mod__line">物流公司：{{ order.logisticsCompany || '待填写' }}</text>
        <text class="mod__line">物流单号：{{ order.logisticsNo || '待填写' }}</text>
      </view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getMallOrderDetail } from '@/api/mall'
import type { MallOrder } from '@/types/mall'

const id = ref('')
const order = ref<MallOrder | null>(null)

async function load() {
  if (!id.value) return
  order.value = await getMallOrderDetail(id.value)
}

onLoad((q) => { id.value = typeof q?.id === 'string' ? decodeURIComponent(q.id) : '' })
onShow(() => void load())

function formatPrice(fen: number): string {
  return `¥${(fen / 100).toFixed(2)}`
}

function orderStatusText(status: string): string {
  if (status === 'pending') return '待处理'
  if (status === 'paid') return '已支付'
  if (status === 'shipping') return '配送中'
  if (status === 'completed') return '已完成'
  if (status === 'cancelled') return '已取消'
  return status
}

function payStatusText(status: string): string {
  if (status === 'unpaid') return '未支付'
  if (status === 'paid') return '已支付'
  if (status === 'refunded') return '已退款'
  return status
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.mod__line{display:block;margin-top:8rpx;font-size:24rpx;color:$mp-text-secondary;line-height:1.5}
.mod__product{display:flex;gap:12rpx;margin-top:8rpx}
.mod__img{width:136rpx;height:136rpx;border-radius:12rpx;background:#f3f4f6;flex-shrink:0}
.mod__name{display:block;font-size:27rpx;font-weight:700;color:$mp-text-primary}
.mod__total{display:block;margin-top:10rpx;font-size:28rpx;font-weight:700;color:$mp-accent}
.mod__muted{display:block;padding:16rpx 0;font-size:24rpx;color:$mp-text-muted}
</style>
