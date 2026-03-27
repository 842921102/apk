/** 圈子一期：帖子与评论（与后端联调时字段保持一致） */

export type CircleFeedTab = 'recommend' | 'latest' | 'following'

export interface CirclePost {
  id: string
  userId: string
  nickname: string
  avatar: string
  title: string
  content: string
  images: string[]
  topic: string
  likeCount: number
  commentCount: number
  isLiked: boolean
  isCollected: boolean
  createdAt: string
}

export interface CircleComment {
  id: string
  postId: string
  userId: string
  nickname: string
  avatar: string
  content: string
  createdAt: string
}

export interface CirclePostListParams {
  tab: CircleFeedTab
  page: number
  perPage: number
  keyword?: string
}

export interface CircleCreatePostPayload {
  title: string
  content: string
  topic: string
  images: string[]
}
