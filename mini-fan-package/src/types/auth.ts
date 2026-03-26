/**
 * 小程序登录态中的用户摘要（后续 BFF 返回的 profile 可对齐扩展）
 */
export interface AuthCurrentUser {
  id: string
  nickname?: string
  avatarUrl?: string
}
