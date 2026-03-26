import type { AuthCurrentUser } from '@/types/auth'

/**
 * 仅获取微信 login code，不涉及 BFF / openid。
 * 下一步：将 code POST 给 BFF，替换下方的 mock 组装逻辑。
 */
export function requestWeChatLoginCode(): Promise<string> {
  return new Promise((resolve, reject) => {
    uni.login({
      provider: 'weixin',
      success: (res) => {
        if (res.code) {
          resolve(res.code)
        } else {
          reject(new Error('uni.login 未返回 code'))
        }
      },
      fail: (err) => {
        reject(err instanceof Error ? err : new Error('uni.login 失败'))
      },
    })
  })
}

export interface MockLoginResult {
  accessToken: string
  user: AuthCurrentUser
}

/**
 * 占位：模拟 BFF 成功响应。接入真实接口后删除此函数，改为解析 HTTP 响应。
 */
export function buildMockLoginResult(wxCode: string): MockLoginResult {
  const suffix = wxCode.length >= 8 ? wxCode.slice(0, 8) : wxCode
  return {
    accessToken: `mock_access_${Date.now()}`,
    user: {
      id: `mock_user_${suffix}`,
      nickname: '微信用户（mock）',
    },
  }
}
