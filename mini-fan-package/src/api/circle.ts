/**
 * 圈子 API：一期默认走本地 Mock + Storage 持久化；
 * 接后端时将 USE_MOCK 改为 false，并实现下方 request 调用。
 */
import { request } from '@/api/http'
import type {
  CircleComment,
  CircleCreatePostPayload,
  CirclePost,
  CirclePostListParams,
} from '@/types/circle'

/** true：演示数据；false：请求 BFF（需后端就绪） */
export const CIRCLE_USE_MOCK = true

const STORAGE_KEY = 'wte_circle_mvp_v1'

/** 静态话题（一期枚举，可后台配置后替换） */
export const CIRCLE_TOPICS = [
  { id: 'food', label: '吃喝推荐' },
  { id: 'recipe', label: '菜谱交流' },
  { id: 'daily', label: '日常吐槽' },
  { id: 'seek', label: '求推荐' },
] as const

/** 当前登录用户在 Mock 中的身份（接入账号体系后与真实 userId 对齐） */
export const CIRCLE_MOCK_ME = {
  userId: 'u_self',
  nickname: '我',
  avatar: '',
}

/** 关注列表（Mock：关注这些 userId 的帖子会出现在「关注」） */
const MOCK_FOLLOWING_IDS = new Set(['u_kitchen', 'u_light'])

type PersistState = {
  posts: CirclePost[]
  commentsByPost: Record<string, CircleComment[]>
}

function nowIso(): string {
  return new Date().toISOString()
}

function seedPosts(): CirclePost[] {
  const t = nowIso()
  return [
    {
      id: 'p_seed_1',
      userId: 'u_kitchen',
      nickname: '厨房练习生',
      avatar: '',
      title: '周末二人食求简单菜谱',
      content:
        '冰箱里有西红柿、鸡蛋、青椒和一块五花肉，想做得快一点，半小时内开饭最好，口味偏淡一点～',
      images: [],
      topic: '求推荐',
      likeCount: 12,
      commentCount: 2,
      isLiked: false,
      isCollected: false,
      createdAt: new Date(Date.now() - 3600_000 * 5).toISOString(),
    },
    {
      id: 'p_seed_2',
      userId: 'u_light',
      nickname: '轻食阿卓',
      avatar: '',
      title: '',
      content: '减脂期晚餐打卡：鸡胸肉 + 西兰花 + 半根玉米，蘸柠檬汁和黑胡椒，意外地好吃。',
      images: [],
      topic: '吃喝推荐',
      likeCount: 28,
      commentCount: 5,
      isLiked: true,
      isCollected: false,
      createdAt: new Date(Date.now() - 3600_000 * 12).toISOString(),
    },
    {
      id: 'p_seed_3',
      userId: 'u_night',
      nickname: '夜宵逃犯',
      avatar: '',
      title: '酸辣开胃，适合下饭',
      content: '自己试了个酸辣土豆丝改良版，多加了一步焯水，口感更脆，辣度用剁椒控制。',
      images: [],
      topic: '菜谱交流',
      likeCount: 7,
      commentCount: 1,
      isLiked: false,
      isCollected: true,
      createdAt: new Date(Date.now() - 3600_000 * 26).toISOString(),
    },
  ]
}

function seedComments(): Record<string, CircleComment[]> {
  return {
    p_seed_1: [
      {
        id: 'c_1',
        postId: 'p_seed_1',
        userId: 'u_light',
        nickname: '轻食阿卓',
        avatar: '',
        content: '试试西红柿炒蛋 + 青椒肉丝双拼？五花肉可以小炒肉。',
        createdAt: new Date(Date.now() - 3600_000 * 4).toISOString(),
      },
      {
        id: 'c_2',
        postId: 'p_seed_1',
        userId: 'u_self',
        nickname: '我',
        avatar: '',
        content: '谢谢，我今晚就试青椒肉丝！',
        createdAt: new Date(Date.now() - 3600_000 * 3).toISOString(),
      },
    ],
    p_seed_2: [
      {
        id: 'c_3',
        postId: 'p_seed_2',
        userId: 'u_kitchen',
        nickname: '厨房练习生',
        avatar: '',
        content: '同款打卡，柠檬汁是灵魂～',
        createdAt: new Date(Date.now() - 3600_000 * 10).toISOString(),
      },
    ],
    p_seed_3: [
      {
        id: 'c_4',
        postId: 'p_seed_3',
        userId: 'u_kitchen',
        nickname: '厨房练习生',
        avatar: '',
        content: '焯水这一步学到了，下次我也试试。',
        createdAt: new Date(Date.now() - 3600_000 * 20).toISOString(),
      },
    ],
  }
}

function readStorage(): PersistState | null {
  try {
    const raw = uni.getStorageSync(STORAGE_KEY) as string | PersistState | undefined
    if (!raw) return null
    if (typeof raw === 'string') {
      return JSON.parse(raw) as PersistState
    }
    return raw as PersistState
  } catch {
    return null
  }
}

function writeStorage(s: PersistState) {
  try {
    uni.setStorageSync(STORAGE_KEY, JSON.stringify(s))
  } catch {
    // ignore
  }
}

let cache: PersistState | null = null

function ensureCache(): PersistState {
  if (cache) return cache
  const loaded = readStorage()
  if (loaded?.posts?.length) {
    cache = loaded
    return cache
  }
  cache = {
    posts: seedPosts(),
    commentsByPost: seedComments(),
  }
  writeStorage(cache)
  return cache
}

function clonePost(post: CirclePost): CirclePost {
  return { ...post, images: [...post.images] }
}

function sortAndFilterPosts(list: CirclePost[], params: CirclePostListParams): CirclePost[] {
  let rows = [...list].map(clonePost)
  const kw = (params.keyword || '').trim().toLowerCase()
  if (kw) {
    rows = rows.filter(
      (p) =>
        p.title.toLowerCase().includes(kw) ||
        p.content.toLowerCase().includes(kw) ||
        p.topic.toLowerCase().includes(kw) ||
        p.nickname.toLowerCase().includes(kw),
    )
  }
  if (params.tab === 'latest') {
    rows.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
  } else if (params.tab === 'recommend') {
    rows.sort((a, b) => {
      const s = b.likeCount * 2 + b.commentCount - (a.likeCount * 2 + a.commentCount)
      if (s !== 0) return s
      return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    })
  } else {
    rows = rows.filter((p) => MOCK_FOLLOWING_IDS.has(p.userId))
    rows.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
  }
  return rows
}

// —— Mock 实现 ——

function mockList(params: CirclePostListParams): { items: CirclePost[]; hasMore: boolean } {
  const s = ensureCache()
  const sorted = sortAndFilterPosts(s.posts, params)
  const start = (params.page - 1) * params.perPage
  const slice = sorted.slice(start, start + params.perPage)
  return {
    items: slice,
    hasMore: start + slice.length < sorted.length,
  }
}

function mockDetail(id: string): CirclePost | null {
  const s = ensureCache()
  const p = s.posts.find((x) => x.id === id)
  return p ? clonePost(p) : null
}

function mockCreate(payload: CircleCreatePostPayload): CirclePost {
  const s = ensureCache()
  const id = `p_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
  const post: CirclePost = {
    id,
    userId: CIRCLE_MOCK_ME.userId,
    nickname: CIRCLE_MOCK_ME.nickname,
    avatar: CIRCLE_MOCK_ME.avatar,
    title: payload.title.trim(),
    content: payload.content.trim(),
    images: [...payload.images],
    topic: payload.topic,
    likeCount: 0,
    commentCount: 0,
    isLiked: false,
    isCollected: false,
    createdAt: nowIso(),
  }
  s.posts.unshift(post)
  s.commentsByPost[id] = s.commentsByPost[id] || []
  writeStorage(s)
  return clonePost(post)
}

function mockLike(id: string): CirclePost | null {
  const s = ensureCache()
  const p = s.posts.find((x) => x.id === id)
  if (!p) return null
  const nextLiked = !p.isLiked
  p.isLiked = nextLiked
  p.likeCount = Math.max(0, p.likeCount + (nextLiked ? 1 : -1))
  writeStorage(s)
  return clonePost(p)
}

function mockCollect(id: string): CirclePost | null {
  const s = ensureCache()
  const p = s.posts.find((x) => x.id === id)
  if (!p) return null
  p.isCollected = !p.isCollected
  writeStorage(s)
  return clonePost(p)
}

function mockComments(postId: string): CircleComment[] {
  const s = ensureCache()
  return [...(s.commentsByPost[postId] || [])].sort(
    (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
  )
}

function mockAddComment(postId: string, content: string): CircleComment | null {
  const s = ensureCache()
  const p = s.posts.find((x) => x.id === postId)
  if (!p) return null
  const c: CircleComment = {
    id: `c_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
    postId,
    userId: CIRCLE_MOCK_ME.userId,
    nickname: CIRCLE_MOCK_ME.nickname,
    avatar: CIRCLE_MOCK_ME.avatar,
    content: content.trim(),
    createdAt: nowIso(),
  }
  if (!s.commentsByPost[postId]) s.commentsByPost[postId] = []
  s.commentsByPost[postId].push(c)
  p.commentCount += 1
  writeStorage(s)
  return c
}

function mockMyPosts(): CirclePost[] {
  const s = ensureCache()
  return s.posts
    .filter((p) => p.userId === CIRCLE_MOCK_ME.userId)
    .map((p) => clonePost(p))
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
}

// —— 对外 API（Mock / 真接口切换点） ——

export async function getCirclePostList(
  params: CirclePostListParams,
): Promise<{ items: CirclePost[]; hasMore: boolean }> {
  if (CIRCLE_USE_MOCK) {
    return mockList(params)
  }
  const raw = await request<{ items?: CirclePost[]; has_more?: boolean }>({
    url: '/api/circle/posts',
    method: 'GET',
    data: {
      tab: params.tab,
      page: params.page,
      per_page: params.perPage,
      keyword: params.keyword || '',
    },
  })
  return {
    items: Array.isArray(raw?.items) ? raw.items : [],
    hasMore: Boolean(raw?.has_more),
  }
}

export async function getCirclePostDetail(id: string): Promise<CirclePost | null> {
  if (CIRCLE_USE_MOCK) return mockDetail(id)
  const raw = await request<{ data?: CirclePost }>({
    url: `/api/circle/posts/${encodeURIComponent(id)}`,
    method: 'GET',
  })
  return raw?.data ?? null
}

export async function createCirclePost(payload: CircleCreatePostPayload): Promise<CirclePost> {
  if (CIRCLE_USE_MOCK) return mockCreate(payload)
  const raw = await request<{ data?: CirclePost }>({
    url: '/api/circle/posts',
    method: 'POST',
    data: payload,
  })
  if (!raw?.data) throw new Error('发布失败')
  return raw.data
}

export async function likeCirclePost(id: string): Promise<CirclePost | null> {
  if (CIRCLE_USE_MOCK) return mockLike(id)
  const raw = await request<{ data?: CirclePost }>({
    url: `/api/circle/posts/${encodeURIComponent(id)}/like`,
    method: 'POST',
  })
  return raw?.data ?? null
}

export async function collectCirclePost(id: string): Promise<CirclePost | null> {
  if (CIRCLE_USE_MOCK) return mockCollect(id)
  const raw = await request<{ data?: CirclePost }>({
    url: `/api/circle/posts/${encodeURIComponent(id)}/collect`,
    method: 'POST',
  })
  return raw?.data ?? null
}

export async function getCircleComments(postId: string): Promise<CircleComment[]> {
  if (CIRCLE_USE_MOCK) return mockComments(postId)
  const raw = await request<{ items?: CircleComment[] }>({
    url: `/api/circle/posts/${encodeURIComponent(postId)}/comments`,
    method: 'GET',
  })
  return Array.isArray(raw?.items) ? raw.items : []
}

export async function createCircleComment(postId: string, content: string): Promise<CircleComment | null> {
  if (CIRCLE_USE_MOCK) return mockAddComment(postId, content)
  const raw = await request<{ data?: CircleComment }>({
    url: `/api/circle/posts/${encodeURIComponent(postId)}/comments`,
    method: 'POST',
    data: { content },
  })
  return raw?.data ?? null
}

export async function getMyCirclePosts(): Promise<CirclePost[]> {
  if (CIRCLE_USE_MOCK) return mockMyPosts()
  const raw = await request<{ items?: CirclePost[] }>({
    url: '/api/circle/me/posts',
    method: 'GET',
  })
  return Array.isArray(raw?.items) ? raw.items : []
}

/** 重置本地演示数据（仅开发/调试可调） */
export function clearCircleMockStorage() {
  cache = null
  try {
    uni.removeStorageSync(STORAGE_KEY)
  } catch {
    // ignore
  }
}
