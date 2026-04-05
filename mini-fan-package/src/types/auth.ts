/**
 * 小程序登录态中的用户摘要（后续 BFF 返回的 profile 可对齐扩展）
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
