/**
 * 鉴权 HTTP（后续 BFF：POST /api/auth/wechat）
 * 当前小程序内会话由 Supabase Auth 管理，见：
 * - `src/services/auth/supabaseEmailAuth.ts`
 * - `src/services/auth/wechatLoginSkeleton.ts`
 */

import { request } from '@/api/http'

export interface WechatLoginResult {
  /** 微信 openid（或 unionid） */
  openid?: string
  unionid?: string

  /**
   * BFF 返回的 Supabase session tokens（字段名可能不止一种，前端会做兼容提取）
   * - access_token / accessToken / supabase_access_token / supabaseAccessToken
   * - refresh_token / refreshToken / supabase_refresh_token / supabaseRefreshToken
   */
  access_token?: string
  refresh_token?: string
  accessToken?: string
  refreshToken?: string
  supabase_access_token?: string
  supabase_refresh_token?: string
  supabaseAccessToken?: string
  supabaseRefreshToken?: string

  /** 可选：用户信息（昵称等） */
  user?: unknown
}

export async function loginWithWechatCode(code: string): Promise<WechatLoginResult> {
  // BFF：POST /api/auth/wechat
  return request<WechatLoginResult>({
    url: '/api/auth/wechat',
    method: 'POST',
    data: {
      code,
    },
  })
}
