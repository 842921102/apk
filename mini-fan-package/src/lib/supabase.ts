import { createClient, type SupabaseClient } from '@supabase/supabase-js'

/** 避免构建产物把未定义打成字面量 "undefined" */
function normalizeEnvString(v: unknown): string {
  if (v === undefined || v === null) return ''
  const s = String(v).trim()
  if (s === '' || s === 'undefined' || s === 'null') return ''
  return s.replace(/[\u200b\ufeff\r\n]/g, '')
}

const rawUrl = normalizeEnvString(import.meta.env.VITE_SUPABASE_URL)
const rawKey = normalizeEnvString(import.meta.env.VITE_SUPABASE_ANON_KEY)

/**
 * 不用全局 URL 解析用户输入（部分小程序环境 URL 行为异常），仅用正则校验格式。
 * Supabase SDK 内部仍会 new URL，依赖 main.ts 最先加载的 url-polyfill。
 */
function resolveSupabaseUrl(url: string): string | null {
  if (!url) return null
  const s = url.replace(/\/$/, '')
  const m = /^https:\/\/([a-z0-9]{10,})\.supabase\.co$/i.exec(s)
  if (!m) return null
  return s
}

function resolveAnonKey(key: string): string | null {
  if (!key) return null
  if (key.length < 60) return null
  return key
}

const supabaseUrlResolved = resolveSupabaseUrl(rawUrl)
const supabaseKeyResolved = resolveAnonKey(rawKey)

const credentialsLookValid = Boolean(supabaseUrlResolved && supabaseKeyResolved)

/** 与真实项目 ref 形态一致（字母），避免纯 x 等被个别运行环境误判 */
const PLACEHOLDER_SUPABASE_URL = 'https://abcdefghijklmnopqrst.supabase.co'
const PLACEHOLDER_ANON_KEY =
  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyb2xlIjoiYW5vbiJ9.placeholder-not-a-real-jwt-signature'

/** 仅保证 new URL 可通过；业务层 isSupabaseConfigured 为 false 时不应发真实请求 */
const FALLBACK_PUBLIC_URL = 'https://example.com'

const uniAdapter = {
  getItem: (key: string) => {
    try {
      const v = uni.getStorageSync(key)
      if (v === '' || v === undefined || v === null) return null
      return typeof v === 'string' ? v : String(v)
    } catch {
      return null
    }
  },
  setItem: (key: string, value: string) => {
    try {
      uni.setStorageSync(key, value)
    } catch {
      /* ignore */
    }
  },
  removeItem: (key: string) => {
    try {
      uni.removeStorageSync(key)
    } catch {
      /* ignore */
    }
  },
}

const clientOptions = {
  auth: {
    storage: uniAdapter,
    persistSession: true,
    autoRefreshToken: true,
    detectSessionInUrl: false,
  },
} as const

let clientInitSucceeded = false

/** 体验版：当未配置 Supabase 时启用内存登录（只为让登录页可体验，收藏/历史仍会提示“未配置”） */
function createMinimalStubClient(): SupabaseClient {
  type SessionLike = {
    access_token: string
    refresh_token?: string
    user: { id: string; email?: string }
  }

  let session: SessionLike | null = null

  const dead = async () =>
    ({
      data: null,
      error: { message: 'Supabase 未初始化', name: 'StubError' },
    }) as const

  const noopSub = { unsubscribe: () => {} }
  const restChain: Record<string, unknown> = {
    select: () => restChain,
    insert: () => restChain,
    delete: () => restChain,
    eq: () => restChain,
    order: () =>
      Promise.resolve({
        data: null,
        error: { message: 'offline' },
      }),
    single: () =>
      Promise.resolve({
        data: null,
        error: { message: 'offline' },
      }),
  }

  return {
    auth: {
      getSession: async () =>
        ({
          data: { session },
          error: null,
        }) as const,
      signOut: async () => {
        session = null
        return { error: null } as const
      },
      setSession: async (s: { access_token: string; refresh_token?: string }) => {
        session = {
          access_token: s.access_token,
          refresh_token: s.refresh_token,
          user: { id: 'stub_user', email: undefined },
        }
        return { error: null } as const
      },
      signInWithPassword: async (payload: { email: string; password: string }) => {
        // 体验版：不校验密码，生成可用于页面登录态的 stub session
        const email = String(payload.email || '').trim() || 'stub'
        const suffix = email.slice(0, 8)
        session = {
          access_token: `stub_access_${Date.now()}`,
          refresh_token: `stub_refresh_${Date.now()}`,
          user: { id: `stub_user_${suffix}`, email },
        }
        return {
          data: { session },
          error: null,
        } as const
      },
      onAuthStateChange: () => ({ data: { subscription: noopSub } }),
    },
    from: () => restChain,
  } as unknown as SupabaseClient
}

function createSafe(url: string, key: string): SupabaseClient {
  return createClient(url, key, clientOptions)
}

// createMinimalStubClient 已在文件顶部定义（支持体验版登录）

let supabase: SupabaseClient

try {
  if (credentialsLookValid) {
    supabase = createSafe(supabaseUrlResolved as string, supabaseKeyResolved as string)
    clientInitSucceeded = true
  } else {
    // 体验版：未配置 Supabase 时，直接使用内存 stub（让登录态可用，但收藏/历史仍会提示未配置）
    supabase = createMinimalStubClient()
    clientInitSucceeded = false
    console.warn(
      '[wte-mp][supabase] 未配置 VITE_SUPABASE_URL / VITE_SUPABASE_ANON_KEY。已启用内存 stub 登录态（不保证收藏/历史数据）。请在 fan-miniapp-phase1/.env 填写。',
    )
  }
} catch (err) {
  console.error(
    '[wte-mp][supabase] createClient 失败，尝试降级 URL（微信部分环境对 *.supabase.co 的 URL 解析异常）:',
    err,
  )
  clientInitSucceeded = false
  try {
    supabase = createSafe(PLACEHOLDER_SUPABASE_URL, PLACEHOLDER_ANON_KEY)
  } catch (e2) {
    console.error('[wte-mp][supabase] 占位 URL 仍失败，尝试 example.com:', e2)
    try {
      supabase = createSafe(FALLBACK_PUBLIC_URL, PLACEHOLDER_ANON_KEY)
    } catch (e3) {
      console.error('[wte-mp][supabase] 使用内存 Stub，避免白屏:', e3)
      supabase = createMinimalStubClient()
    }
  }
}

export { supabase }

export function isSupabaseConfigured(): boolean {
  return credentialsLookValid && clientInitSucceeded
}
