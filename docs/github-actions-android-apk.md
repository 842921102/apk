# GitHub Actions 免费打包 APK

已提供工作流：`.github/workflows/android-apk.yml`

## 触发方式

1. 进入 GitHub 仓库的 `Actions`
2. 选择 `Build Android APK (Debug)`
3. 点击 `Run workflow`

也可在推送到 `main/master` 且命中相关路径变更时自动触发。

## 需要配置的 Secrets（建议）

在仓库 `Settings -> Secrets and variables -> Actions` 中添加：

- `VITE_TEXT_GENERATION_BASE_URL`
- `VITE_TEXT_GENERATION_API_KEY`
- `VITE_TEXT_GENERATION_MODEL`
- `VITE_TEXT_GENERATION_TEMPERATURE`
- `VITE_TEXT_GENERATION_TIMEOUT`
- `VITE_IMAGE_GENERATION_BASE_URL`
- `VITE_IMAGE_GENERATION_API_KEY`
- `VITE_IMAGE_GENERATION_MODEL`

## 产物下载

构建成功后，在该次 workflow 的 `Artifacts` 区域下载：

- `fan-app-debug-apk`

解压后文件即：

- `app-debug.apk`

## 说明

- 这是安卓体验版 debug 包流程，免费且不依赖本地 Android Studio。
- 如后续需要 release 包，再补签名 keystore 与 release workflow。
