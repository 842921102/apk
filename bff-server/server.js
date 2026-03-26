/* eslint-disable no-console */

const http = require('http')
const { URL } = require('url')
const fs = require('fs')
const path = require('path')

// Minimal .env loader (no dependency): makes local `npm start` pick up `bff-server/.env`.
function loadDotEnv(dotEnvPath) {
  try {
    if (!fs.existsSync(dotEnvPath)) return
    const raw = fs.readFileSync(dotEnvPath, 'utf8')
    raw.split('\n').forEach((line) => {
      const s = String(line).trim()
      if (!s || s.startsWith('#')) return
      const idx = s.indexOf('=')
      if (idx <= 0) return
      const key = s.slice(0, idx).trim()
      let val = s.slice(idx + 1).trim()
      if (!key) return
      // strip wrapping quotes: KEY="xxx" / KEY='xxx'
      if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
        val = val.slice(1, -1)
      }
      // Allow runtime overrides: e.g. `PORT=8788 npm start`
      if (process.env[key] == null) process.env[key] = val
    })
  } catch (e) {
    // ignore: fallback to process.env
  }
}

loadDotEnv(path.join(__dirname, '.env'))

const PORT = Number(process.env.PORT || 8787)
/** 绑定到所有网卡，手机真机才能访问电脑的局域网 IP（勿用默认仅本机回环） */
const HOST = process.env.HOST || '0.0.0.0'

const BIGMODEL_BASE_URL = process.env.BIGMODEL_BASE_URL || 'https://open.bigmodel.cn/api/paas/v4'
const BIGMODEL_API_KEY = process.env.BIGMODEL_API_KEY || ''
const BIGMODEL_MODEL = process.env.BIGMODEL_MODEL || 'glm-4-flash'

const SUPABASE_URL = process.env.SUPABASE_URL || ''
const SUPABASE_ANON_KEY = process.env.SUPABASE_ANON_KEY || ''

function readJsonBody(req) {
  return new Promise((resolve, reject) => {
    let raw = ''
    req.on('data', (chunk) => {
      raw += String(chunk)
    })
    req.on('end', () => {
      if (!raw) return resolve({})
      try {
        resolve(JSON.parse(raw))
      } catch (e) {
        reject(new Error('invalid_json_body'))
      }
    })
    req.on('error', reject)
  })
}

function json(res, statusCode, payload) {
  const body = JSON.stringify(payload ?? null)
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store',
  })
  res.end(body)
}

function safeString(v) {
  if (v == null) return ''
  return typeof v === 'string' ? v : String(v)
}

function tryParseJsonFromText(text) {
  if (!text || typeof text !== 'string') return null
  const s = text.trim()
  if (!s) return null
  try {
    return JSON.parse(s)
  } catch {}

  // 兜底：从 { ... } 中提取 JSON
  const start = s.indexOf('{')
  const end = s.lastIndexOf('}')
  if (start >= 0 && end > start) {
    const slice = s.slice(start, end + 1)
    try {
      return JSON.parse(slice)
    } catch {}
  }

  return null
}

function normalizeIngredients(x) {
  if (Array.isArray(x)) {
    return x.map(i => safeString(i)).filter(Boolean).slice(0, 12)
  }
  if (typeof x === 'string') {
    return x
      .split(/[,，、\n]/)
      .map(i => safeString(i))
      .filter(Boolean)
      .slice(0, 12)
  }
  return []
}

function getAuthHeader(req) {
  const auth = req.headers.authorization
  if (!auth || typeof auth !== 'string') return ''
  return auth
}

async function fetchSupabaseRest(pathWithQuery, authHeader) {
  if (!SUPABASE_URL) {
    const e = new Error('SUPABASE_URL missing')
    e.statusCode = 404
    throw e
  }
  if (!authHeader) {
    const e = new Error('Unauthorized')
    e.statusCode = 401
    throw e
  }

  const url = `${SUPABASE_URL.replace(/\/$/, '')}${pathWithQuery.startsWith('/') ? '' : '/'}${pathWithQuery}`

  const resp = await fetch(url, {
    headers: {
      apikey: SUPABASE_ANON_KEY,
      Authorization: authHeader,
      Accept: 'application/json',
    },
  })
  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.message || data?.error_description || data?.error || `supabase_request_failed(${status})`
    const e = new Error(msg)
    e.statusCode = status
    e.body = data
    throw e
  }
  return data
}

async function proxyToBigModel({ preferences, locale }) {
  if (!BIGMODEL_API_KEY) throw new Error('BIGMODEL_API_KEY missing')

  const taste = safeString(preferences?.taste)
  const avoid = safeString(preferences?.avoid)
  const people = preferences?.people != null ? Number(preferences?.people) : undefined
  const peopleText = Number.isFinite(people) && people > 0 ? String(people) : '不限'

  const sys = [
    '你是“饭否”小程序的晚餐生成器。',
    '请只输出 JSON，不要输出任何额外解释或 Markdown。',
    'JSON 必须包含字段：',
    '- title: string（菜名）',
    '- cuisine: string（菜系，可选但建议输出）',
    '- ingredients: string[]（食材，建议 5-10 个）',
    '- content: string（菜谱正文/步骤摘要，可用中文，清晰分段）',
  ].join('\n')

  const user = [
    `用户偏好：`,
    `- 口味偏好 taste: ${taste || '无'}`,
    `- 忌口 avoid: ${avoid || '无'}`,
    `- 用餐人数 people: ${peopleText}`,
    ``,
    `请生成一份适合“今天晚餐”的菜谱，并确保 ingredients 与 content 相互一致。`,
    `语言 locale: ${safeString(locale) || 'zh-CN'}`,
  ].join('\n')

  const resp = await fetch(`${BIGMODEL_BASE_URL.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${BIGMODEL_API_KEY}`,
    },
    body: JSON.stringify({
      model: BIGMODEL_MODEL,
      temperature: 0.7,
      messages: [
        { role: 'system', content: sys },
        { role: 'user', content: user },
      ],
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `bigmodel_request_failed(${status})`
    throw new Error(msg)
  }

  const content = data?.choices?.[0]?.message?.content
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') {
    throw new Error('bigmodel_response_not_json')
  }

  const title = safeString(jsonObj.title)
  if (!title) throw new Error('bigmodel_title_missing')

  const cuisine = safeString(jsonObj.cuisine || '')
  const ingredients = normalizeIngredients(jsonObj.ingredients)
  const recipeContent = safeString(jsonObj.content)
  if (!recipeContent) throw new Error('bigmodel_content_missing')

  return {
    title,
    cuisine: cuisine || undefined,
    ingredients,
    content: recipeContent,
    history_saved: false,
  }
}

function normalizeDishTags(x) {
  if (Array.isArray(x)) {
    return x.map(i => safeString(i)).filter(Boolean).slice(0, 8)
  }
  if (typeof x === 'string') {
    return x
      .split(/[,，、\n]/)
      .map(i => safeString(i))
      .filter(Boolean)
      .slice(0, 8)
  }
  return []
}

function normalizeNumber(x) {
  const n = typeof x === 'number' ? x : Number(x)
  return Number.isFinite(n) ? n : undefined
}

function normalizeFortuneSteps(raw) {
  if (Array.isArray(raw)) {
    return raw
      .map((s) => {
        if (typeof s === 'string') return s
        const o = s && typeof s === 'object' ? s : null
        if (o && typeof o.description === 'string') return o.description
        if (o && typeof o.step === 'string') return o.step
        return null
      })
      .filter(Boolean)
  }
  return []
}

function normalizeDifficulty(v) {
  if (v === 'easy' || v === 'hard') return v
  return 'medium'
}

function normalizeSauceCategory(v) {
  const allowed = ['spicy', 'garlic', 'sweet', 'complex', 'regional', 'fusion']
  const s = safeString(v).trim()
  return allowed.includes(s) ? s : 'complex'
}

function clampInt(n, min, max, fallback) {
  const x = typeof n === 'number' ? n : Number(n)
  if (!Number.isFinite(x)) return fallback
  const i = Math.round(x)
  return Math.max(min, Math.min(max, i))
}

async function proxyToSauceRecommend({ preferences, locale }) {
  if (!BIGMODEL_API_KEY) throw new Error('BIGMODEL_API_KEY missing')

  const loc = safeString(locale) || 'zh-CN'
  const spiceLevel = clampInt(preferences?.spiceLevel, 1, 5, 3)
  const sweetLevel = clampInt(preferences?.sweetLevel, 1, 5, 3)
  const saltLevel = clampInt(preferences?.saltLevel, 1, 5, 3)
  const sourLevel = clampInt(preferences?.sourLevel, 1, 5, 3)

  const useCase = Array.isArray(preferences?.useCase)
    ? preferences.useCase.map(safeString).filter(Boolean)
    : []
  const availableIngredients = Array.isArray(preferences?.availableIngredients)
    ? preferences.availableIngredients.map(safeString).filter(Boolean)
    : []

  const useText = useCase.length ? useCase.join('、') : '无'
  const ingText = availableIngredients.length ? availableIngredients.join('、') : '无'

  const sys = [
    '你是“饭否”小程序的酱料推荐生成器（sauce-recommend）。',
    '请只输出 JSON，不要输出任何额外解释或 Markdown。',
    'JSON 必须包含字段：',
    '- recommendations: string[]（推荐酱名，至少 3 个，建议 5 个）',
  ].join('\n')

  const user = [
    '请根据用户口味与场景，推荐适合搭配的酱料名称。',
    `口味辣度 spiceLevel=${spiceLevel}/5`,
    `甜度 sweetLevel=${sweetLevel}/5`,
    `咸度 saltLevel=${saltLevel}/5`,
    `酸度 sourLevel=${sourLevel}/5`,
    `使用场景 useCase: ${useText}`,
    `现有食材 availableIngredients: ${ingText}`,
    `语言 locale=${loc}`,
    '',
    '要求：酱名必须是中文、简洁可读（不要带“配方/步骤”）。',
  ].join('\n')

  const resp = await fetch(`${BIGMODEL_BASE_URL.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${BIGMODEL_API_KEY}`,
    },
    body: JSON.stringify({
      model: BIGMODEL_MODEL,
      temperature: 0.7,
      messages: [
        { role: 'system', content: sys },
        { role: 'user', content: user },
      ],
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `bigmodel_request_failed(${status})`
    throw new Error(msg)
  }

  const content = data?.choices?.[0]?.message?.content
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') throw new Error('bigmodel_response_not_json')

  const recs = Array.isArray(jsonObj.recommendations)
    ? jsonObj.recommendations.map(safeString).filter(Boolean)
    : []

  return { recommendations: recs.slice(0, 10) }
}

async function proxyToSauceRecipe({ sauce_name, locale }) {
  if (!BIGMODEL_API_KEY) throw new Error('BIGMODEL_API_KEY missing')

  const loc = safeString(locale) || 'zh-CN'
  const name = safeString(sauce_name).trim()
  if (!name) throw new Error('sauce_name missing')

  const sys = [
    '你是“饭否”小程序的酱料配方生成器（sauce-recipe）。',
    '请只输出 JSON，不要输出任何额外解释或 Markdown。',
    'JSON 必须包含字段：',
    '- name: string（酱名）',
    '- category: string（只能为 spicy|garlic|sweet|complex|regional|fusion 其中之一）',
    '- ingredients: string[]（食材）',
    '- steps: string[]（制作步骤，建议 4-8 条，每条可直接展示）',
    '- makingTime: number（分钟）',
    '- difficulty: string（easy|medium|hard）',
    '- tips: string[]（建议 3-5 条）',
    '- storage: { method: string, duration: string, temperature: string }',
    '- pairings: string[]（搭配建议）',
    '- tags: string[]（标签）',
    '- description: string（酱料特色）',
  ].join('\n')

  const user = [
    `请生成酱料配方：${name}`,
    `语言 locale=${loc}`,
    '',
    '要求：steps 需要能直接用于小程序展示；ingredients 与 steps 要一致；storage 三个字段用简短中文。',
  ].join('\n')

  const resp = await fetch(`${BIGMODEL_BASE_URL.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${BIGMODEL_API_KEY}`,
    },
    body: JSON.stringify({
      model: BIGMODEL_MODEL,
      temperature: 0.7,
      messages: [
        { role: 'system', content: sys },
        { role: 'user', content: user },
      ],
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `bigmodel_request_failed(${status})`
    throw new Error(msg)
  }

  const content = data?.choices?.[0]?.message?.content
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') throw new Error('bigmodel_response_not_json')

  const storage = jsonObj.storage && typeof jsonObj.storage === 'object' ? jsonObj.storage : {}

  const out = {
    id: safeString(jsonObj.id || `sauce-${Date.now()}`),
    name: safeString(jsonObj.name || name),
    category: normalizeSauceCategory(jsonObj.category),
    ingredients: normalizeIngredients(jsonObj.ingredients),
    steps: Array.isArray(jsonObj.steps) ? jsonObj.steps.map(safeString).filter(Boolean) : [],
    makingTime: clampInt(jsonObj.makingTime ?? jsonObj.making_time, 1, 240, 25),
    difficulty: normalizeDifficulty(safeString(jsonObj.difficulty || 'medium')),
    tips: Array.isArray(jsonObj.tips) ? jsonObj.tips.map(safeString).filter(Boolean).slice(0, 8) : [],
    storage: {
      method: safeString(storage.method || '密封保存') || '密封保存',
      duration: safeString(storage.duration || '—') || '—',
      temperature: safeString(storage.temperature || '—') || '—',
    },
    pairings: Array.isArray(jsonObj.pairings) ? jsonObj.pairings.map(safeString).filter(Boolean).slice(0, 8) : [],
    tags: Array.isArray(jsonObj.tags) ? jsonObj.tags.map(safeString).filter(Boolean).slice(0, 8) : [],
    description: typeof jsonObj.description === 'string' ? jsonObj.description : undefined,
  }

  return { recipe: out, history_saved: false }
}

async function proxyToFortune({ fortuneType, daily, mood, couple, number, locale }) {
  if (!BIGMODEL_API_KEY) throw new Error('BIGMODEL_API_KEY missing')

  const loc = safeString(locale) || 'zh-CN'
  const ft = safeString(fortuneType)
  const allowed = ['daily', 'mood', 'couple', 'number']
  const type = allowed.includes(ft) ? ft : 'daily'

  const sys = [
    '你是“饭否”小程序的玄学占卜生成器。',
    '请只输出 JSON，不要输出任何额外解释或 Markdown。',
    'JSON 必须包含字段：',
    '- type: string（只能为 daily|mood|couple|number）',
    '- date: string（YYYY-MM-DD）',
    '- dishName: string（菜名/占卜题目，用中文）',
    '- reason: string（简短理由）',
    '- luckyIndex: number（1-10，整数）',
    '- description: string（神秘解析，较详细）',
    '- tips: string[]（建议，3-5 条）',
    '- difficulty: string（easy|medium|hard）',
    '- cookingTime: number（分钟，15-90）',
    '- mysticalMessage: string（占卜师的话）',
    '- ingredients: string[]（神秘食材，建议 5-12 个）',
    '- steps: string[]（制作步骤/指引，建议 3-6 条，字符串即可）',
  ].join('\n')

  // 输入摘要（根据类型塞入）
  let user = []
  if (type === 'daily') {
    const zodiac = safeString(daily?.zodiac)
    const animal = safeString(daily?.animal)
    const date = safeString(daily?.date) || new Date().toISOString().slice(0, 10)
    user = [
      `占卜类型 type=daily`,
      `星座 zodiac=${zodiac || '未知'}`,
      `生肖 animal=${animal || '未知'}`,
      `日期 date=${date}`,
      `语言 locale=${loc}`,
      '',
      '请生成一份“今日运势菜”。'
    ]
  } else if (type === 'mood') {
    const moods = Array.isArray(mood?.moods) ? mood.moods.map(safeString).filter(Boolean) : []
    const intensity = Number(mood?.intensity)
    const i = Number.isFinite(intensity) ? Math.round(Math.max(1, Math.min(5, intensity))) : 3
    user = [
      `占卜类型 type=mood`,
      `情绪 moods=${moods.length ? moods.join('、') : '未知'}`,
      `情绪强度 intensity=${i}`,
      `语言 locale=${loc}`,
      '',
      '请生成一份“心情料理”。'
    ]
  } else if (type === 'couple') {
    const u1 = couple?.user1 || {}
    const u2 = couple?.user2 || {}
    const p1 = Array.isArray(u1?.personality) ? u1.personality.map(safeString).filter(Boolean) : []
    const p2 = Array.isArray(u2?.personality) ? u2.personality.map(safeString).filter(Boolean) : []
    user = [
      `占卜类型 type=couple`,
      `甲方: zodiac=${safeString(u1.zodiac) || '未知'} animal=${safeString(u1.animal) || '未知'} personality=${p1.length ? p1.join('、') : '无'}`,
      `乙方: zodiac=${safeString(u2.zodiac) || '未知'} animal=${safeString(u2.animal) || '未知'} personality=${p2.length ? p2.join('、') : '无'}`,
      `语言 locale=${loc}`,
      '',
      '请生成一份“双人星座默契菜”。'
    ]
  } else {
    const n = normalizeNumber(number?.number) ?? 7
    const isRandom = number?.is_random === true
    user = [
      `占卜类型 type=number`,
      `幸运数字 number=${n}`,
      `是否随机 is_random=${isRandom}`,
      `语言 locale=${loc}`,
      '',
      '请生成一份“幸运数字菜”。'
    ]
  }

  const resp = await fetch(`${BIGMODEL_BASE_URL.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${BIGMODEL_API_KEY}`,
    },
    body: JSON.stringify({
      model: BIGMODEL_MODEL,
      temperature: 0.7,
      messages: [
        { role: 'system', content: sys },
        { role: 'user', content: user.join('\n') },
      ],
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `bigmodel_request_failed(${status})`
    throw new Error(msg)
  }

  const content = data?.choices?.[0]?.message?.content
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') {
    throw new Error('bigmodel_response_not_json')
  }

  const outType = normalizeDifficulty
    ? (jsonObj.type && allowed.includes(jsonObj.type) ? jsonObj.type : type)
    : type

  const date = safeString(jsonObj.date || new Date().toISOString().slice(0, 10))
  const dishName = safeString(jsonObj.dishName || jsonObj.dish_name || '')
  const reason = safeString(jsonObj.reason || '')
  let luckyIndex = jsonObj.luckyIndex ?? jsonObj.lucky_index ?? 7
  luckyIndex = Number.isFinite(Number(luckyIndex)) ? Math.round(Number(luckyIndex)) : 7
  luckyIndex = Math.min(10, Math.max(1, luckyIndex))
  const description = safeString(jsonObj.description || '')
  const tips = Array.isArray(jsonObj.tips)
    ? jsonObj.tips.map(safeString).filter(Boolean).slice(0, 6)
    : []
  const difficulty = normalizeDifficulty(safeString(jsonObj.difficulty || 'medium'))
  let cookingTime = jsonObj.cookingTime ?? jsonObj.cooking_time ?? 30
  cookingTime = Number.isFinite(Number(cookingTime)) ? Math.round(Number(cookingTime)) : 30
  cookingTime = Math.min(90, Math.max(15, cookingTime))
  const mysticalMessage = safeString(jsonObj.mysticalMessage || jsonObj.mystical_message || '')
  const ingredients = normalizeIngredients(jsonObj.ingredients)
  const steps = normalizeFortuneSteps(jsonObj.steps)

  return {
    result: {
      id: safeString(jsonObj.id || `fortune-${Date.now()}`),
      type: outType,
      date,
      dishName: dishName || '神秘料理',
      reason,
      luckyIndex,
      description,
      tips,
      difficulty,
      cookingTime,
      mysticalMessage,
      ingredients,
      steps,
    },
    history_saved: false,
  }
}

async function proxyToTableMenu({ config, locale }) {
  if (!BIGMODEL_API_KEY) throw new Error('BIGMODEL_API_KEY missing')

  const dishCount = normalizeNumber(config?.dishCount) ?? 4
  const flexibleCount = !!config?.flexibleCount
  const count = flexibleCount ? Math.max(3, Math.min(8, dishCount)) : Math.max(2, Math.min(10, dishCount))

  const tastes = Array.isArray(config?.tastes) ? config.tastes.map(safeString).filter(Boolean) : []
  const cuisineStyle = safeString(config?.cuisineStyle)
  const diningScene = safeString(config?.diningScene)
  const nutritionFocus = safeString(config?.nutritionFocus)
  const customRequirement = safeString(config?.customRequirement)
  const customDishes = Array.isArray(config?.customDishes) ? config.customDishes.map(safeString).filter(Boolean) : []

  const sys = [
    '你是“饭否”小程序的一桌菜策划器（table-menu）。',
    '请只输出 JSON，不要输出任何额外解释或 Markdown。',
    'JSON 必须包含字段：',
    '- dishes: Array，其中每个元素都包含：',
    '  - name: string（菜名）',
    '  - description: string（简短说明）',
    '  - category: string（菜系/类别）',
    '  - tags: string[]（最多 3-5 个标签，可为空数组）',
    '并确保 dishes 数量约为 {{count}}。',
  ].join('\n').replace('{{count}}', String(count))

  const user = [
    '请生成一桌好菜（总计约 count 道）。',
    '',
    `口味偏好 tastes: ${tastes.length ? tastes.join('、') : '无'}`,
    `菜系/风格 cuisineStyle: ${cuisineStyle || '无'}`,
    `用餐场景 diningScene: ${diningScene || '无'}`,
    `营养侧重点 nutritionFocus: ${nutritionFocus || '无'}`,
    `自定义要求 customRequirement: ${customRequirement || '无'}`,
    `参考菜 customDishes: ${customDishes.length ? customDishes.join('、') : '无'}`,
    '',
    `语言 locale: ${safeString(locale) || 'zh-CN'}`,
    '',
    '注意：tags 必须是数组，description 要简短但具体。'
  ].join('\n')

  const resp = await fetch(`${BIGMODEL_BASE_URL.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${BIGMODEL_API_KEY}`,
    },
    body: JSON.stringify({
      model: BIGMODEL_MODEL,
      temperature: 0.7,
      messages: [
        { role: 'system', content: sys },
        { role: 'user', content: user },
      ],
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `bigmodel_request_failed(${status})`
    throw new Error(msg)
  }

  const content = data?.choices?.[0]?.message?.content
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') {
    throw new Error('bigmodel_response_not_json')
  }

  const dishesRaw = Array.isArray(jsonObj.dishes) ? jsonObj.dishes : []
  const dishes = []
  for (const d of dishesRaw.slice(0, 10)) {
    const dn = safeString(d?.name).trim()
    if (!dn) continue
    dishes.push({
      name: dn,
      description: safeString(d?.description || '').trim(),
      category: safeString(d?.category || '').trim(),
      tags: normalizeDishTags(d?.tags),
    })
  }

  return { dishes, history_saved: false }
}

async function proxyToTableDishRecipe({ dish_name, dish_description, category, locale }) {
  if (!BIGMODEL_API_KEY) throw new Error('BIGMODEL_API_KEY missing')

  const name = safeString(dish_name)
  const desc = safeString(dish_description)
  const cat = safeString(category)
  const loc = safeString(locale) || 'zh-CN'

  const sys = [
    '你是“饭否”小程序的单道菜谱生成器（table-dish-recipe）。',
    '请只输出 JSON，不要输出任何额外解释或 Markdown。',
    'JSON 必须包含字段：',
    '- name: string（菜名）',
    '- ingredients: string[]（食材，建议 6-12 个）',
    '- steps: Array，每个元素包含：',
    '  - step: number（步骤序号，从 1 开始）',
    '  - description: string（步骤说明）',
    '  - time?: number（可选：预计耗时分钟）',
    '  - temperature?: string（可选：温度/火候）',
    '并尽量给出结构化烹饪过程。'
  ].join('\n')

  const user = [
    `菜名 name: ${name}`,
    `菜品描述 dish_description: ${desc || '无'}`,
    `类别 category: ${cat || '无'}`,
    `语言 locale: ${loc}`,
    '',
    '输出内容必须是可执行的烹饪步骤；ingredients 与步骤要一致。'
  ].join('\n')

  const resp = await fetch(`${BIGMODEL_BASE_URL.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${BIGMODEL_API_KEY}`,
    },
    body: JSON.stringify({
      model: BIGMODEL_MODEL,
      temperature: 0.7,
      messages: [
        { role: 'system', content: sys },
        { role: 'user', content: user },
      ],
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `bigmodel_request_failed(${status})`
    throw new Error(msg)
  }

  const content = data?.choices?.[0]?.message?.content
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') {
    throw new Error('bigmodel_response_not_json')
  }

  // 前端 normalizeDishRecipeResponse 会二次处理 steps/ingredients，这里尽量输出标准字段
  return {
    name: safeString(jsonObj.name || name).trim(),
    cuisine: safeString(jsonObj.cuisine || cat).trim() || undefined,
    ingredients: normalizeIngredients(jsonObj.ingredients),
    steps: Array.isArray(jsonObj.steps) ? jsonObj.steps : [],
    cookingTime: normalizeNumber(jsonObj.cookingTime),
    difficulty: safeString(jsonObj.difficulty || '').trim() || undefined,
    tips: Array.isArray(jsonObj.tips) ? jsonObj.tips.map(i => safeString(i)).filter(Boolean).slice(0, 6) : undefined,
  }
}

function getRoute(req) {
  const u = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
  let pathname = u.pathname || '/'
  // 兼容 /api/auth/wechat/ 这类尾随斜杠，否则会被 404
  if (pathname.length > 1 && pathname.endsWith('/')) {
    pathname = pathname.slice(0, -1)
  }
  return { pathname }
}

const server = http.createServer(async (req, res) => {
  try {
    if (!req.url) return json(res, 404, { error: { message: 'not_found' } })

    const { pathname } = getRoute(req)

    if (req.method === 'GET' && pathname === '/health') {
      return json(res, 200, { ok: true })
    }

    // 小程序运营配置（空对象即用前端 defaultConfig；见 mini-fan-package src/api/config.ts）
    if (req.method === 'GET' && pathname === '/api/miniapp/config') {
      return json(res, 200, {})
    }

    // 微信登录：POST /api/auth/wechat
    // 小程序：uni.login(provider:'weixin') -> code
    // BFF：把 code 转发给 Laravel -> 返回 access_token + user
    if (req.method === 'POST' && pathname === '/api/auth/wechat') {
      const body = await readJsonBody(req)
      const code = body?.code

      const adminBackendBaseUrl =
        process.env.ADMIN_BACKEND_BASE_URL || process.env.LARAVEL_ADMIN_BASE_URL || ''

      if (!adminBackendBaseUrl) {
        return json(res, 500, {
          error: { message: 'ADMIN_BACKEND_BASE_URL missing' },
        })
      }

      if (!code || typeof code !== 'string') {
        return json(res, 400, { error: { message: 'code_missing' } })
      }

      try {
        const url = `${adminBackendBaseUrl.replace(/\/$/, '')}/api/auth/wechat`
        const resp = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify({ code }),
        })

        const data = await resp.json().catch(() => ({}))

        if (!resp.ok) {
          return json(res, resp.status || 500, data)
        }

        return json(res, 200, data)
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'internal_error'
        return json(res, 500, { error: { message: msg } })
      }
    }

    // 云端图鉴列表：GET /api/gallery/list
    // 目前最小实现：从 Supabase `recipe_histories` 中取出 `image_url` 非空记录，映射为小程序 `GalleryItem`。
    if (req.method === 'GET' && pathname === '/api/gallery/list') {
      const authHeader = getAuthHeader(req)
      try {
        const select = [
          'id',
          'user_id',
          'title',
          'cuisine',
          'ingredients',
          'request_payload',
          'response_content',
          'image_url',
          'created_at',
        ].join(',')

        const raw = await fetchSupabaseRest(
          `/rest/v1/recipe_histories?select=${encodeURIComponent(select)}&order=created_at.desc&limit=50`,
          authHeader,
        )

        const rows = Array.isArray(raw) ? raw : raw?.data ?? []
        const items = rows
          .map((r) => {
            const url = safeString(r?.image_url).trim()
            if (!url) return null
            const id = safeString(r?.id).trim()
            const recipeName = safeString(r?.title).trim() || '未命名'
            const cuisine = safeString(r?.cuisine).trim()
            const ingredientsRaw = r?.ingredients
            const ingredients = Array.isArray(ingredientsRaw)
              ? ingredientsRaw.map((x) => safeString(x)).filter(Boolean)
              : []

            const createdAt = safeString(r?.created_at).trim() || new Date().toISOString()

            const promptCandidate = r?.request_payload ?? r?.response_content
            const prompt =
              promptCandidate == null
                ? undefined
                : typeof promptCandidate === 'string'
                  ? promptCandidate
                  : JSON.stringify(promptCandidate)

            return {
              id,
              url,
              recipeName,
              recipeId: safeString(r?.recipe_id ?? '').trim(),
              cuisine,
              ingredients,
              generatedAt: createdAt,
              prompt,
              userId: safeString(r?.user_id ?? '').trim(),
              userEmail: undefined,
            }
          })
          .filter((x) => x != null)

        return json(res, 200, { items })
      } catch (e) {
        const status =
          e && typeof e === 'object' && 'statusCode' in e ? Number(e.statusCode) || 500 : 500
        if (status === 404) {
          return json(res, 404, { error: { message: 'gallery_not_configured' } })
        }
        if (status === 401) {
          return json(res, 401, { error: { message: 'unauthorized' } })
        }
        const msg = e instanceof Error ? e.message : 'internal_error'
        return json(res, 500, { error: { message: msg } })
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/today-eat') {
      const body = await readJsonBody(req)
      const preferences = body?.preferences ?? {}
      const locale = body?.locale ?? 'zh-CN'

      const result = await proxyToBigModel({ preferences, locale })
      return json(res, 200, result)
    }

    if (
      req.method === 'POST' &&
      (pathname === '/api/ai/fortune' || pathname === '/api/ai/fortune-cooking')
    ) {
      const body = await readJsonBody(req)
      const fortuneType = body?.fortune_type ?? body?.fortuneType ?? 'daily'
      const locale = body?.locale ?? 'zh-CN'

      const payload = {
        fortuneType,
        daily: body?.daily ?? null,
        mood: body?.mood ?? null,
        couple: body?.couple ?? null,
        number: body?.number ?? null,
        locale,
      }

      const result = await proxyToFortune(payload)
      return json(res, 200, result)
    }

    if (req.method === 'POST' && pathname === '/api/ai/sauce-recommend') {
      const body = await readJsonBody(req)
      const preferences = body?.preferences ?? body?.pref ?? {}
      const locale = body?.locale ?? 'zh-CN'
      const result = await proxyToSauceRecommend({ preferences, locale })
      return json(res, 200, result)
    }

    if (req.method === 'POST' && pathname === '/api/ai/sauce-recipe') {
      const body = await readJsonBody(req)
      const sauce_name = body?.sauce_name ?? body?.sauceName
      const locale = body?.locale ?? 'zh-CN'
      const result = await proxyToSauceRecipe({ sauce_name, locale })
      return json(res, 200, result)
    }

    if (req.method === 'POST' && pathname === '/api/ai/table-menu') {
      const body = await readJsonBody(req)
      const config = body?.config ?? {}
      const locale = body?.locale ?? 'zh-CN'
      const result = await proxyToTableMenu({ config, locale })
      return json(res, 200, result)
    }

    if (req.method === 'POST' && pathname === '/api/ai/table-dish-recipe') {
      const body = await readJsonBody(req)
      const result = await proxyToTableDishRecipe({
        dish_name: body?.dish_name,
        dish_description: body?.dish_description,
        category: body?.category,
        locale: body?.locale ?? 'zh-CN',
      })
      return json(res, 200, result)
    }

    return json(res, 404, {
      error: {
        message: 'not_found',
        path: pathname,
        method: req.method,
      },
    })
  } catch (e) {
    console.error('[bff today-eat]', e)
    const msg = e instanceof Error ? e.message : 'internal_error'
    return json(res, 500, { error: { message: msg } })
  }
})

server.listen(PORT, HOST, () => {
  const tip =
    HOST === '0.0.0.0' || HOST === '::'
      ? `（局域网可访问，例 http://<本机IP>:${PORT}）`
      : ''
  console.log(`[bff today-eat] listening on http://${HOST}:${PORT} ${tip}`)
})

