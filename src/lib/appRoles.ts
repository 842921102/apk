/** 应用内角色（数据库存 user / operator / super_admin；旧数据 admin 视为 super_admin） */

export type AppRole = 'user' | 'operator' | 'super_admin'

export function normalizeRole(raw: string | null | undefined): AppRole {
  if (!raw) return 'user'
  if (raw === 'admin') return 'super_admin'
  if (raw === 'operator' || raw === 'super_admin' || raw === 'user') return raw
  return 'user'
}

export function canAccessAdmin(raw: string | null | undefined): boolean {
  const r = normalizeRole(raw)
  return r === 'operator' || r === 'super_admin'
}

export function isSuperAdminRole(raw: string | null | undefined): boolean {
  return normalizeRole(raw) === 'super_admin'
}

export function roleLabelCN(role: AppRole): string {
  const m: Record<AppRole, string> = {
    user: '普通用户',
    operator: '运营人员',
    super_admin: '超级管理员'
  }
  return m[role]
}
