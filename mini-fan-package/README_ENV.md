# 小程序多环境配置说明（mini-fan-package）

## 核心思路

- **唯一开关**：`config/env/index.ts` 里的 `ENV_MODE`（`'dev' | 'test' | 'prod'`）。
- **统一出口**：业务代码通过 `@/constants` 读取 `API_BASE_URL`、`APP_CONFIG_URL`、`REQUEST_TIMEOUT_MS` 等；`src/api/http.ts` 的 `request()` 自动拼接域名。
- **与 `.env` 的关系**：Supabase 密钥仍在 `.env` 的 `VITE_SUPABASE_*`；**Laravel API 域名在 `config/env` 各文件维护**，不再依赖 `VITE_API_BASE_URL` 驱动业务。

---

## 1. 本地开发

1. 默认 **`ENV_MODE = 'dev'`**（见 `config/env/index.ts`），`config/env/dev.ts` 默认指向 `http://127.0.0.1:8000`（与本仓库 `php artisan serve` 默认端口一致）。
2. 若本机 Laravel 端口不同，改 `dev.ts` 里的 `baseUrl` / `uploadUrl` / `downloadUrl`。
3. **真机预览**：手机无法访问你电脑上的 `127.0.0.1`，请把 `dev` 里的地址改成电脑的 **局域网 IP**（例如 `http://192.168.0.108:8000`），手机与电脑同一 Wi‑Fi。
4. 微信开发者工具：**详情 → 本地设置** 勾选「不校验合法域名、web-view、TLS 版本以及 HTTPS 证书」（仅开发期）。

---

## 2. 联调 / 测试环境

1. 将 `ENV_MODE` 改为 **`'test'`**。
2. 当前 `config/env/test.ts` 已配置为 `https://fanf.yajianjs.com`；若后续有独立测试域名，再替换为对应地址。
3. 重新编译小程序后再预览。

---

## 3. 上传体验版 / 正式版前

1. 将 **`ENV_MODE` 改为 `'prod'`**（`config/env/index.ts`）。
2. 在 `config/env/prod.ts` 中确认 **`https://` 生产域名** 与线上一致；当前已配置为 `https://fanf.yajianjs.com`。
3. 确认 **全部为 https**（上传、下载若独立域名也需 https）。
4. 重新执行 **`npm run build:mp-weixin`**，用微信开发者工具上传。
5. 控制台应看到类似：`[mini-fan] env=prod baseUrl=https://...`（生产环境日志较短）。

---

## 4. 上传正式版前检查项（简表）

- [ ] `config/env/index.ts` 中 **`ENV_MODE === 'prod'`**
- [ ] `prod.ts` 中域名 **无** `localhost` / `127.0.0.1` / 内网 IP / 明显测试域名
- [ ] 域名均为 **https**（与微信后台合法域名一致）
- [ ] 已 **重新编译** 再上传（改环境后必须重新 build）
- [ ] 在真机体验版上抽测登录、核心接口

---

## 5. 微信公众平台：合法域名清单（待配置）

将 **实际生产主机**（与 `prod.ts` 一致）配置到小程序后台（均为 **https**，**wss** 用于 WebSocket）：

| 类型 | 说明 |
|------|------|
| **request 合法域名** | Laravel API 根域名，如 `https://fanf.yajianjs.com` |
| **uploadFile 合法域名** | 若与 API 同域，与上相同；若分域名则单独添加 |
| **downloadFile 合法域名** | 同上 |
| **socket 合法域名** | 若使用 WebSocket，配置 **wss://** 主机（与 `prod.ts` 的 `wsUrl` 一致） |

说明：域名必须备案且与证书一致；路径不用写，只配主机。

---

## 6. 目录说明

| 文件 | 作用 |
|------|------|
| `config/env/base.ts` | 公共类型、默认值（超时、默认 header 等） |
| `config/env/dev.ts` | 本地开发 |
| `config/env/test.ts` | 测试 |
| `config/env/prod.ts` | 生产（体验版/正式版） |
| `config/env/index.ts` | **`ENV_MODE` 开关**与合并后的 `config` |
| `src/utils/env.ts` | 环境工具与启动日志 |
| `src/constants/index.ts` | 对业务导出 `API_BASE_URL` 等 |

---

## 7. 上传 / 下载 / WebSocket

当前仓库小程序侧 **未发现** `uni.uploadFile` / `uni.downloadFile` / `uni.connectSocket` 调用；若后续增加，请使用 `@/utils/env` 的 `resolveUploadUrl` / `resolveDownloadUrl`，并将域名与 `config/env` 中 `uploadUrl` / `downloadUrl` / `wsUrl` 对齐。

---

## 8. 调试日志

- 开发（`dev`/`test` 或 `debugLog: true`）：启动时在控制台打印当前环境、baseUrl、uploadUrl 等。
- 生产（`prod` 且 `debugLog: false`）：仅打印一行关键信息，减少噪音。
