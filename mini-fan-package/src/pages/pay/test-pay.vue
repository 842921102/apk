<template>
  <view class="pay-page">
    <view class="mp-card pay-card">
      <text class="pay-title">微信支付测试（一期）</text>
      <text class="pay-desc">仅用于打通支付最小闭环，业务类型固定为 test_pay。</text>

      <input v-model="title" class="pay-input" maxlength="40" placeholder="订单标题" />
      <input v-model="amountYuan" class="pay-input" type="digit" placeholder="金额（元）如 0.01" />

      <button class="mp-btn-primary pay-btn" :disabled="loading" @click="startPay">
        {{ loading ? '处理中…' : '创建订单并支付' }}
      </button>

      <button class="mp-btn-ghost pay-btn" :disabled="loading || !orderId" @click="queryStatus">
        查询订单状态
      </button>

      <view class="pay-result" v-if="orderNo">
        <text>订单号：{{ orderNo }}</text>
        <text>状态：{{ statusText }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { createPayOrder, createWechatPrepay, getPayOrder, type PayOrder } from '@/api/pay'
import { HttpError } from '@/api/http'

const loading = ref(false)
const title = ref('饭否支付测试订单')
const amountYuan = ref('0.01')
const orderId = ref('')
const orderNo = ref('')
const status = ref<PayOrder['status']>('pending')

const statusText = computed(() => status.value)

async function startPay() {
  if (loading.value) return
  const amountFen = yuanToFen(amountYuan.value)
  if (amountFen <= 0) {
    uni.showToast({ title: '请输入正确金额', icon: 'none' })
    return
  }

  loading.value = true
  try {
    const order = await createPayOrder({
      business_type: 'test_pay',
      title: title.value.trim() || '饭否支付测试订单',
      amount_fen: amountFen,
      description: '一期最小闭环测试',
    })
    orderId.value = order.id
    orderNo.value = order.order_no
    status.value = order.status

    const payParams = await createWechatPrepay(order.id)
    await requestPayment(payParams)

    await queryStatus()
  } catch (e: unknown) {
    const msg = toErrorMessage(e)
    uni.showToast({ title: msg, icon: 'none', duration: 2600 })
  } finally {
    loading.value = false
  }
}

async function queryStatus() {
  if (!orderId.value) return
  try {
    const latest = await getPayOrder(orderId.value)
    status.value = latest.status
    if (latest.status === 'paid') {
      uni.showToast({ title: '支付成功', icon: 'success' })
    } else {
      uni.showToast({ title: `当前状态：${latest.status}`, icon: 'none' })
    }
  } catch (e: unknown) {
    uni.showToast({ title: toErrorMessage(e), icon: 'none' })
  }
}

function requestPayment(params: {
  timeStamp: string
  nonceStr: string
  package: string
  paySign: string
  signType: 'RSA'
}): Promise<void> {
  return new Promise((resolve, reject) => {
    uni.requestPayment({
      provider: 'wxpay',
      timeStamp: params.timeStamp,
      nonceStr: params.nonceStr,
      package: params.package,
      signType: params.signType,
      paySign: params.paySign,
      success: () => resolve(),
      fail: (err) => reject(err),
    })
  })
}

function yuanToFen(value: string): number {
  const n = Number(value)
  if (!Number.isFinite(n) || n <= 0) return 0
  return Math.round(n * 100)
}

function toErrorMessage(e: unknown): string {
  const maybe = e as { errMsg?: string }
  if (maybe?.errMsg?.includes('cancel')) return '已取消支付'
  if (e instanceof HttpError) return e.message.slice(0, 60)
  if (e instanceof Error) return e.message.slice(0, 60)
  return '支付失败，请稍后重试'
}
</script>

<style scoped lang="scss">
.pay-page {
  padding: 24rpx;
}

.pay-card {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.pay-title {
  font-size: 32rpx;
  font-weight: 700;
}

.pay-desc {
  font-size: 24rpx;
  color: #8a8a99;
}

.pay-input {
  border: 1rpx solid #e8e8ef;
  border-radius: 12rpx;
  padding: 16rpx;
  font-size: 26rpx;
  background: #fafbfc;
}

.pay-btn {
  margin-top: 8rpx;
}

.pay-result {
  margin-top: 16rpx;
  font-size: 24rpx;
  color: #444;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}
</style>
