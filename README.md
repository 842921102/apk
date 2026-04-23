# 饭否（当前版本）

English: [README_EN.md](./README_EN.md)

当前仓库仅保留以下两部分：

- `admin-backend`：Laravel 管理端与 API（含小程序生成式 AI、运营配置等）
- `mini-fan-package`：微信小程序前端

已移除旧安卓工程、旧 Web 前端、原 Node BFF 及无关部署文件。

## 功能范围

- 吃什么 / 一桌好菜 / 玄学厨房 / 酱料大师 / 图鉴
- 收藏、历史、统一结果详情、回看与二次操作
- 管理端数据管理（按功能分菜单）
- 模型中心（供应商、模型、场景配置、测试日志）

## 本地启动

在仓库根目录执行：

```bash
# 小程序开发
npm run dev:mp-weixin

# Laravel（默认 http://127.0.0.1:8000）
npm run admin:serve
```

或分别在子目录执行：

```bash
cd mini-fan-package && npm run dev:mp-weixin
cd admin-backend && php artisan serve
```

## 目录说明

```text
admin-backend/      Laravel 后台与 API
mini-fan-package/   新版微信小程序
```
