/**
 * 小程序登录态中的用户摘要（可按后端 profile 扩展）
 */
export interface AuthCurrentUser {
  id: string
  nickname?: string
  avatarUrl?: string
  /** 服务端 profile 未完成首次引导 */
  needsOnboarding?: boolean
  /** 是否开启「特殊时期贴心推荐」能力（与性别无关） */
  periodFeatureEnabled?: boolean
}
