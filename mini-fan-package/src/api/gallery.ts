import { request, HttpError } from '@/api/http'
import type { GalleryItem } from '@/types/gallery'

/** 与 Web `recipe-gallery-images` 对应的小程序本机键（便于后续菜谱保存入口写入） */
export const GALLERY_LOCAL_STORAGE_KEY = 'mp_recipe_gallery_images_v1'

function asRecord(v: unknown): Record<string, unknown> | null {
  if (!v || typeof v !== 'object' || Array.isArray(v)) return null
  return v as Record<string, unknown>
}

export function normalizeGalleryItem(raw: unknown): GalleryItem | null {
  const o = asRecord(raw)
  if (!o) return null
  const id = String(o.id ?? o.image_id ?? '').trim()
  const url = String(o.url ?? o.image_url ?? '').trim()
  if (!id || !url) return null
  const ing = o.ingredients
  const ingredients = Array.isArray(ing)
    ? ing.filter((x): x is string => typeof x === 'string')
    : []
  return {
    id,
    url,
    recipeName: String(o.recipeName ?? o.recipe_name ?? o.title ?? '未命名').trim() || '未命名',
    recipeId: String(o.recipeId ?? o.recipe_id ?? ''),
    cuisine: String(o.cuisine ?? ''),
    ingredients,
    generatedAt: String(o.generatedAt ?? o.generated_at ?? new Date().toISOString()),
    prompt: typeof o.prompt === 'string' ? o.prompt : undefined,
    userId: typeof o.userId === 'string' ? o.userId : typeof o.user_id === 'string' ? o.user_id : undefined,
    userEmail:
      typeof o.userEmail === 'string'
        ? o.userEmail
        : typeof o.user_email === 'string'
          ? o.user_email
          : undefined,
  }
}

function normalizeGalleryListPayload(raw: unknown): GalleryItem[] {
  let cur: unknown = raw
  for (let i = 0; i < 3; i++) {
    const o = asRecord(cur)
    if (!o) break
    const arr = o.items ?? o.list ?? o.images ?? o.gallery ?? o.records
    if (Array.isArray(arr)) {
      const out: GalleryItem[] = []
      for (const row of arr) {
        const item = normalizeGalleryItem(row)
        if (item) out.push(item)
      }
      return out
    }
    cur = o.data ?? o.result
  }
  if (Array.isArray(raw)) {
    return raw.map(normalizeGalleryItem).filter((x): x is GalleryItem => x != null)
  }
  return []
}

export function readLocalGallery(): GalleryItem[] {
  try {
    const s = uni.getStorageSync(GALLERY_LOCAL_STORAGE_KEY) as string | undefined
    if (!s || typeof s !== 'string') return []
    const parsed = JSON.parse(s) as unknown
    if (!Array.isArray(parsed)) return []
    return parsed.map(normalizeGalleryItem).filter((x): x is GalleryItem => x != null)
  } catch {
    return []
  }
}

export function writeLocalGallery(items: GalleryItem[]): void {
  try {
    uni.setStorageSync(GALLERY_LOCAL_STORAGE_KEY, JSON.stringify(items))
  } catch {
    /* ignore */
  }
}

/** 追加或按 id 更新一条（供后续「保存到图鉴」入口调用） */
export function upsertLocalGalleryItem(item: GalleryItem): void {
  const list = readLocalGallery()
  const i = list.findIndex((x) => x.id === item.id)
  if (i >= 0) list[i] = item
  else list.unshift(item)
  writeLocalGallery(list)
}

export function removeLocalGalleryItem(id: string): void {
  writeLocalGallery(readLocalGallery().filter((x) => x.id !== id))
}

export function clearLocalGallery(): void {
  try {
    uni.removeStorageSync(GALLERY_LOCAL_STORAGE_KEY)
  } catch {
    writeLocalGallery([])
  }
}

/**
 * 从 BFF 拉取图鉴列表；成功则写本地缓存并返回。
 * 失败（含 404/401/网络）返回本机列表，由调用方展示 `hint`。
 */
export async function fetchGalleryList(): Promise<{ items: GalleryItem[]; hint: string }> {
  try {
    const raw = await request<unknown>({
      url: '/api/gallery/list',
      method: 'GET',
    })
    const cloudItems = normalizeGalleryListPayload(raw)
    const cloudIds = new Set(cloudItems.map((x) => x.id))
    const localOnly = readLocalGallery().filter((x) => !cloudIds.has(x.id))
    const merged = [...cloudItems, ...localOnly]
    writeLocalGallery(merged)
    return { items: merged, hint: '' }
  } catch (e: unknown) {
    const local = readLocalGallery()
    let hint = '暂时无法拉取云端图鉴，已显示本机保存的图片。'
    if (e instanceof HttpError) {
      if (e.statusCode === 404) {
        hint = '云端图鉴未部署（GET /api/gallery/list），当前为本机图鉴。'
      } else if (e.statusCode === 401) {
        hint = '登录后可同步云端图鉴；当前为本机图鉴。'
      }
    }
    return { items: local, hint }
  }
}
