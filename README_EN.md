# Fanfou (Current Version)

中文: [README.md](./README.md)

This repository now keeps only these parts:

- `admin-backend`: Laravel admin backend and APIs
- `bff-server`: Node BFF proxy
- `mini-fan-package`: WeChat mini program frontend

Legacy Android project, old web frontend, and unrelated deployment files were removed.

## Feature Scope

- Today Eat / Table Menu / Fortune Cooking / Sauce Design / Gallery
- Favorites, histories, unified result detail, replay, and secondary actions
- Admin data management menus per feature
- AI model center (providers, models, scene configs, connection test logs)

## Local Run

From repository root:

```bash
# Mini program dev
npm run dev:mp-weixin

# BFF
npm run bff:start

# Laravel (or run artisan directly in admin-backend)
npm run admin:serve
```

Or run each project directly:

```bash
cd mini-fan-package && npm run dev:mp-weixin
cd bff-server && npm run start
cd admin-backend && php artisan serve
```

## Structure

```text
admin-backend/      Laravel admin and APIs
bff-server/         Node BFF
mini-fan-package/   WeChat mini program
```
