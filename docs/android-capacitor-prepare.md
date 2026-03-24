# 安卓体验版（Capacitor）接入说明

## 1) 当前状态评估

项目适合接入 Capacitor，原因：

- 前端为标准 Vue + Vite，构建产物目录固定为 `dist`
- UI 已移动端优先，路由与交互可直接运行在 WebView
- 业务逻辑与后端调用均在前端层，不依赖浏览器专属扩展

## 2) 已完成的基础接入

- 安装依赖：
  - `@capacitor/core`
  - `@capacitor/android`
  - `@capacitor/cli`
- 新增配置：`capacitor.config.ts`
- 生成安卓壳工程：`android/`
- 已执行同步：`npx cap sync android`

## 3) 关键配置说明

`capacitor.config.ts`：

- `appId`: `top.lzt.fan`
- `appName`: `饭否`
- `webDir`: `dist`（把前端构建产物接入安卓壳）
- `android.backgroundColor`: `#F5F6F8`（与当前品牌背景一致）

## 4) 常用命令

```bash
# 构建并同步全部平台
npm run cap:sync

# 构建并同步安卓
npm run cap:sync:android

# 用 Android Studio 打开原生工程
npm run cap:open:android

# 连接设备快速运行（已安装 adb 时）
npm run cap:run:android
```

## 5) 安卓体验包流程（建议）

1. `npm run cap:sync:android`
2. `npm run cap:open:android`
3. 在 Android Studio 中等待 Gradle 同步完成
4. Build -> Build Bundle(s) / APK(s) -> Build APK(s)
5. 使用 debug APK 做体验分发

## 6) 当前还缺什么（上线前）

- 本机 Android Studio + Android SDK 环境（打包必需）
- 真实品牌图标替换（当前为占位资源）
- 正式签名 keystore（体验包可先用 debug）
- 发布级隐私合规与网络安全策略检查（体验版可后置）
- 若后续接入原生能力（相机、分享、文件等），需按需安装 Capacitor 插件
