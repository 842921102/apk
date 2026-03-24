# Capawesome Cloud 云端构建（安卓体验版）准备清单

本文用于当前 `Vue + Capacitor` 项目，不依赖本地 Android Studio，直接准备云端构建 Android 体验包。

## 1. 当前项目接入状态评估

已具备基础条件：

- 已完成 Capacitor 接入（含 `android/` 工程）
- `capacitor.config.ts` 存在且 `webDir` 为 `dist`
- 可通过 `npm run build` 正常生成前端产物
- 已有品牌基础信息与图标占位（`public/brand/*`、`manifest.webmanifest`）

当前关键配置（已存在）：

- `appId`: `top.lzt.fan`
- `appName`: `饭否`
- `webDir`: `dist`
- Android `applicationId`: `top.lzt.fan`
- Android `versionCode`: `1`
- Android `versionName`: `1.0`
- npm `version`: `1.0.0`

## 2. 云端构建前还缺什么

为了稳定产出安卓体验包，建议补齐以下信息：

1. **签名策略**
   - 体验版可先使用云端 debug 或临时签名
   - 若要长期分发，建议准备 release keystore（建议通过云端 Secret 管理）
2. **版本策略**
   - 每次分发建议递增 `android/app/build.gradle` 中 `versionCode`
   - `versionName` 与 `package.json version` 建议同步维护
3. **图标与启动图**
   - 当前为占位资源，正式体验分发前建议替换为品牌定稿图
4. **环境变量**
   - 若云端构建需要运行前端构建（`npm run build`），必须注入 `VITE_*` 变量

## 3. 需要提交到 Git 的文件

建议提交（云端构建必需）：

- `package.json`
- `package-lock.json`
- `capacitor.config.ts`
- `android/`（整个 Capacitor 安卓工程）
- `public/manifest.webmanifest`
- `public/brand/*`（图标/启动页占位）
- `src/**` 与 `vite.config.ts` 等前端源码

不要提交：

- `android/local.properties`（本机 SDK 路径）
- `.env.local` / `.env.*.local`（本地密钥）
- `*.jks` / `*.keystore`（签名文件，建议改用云端 Secret）

## 4. 云端环境变量准备（构建阶段）

至少准备以下变量（和 `.env.example` 对齐）：

- `VITE_TEXT_GENERATION_BASE_URL`
- `VITE_TEXT_GENERATION_API_KEY`
- `VITE_TEXT_GENERATION_MODEL`
- `VITE_TEXT_GENERATION_TEMPERATURE`
- `VITE_TEXT_GENERATION_TIMEOUT`
- `VITE_IMAGE_GENERATION_BASE_URL`
- `VITE_IMAGE_GENERATION_API_KEY`
- `VITE_IMAGE_GENERATION_MODEL`

> 若希望体验版先“可打开而不依赖真实 AI 接口”，可先给测试值，但会影响真实功能可用性。

## 5. Capawesome Cloud 构建建议流程（安卓体验版）

1. 将代码推到 Git 仓库（建议主分支/体验分支）
2. 在 Capawesome Cloud 创建项目并连接仓库
3. 配置构建命令：
   - Web build: `npm ci && npm run build`
   - Sync: `npx cap sync android`
4. 注入上文 `VITE_*` 环境变量
5. 选择 Android Debug 或体验签名配置发起构建
6. 构建完成后下载 APK，做真机安装验证

## 6. 安卓云构建前建议再补的项目元信息

- 应用展示名：`饭否`
- 包名：`top.lzt.fan`（已配置）
- 应用版本：
  - `package.json version`
  - `android/app/build.gradle` 的 `versionCode` / `versionName`
- 应用图标：替换 `public/brand` 占位图并生成安卓 launcher 资源（如需要）
- 隐私政策与用户协议链接（体验分发可后置，正式上架前必需）
