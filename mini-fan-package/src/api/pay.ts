import { request } from '@/api/http'

export interface CreatePayOrderPayload {
  business_type: string
  business_id?: number
  title: string
  description?: string
  amount_fen: number
}

export interface PayOrder {
  id: string
  order_no: string
  status: 'pending' | 'paid' | 'closed' | 'failed' | 'refunded'
  amount_fen: number
  title: string
  description?: string | null
  business_type: string
  business_id?: number | null
  pay_channel: string
  transaction_id?: string | null
  paid_at?: string | null
  expired_at?: string | null
  created_at?: string | null
}

export interface WechatPayParams {
  timeStamp: string
  nonceStr: string
  package: string
  paySign: string
  signType: 'RSA'
}

export async function createPayOrder(payload: CreatePayOrderPayload): Promise<PayOrder> {
  const raw = await request<{ data?: PayOrder }>({
    url: '/api/pay/orders',
    method: 'POST',
    data: payload,
  })
  if (!raw?.data) throw new Error('创建支付订单失败')
  return raw.data
}

export async function createWechatPrepay(orderId: string): Promise<WechatPayParams> {
  const raw = await request<{ data?: WechatPayParams }>({
    url: `/api/pay/orders/${encodeURIComponent(orderId)}/wechat-prepay`,
    method: 'POST',
  })
  if (!raw?.data) throw new Error('获取支付参数失败')
  return raw.data
}

export async function getPayOrder(orderId: string): Promise<PayOrder> {
  const raw = await request<{ data?: PayOrder }>({
    url: `/api/pay/orders/${encodeURIComponent(orderId)}`,
    method: 'GET',
  })
  if (!raw?.data) throw new Error('查询订单失败')
  return raw.data
}

/** 当前用户爱心赞助且支付成功的订单列表 */
export async function listSponsorPayOrders(): Promise<PayOrder[]> {
  const raw = await request<{ data?: PayOrder[] }>({
    url: '/api/pay/orders',
    method: 'GET',
  })
  return Array.isArray(raw?.data) ? raw.data : []
}

