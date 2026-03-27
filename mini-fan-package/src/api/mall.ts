import { request } from '@/api/http'
import type { CreateOrderPayload, MallOrder, MallProduct } from '@/types/mall'

export async function getMallProductDetail(id: string): Promise<MallProduct | null> {
  const raw = await request<{ data?: MallProduct }>({
    url: `/api/mall/products/${encodeURIComponent(id)}`,
    method: 'GET',
  })
  return raw?.data ?? null
}

export async function createMallOrder(payload: CreateOrderPayload): Promise<MallOrder> {
  const raw = await request<{ data?: MallOrder }>({
    url: '/api/mall/orders',
    method: 'POST',
    data: payload,
  })
  if (!raw?.data) throw new Error('下单失败')
  return raw.data
}

export async function getMyMallOrders(): Promise<MallOrder[]> {
  const raw = await request<{ items?: MallOrder[] }>({
    url: '/api/mall/orders',
    method: 'GET',
  })
  return Array.isArray(raw?.items) ? raw.items : []
}

export async function getMallOrderDetail(id: string): Promise<MallOrder | null> {
  const raw = await request<{ data?: MallOrder }>({
    url: `/api/mall/orders/${encodeURIComponent(id)}`,
    method: 'GET',
  })
  return raw?.data ?? null
}
