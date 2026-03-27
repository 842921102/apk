export interface MallProduct {
  id: string
  name: string
  coverImage: string
  images: string[]
  price: number
  originalPrice: number | null
  stock: number
  description: string
  status: string
}

export interface MallOrder {
  id: string
  orderNo: string
  userId: string
  productId: string
  productName: string
  productImage: string
  productPrice: number
  quantity: number
  totalAmount: number
  orderStatus: string
  payStatus: string
  consigneeName: string
  consigneePhone: string
  consigneeAddress: string
  logisticsCompany: string | null
  logisticsNo: string | null
  createdAt: string
}

export interface CreateOrderPayload {
  product_id: number
  quantity: number
  consignee_name: string
  consignee_phone: string
  consignee_address: string
}
