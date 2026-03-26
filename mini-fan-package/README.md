# fan-miniapp-phase1

饭否微信小程序用户端 — **第一阶段可运行骨架**（独立工程，与根目录 Web 项目分离）。

## 本地运行（微信开发者工具）

```bash
cd fan-miniapp-phase1
npm install
npm run dev:mp-weixin
```

**重要：`app.json` 在编译后才会生成，不要导入 `fan-miniapp-phase1` 源码根目录。**

1. 先保持终端里 `npm run dev:mp-weixin` 在跑（或先执行一次 `npm run build:mp-weixin`）。
2. 打开 **微信开发者工具** → **导入项目**。
3. **项目目录** 必须选（二选一）：
   - 开发：`fan-miniapp-phase1/dist/dev/mp-weixin`
   - 仅构建：`fan-miniapp-phase1/dist/build/mp-weixin`

若选错成源码根目录，会报错：`在项目根目录未找到 app.json`。

首次请在 `src/manifest.json` 的 `mp-weixin.appid` 填入你的小程序 AppID；无号时可使用测试号。

### Supabase（收藏 / 历史真实数据）

1. 在 `fan-miniapp-phase1` 根目录复制 `.env.example` 为 `.env`，填入与 Web 相同的 `VITE_SUPABASE_URL`、`VITE_SUPABASE_ANON_KEY`。
2. 重新执行 `npm run dev:mp-weixin`。
3. 登录页使用 **邮箱 + 密码** 登录（与 Web 端同一 Supabase 用户），即可在收藏/历史页拉取同账号数据。
4. 微信公众平台 → 开发 → 开发管理 → **服务器域名**，将 `https://<你的项目>.supabase.co` 加入 **request 合法域名**（否则真机无法请求）。

### AI 代理 BFF（吃什么）

在 `.env` 中配置 `VITE_API_BASE_URL`（与 Supabase 域名一并加入小程序 **request 合法域名**）。

小程序调用 **`POST {VITE_API_BASE_URL}/api/ai/today-eat`**，请求体示例：

```json
{
  "preferences": { "taste": "清淡", "avoid": "香菜", "people": 2 },
  "locale": "zh-CN"
}
```

建议响应 JSON（字段可扩展）：

```json
{
  "title": "菜名",
  "cuisine": "粤菜",
  "ingredients": ["番茄", "鸡蛋"],
  "content": "菜谱正文或摘要（纯文本/Markdown）",
  "history_saved": false
}
```

- **`history_saved: true`**：表示 BFF 已写入 `recipe_histories`，小程序不再补写。
- 若为 **`false` 或省略** 且用户已登录：小程序会尝试用 Supabase **客户端**插入一条历史（兜底）；**优先仍建议 BFF 写库**。

请求头可带 **`Authorization: Bearer <access_token>`**（与邮箱登录后的 Supabase JWT 一致），便于 BFF 校验用户；若 BFF 允许匿名生成，可不校验。

## 说明

### 若模拟器「整页空白」

1. **重新编译**：修改 `.env` 或依赖后执行 `npm run dev:mp-weixin`，再在开发者工具里**重新导入** `dist/dev/mp-weixin`。
2. **提高调试基础库**：微信开发者工具 → 详情 → 本地设置 → **调试基础库** 选 **较新版本**（建议 2.30+）。过低版本对 **flex `gap`**、Vue3 编译产物支持不完整。
3. **确认导入的是编译目录**：应导入 **`dist/dev/mp-weixin`**，不要改编译产物里的 `pages/*.js`（重新编译会覆盖）。
4. **App 根节点**：`App.vue` 使用 **非空的 `<template>` 根节点**（如单个 `<view class="uni-app-host" />`）。**不要**用空的 `<template></template>`；也不要在根上用 **`h('view')`**，在部分 **Vue 3.4 + 基础库** 下会触发 `Cannot create property '$eS' on string 'view'` 与 `Page has not been registered yet`。历史方案里的 `h('view')` 已弃用。
5. **样式不要使用 WXSS 不支持的通配符**：例如 `.page > * + *` 会报 `[ WXSS 文件编译错误] error at token *`，并可能导致后续 `vendor.js` 里 `bind of undefined`。请只用类名、标签名等[微信支持的选择器](https://developers.weixin.qq.com/miniprogram/dev/framework/view/wxss.html)。
6. **`Cannot read property 'bind' of undefined`（vendor.js）**：微信小程序没有 `fetch`，`@supabase/supabase-js` 在 vendor 里会执行 `global.fetch.bind(...)` 直接报错。工程已在 `vite.config.ts` 的 **`closeBundle`** 阶段向 `dist/**/mp-weixin/common/vendor.js` **头部注入 fetch/Headers 等 polyfill**（基于 `uni.request` / `wx.request`）。每次构建/保存编译后都会自动带上；若手动改动了 `vendor.js` 请重新 `npm run dev:mp-weixin`。

- `pages.json` / `manifest.json` 位于 **`src/`**（uni-app + Vite 约定），根目录为 `vite.config.ts`。
- TabBar 图标为 **1×1 占位 PNG**，发布前请替换为规范尺寸图标（建议 81×81 px）。
- 登录页为 **微信登录骨架**（`uni.login` + mock token），接 BFF 后替换为真实鉴权。
