<template>
  <view class="mp-page moc">
    <view v-if="!product" class="mp-card"><text class="moc__muted">商品信息加载失败</text></view>
    <template v-else>
      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">商品信息</text>
        <view class="moc__product">
          <image class="moc__img" :src="product.coverImage || product.images[0]" mode="aspectFill" />
          <view class="moc__body">
            <text class="moc__name">{{ product.name }}</text>
            <text class="moc__price">{{ formatPrice(product.price) }}</text>
          </view>
        </view>
      </view>
      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">收货地址</text>
        <input v-model.trim="consigneeName" class="moc__input" placeholder="收货人姓名" />
        <input v-model.trim="consigneePhone" class="moc__input" placeholder="联系电话" />
        <textarea v-model.trim="consigneeAddress" class="moc__textarea" placeholder="详细收货地址" />
      </view>
      <view class="mp-card">
        <text class="mp-kicker mp-kicker--accent">购买数量</text>
        <view class="moc__qty-row">
          <button class="mp-btn-ghost moc__qty-btn" @click="decreaseQty">-</button>
          <text class="moc__qty">{{ quantity }}</text>
          <button class="mp-btn-ghost moc__qty-btn" @click="increaseQty">+</button>
        </view>
        <text class="moc__total">合计：{{ formatPrice(product.price * quantity) }}</text>
      </view>
      <button class="mp-btn-primary" :loading="submitting" @click="submitOrder">提交订单</button>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { createMallOrder, getMallProductDetail } from '@/api/mall'
import type { MallProduct } from '@/types/mall'

const product = ref<MallProduct | null>(null)
const quantity = ref(1)
const submitting = ref(false)
const consigneeName = ref('')
const consigneePhone = ref('')
const consigneeAddress = ref('')

onLoad(async (q) => {
  const id = typeof q?.productId === 'string' ? decodeURIComponent(q.productId) : ''
  if (!id) return
  product.value = await getMallProductDetail(id)
})

function formatPrice(fen: number): string {
  return `¥${(fen / 100).toFixed(2)}`
}

function decreaseQty() {
  quantity.value = Math.max(1, quantity.value - 1)
}

function increaseQty() {
  const max = product.value?.stock || 99
  quantity.value = Math.min(max, quantity.value + 1)
}

async function submitOrder() {
  if (!product.value) return
  if (!consigneeName.value || !consigneePhone.value || !consigneeAddress.value) {
    uni.showToast({ title: '请填写完整收货信息', icon: 'none' })
    return
  }
  submitting.value = true
  try {
    const order = await createMallOrder({
      product_id: Number(product.value.id),
      quantity: quantity.value,
      consignee_name: consigneeName.value,
      consignee_phone: consigneePhone.value,
      consignee_address: consigneeAddress.value,
    })
    uni.redirectTo({ url: `/pages/mall/order-detail?id=${encodeURIComponent(order.id)}` })
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/uni.scss';
.moc__product{display:flex;gap:12rpx;margin-top:8rpx}
.moc__img{width:136rpx;height:136rpx;border-radius:12rpx;background:#f3f4f6;flex-shrink:0}
.moc__body{flex:1}
.moc__name{display:block;font-size:27rpx;font-weight:700;color:$mp-text-primary}
.moc__price{display:block;margin-top:8rpx;font-size:25rpx;color:#d15454}
.moc__input,.moc__textarea{width:100%;margin-top:10rpx;padding:14rpx 16rpx;border-radius:14rpx;border:1rpx solid $mp-border;background:#fafbfc;font-size:25rpx}
.moc__textarea{min-height:140rpx}
.moc__qty-row{display:flex;align-items:center;gap:14rpx;margin-top:8rpx}
.moc__qty-btn{width:90rpx}
.moc__qty{min-width:80rpx;text-align:center;font-size:30rpx;font-weight:700}
.moc__total{display:block;margin-top:12rpx;font-size:28rpx;font-weight:700;color:$mp-accent}
.moc__muted{display:block;padding:16rpx 0;font-size:24rpx;color:$mp-text-muted}
</style>
