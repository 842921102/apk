import { HttpError, request } from '@/api/http'
import type {
  CreateInspirationPayload,
  InspirationComment,
  InspirationItem,
  InspirationListParams,
} from '@/types/inspiration'

// 线上默认读取后端公共流，避免误用本地 mock 导致只看到本地演示数据。
// 如确实需要 mock 调试，请在此处临时改为 true。
export const INSPIRATION_USE_MOCK = false
const STORAGE_KEY = 'wte_inspiration_v2'

const ME = { userId: 'u_self', nickname: '我', avatar: '' }

type PersistState = {
  items: InspirationItem[]
  commentsByItem: Record<string, InspirationComment[]>
}

function nowIso(): string {
  return new Date().toISOString()
}

function cloneItem(item: InspirationItem): InspirationItem {
  return { ...item, images: [...item.images], relatedProduct: item.relatedProduct ?? null }
}

const SEED_TITLES = [
  '番茄牛腩晚餐灵感',
  '轻食鸡胸拼盘',
  '香煎三文鱼摆盘',
  '奶油蘑菇意面',
  '椒盐虾仁快手菜',
  '空气炸锅烤鸡翅',
  '日式咖喱饭',
  '麻辣香锅家常版',
  '蒜蓉西兰花',
  '红烧排骨',
  '香菇滑鸡',
  '柠檬烤鱼',
  '牛油果早餐碗',
  '芝士焗土豆',
  '黑椒牛排',
  '糖醋里脊',
  '虾仁炒蛋',
  '照烧鸡腿饭',
  '家常豆腐',
  '清汤面暖胃版',
] as const

const SEED_NICKNAMES = [
  '灵感小厨',
  '阿卓实拍',
  'AI摆盘官',
  '家常风味',
  '轻食打卡',
] as const

const SEED_DESC_SNIPPETS = [
  '图片为演示数据，适合首页图片流展示。',
  '偏清淡口味，适合晚餐。',
  '摆盘突出食材质感，适合收藏。',
  '一锅完成，适合下班后快速开饭。',
  '留出商品位，后续可挂锅具/食材包。',
] as const

function seedItems(): InspirationItem[] {
  return Array.from({ length: 20 }, (_, i) => {
    const n = i + 1
    const sourceType: InspirationItem['sourceType'] = i % 2 === 0 ? 'ai_generated' : 'user_uploaded'
    const publishSource: InspirationItem['publishSource'] =
      sourceType === 'ai_generated' ? 'ai_result' : 'manual_upload'
    const seed = 500 + i
    const img = `https://picsum.photos/seed/wte-insp-${seed}/720/960`
    const img2 = `https://picsum.photos/seed/wte-insp-${seed}-b/720/960`
    const base: InspirationItem = {
      id: `i_seed_${n}`,
      userId: `u_demo_${(i % SEED_NICKNAMES.length) + 1}`,
      nickname: SEED_NICKNAMES[i % SEED_NICKNAMES.length],
      avatar: '',
      title: SEED_TITLES[i] ?? `灵感条目 ${n}`,
      description: SEED_DESC_SNIPPETS[i % SEED_DESC_SNIPPETS.length],
      images: [img, img2],
      coverImage: img,
      sourceType,
      publishSource,
      favoriteCount: 8 + ((i * 7) % 73),
      likeCount: 12 + ((i * 11) % 109),
      commentCount: i === 0 ? 3 : i === 1 ? 1 : 0,
      isFavorited: i === 1,
      isLiked: i === 0,
      createdAt: new Date(Date.now() - 3600_000 * (2 + i * 3)).toISOString(),
      relatedProductId: null,
      relatedProduct: null,
    }
    if (i === 1) {
      return {
        ...base,
        relatedProductId: 'sku_demo_001',
        relatedProduct: {
          id: 'sku_demo_001',
          name: '不粘平底锅 28cm',
          priceText: '¥129',
          image: 'https://picsum.photos/seed/wte-product-1/320/320',
        },
      }
    }
    return base
  })
}

function seedComments(): Record<string, InspirationComment[]> {
  return {
    i_seed_1: [
      {
        id: 'ic_1',
        itemId: 'i_seed_1',
        userId: 'u_demo_2',
        nickname: '阿卓实拍',
        avatar: '',
        content: '这个摆盘很适合发朋友圈！',
        createdAt: new Date(Date.now() - 3600_000 * 3).toISOString(),
      },
    ],
    i_seed_2: [
      {
        id: 'ic_2',
        itemId: 'i_seed_2',
        userId: 'u_demo_1',
        nickname: '灵感小厨',
        avatar: '',
        content: '慢炖的颜色很正，周末我也试试。',
        createdAt: new Date(Date.now() - 3600_000 * 8).toISOString(),
      },
    ],
  }
}

let cache: PersistState | null = null

function readStorage(): PersistState | null {
  try {
    const raw = uni.getStorageSync(STORAGE_KEY) as string | PersistState | undefined
    if (!raw) return null
    if (typeof raw === 'string') return JSON.parse(raw) as PersistState
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

function ensureCache(): PersistState {
  if (cache) return cache
  const loaded = readStorage()
  if (loaded?.items?.length) {
    cache = loaded
    return cache
  }
  cache = { items: seedItems(), commentsByItem: seedComments() }
  writeStorage(cache)
  return cache
}

function sortAndFilter(rows: InspirationItem[], params: InspirationListParams): InspirationItem[] {
  let list = rows.map(cloneItem)
  const kw = (params.keyword || '').trim().toLowerCase()
  if (kw) {
    list = list.filter((x) =>
      [x.title, x.description, x.nickname].some((v) => (v || '').toLowerCase().includes(kw)),
    )
  }
  if (params.tab === 'ai_generated') list = list.filter((x) => x.sourceType === 'ai_generated')
  if (params.tab === 'user_uploaded') list = list.filter((x) => x.sourceType === 'user_uploaded')
  list.sort((a, b) => {
    if (params.tab === 'recommend') {
      const scoreA = a.favoriteCount * 2 + a.likeCount
      const scoreB = b.favoriteCount * 2 + b.likeCount
      if (scoreA !== scoreB) return scoreB - scoreA
    }
    return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  })
  return list
}

function mockList(params: InspirationListParams): { items: InspirationItem[]; hasMore: boolean } {
  const s = ensureCache()
  const sorted = sortAndFilter(s.items, params)
  const start = (params.page - 1) * params.perPage
  const slice = sorted.slice(start, start + params.perPage)
  return { items: slice, hasMore: start + slice.length < sorted.length }
}

function mockDetail(id: string): InspirationItem | null {
  const item = ensureCache().items.find((x) => x.id === id)
  return item ? cloneItem(item) : null
}

function mockCreate(payload: CreateInspirationPayload): InspirationItem {
  const s = ensureCache()
  const id = `i_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
  const cover = payload.images[0] || ''
  const created: InspirationItem = {
    id,
    userId: ME.userId,
    nickname: ME.nickname,
    avatar: ME.avatar,
    title: payload.title.trim(),
    description: payload.description.trim(),
    images: [...payload.images],
    coverImage: cover,
    sourceType: payload.sourceType,
    publishSource: payload.publishSource,
    favoriteCount: 0,
    likeCount: 0,
    commentCount: 0,
    isFavorited: false,
    isLiked: false,
    createdAt: nowIso(),
    relatedProductId: payload.relatedProductId ?? null,
    relatedProduct: null,
  }
  s.items.unshift(created)
  s.commentsByItem[id] = []
  writeStorage(s)
  return cloneItem(created)
}

function mockToggleLike(id: string): InspirationItem | null {
  const s = ensureCache()
  const item = s.items.find((x) => x.id === id)
  if (!item) return null
  item.isLiked = !item.isLiked
  item.likeCount = Math.max(0, item.likeCount + (item.isLiked ? 1 : -1))
  writeStorage(s)
  return cloneItem(item)
}

function mockToggleFavorite(id: string): InspirationItem | null {
  const s = ensureCache()
  const item = s.items.find((x) => x.id === id)
  if (!item) return null
  item.isFavorited = !item.isFavorited
  item.favoriteCount = Math.max(0, item.favoriteCount + (item.isFavorited ? 1 : -1))
  writeStorage(s)
  return cloneItem(item)
}

function mockComments(itemId: string): InspirationComment[] {
  return [...(ensureCache().commentsByItem[itemId] || [])].sort(
    (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
  )
}

function mockAddComment(itemId: string, content: string): InspirationComment | null {
  const s = ensureCache()
  const item = s.items.find((x) => x.id === itemId)
  if (!item) return null
  const comment: InspirationComment = {
    id: `ic_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
    itemId,
    userId: ME.userId,
    nickname: ME.nickname,
    avatar: ME.avatar,
    content: content.trim(),
    createdAt: nowIso(),
  }
  if (!s.commentsByItem[itemId]) s.commentsByItem[itemId] = []
  s.commentsByItem[itemId].push(comment)
  item.commentCount += 1
  writeStorage(s)
  return comment
}

function mockMine(): InspirationItem[] {
  return ensureCache().items
    .filter((x) => x.userId === ME.userId)
    .map(cloneItem)
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
}

export async function getInspirationList(
  params: InspirationListParams,
): Promise<{ items: InspirationItem[]; hasMore: boolean }> {
  if (INSPIRATION_USE_MOCK) return mockList(params)
  try {
    const raw = await requestWithFallback<{ items?: InspirationItem[]; has_more?: boolean }>(
      {
        url: '/api/inspiration/posts',
        method: 'GET',
        data: { tab: params.tab, page: params.page, per_page: params.perPage, keyword: params.keyword || '' },
      },
      {
        url: '/api/circle/posts',
        method: 'GET',
        data: { tab: params.tab, page: params.page, per_page: params.perPage, keyword: params.keyword || '' },
      },
    )
    return { items: Array.isArray(raw?.items) ? raw.items : [], hasMore: Boolean(raw?.has_more) }
  } catch (e: unknown) {
    // 开发联调阶段：后端临时不可达时，先回退到本地种子数据，避免页面空白不可用。
    if (!(e instanceof HttpError) || e.statusCode >= 500) {
      return mockList(params)
    }
    throw e
  }
}

export async function getInspirationDetail(id: string): Promise<InspirationItem | null> {
  if (INSPIRATION_USE_MOCK) return mockDetail(id)
  const raw = await requestWithFallback<{ data?: InspirationItem }>(
    {
      url: `/api/inspiration/posts/${encodeURIComponent(id)}`,
      method: 'GET',
    },
    {
      url: `/api/circle/posts/${encodeURIComponent(id)}`,
      method: 'GET',
    },
  )
  return raw?.data ?? null
}

export async function createInspiration(payload: CreateInspirationPayload): Promise<InspirationItem> {
  if (INSPIRATION_USE_MOCK) return mockCreate(payload)
  const raw = await requestWithFallback<{ data?: InspirationItem }>(
    {
      url: '/api/inspiration/posts',
      method: 'POST',
      data: payload,
    },
    {
      url: '/api/circle/posts',
      method: 'POST',
      data: payload,
    },
  )
  if (!raw?.data) throw new Error('发布失败')
  return raw.data
}

export async function favoriteInspiration(id: string): Promise<InspirationItem | null> {
  if (INSPIRATION_USE_MOCK) return mockToggleFavorite(id)
  const raw = await requestWithFallback<{ data?: InspirationItem }>(
    {
      url: `/api/inspiration/posts/${encodeURIComponent(id)}/collect`,
      method: 'POST',
    },
    {
      url: `/api/circle/posts/${encodeURIComponent(id)}/collect`,
      method: 'POST',
    },
  )
  return raw?.data ?? null
}

export async function likeInspiration(id: string): Promise<InspirationItem | null> {
  if (INSPIRATION_USE_MOCK) return mockToggleLike(id)
  const raw = await requestWithFallback<{ data?: InspirationItem }>(
    {
      url: `/api/inspiration/posts/${encodeURIComponent(id)}/like`,
      method: 'POST',
    },
    {
      url: `/api/circle/posts/${encodeURIComponent(id)}/like`,
      method: 'POST',
    },
  )
  return raw?.data ?? null
}

export async function getInspirationComments(itemId: string): Promise<InspirationComment[]> {
  if (INSPIRATION_USE_MOCK) return mockComments(itemId)
  const raw = await requestWithFallback<{ items?: InspirationComment[] }>(
    {
      url: `/api/inspiration/posts/${encodeURIComponent(itemId)}/comments`,
      method: 'GET',
    },
    {
      url: `/api/circle/posts/${encodeURIComponent(itemId)}/comments`,
      method: 'GET',
    },
  )
  return Array.isArray(raw?.items) ? raw.items : []
}

export async function createInspirationComment(
  itemId: string,
  content: string,
): Promise<InspirationComment | null> {
  if (INSPIRATION_USE_MOCK) return mockAddComment(itemId, content)
  const raw = await requestWithFallback<{ data?: InspirationComment }>(
    {
      url: `/api/inspiration/posts/${encodeURIComponent(itemId)}/comments`,
      method: 'POST',
      data: { content },
    },
    {
      url: `/api/circle/posts/${encodeURIComponent(itemId)}/comments`,
      method: 'POST',
      data: { content },
    },
  )
  return raw?.data ?? null
}

export async function getMyInspirations(): Promise<InspirationItem[]> {
  if (INSPIRATION_USE_MOCK) return mockMine()
  const raw = await requestWithFallback<{ items?: InspirationItem[] }>(
    {
      url: '/api/inspiration/me/posts',
      method: 'GET',
    },
    {
      url: '/api/circle/me/posts',
      method: 'GET',
    },
  )
  return Array.isArray(raw?.items) ? raw.items : []
}

async function requestWithFallback<T>(
  primary: { url: string; method: 'GET' | 'POST'; data?: Record<string, unknown> },
  fallback: { url: string; method: 'GET' | 'POST'; data?: Record<string, unknown> },
): Promise<T> {
  try {
    return await request<T>(primary)
  } catch (e: unknown) {
    if (e instanceof HttpError && e.statusCode === 404) {
      return request<T>(fallback)
    }
    throw e
  }
}
