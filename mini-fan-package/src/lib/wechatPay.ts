import { createWechatPrepay, getPayOrder, type WechatPayParams } from '@/api/pay'

/** 调起微信小程序支付（与后端 wechat-prepay 返回字段一致） */
export function requestWechatPayment(params: WechatPayParams): Promise<void> {
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

/**
 * 用户完成收银台后，异步通知可能略延迟，轮询直到 paid 或终态/超时。
 */
export async function waitForPayOrderPaid(
  orderId: string,
  opts?: { maxAttempts?: number; intervalMs?: number },
): Promise<'paid' | 'failed' | 'timeout'> {
  const maxAttempts = opts?.maxAttempts ?? 28
  const intervalMs = opts?.intervalMs ?? 700
  for (let i = 0; i < maxAttempts; i++) {
    const o = await getPayOrder(orderId)
    if (o.status === 'paid') return 'paid'
    if (o.status === 'failed' || o.status === 'closed' || o.status === 'refunded') return 'failed'
    await new Promise((r) => setTimeout(r, intervalMs))
  }
  return 'timeout'
}

export function yuanStringToFen(value: string): number {
  const n = parseFloat(value.trim())
  if (!Number.isFinite(n) || n <= 0) return 0
  return Math.round(n * 100)
}
