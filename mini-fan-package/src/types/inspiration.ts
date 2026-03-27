export type InspirationFeedTab = 'recommend' | 'latest' | 'ai_generated' | 'user_uploaded'

export type InspirationSourceType = 'ai_generated' | 'user_uploaded'
export type InspirationPublishSource = 'ai_result' | 'manual_upload'

export interface InspirationItem {
  id: string
  userId: string
  nickname: string
  avatar: string
  title: string
  description: string
  images: string[]
  coverImage: string
  sourceType: InspirationSourceType
  publishSource: InspirationPublishSource
  favoriteCount: number
  likeCount: number
  commentCount: number
  isFavorited: boolean
  isLiked: boolean
  createdAt: string
  relatedProductId?: string | null
  relatedProduct?: {
    id: string
    name: string
    priceText: string
    image: string
  } | null
}

export interface InspirationComment {
  id: string
  itemId: string
  userId: string
  nickname: string
  avatar: string
  content: string
  createdAt: string
}

export interface InspirationListParams {
  tab: InspirationFeedTab
  page: number
  perPage: number
  keyword?: string
}

export interface CreateInspirationPayload {
  title: string
  description: string
  images: string[]
  sourceType: InspirationSourceType
  publishSource: InspirationPublishSource
  relatedProductId?: string | null
}
