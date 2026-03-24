import { supabase } from '../lib/supabase'

/**
 * Supabase 表名：`admin_logs`
 * 示例 DDL（在 SQL Editor 中执行并配置 RLS）：
 * create table public.admin_logs (
 *   id bigint generated always as identity primary key,
 *   operator_email text not null,
 *   action_type text not null,
 *   target_type text not null,
 *   target_id text,
 *   detail text,
 *   created_at timestamptz not null default now()
 * );
 */
export type AdminLog = {
  id: number | string
  operator_email: string
  action_type: string
  target_type: string
  target_id: string | null
  detail: string | null
  created_at: string
}

export type AdminLogInput = {
  /** 不传则从当前登录用户读取邮箱 */
  operator_email?: string
  action_type: string
  target_type: string
  target_id?: string | null
  detail?: string | null
}

export async function getAllAdminLogs(): Promise<AdminLog[]> {
  const { data, error } = await supabase
    .from('admin_logs')
    .select('id,operator_email,action_type,target_type,target_id,detail,created_at')
    .order('created_at', { ascending: false })

  if (error) throw error
  return (data ?? []) as AdminLog[]
}

export async function addAdminLog(log: AdminLogInput): Promise<void> {
  let operator_email = log.operator_email
  if (!operator_email) {
    const { data: { user } } = await supabase.auth.getUser()
    operator_email = user?.email ?? 'unknown'
  }

  const { error } = await supabase.from('admin_logs').insert({
    operator_email,
    action_type: log.action_type,
    target_type: log.target_type,
    target_id: log.target_id ?? null,
    detail: log.detail ?? null
  })

  if (error) throw error
}
