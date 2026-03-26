/** 应用内角色（数据库存 user / viewer / operator / super_admin；旧数据 admin 视为 super_admin） */

export type AppRole = 'user' | 'viewer' | 'operator' | 'super_admin'

export function normalizeRole(raw: string | null | undefined): AppRole {
  if (!raw) return 'user'
  const normalized = String(raw).trim().toLowerCase().replace(/[-\s]+/g, '_')
  if (normalized === 'admin' || normalized === 'superadmin' || normalized === 'super_admin') return 'super_admin'
  if (normalized === 'viewer' || normalized === 'operator' || normalized === 'user') return normalized
  return 'user'
}

export function canAccessAdmin(raw: string | null | undefined): boolean {
  const r = normalizeRole(raw)
  return r === 'viewer' || r === 'operator' || r === 'super_admin'
}

export function isSuperAdminRole(raw: string | null | undefined): boolean {
  return normalizeRole(raw) === 'super_admin'
}

export function roleLabelCN(role: AppRole): string {
  const m: Record<AppRole, string> = {
    user: '普通用户',
    viewer: '只读审核员',
    operator: '运营人员',
    super_admin: '超级管理员'
  }
  return m[role]
}
