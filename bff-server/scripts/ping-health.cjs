#!/usr/bin/env node
/**
 * 本机探测 BFF 是否已监听（读 bff-server/.env 的 PORT，默认 8787）
 * 用法：cd bff-server && node scripts/ping-health.cjs
 */
const fs = require('fs')
const http = require('http')
const path = require('path')

const envPath = path.join(__dirname, '..', '.env')
let port = 8787
try {
  if (fs.existsSync(envPath)) {
    const raw = fs.readFileSync(envPath, 'utf8')
    for (const line of raw.split('\n')) {
      const s = String(line).trim()
      if (!s || s.startsWith('#')) continue
      const m = s.match(/^PORT=(.+)$/)
      if (m) {
        const v = Number(String(m[1]).trim().replace(/^["']|["']$/g, ''))
        if (Number.isFinite(v) && v > 0) port = v
        break
      }
    }
  }
} catch {
  /* use default */
}

const req = http.get(`http://127.0.0.1:${port}/health`, (res) => {
  let body = ''
  res.on('data', (c) => {
    body += c
  })
  res.on('end', () => {
    const ok = res.statusCode === 200
    console.log(ok ? `[bff] OK http://127.0.0.1:${port}/health → ${res.statusCode}` : `[bff] 异常 status=${res.statusCode}`)
    if (body) console.log(body)
    process.exit(ok ? 0 : 1)
  })
})

req.on('error', (e) => {
  console.error(`[bff] 连不上 http://127.0.0.1:${port}（请先在本目录执行 npm start）`, e.message)
  process.exit(1)
})

req.setTimeout(4000, () => {
  req.destroy()
  console.error(`[bff] 超时 ${port}`)
  process.exit(1)
})
