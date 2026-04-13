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

const RAW_ADMIN_BACKEND_URL = process.env.ADMIN_BACKEND_BASE_URL || process.env.LARAVEL_ADMIN_BASE_URL || ''

/**
 * Laravel 根地址（无末尾 /、无 /api 后缀）。常见误配 `http://127.0.0.1:8000/api`：
 * 再拼 pathname `/api/pay/orders` 会得到 `/api/api/pay/orders` → 404。
 */
function normalizeAdminBackendBaseUrl(raw) {
  const s = String(raw ?? '').trim()
  if (!s) return ''
  let b = s.replace(/\/$/, '')
  if (b.endsWith('/api')) b = b.slice(0, -4)
  return b
}

/** 本地开发常忘配该项；生产环境须显式设置 ADMIN_BACKEND_BASE_URL（NODE_ENV=production 时不使用默认值） */
const ADMIN_BACKEND_BASE_URL = normalizeAdminBackendBaseUrl(
  RAW_ADMIN_BACKEND_URL || (process.env.NODE_ENV === 'production' ? '' : 'http://127.0.0.1:8000'),
)
const INTERNAL_SERVICE_TOKEN = process.env.INTERNAL_SERVICE_TOKEN || ''

const SUPABASE_URL = process.env.SUPABASE_URL || ''
const SUPABASE_ANON_KEY = process.env.SUPABASE_ANON_KEY || ''

const sceneConfigCache = new Map()
const AI_SCENES = Object.freeze({
  TEXT: 'recipe_text_generation',
  IMAGE: 'recipe_image_generation',
})

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
    // 小程序端可据此区分「本 Node BFF」与「前面 Nginx/其它进程返回的空 502」
    'X-Wte-Bff': '1',
  })
  res.end(body)
}

function safeString(v) {
  if (v == null) return ''
  return typeof v === 'string' ? v : String(v)
}

/** base_url + api_path；若 base 已含 path（误填完整端点），避免重复拼接导致 404 */
function buildModelRequestUrl(baseUrl, apiPath) {
  const base = safeString(baseUrl).replace(/\/$/, '')
  const path = safeString(apiPath).replace(/^\//, '')
  if (!path) return base
  const suffix = `/${path}`
  return base.endsWith(suffix) ? base : `${base}${suffix}`
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

function extractTextFromModelResponse(data) {
  // Chat Completions shape
  const chatContent = data?.choices?.[0]?.message?.content
  if (typeof chatContent === 'string' && chatContent.trim()) return chatContent
  if (Array.isArray(chatContent)) {
    const txt = chatContent
      .map((p) => (typeof p?.text === 'string' ? p.text : ''))
      .filter(Boolean)
      .join('\n')
      .trim()
    if (txt) return txt
  }

  // Responses API shape
  if (typeof data?.output_text === 'string' && data.output_text.trim()) return data.output_text
  if (Array.isArray(data?.output)) {
    const txt = data.output
      .flatMap((item) => (Array.isArray(item?.content) ? item.content : []))
      .map((p) => (typeof p?.text === 'string' ? p.text : ''))
      .filter(Boolean)
      .join('\n')
      .trim()
    if (txt) return txt
  }

  return ''
}

function fallbackModelCandidates(modelCode, configuredFallbacks) {
  const current = safeString(modelCode).trim()
  const cfg = Array.isArray(configuredFallbacks)
    ? configuredFallbacks.map((v) => safeString(v).trim()).filter(Boolean)
    : []
  const defaults = cfg.length ? cfg : ['gpt-5-mini', 'gpt-4o-mini']
  const out = []
  if (current) out.push(current)
  defaults.forEach((m) => {
    if (m && m !== current) out.push(m)
  })
  return [...new Set(out)]
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

async function getSceneRuntimeConfig(sceneCode) {
  const key = String(sceneCode || '').trim()
  if (!key) throw new Error('scene_code_missing')
  if (!ADMIN_BACKEND_BASE_URL) throw new Error('ADMIN_BACKEND_BASE_URL missing')
  if (!INTERNAL_SERVICE_TOKEN) throw new Error('INTERNAL_SERVICE_TOKEN missing')

  const now = Date.now()
  const cached = sceneConfigCache.get(key)
  if (cached && cached.expiresAt > now) return cached.value

  const url = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/ai-runtime/scenes/${encodeURIComponent(key)}`
  const resp = await fetch(url, {
    headers: {
      Accept: 'application/json',
      'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
    },
  })
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `scene_runtime_fetch_failed(${resp.status || 0})`
    throw new Error(msg)
  }

  const runtime = data?.data ?? {}
  sceneConfigCache.set(key, {
    value: runtime,
    expiresAt: now + 60_000,
  })
  return runtime
}

async function postEatMemeRecord(payload) {
  if (!ADMIN_BACKEND_BASE_URL || !INTERNAL_SERVICE_TOKEN) return
  const url = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/eat-meme`
  try {
    await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
      },
      body: JSON.stringify(payload || {}),
    })
  } catch {
    // eat-meme logging is best-effort; never block main flow
  }
}

async function postFeatureDataRecord(payload) {
  if (!ADMIN_BACKEND_BASE_URL || !INTERNAL_SERVICE_TOKEN) return
  const url = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/feature-data`
  try {
    await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
      },
      body: JSON.stringify(payload || {}),
    })
  } catch {
    // feature logging is best-effort; never block main flow
  }
}

async function callChatCompletionByScene(sceneCode, { systemPrompt, userPrompt, temperature }) {
  const runtime = await getSceneRuntimeConfig(sceneCode)
  const baseUrl = safeString(runtime?.base_url).replace(/\/$/, '')
  const apiPath = safeString(runtime?.model?.api_path || '/chat/completions')
  const modelCode = safeString(runtime?.model?.model_code)
  const apiKey = safeString(runtime?.api_key)

  if (!baseUrl || !modelCode || !apiKey) {
    throw new Error('scene_runtime_invalid')
  }

  const timeoutMs = Number(runtime?.timeout_ms || 12000)
  const timeoutSec = Math.max(3, Math.ceil(timeoutMs / 1000))
  const resolvedTemperature = Number.isFinite(Number(temperature))
    ? Number(temperature)
    : Number(runtime?.temperature ?? 0.7)

  const requestUrl = buildModelRequestUrl(baseUrl, apiPath)
  const isResponsesApi = /\/responses$/i.test(requestUrl)
  const modelCandidates = fallbackModelCandidates(modelCode, runtime?.fallback_model_codes)
  let lastErr = new Error('model_request_failed')

  for (const candidateModel of modelCandidates) {
    const requestBody = isResponsesApi
      ? {
          model: candidateModel,
          temperature: resolvedTemperature,
          input: [
            {
              role: 'system',
              content: [{ type: 'input_text', text: safeString(systemPrompt) }],
            },
            {
              role: 'user',
              content: [{ type: 'input_text', text: safeString(userPrompt) }],
            },
          ],
        }
      : {
          model: candidateModel,
          temperature: resolvedTemperature,
          messages: [
            { role: 'system', content: systemPrompt },
            { role: 'user', content: userPrompt },
          ],
        }
    const resp = await fetch(requestUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${apiKey}`,
      },
      signal: AbortSignal.timeout(timeoutSec * 1000),
      body: JSON.stringify(requestBody),
    })

    const status = resp.status || 0
    const data = await resp.json().catch(() => ({}))
    if (resp.ok) return data

    const msg = data?.error?.message || data?.message || `model_request_failed(${status})`
    lastErr = new Error(`[${candidateModel}] ${msg}`)
  }

  throw lastErr
}

async function callMessagesByScene(sceneCode, { messages, temperature }) {
  const runtime = await getSceneRuntimeConfig(sceneCode)
  const baseUrl = safeString(runtime?.base_url).replace(/\/$/, '')
  const apiPath = safeString(runtime?.model?.api_path || '/chat/completions')
  const modelCode = safeString(runtime?.model?.model_code)
  const apiKey = safeString(runtime?.api_key)

  if (!baseUrl || !modelCode || !apiKey) {
    throw new Error('scene_runtime_invalid')
  }

  const timeoutMs = Number(runtime?.timeout_ms || 18000)
  const timeoutSec = Math.max(5, Math.ceil(timeoutMs / 1000))
  const resolvedTemperature = Number.isFinite(Number(temperature))
    ? Number(temperature)
    : Number(runtime?.temperature ?? 0.3)

  const requestUrl = buildModelRequestUrl(baseUrl, apiPath)
  const isResponsesApi = /\/responses$/i.test(requestUrl)
  const modelCandidates = fallbackModelCandidates(modelCode, runtime?.fallback_model_codes)
  let lastErr = new Error('model_request_failed')

  for (const candidateModel of modelCandidates) {
    const requestBody = isResponsesApi
      ? {
          model: candidateModel,
          temperature: resolvedTemperature,
          input: Array.isArray(messages)
            ? messages.map((m) => {
                const role = safeString(m?.role) || 'user'
                const content = m?.content
                if (typeof content === 'string') {
                  return { role, content: [{ type: 'input_text', text: content }] }
                }
                if (Array.isArray(content)) {
                  const mapped = content
                    .map((c) => {
                      if (c?.type === 'text' && typeof c?.text === 'string') {
                        return { type: 'input_text', text: c.text }
                      }
                      if (c?.type === 'image_url' && typeof c?.image_url?.url === 'string') {
                        return { type: 'input_image', image_url: c.image_url.url }
                      }
                      return null
                    })
                    .filter(Boolean)
                  return { role, content: mapped.length ? mapped : [{ type: 'input_text', text: '' }] }
                }
                return { role, content: [{ type: 'input_text', text: safeString(content) }] }
              })
            : [],
        }
      : {
          model: candidateModel,
          temperature: resolvedTemperature,
          messages,
        }
    const resp = await fetch(requestUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${apiKey}`,
      },
      signal: AbortSignal.timeout(timeoutSec * 1000),
      body: JSON.stringify(requestBody),
    })

    const status = resp.status || 0
    const data = await resp.json().catch(() => ({}))
    if (resp.ok) return data

    const msg = data?.error?.message || data?.message || `model_request_failed(${status})`
    lastErr = new Error(`[${candidateModel}] ${msg}`)
  }

  throw lastErr
}

async function callImageGenerationByScene(sceneCode, { prompt, size = '1024x1024' }) {
  const runtime = await getSceneRuntimeConfig(sceneCode)
  const baseUrl = safeString(runtime?.base_url).replace(/\/$/, '')
  const apiPath = safeString(runtime?.model?.api_path || '/images/generations')
  const modelCode = safeString(runtime?.model?.model_code)
  const apiKey = safeString(runtime?.api_key)

  if (!baseUrl || !modelCode || !apiKey) {
    throw new Error('scene_runtime_invalid')
  }

  const timeoutMs = Number(runtime?.timeout_ms || 20000)
  const timeoutSec = Math.max(5, Math.ceil(timeoutMs / 1000))
  const requestUrl = buildModelRequestUrl(baseUrl, apiPath)

  const resp = await fetch(requestUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${apiKey}`,
    },
    signal: AbortSignal.timeout(timeoutSec * 1000),
    body: JSON.stringify({
      model: modelCode,
      prompt: safeString(prompt),
      size,
    }),
  })

  const status = resp.status || 0
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    const msg = data?.error?.message || data?.message || `model_image_request_failed(${status})`
    throw new Error(msg)
  }

  return data
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

async function proxyToBigModel({ preferences, locale, contextTags }) {
  const taste = safeString(preferences?.taste)
  const avoid = safeString(preferences?.avoid)
  const people = preferences?.people != null ? Number(preferences?.people) : undefined
  const peopleText = Number.isFinite(people) && people > 0 ? String(people) : '不限'
  const tags = Array.isArray(contextTags)
    ? contextTags.map(safeString).filter(Boolean).slice(0, 48)
    : []
  const tagBlock =
    tags.length > 0
      ? [
          '【推荐上下文】以下为系统生成的推荐上下文标签（来自用户授权与当日自填，与性别无硬编码关联）。请据此微调味型、易消化程度与食材；输出 JSON 中不要复述标签名，也不要输出任何生理或医疗诊断表述。',
          tags.join('、'),
        ].join('\n')
      : ''

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
    tagBlock,
    ``,
    `请生成一份适合“今天晚餐”的菜谱，并确保 ingredients 与 content 相互一致。`,
    `语言 locale: ${safeString(locale) || 'zh-CN'}`,
  ]
    .filter((line) => line !== '')
    .join('\n')

  const data = await callChatCompletionByScene(AI_SCENES.TEXT, {
    systemPrompt: sys,
    userPrompt: user,
    temperature: 0.7,
  })

  const content = extractTextFromModelResponse(data)
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

function normalizeLegacyTodayEatResult(raw, contextTags) {
  const tags = Array.isArray(contextTags)
    ? contextTags.map(safeString).filter(Boolean).slice(0, 8)
    : []
  if (raw && typeof raw === 'object' && safeString(raw.recommended_dish)) {
    return raw
  }
  const title = safeString(raw?.title)
  const content = safeString(raw?.content)
  if (!title || !content) {
    return raw
  }
  const cuisine = safeString(raw?.cuisine).trim()
  const ingredients = Array.isArray(raw?.ingredients)
    ? raw.ingredients.map((x) => safeString(x)).filter(Boolean).slice(0, 12)
    : []
  const tagCandidates = [
    cuisine || '',
    ingredients.length <= 6 ? '快手' : '',
    ingredients.length >= 8 ? '营养均衡' : '',
    '今日推荐',
  ].map((x) => safeString(x).trim()).filter(Boolean)
  const normalizedTags = [...new Set([...(tags.length ? tags : []), ...tagCandidates])].slice(0, 6)
  const ingredientPreview = ingredients.slice(0, 4).join('、')
  const reasonText =
    `结合你这次的口味与场景，今天更适合做「${title}」：` +
    `${cuisine ? `${cuisine}风味，` : ''}` +
    `${ingredientPreview ? `食材以${ingredientPreview}为主，` : ''}` +
    '步骤清晰、落地性强，做完就能稳稳开饭。'

  return {
    recommended_dish: title,
    tags: normalizedTags.length ? normalizedTags : ['今日推荐'],
    reason_text: reasonText,
    destiny_text: '把这一餐认真做好，今天就算过得很不错。',
    alternatives: [],
    title,
    cuisine: cuisine || undefined,
    ingredients,
    content,
    history_saved: Boolean(raw?.history_saved),
  }
}

async function proxyTodayEatViaLaravel(authHeader, body) {
  if (!ADMIN_BACKEND_BASE_URL) {
    throw new Error('ADMIN_BACKEND_BASE_URL missing')
  }
  const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/me/today-eat`
  const resp = await fetch(target, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: authHeader,
    },
    body: JSON.stringify(body ?? {}),
    signal: AbortSignal.timeout(120000),
  })
  const text = await resp.text()
  let data = null
  try {
    data = text ? JSON.parse(text) : null
  } catch {
    data = null
  }
  if (!resp.ok) {
    const msg =
      (data && data.message) ||
      (data && data.error && data.error.message) ||
      `laravel_today_eat_${resp.status || 0}`
    const e = new Error(typeof msg === 'string' ? msg : 'laravel_today_eat_failed')
    e.statusCode = resp.status || 502
    throw e
  }
  return data
}

async function proxyTodayEatRerollViaLaravel(authHeader, body) {
  if (!ADMIN_BACKEND_BASE_URL) {
    throw new Error('ADMIN_BACKEND_BASE_URL missing')
  }
  const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/me/today-eat/reroll`
  const resp = await fetch(target, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: authHeader,
    },
    body: JSON.stringify(body ?? {}),
    signal: AbortSignal.timeout(120000),
  })
  const text = await resp.text()
  let data = null
  try {
    data = text ? JSON.parse(text) : null
  } catch {
    data = null
  }
  if (!resp.ok) {
    const msg =
      (data && data.message) ||
      (data && data.error && data.error.message) ||
      `laravel_today_eat_reroll_${resp.status || 0}`
    const e = new Error(typeof msg === 'string' ? msg : 'laravel_today_eat_reroll_failed')
    e.statusCode = resp.status || 502
    throw e
  }
  return data
}

async function proxyTodayEatSelectAlternativeViaLaravel(authHeader, body) {
  if (!ADMIN_BACKEND_BASE_URL) {
    throw new Error('ADMIN_BACKEND_BASE_URL missing')
  }
  const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/me/today-eat/select-alternative`
  const resp = await fetch(target, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: authHeader,
    },
    body: JSON.stringify(body ?? {}),
    signal: AbortSignal.timeout(120000),
  })
  const text = await resp.text()
  let data = null
  try {
    data = text ? JSON.parse(text) : null
  } catch {
    data = null
  }
  if (!resp.ok) {
    const msg =
      (data && data.message) ||
      (data && data.error && data.error.message) ||
      `laravel_today_eat_select_alt_${resp.status || 0}`
    const e = new Error(typeof msg === 'string' ? msg : 'laravel_today_eat_select_alt_failed')
    e.statusCode = resp.status || 502
    throw e
  }
  return data
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

  const data = await callChatCompletionByScene(AI_SCENES.TEXT, {
    systemPrompt: sys,
    userPrompt: user,
    temperature: 0.7,
  })

  const content = extractTextFromModelResponse(data)
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') throw new Error('bigmodel_response_not_json')

  const recs = Array.isArray(jsonObj.recommendations)
    ? jsonObj.recommendations.map(safeString).filter(Boolean)
    : []

  return { recommendations: recs.slice(0, 10) }
}

async function proxyToSauceRecipe({ sauce_name, locale }) {
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

  const data = await callChatCompletionByScene(AI_SCENES.TEXT, {
    systemPrompt: sys,
    userPrompt: user,
    temperature: 0.7,
  })

  const content = extractTextFromModelResponse(data)
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

  const data = await callChatCompletionByScene(AI_SCENES.TEXT, {
    systemPrompt: sys,
    userPrompt: user.join('\n'),
    temperature: 0.7,
  })

  const content = extractTextFromModelResponse(data)
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
    '请生成家常好菜（总计约 count 道）。',
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

  const data = await callChatCompletionByScene(AI_SCENES.TEXT, {
    systemPrompt: sys,
    userPrompt: user,
    temperature: 0.7,
  })

  const content = extractTextFromModelResponse(data)
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

  const data = await callChatCompletionByScene(AI_SCENES.TEXT, {
    systemPrompt: sys,
    userPrompt: user,
    temperature: 0.7,
  })

  const content = extractTextFromModelResponse(data)
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

async function proxyToRecipeImage({ prompt, size }) {
  const data = await callImageGenerationByScene(AI_SCENES.IMAGE, {
    prompt,
    size: safeString(size || '1024x1024') || '1024x1024',
  })

  const item = Array.isArray(data?.data) ? data.data[0] : null
  const url = safeString(item?.url || item?.image_url || data?.url)
  if (!url) {
    throw new Error('image_url_missing')
  }

  return { url, raw: data }
}

async function proxyToRecognizeIngredients({ imageBase64 }) {
  const base64 = safeString(imageBase64).trim()
  if (!base64) throw new Error('image_base64_missing')

  const systemPrompt = [
    '你是食材识别助手。',
    '请识别图片里的食材，仅返回 JSON。',
    '格式必须为：{"ingredients":["食材1","食材2"]}',
    '不要输出任何额外说明。',
  ].join('\n')

  const data = await callMessagesByScene(AI_SCENES.TEXT, {
    temperature: 0.2,
    messages: [
      { role: 'system', content: systemPrompt },
      {
        role: 'user',
        content: [
          {
            type: 'image_url',
            image_url: { url: `data:image/jpeg;base64,${base64}` },
          },
          {
            type: 'text',
            text: '识别图片中的食材，返回 JSON。',
          },
        ],
      },
    ],
  })

  const content = extractTextFromModelResponse(data)
  const jsonObj = tryParseJsonFromText(content)
  if (!jsonObj || typeof jsonObj !== 'object') {
    throw new Error('ingredients_recognize_not_json')
  }
  const ingredients = normalizeIngredients(jsonObj.ingredients)
  return { ingredients: ingredients.slice(0, 12), raw: data }
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

/** 供 /health/deps 与本地排障：从 BFF 进程视角探测 Laravel 是否可达 */
async function probeAdminBackend() {
  if (!safeString(ADMIN_BACKEND_BASE_URL)) {
    return {
      configured: false,
      error: 'ADMIN_BACKEND_BASE_URL missing',
    }
  }
  const base = ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')
  const upUrl = `${base}/up`
  try {
    const ac = new AbortController()
    const t = setTimeout(() => ac.abort(), 4000)
    let resp
    try {
      resp = await fetch(upUrl, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        signal: ac.signal,
      })
    } finally {
      clearTimeout(t)
    }
    return {
      configured: true,
      laravel_base_url: base,
      up_probe_url: upUrl,
      up_http_status: resp.status,
      up_ok: resp.ok,
    }
  } catch (e) {
    const msg = e instanceof Error ? e.message : 'probe_failed'
    return {
      configured: true,
      laravel_base_url: base,
      up_probe_url: upUrl,
      up_http_status: null,
      up_ok: false,
      error: msg,
    }
  }
}

function isLikelyUpstreamNetworkError(message) {
  return /ECONNREFUSED|ENOTFOUND|ETIMEDOUT|fetch failed|network|UND_ERR_CONNECT|aborted|timeout/i.test(
    message,
  )
}

const server = http.createServer(async (req, res) => {
  try {
    if (!req.url) return json(res, 404, { error: { message: 'not_found' } })

    const { pathname } = getRoute(req)

    if (req.method === 'GET' && pathname === '/health') {
      return json(res, 200, { ok: true })
    }

    // 排障：GET http://<本机IP>:PORT/health/deps —— 看 BFF 能否访问 Laravel /up（与微信登录同源）
    if (req.method === 'GET' && pathname === '/health/deps') {
      const admin = await probeAdminBackend()
      return json(res, 200, { ok: true, bff: true, admin_backend: admin })
    }

    // 小程序运营配置（空对象即用前端 defaultConfig；见 mini-fan-package src/api/config.ts）
    if (req.method === 'GET' && pathname === '/api/miniapp/config') {
      return json(res, 200, {})
    }

    // 首页 Banner 城市与天气：由 admin-backend 内部接口 + 系统配置驱动
    if (req.method === 'GET' && pathname === '/api/miniapp/home-banner-ambient') {
      if (!ADMIN_BACKEND_BASE_URL || !INTERNAL_SERVICE_TOKEN) {
        return json(res, 200, {
          city_name: '深圳',
          weather_text: '晴',
          weather_code: 'sunny',
          weather_icon_emoji: '☀️',
        })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const lat = Number(uFull.searchParams.get('latitude'))
      const lng = Number(uFull.searchParams.get('longitude'))
      const query = new URLSearchParams()
      if (Number.isFinite(lat)) query.set('latitude', String(lat))
      if (Number.isFinite(lng)) query.set('longitude', String(lng))
      const q = query.toString()
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/miniapp/weather/ambient${q ? `?${q}` : ''}`
      try {
        const resp = await fetch(target, {
          headers: {
            Accept: 'application/json',
            'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
          },
        })
        const data = await resp.json().catch(() => null)
        if (!resp.ok || !data || typeof data !== 'object') {
          return json(res, 200, {
            city_name: '深圳',
            weather_text: '晴',
            weather_code: 'sunny',
            weather_icon_emoji: '☀️',
          })
        }
        return json(res, 200, data)
      } catch {
        return json(res, 200, {
          city_name: '深圳',
          weather_text: '晴',
          weather_code: 'sunny',
          weather_icon_emoji: '☀️',
        })
      }
    }

    // 微信登录：POST /api/auth/wechat
    // 小程序：uni.login(provider:'weixin') -> code
    // BFF：把 code 转发给 Laravel -> 返回 access_token + user
    if (req.method === 'POST' && pathname === '/api/auth/wechat') {
      const body = await readJsonBody(req)
      const code = body?.code

      if (!ADMIN_BACKEND_BASE_URL) {
        return json(res, 500, {
          error: { message: 'ADMIN_BACKEND_BASE_URL missing' },
        })
      }

      if (!code || typeof code !== 'string') {
        return json(res, 400, { error: { message: 'code_missing' } })
      }

      try {
        const url = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/auth/wechat`
        const ac = new AbortController()
        const loginTimer = setTimeout(() => ac.abort(), 25_000)
        let resp
        try {
          resp = await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
            body: JSON.stringify({ code }),
            signal: ac.signal,
          })
        } finally {
          clearTimeout(loginTimer)
        }

        const rawText = await resp.text()
        let data
        try {
          data = rawText ? JSON.parse(rawText) : {}
        } catch {
          data = {
            error: {
              message: 'laravel_non_json_response',
              detail: rawText.replace(/\s+/g, ' ').slice(0, 400),
            },
          }
        }

        if (!resp.ok) {
          const emptyPayload =
            data == null ||
            (typeof data === 'object' &&
              !Array.isArray(data) &&
              Object.keys(data).length === 0)
          if (emptyPayload) {
            let targetHint = url
            try {
              const u = new URL(url)
              targetHint = `${u.hostname}${u.port ? `:${u.port}` : ''}`
            } catch {
              /* keep url */
            }
            console.error(
              '[bff] /api/auth/wechat upstream not ok, empty body',
              'status=',
              resp.status,
              'bytes=',
              rawText.length,
              'target=',
              targetHint,
            )
            data = {
              error: {
                message: 'laravel_upstream_empty_or_unreadable',
                detail: `http_status=${resp.status} body_len=${rawText.length} laravel_host=${targetHint} 请检查 ADMIN_BACKEND_BASE_URL 对应服务是否已启动、PHP-FPM/Nginx 是否正常`,
              },
            }
          }
          // 微信端对 HTTP 502/503 常不返回 response data，小程序只能看到「无响应体」；统一用 500 保留 JSON
          let outStatus = resp.status || 500
          if (outStatus === 502 || outStatus === 503) outStatus = 500
          return json(res, outStatus, data)
        }

        return json(res, 200, data)
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'internal_error'
        console.error('[bff] /api/auth/wechat fetch error', msg)
        const upstream = isLikelyUpstreamNetworkError(msg)
        return json(res, 500, {
          error: {
            message: upstream ? 'laravel_unreachable' : 'bff_auth_error',
            detail: msg,
            hint:
              '在本机启动 admin-backend（如 cd admin-backend && php artisan serve），并核对 bff-server/.env 的 ADMIN_BACKEND_BASE_URL 与端口一致；BFF 若在 Docker 内勿用 127.0.0.1，改用 host.docker.internal。排障可访问 GET /health/deps',
          },
        })
      }
    }

    // 小程序「我的」资料与每日状态：透传 Laravel /api/me/*
    if (pathname.startsWith('/api/me')) {
      if (!ADMIN_BACKEND_BASE_URL) {
        return json(res, 500, {
          error: { message: 'ADMIN_BACKEND_BASE_URL missing' },
        })
      }
      if (!['GET', 'PUT', 'PATCH', 'POST'].includes(req.method)) {
        return json(res, 405, { error: { message: 'method_not_allowed' } })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const search = uFull.search || ''
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}${pathname}${search}`
      const headers = {
        Accept: 'application/json',
      }
      const auth = req.headers.authorization
      if (auth) {
        headers.Authorization = auth
      }
      let body
      if (req.method === 'PUT' || req.method === 'PATCH' || req.method === 'POST') {
        const jsonBody = await readJsonBody(req)
        headers['Content-Type'] = 'application/json'
        body = JSON.stringify(jsonBody ?? {})
      }
      try {
        const resp = await fetch(target, { method: req.method, headers, body })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
      }
    }

    // 用户画像 REST：透传 Laravel /api/user/*（含 POST 问卷落库）
    if (pathname.startsWith('/api/user')) {
      if (!ADMIN_BACKEND_BASE_URL) {
        return json(res, 500, {
          error: { message: 'ADMIN_BACKEND_BASE_URL missing' },
        })
      }
      if (!['GET', 'POST', 'PUT', 'PATCH'].includes(req.method)) {
        return json(res, 405, { error: { message: 'method_not_allowed' } })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const search = uFull.search || ''
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}${pathname}${search}`
      const headers = {
        Accept: 'application/json',
      }
      const auth = req.headers.authorization
      if (auth) {
        headers.Authorization = auth
      }
      let body
      if (req.method === 'POST' || req.method === 'PUT' || req.method === 'PATCH') {
        const jsonBody = await readJsonBody(req)
        headers['Content-Type'] = 'application/json'
        body = JSON.stringify(jsonBody ?? {})
      }
      try {
        const resp = await fetch(target, { method: req.method, headers, body })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
      }
    }

    // 微信支付：透传 Laravel /api/pay/*（创建订单、预支付、查询）
    if (pathname.startsWith('/api/pay')) {
      if (!ADMIN_BACKEND_BASE_URL) {
        return json(res, 500, {
          error: { message: 'ADMIN_BACKEND_BASE_URL missing' },
        })
      }
      if (!['GET', 'POST'].includes(req.method)) {
        return json(res, 405, { error: { message: 'method_not_allowed' } })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const search = uFull.search || ''
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}${pathname}${search}`
      const headers = {
        Accept: 'application/json',
      }
      const auth = req.headers.authorization
      if (auth) {
        headers.Authorization = auth
      }
      let body
      if (req.method === 'POST') {
        const jsonBody = await readJsonBody(req)
        headers['Content-Type'] = 'application/json'
        body = JSON.stringify(jsonBody ?? {})
      }
      try {
        const resp = await fetch(target, { method: req.method, headers, body })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
      }
    }

    // Laravel 业务 API 透传（收藏/历史/灵感）：需 Authorization: Bearer（未登录接口可不带）
    if (
      pathname.startsWith('/api/favorites') ||
      pathname.startsWith('/api/histories') ||
      pathname.startsWith('/api/circle') ||
      pathname.startsWith('/api/inspiration')
    ) {
      if (!ADMIN_BACKEND_BASE_URL) {
        return json(res, 500, {
          error: { message: 'ADMIN_BACKEND_BASE_URL missing' },
        })
      }
      if (!['GET', 'POST', 'DELETE'].includes(req.method)) {
        return json(res, 405, { error: { message: 'method_not_allowed' } })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const search = uFull.search || ''
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}${pathname}${search}`
      const headers = {
        Accept: 'application/json',
      }
      const auth = req.headers.authorization
      if (auth) {
        headers.Authorization = auth
      }
      let body
      if (req.method === 'POST') {
        const jsonBody = await readJsonBody(req)
        headers['Content-Type'] = 'application/json'
        body = JSON.stringify(jsonBody)
      }
      try {
        const resp = await fetch(target, { method: req.method, headers, body })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
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

        await postFeatureDataRecord({
          feature_type: 'gallery',
          sub_type: 'list',
          status: 'success',
          title: '图鉴列表',
          input_payload: { limit: 50 },
          result_summary: `items:${items.length}`,
          requested_at: new Date().toISOString(),
        })

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
        await postFeatureDataRecord({
          feature_type: 'gallery',
          sub_type: 'list',
          status: 'failed',
          title: '图鉴列表',
          error_message: e instanceof Error ? e.message : 'gallery_list_failed',
          requested_at: new Date().toISOString(),
        })
        const msg = e instanceof Error ? e.message : 'internal_error'
        return json(res, 500, { error: { message: msg } })
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/today-eat') {
      const body = await readJsonBody(req)
      const preferences = body?.preferences ?? {}
      const locale = body?.locale ?? 'zh-CN'
      const contextTagsRaw = Array.isArray(body?.context_tags)
        ? body.context_tags
        : Array.isArray(body?.contextTags)
          ? body.contextTags
          : []
      const contextTags = contextTagsRaw.map(safeString).filter(Boolean).slice(0, 48)
      const realtimeContext =
        body?.realtime_context && typeof body.realtime_context === 'object'
          ? body.realtime_context
          : null
      try {
        const auth = getAuthHeader(req)
        let result
        if (auth && ADMIN_BACKEND_BASE_URL) {
          try {
            result = await proxyTodayEatViaLaravel(auth, {
              preferences,
              locale,
              context_tags: contextTags,
              realtime_context: realtimeContext,
            })
          } catch (laravelErr) {
            console.warn('[bff today-eat] Laravel recommend failed, fallback to legacy bigmodel', laravelErr)
            result = await proxyToBigModel({ preferences, locale, contextTags })
            result = normalizeLegacyTodayEatResult(result, contextTags)
          }
        } else {
          result = await proxyToBigModel({ preferences, locale, contextTags })
          result = normalizeLegacyTodayEatResult(result, contextTags)
        }

        const dishTitle = safeString(result?.title || result?.recommended_dish)
        await postEatMemeRecord({
          channel: 'android_app',
          taste: safeString(preferences?.taste),
          avoid: safeString(preferences?.avoid),
          people: Number.isFinite(Number(preferences?.people)) ? Number(preferences?.people) : null,
          result_title: dishTitle,
          result_cuisine: safeString(result?.cuisine),
          result_ingredients: Array.isArray(result?.ingredients) ? result.ingredients : [],
          result_content: safeString(result?.content),
          status: 'success',
          requested_at: new Date().toISOString(),
        })
        await postFeatureDataRecord({
          feature_type: 'custom_cuisine',
          sub_type: 'generate',
          status: 'success',
          title: dishTitle,
          input_payload: { preferences, locale, context_tags: contextTags, realtime_context: realtimeContext },
          result_summary: safeString(result?.reason_text || result?.content).slice(0, 200),
          result_payload: {
            cuisine: safeString(result?.cuisine),
            ingredients: normalizeIngredients(result?.ingredients),
            tags: Array.isArray(result?.tags) ? result.tags : [],
            alternatives: Array.isArray(result?.alternatives) ? result.alternatives : [],
          },
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postEatMemeRecord({
          channel: 'android_app',
          taste: safeString(preferences?.taste),
          avoid: safeString(preferences?.avoid),
          people: Number.isFinite(Number(preferences?.people)) ? Number(preferences?.people) : null,
          status: 'failed',
          error_message: e instanceof Error ? e.message : 'generate_failed',
          requested_at: new Date().toISOString(),
        })
        await postFeatureDataRecord({
          feature_type: 'custom_cuisine',
          sub_type: 'generate',
          status: 'failed',
          input_payload: { preferences, locale, context_tags: contextTags, realtime_context: realtimeContext },
          error_message: e instanceof Error ? e.message : 'generate_failed',
          requested_at: new Date().toISOString(),
        })
        console.warn('[bff today-eat] Laravel + legacy 均失败，返回静态兜底 JSON', e)
        const fallback = {
          recommended_dish: '番茄鸡蛋面',
          tags: contextTags.length ? contextTags.slice(0, 8) : ['家常', '快手'],
          reason_text:
            '网络或小模型暂时不可用，先给你一份稳妥的家常方案顶一顶；食材常见，步骤短，适合今晚快速落桌。',
          destiny_text: '先吃饱，我们再慢慢变好。',
          alternatives: ['清蒸鲈鱼', '冬瓜排骨汤', '白灼菜心'],
          title: '番茄鸡蛋面',
          cuisine: '家常菜',
          ingredients: ['番茄', '鸡蛋', '面条', '葱'],
          content:
            '1）番茄切块，鸡蛋打散；热锅少油先炒蛋盛起。\n2）再少油炒番茄出汁，回锅鸡蛋，盐糖调味。\n3）另煮面条，沥干后与浇头拌匀即可。',
          history_saved: false,
          recommendation_fallback: true,
        }
        return json(res, 200, fallback)
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/today-eat/reroll') {
      const body = await readJsonBody(req)
      const preferences = body?.preferences ?? {}
      const locale = body?.locale ?? 'zh-CN'
      const recommendationSessionId = safeString(body?.recommendation_session_id)
      const realtimeContext =
        body?.realtime_context && typeof body.realtime_context === 'object'
          ? body.realtime_context
          : null
      if (!recommendationSessionId) {
        return json(res, 400, { error: { message: 'recommendation_session_id_required' } })
      }
      const auth = getAuthHeader(req)
      if (!auth || !ADMIN_BACKEND_BASE_URL) {
        return json(res, 401, { error: { message: 'reroll_requires_auth' } })
      }
      try {
        const result = await proxyTodayEatRerollViaLaravel(auth, {
          recommendation_session_id: recommendationSessionId,
          preferences,
          locale,
          realtime_context: realtimeContext,
        })
        const dishTitle = safeString(result?.title || result?.recommended_dish)
        await postEatMemeRecord({
          channel: 'android_app',
          taste: safeString(preferences?.taste),
          avoid: safeString(preferences?.avoid),
          people: Number.isFinite(Number(preferences?.people)) ? Number(preferences?.people) : null,
          result_title: dishTitle,
          result_cuisine: safeString(result?.cuisine),
          result_ingredients: Array.isArray(result?.ingredients) ? result.ingredients : [],
          result_content: safeString(result?.content),
          status: 'success',
          requested_at: new Date().toISOString(),
        })
        await postFeatureDataRecord({
          feature_type: 'custom_cuisine',
          sub_type: 'reroll',
          status: 'success',
          title: dishTitle,
          input_payload: { preferences, locale, recommendation_session_id: recommendationSessionId, realtime_context: realtimeContext },
          result_summary: safeString(result?.reason_text || result?.content).slice(0, 200),
          result_payload: {
            cuisine: safeString(result?.cuisine),
            ingredients: normalizeIngredients(result?.ingredients),
            tags: Array.isArray(result?.tags) ? result.tags : [],
            alternatives: Array.isArray(result?.alternatives) ? result.alternatives : [],
          },
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postEatMemeRecord({
          channel: 'android_app',
          taste: safeString(preferences?.taste),
          avoid: safeString(preferences?.avoid),
          people: Number.isFinite(Number(preferences?.people)) ? Number(preferences?.people) : null,
          status: 'failed',
          error_message: e instanceof Error ? e.message : 'reroll_failed',
          requested_at: new Date().toISOString(),
        })
        await postFeatureDataRecord({
          feature_type: 'custom_cuisine',
          sub_type: 'reroll',
          status: 'failed',
          input_payload: { preferences, locale, recommendation_session_id: recommendationSessionId, realtime_context: realtimeContext },
          error_message: e instanceof Error ? e.message : 'reroll_failed',
          requested_at: new Date().toISOString(),
        })
        const msg = e instanceof Error ? e.message : 'reroll_failed'
        const code = e && typeof e.statusCode === 'number' ? e.statusCode : 502
        return json(res, code >= 400 && code < 600 ? code : 502, { error: { message: msg } })
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/today-eat/select-alternative') {
      const body = await readJsonBody(req)
      const preferences = body?.preferences ?? {}
      const locale = body?.locale ?? 'zh-CN'
      const recommendationSessionId = safeString(body?.recommendation_session_id)
      const selectedDish = safeString(body?.selected_dish)
      const realtimeContext =
        body?.realtime_context && typeof body.realtime_context === 'object'
          ? body.realtime_context
          : null
      if (!recommendationSessionId) {
        return json(res, 400, { error: { message: 'recommendation_session_id_required' } })
      }
      if (!selectedDish) {
        return json(res, 400, { error: { message: 'selected_dish_required' } })
      }
      const auth = getAuthHeader(req)
      if (!auth || !ADMIN_BACKEND_BASE_URL) {
        return json(res, 401, { error: { message: 'select_alternative_requires_auth' } })
      }
      try {
        const result = await proxyTodayEatSelectAlternativeViaLaravel(auth, {
          recommendation_session_id: recommendationSessionId,
          selected_dish: selectedDish,
          preferences,
          locale,
          realtime_context: realtimeContext,
        })
        const dishTitle = safeString(result?.title || result?.recommended_dish)
        await postEatMemeRecord({
          channel: 'android_app',
          taste: safeString(preferences?.taste),
          avoid: safeString(preferences?.avoid),
          people: Number.isFinite(Number(preferences?.people)) ? Number(preferences?.people) : null,
          result_title: dishTitle,
          result_cuisine: safeString(result?.cuisine),
          result_ingredients: Array.isArray(result?.ingredients) ? result.ingredients : [],
          result_content: safeString(result?.content),
          status: 'success',
          requested_at: new Date().toISOString(),
        })
        await postFeatureDataRecord({
          feature_type: 'custom_cuisine',
          sub_type: 'alternative_selected',
          status: 'success',
          title: dishTitle,
          input_payload: {
            preferences,
            locale,
            recommendation_session_id: recommendationSessionId,
            selected_dish: selectedDish,
            realtime_context: realtimeContext,
          },
          result_summary: safeString(result?.reason_text || result?.content).slice(0, 200),
          result_payload: {
            cuisine: safeString(result?.cuisine),
            ingredients: normalizeIngredients(result?.ingredients),
            tags: Array.isArray(result?.tags) ? result.tags : [],
            alternatives: Array.isArray(result?.alternatives) ? result.alternatives : [],
          },
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postEatMemeRecord({
          channel: 'android_app',
          taste: safeString(preferences?.taste),
          avoid: safeString(preferences?.avoid),
          people: Number.isFinite(Number(preferences?.people)) ? Number(preferences?.people) : null,
          status: 'failed',
          error_message: e instanceof Error ? e.message : 'select_alternative_failed',
          requested_at: new Date().toISOString(),
        })
        await postFeatureDataRecord({
          feature_type: 'custom_cuisine',
          sub_type: 'alternative_selected',
          status: 'failed',
          input_payload: {
            preferences,
            locale,
            recommendation_session_id: recommendationSessionId,
            selected_dish: selectedDish,
            realtime_context: realtimeContext,
          },
          error_message: e instanceof Error ? e.message : 'select_alternative_failed',
          requested_at: new Date().toISOString(),
        })
        const msg = e instanceof Error ? e.message : 'select_alternative_failed'
        const code = e && typeof e.statusCode === 'number' ? e.statusCode : 502
        return json(res, code >= 400 && code < 600 ? code : 502, { error: { message: msg } })
      }
    }

    if (req.method === 'GET' && pathname === '/api/eat-meme') {
      if (!ADMIN_BACKEND_BASE_URL || !INTERNAL_SERVICE_TOKEN) {
        return json(res, 500, { error: { message: 'eat_meme_not_configured' } })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const search = uFull.search || ''
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/eat-meme${search}`
      try {
        const resp = await fetch(target, {
          headers: {
            Accept: 'application/json',
            'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
          },
        })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
      }
    }

    if (req.method === 'GET' && pathname === '/api/feature-data') {
      if (!ADMIN_BACKEND_BASE_URL || !INTERNAL_SERVICE_TOKEN) {
        return json(res, 500, { error: { message: 'feature_data_not_configured' } })
      }
      const uFull = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`)
      const search = uFull.search || ''
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/feature-data${search}`
      try {
        const resp = await fetch(target, {
          headers: {
            Accept: 'application/json',
            'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
          },
        })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
      }
    }

    if (req.method === 'DELETE' && pathname.startsWith('/api/eat-meme/')) {
      if (!ADMIN_BACKEND_BASE_URL || !INTERNAL_SERVICE_TOKEN) {
        return json(res, 500, { error: { message: 'eat_meme_not_configured' } })
      }
      const id = pathname.replace('/api/eat-meme/', '').trim()
      if (!/^\d+$/.test(id)) {
        return json(res, 400, { error: { message: 'invalid_id' } })
      }
      const target = `${ADMIN_BACKEND_BASE_URL.replace(/\/$/, '')}/api/internal/eat-meme/${id}`
      try {
        const resp = await fetch(target, {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            'X-Internal-Token': INTERNAL_SERVICE_TOKEN,
          },
        })
        const text = await resp.text()
        res.writeHead(resp.status, {
          'Content-Type': 'application/json; charset=utf-8',
          'Cache-Control': 'no-store',
        })
        res.end(text)
        return
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'proxy_failed'
        return json(res, 502, { error: { message: msg } })
      }
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

      try {
        const result = await proxyToFortune(payload)
        const fortuneResult =
          result && typeof result === 'object' && result.result && typeof result.result === 'object'
            ? result.result
            : null
        const fortuneTitle = safeString(fortuneResult?.dishName || fortuneResult?.dish_name || '')
        const fortuneSummary = safeString(
          fortuneResult?.description || fortuneResult?.reason || fortuneResult?.mysticalMessage || '',
        )
        await postFeatureDataRecord({
          feature_type: 'fortune_cooking',
          sub_type: safeString(fortuneType) || 'generate',
          status: 'success',
          title: fortuneTitle,
          input_payload: payload,
          result_summary: fortuneSummary.slice(0, 200),
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postFeatureDataRecord({
          feature_type: 'fortune_cooking',
          sub_type: safeString(fortuneType) || 'generate',
          status: 'failed',
          input_payload: payload,
          error_message: e instanceof Error ? e.message : 'fortune_failed',
          requested_at: new Date().toISOString(),
        })
        throw e
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/sauce-recommend') {
      const body = await readJsonBody(req)
      const preferences = body?.preferences ?? body?.pref ?? {}
      const locale = body?.locale ?? 'zh-CN'
      try {
        const result = await proxyToSauceRecommend({ preferences, locale })
        const recList = Array.isArray(result?.recommendations) ? result.recommendations : []
        const recTitle = recList.length ? `酱料推荐（${recList.length}个）` : '酱料推荐'
        const recSummary = recList
          .slice(0, 8)
          .map((x) => safeString(x).trim())
          .filter(Boolean)
          .join('、')
        await postFeatureDataRecord({
          feature_type: 'sauce_design',
          sub_type: 'recommend',
          status: 'success',
          title: recTitle,
          input_payload: { preferences, locale },
          result_summary: recSummary.slice(0, 200),
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postFeatureDataRecord({
          feature_type: 'sauce_design',
          sub_type: 'recommend',
          status: 'failed',
          input_payload: { preferences, locale },
          error_message: e instanceof Error ? e.message : 'sauce_recommend_failed',
          requested_at: new Date().toISOString(),
        })
        throw e
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/sauce-recipe') {
      const body = await readJsonBody(req)
      const sauce_name = body?.sauce_name ?? body?.sauceName
      const locale = body?.locale ?? 'zh-CN'
      try {
        const result = await proxyToSauceRecipe({ sauce_name, locale })
        const recipe = result?.recipe && typeof result.recipe === 'object' ? result.recipe : null
        const recipeName = safeString(recipe?.name || sauce_name)
        const desc = recipe?.description != null ? safeString(recipe.description) : ''
        const ingLine = Array.isArray(recipe?.ingredients)
          ? recipe.ingredients.map(safeString).filter(Boolean).slice(0, 10).join('、')
          : ''
        const stepsArr = Array.isArray(recipe?.steps) ? recipe.steps : []
        let firstStep = ''
        if (typeof stepsArr[0] === 'string') {
          firstStep = safeString(stepsArr[0])
        } else if (stepsArr[0] && typeof stepsArr[0] === 'object' && stepsArr[0].description != null) {
          firstStep = safeString(stepsArr[0].description)
        }
        const recipeSummary = desc || ingLine || firstStep || recipeName
        await postFeatureDataRecord({
          feature_type: 'sauce_design',
          sub_type: 'recipe',
          status: 'success',
          title: recipeName,
          input_payload: { sauce_name, locale },
          result_summary: recipeSummary.slice(0, 200),
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postFeatureDataRecord({
          feature_type: 'sauce_design',
          sub_type: 'recipe',
          status: 'failed',
          title: safeString(sauce_name),
          input_payload: { sauce_name, locale },
          error_message: e instanceof Error ? e.message : 'sauce_recipe_failed',
          requested_at: new Date().toISOString(),
        })
        throw e
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/table-menu') {
      const body = await readJsonBody(req)
      const config = body?.config ?? {}
      const locale = body?.locale ?? 'zh-CN'
      try {
        const result = await proxyToTableMenu({ config, locale })
        const dishList = Array.isArray(result?.dishes) ? result.dishes : []
        const n = dishList.length
        const tableTitle = n ? `家常好菜（${n}道）` : '家常好菜'
        const nameLine = dishList
          .slice(0, 4)
          .map(d => safeString(d?.name).trim())
          .filter(Boolean)
          .join('、')
        const firstDesc =
          dishList[0] && typeof dishList[0].description === 'string'
            ? safeString(dishList[0].description).trim()
            : ''
        const tableSummary =
          nameLine + (n > 4 ? ` 等共${n}道` : '') || firstDesc
        await postFeatureDataRecord({
          feature_type: 'table_menu',
          sub_type: 'generate',
          status: 'success',
          title: tableTitle,
          input_payload: { config, locale },
          result_summary: tableSummary.slice(0, 200),
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postFeatureDataRecord({
          feature_type: 'table_menu',
          sub_type: 'generate',
          status: 'failed',
          input_payload: { config, locale },
          error_message: e instanceof Error ? e.message : 'table_menu_failed',
          requested_at: new Date().toISOString(),
        })
        throw e
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/table-dish-recipe') {
      const body = await readJsonBody(req)
      const payload = {
        dish_name: body?.dish_name,
        dish_description: body?.dish_description,
        category: body?.category,
        locale: body?.locale ?? 'zh-CN',
      }
      try {
        const result = await proxyToTableDishRecipe(payload)
        const recipeName = safeString(result?.name || payload.dish_name)
        const ingLine = Array.isArray(result?.ingredients)
          ? result.ingredients.map(safeString).filter(Boolean).slice(0, 8).join('、')
          : ''
        const stepsArr = Array.isArray(result?.steps) ? result.steps : []
        const firstStep =
          stepsArr[0] && typeof stepsArr[0] === 'object' && stepsArr[0].description != null
            ? safeString(stepsArr[0].description)
            : ''
        const recipeSummary = ingLine || firstStep || recipeName
        await postFeatureDataRecord({
          feature_type: 'table_menu',
          sub_type: 'dish_recipe',
          status: 'success',
          title: recipeName || safeString(payload.dish_name),
          input_payload: payload,
          result_summary: recipeSummary.slice(0, 200),
          requested_at: new Date().toISOString(),
        })
        return json(res, 200, result)
      } catch (e) {
        await postFeatureDataRecord({
          feature_type: 'table_menu',
          sub_type: 'dish_recipe',
          status: 'failed',
          title: safeString(payload.dish_name),
          input_payload: payload,
          error_message: e instanceof Error ? e.message : 'table_dish_recipe_failed',
          requested_at: new Date().toISOString(),
        })
        throw e
      }
    }

    if (req.method === 'POST' && pathname === '/api/ai/recipe-image') {
      const body = await readJsonBody(req)
      const prompt = safeString(body?.prompt).trim()
      if (!prompt) {
        return json(res, 400, { error: { message: 'prompt_missing' } })
      }
      const result = await proxyToRecipeImage({
        prompt,
        size: body?.size,
      })
      return json(res, 200, result)
    }

    if (req.method === 'POST' && pathname === '/api/ai/ingredients-recognize') {
      const body = await readJsonBody(req)
      const result = await proxyToRecognizeIngredients({
        imageBase64: body?.image_base64,
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
  if (RAW_ADMIN_BACKEND_URL) {
    const trimmed = String(RAW_ADMIN_BACKEND_URL).trim().replace(/\/$/, '')
    if (trimmed.endsWith('/api')) {
      console.warn(
        '[bff] ADMIN_BACKEND_BASE_URL 不应以 /api 结尾（应为 Laravel 根地址），已自动去掉 /api：',
        ADMIN_BACKEND_BASE_URL,
      )
    }
  }
  if (!RAW_ADMIN_BACKEND_URL && ADMIN_BACKEND_BASE_URL) {
    console.log(
      '[bff] ADMIN_BACKEND_BASE_URL 未配置，已使用本地默认',
      ADMIN_BACKEND_BASE_URL,
      '（生产环境请设置 .env 并设 NODE_ENV=production）',
    )
  }
  void (async () => {
    try {
      const admin = await probeAdminBackend()
      if (!admin.configured) {
        console.warn('[bff] ADMIN_BACKEND_BASE_URL 未设置，微信登录与 /api/me 代理不可用')
        return
      }
      if (!admin.up_ok) {
        console.warn(
          '[bff] 无法访问 Laravel:',
          admin.laravel_base_url,
          admin.error ? `(${admin.error})` : `HTTP ${admin.up_http_status}`,
          '— 请先: cd admin-backend && php artisan serve（默认 http://127.0.0.1:8000）',
        )
        return
      }
      console.log('[bff] Laravel 探测成功', admin.laravel_base_url)
    } catch (e) {
      console.warn('[bff] Laravel 启动探测异常', e)
    }
  })()
})

