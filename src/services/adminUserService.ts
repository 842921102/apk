import { supabase } from '../lib/supabase'

export type ProfileRole = 'user' | 'viewer' | 'operator' | 'super_admin'

export async function getAllUsers() {
  const { data, error } = await supabase
    .from('user_profiles')
    .select('id,user_id,email,role,created_at')
    .order('created_at', { ascending: false })

  if (error) throw error

  // 体验/联调阶段：会员表可能尚未创建。
  // 这里做“可选附加查询”：有就展示，没有就不影响用户管理页面加载。
  const users = (data ?? []) as any[]
  const userIds = users.map((u) => u.user_id).filter(Boolean) as string[]

  let membershipsByUserId = new Map<string, any>()
  try {
    if (userIds.length > 0) {
      const { data: memberships, error: memErr } = await supabase
        .from('user_memberships')
        .select('*')
        .in('user_id', userIds)

      if (!memErr && Array.isArray(memberships)) {
        for (const m of memberships) {
          const uid = String(m.user_id ?? '')
          if (!uid) continue
          // 同一用户可能存在多条记录：默认取“最近更新/最新结束”的那条
          const cur = membershipsByUserId.get(uid)
          if (!cur) membershipsByUserId.set(uid, m)
          else {
            const curEnd = cur.ended_at ?? cur.expires_at ?? null
            const mEnd = m.ended_at ?? m.expires_at ?? null
            if (curEnd && mEnd) {
              membershipsByUserId.set(uid, String(mEnd) > String(curEnd) ? m : cur)
            }
          }
        }
      }
    }
  } catch {
    // ignore: membership table not ready
  }

  // 命理档案（可选）
  let astrologyProfilesByUserId = new Map<string, any>()
  try {
    if (userIds.length > 0) {
      const { data: profiles, error: pErr } = await supabase
        .from('user_astrology_profiles')
        .select('*')
        .in('user_id', userIds)
      if (!pErr && Array.isArray(profiles)) {
        for (const p of profiles) {
          const uid = String(p.user_id ?? '')
          if (!uid) continue
          astrologyProfilesByUserId.set(uid, p)
        }
      }
    }
  } catch {
    // ignore: astrology table not ready
  }

  // 命理默认/偏好（可选）
  let fortuneDefaultsByUserId = new Map<string, any>()
  try {
    if (userIds.length > 0) {
      const { data: defaults, error: dErr } = await supabase
        .from('user_fortune_defaults')
        .select('*')
        .in('user_id', userIds)
      if (!dErr && Array.isArray(defaults)) {
        for (const d of defaults) {
          const uid = String(d.user_id ?? '')
          if (!uid) continue
          fortuneDefaultsByUserId.set(uid, d)
        }
      }
    }
  } catch {
    // ignore
  }

  return users.map((u) => {
    const m = membershipsByUserId.get(String(u.user_id ?? '')) ?? null

    const membership_tier = m?.tier ?? m?.plan ?? m?.level ?? null
    const membership_status = m?.status ?? m?.state ?? null
    const membership_started_at = m?.started_at ?? m?.start_at ?? m?.created_at ?? null
    const membership_ended_at = m?.ended_at ?? m?.expires_at ?? m?.end_at ?? null
    const membership_autorenew = m?.autorenew ?? m?.auto_renew ?? null
    const membership_provider = m?.payment_provider ?? m?.provider ?? null
    const membership_txn_id = m?.transaction_id ?? m?.txn_id ?? m?.payment_txn_id ?? null
    const membership_id = m?.id ?? null

    const ap = astrologyProfilesByUserId.get(String(u.user_id ?? '')) ?? null
    const fd = fortuneDefaultsByUserId.get(String(u.user_id ?? '')) ?? null

    return {
      ...u,
      membership: m,
      membership_tier,
      membership_status,
      membership_started_at,
      membership_ended_at,
      membership_autorenew,
      membership_provider,
      membership_txn_id,
      membership_id,

      // 命理档案（扁平化：详情弹窗直接读）
      birth_date: ap?.birth_date ?? ap?.dob ?? null,
      birth_time: ap?.birth_time ?? ap?.bt ?? null,
      birth_timezone: ap?.birth_timezone ?? ap?.tz ?? null,
      birth_place: ap?.birth_place ?? ap?.place ?? null,
      gender: ap?.gender ?? ap?.sex ?? null,
      zodiac_source: ap?.zodiac_source ?? ap?.source ?? null,
      zodiac_overrides: ap?.zodiac_overrides ?? ap?.overrides ?? null,

      // 命理默认/偏好（扁平化）
      default_fortune_type: fd?.default_fortune_type ?? fd?.fortune_type ?? fd?.type ?? null,
      default_locale: fd?.default_locale ?? fd?.locale ?? null,
      mood_preferred_moods: fd?.mood_preferred_moods ?? fd?.moods ?? null,
      mood_preferred_intensity: fd?.mood_preferred_intensity ?? fd?.intensity ?? null,
      number_preferred_range: fd?.number_preferred_range ?? fd?.range ?? null,
      couple_preferred_sets: fd?.couple_preferred_sets ?? fd?.couple ?? null,
    }
  })
}

export async function updateUserRole(userId: string, role: ProfileRole) {
  const { error } = await supabase
    .from('user_profiles')
    .update({ role })
    .eq('user_id', userId)

  if (error) throw error
}
