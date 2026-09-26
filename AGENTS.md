# AGENTS.md — crm-api.lan (crm-api)

> Catatan operasional untuk agent AI. Diperbarui: 2026-09-26.

## Project
- **Repo**: `https://github.com/laravelspine/laravelspine` (paket source)
- **Aplikasi**: crm-api.lan — host Laravel yang mengonsumsi paket `spine/laravel-spine`
- **Framework**: Laravel 12.68.0
- **PHP**: 8.4.17
- **Type**: API-only backend (no web routes, no views)
- **Auth**: Laravel Sanctum (PAT)
- **Host ini tidak memiliki model/service domain miliknya sendiri** — semuanya
  milik package `spine/laravel-spine`.

## Infrastruktur
| Item | Nilai |
|---|---|
| Domain | `spine.lan` → `192.168.18.17` |
| Nginx vhost | `/www/server/nginx/conf/vhost/nextjs.spine.lan.conf` (shared) |
| PHP-FPM socket | `/tmp/php-cgi-84.sock` |
| Project root | `/www/wwwroot/crm-api.lan/` |
| Public | `/www/wwwroot/crm-api.lan/public/` |
| Database | `crm_api` @ `127.0.0.1:3306` |
| Frontend domain | `crm-web.lan` |
| Frontend root | `/www/wwwroot/crm-web/` |

## Environment
- `.env` di-.gitignore, `.env.example` di-push.
- APP_URL = `http://crm-api.lan`
- APP_ENV = `local`
- CACHE_STORE / SESSION_DRIVER / QUEUE_CONNECTION = `redis` (sudah aktif & terverifikasi ping OK)

## Routing
- `bootstrap/app.php`: api + web + console + health
- `routes/api.php`: **kosong by design** — semua route datang dari package via
  `SpineServiceProvider::loadRoutes()` (104 route `api/v1/*`)
- `routes/console.php`: artisan commands
- `routes/web.php`: default welcome (bisa diubah)

## Komando Umum
```bash
cd /www/wwwroot/crm-api.lan

# Artisan
php artisan route:list
php artisan migrate          # JANGAN migrate:fresh di DB ini
php artisan tinker
php artisan rbac:sync-core

# Cache
php artisan optimize:clear

# Test
curl -s http://crm-api.lan/api/v1/test
curl -s http://crm-api.lan/api/v1/health
```

> ⚠️ **`php artisan migrate:fresh` tidak boleh dipakai** di `crm_api` — perintah itu
> menjatuhkan seluruh tabel tiap kali dijalankan. Untuk menerapkan ulang schema
> pakai `php artisan migrate:reset` lalu `php artisan migrate`.

## Status Backend (v1.6c)
- ✅ Infrastruktur: Laravel 12, DB, routes, packages terpasang
- ✅ RBAC: 37 permissions + 2 roles (admin, employee) seeded
- ✅ 56 event classes di vendor/spine/laravel-spine/src/Events/
- ✅ RegisterOtpService + IpGuardService
- ✅ SyncCoreRbacCommand
- ✅ API test endpoints: `/api/v1/test` + `/api/v1/health` → 200 OK
- ✅ ULID: kolom `ulid` varchar(26) unique + auto-generate pada create
- ✅ Language: kolom `language` varchar(10) pada users
- ✅ 2FA: TOTP + Email, login challenge flow, enable/disable
- ✅ Public Content API: `GET /api/v1/public/content?lang=` (5 bahasa)
- ✅ Menu Registry: `GET /api/v1/menus/sidebar`, `/quick-actions`, `/meta/settings-tabs`
- ✅ Cron Shell: `AppCron` artisan command + `POST /api/v1/cron/run`
- ✅ Module Management: nwidart + event hooks, RBAC sync, version check, skip escape hatch
- ✅ i18n: SetLocale middleware, translation API, 5 lang files
- ⏳ Phase 2: Customers, Leads, Invoices, dll (belum dimulai)

## Backend API Endpoints

### Auth (Public)
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/login` | Login — returns token or 2fa_required challenge |
| POST | `/api/v1/auth/register` | Register new staff |
| POST | `/api/v1/auth/2fa/verify` | Verify 2FA code (TOTP or email) |
| POST | `/api/v1/auth/2fa/email/send` | Send 2FA email code |

### Auth (Authenticated)
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/logout` | Revoke current token |
| GET | `/api/v1/auth/me` | Current user + permissions + roles + ulid + language |
| GET | `/api/v1/auth/2fa/status` | 2FA status for current user |
| POST | `/api/v1/auth/2fa/enable` | Enable TOTP 2FA (returns secret + QR) |
| POST | `/api/v1/auth/2fa/disable` | Disable 2FA |

### Public Content
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/public/content?lang=en|id|ko|ja|zh` | Landing page content per locale |

### Menu Registry
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menus/sidebar` | Permission-filtered sidebar tree |
| GET | `/api/v1/menus/quick-actions` | Permission-filtered quick actions |
| GET | `/api/v1/meta/settings-tabs` | Settings tabs config |

### Cron
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/cron/run` | Trigger cron tasks (requires system.settings permission) |

### Package APIs (106 routes)
Settings, Activity Logs, Files, Mail, Notifications, Roles, Permissions, Users, Modules, Tags, Dashboard, GDPR, PDF, SMS, QR Code, Excel, Broadcast — see `php artisan route:list --path=api`.

## Schema Tabel `users`
| Field | Type | Key |
|---|---|---|
| `id` | bigint unsigned | PRI (auto_increment) |
| `ulid` | varchar(26) | UNI |
| `name` | varchar(255) | |
| `email` | varchar(255) | UNI |
| `language` | varchar(10) | |
| `email_verified_at` | timestamp | nullable |
| `password` | varchar(255) | |
| `remember_token` | varchar(100) | nullable |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

**2FA state** disimpan di tabel `custom_meta`:
- `meta_key` = `2fa_enabled`, `meta_value` = `1`
- `meta_key` = `2fa_totp_secret`, `meta_value` = encrypted base32 string

## Frontend (crm-web)
- **Stack**: Laravel Inertia.js + React 19 + Vite 6 + Tailwind CSS 4
- **Design system**: Semantic CSS variables dari `nextjs-spine`
- **Auth flow**: Login → (optional 2FA) → Dashboard
- **Language**: URL prefix `/{lang}` dengan fallback English
- **Pages**: Welcome (landing), Login, Register, Dashboard, About
- **Layout**: AppLayout dengan sidebar dinamis dari API menu

## Konvensi Penulisan Kode

### Package — `/www/wwwroot/laravelspine` (SATU-SUMBER)
Semua fitur core milik package, **bukan** milik host ini:
- `src/Http/Controllers/` — AuthController, UserController, dll
- `src/Services/` — TwoFactorService, RegisterOtpService, IpGuardService, dll
- `src/Models/` — model core (ActivityLog, CustomMeta, IpBan, dll)
- `src/Events/` — event classes
- `routes/api.php` — route API core platform
- `database/migrations/` — migration core
- `lang/*.json` — translation files (shared)

`vendor/spine/laravel-spine` adalah **symlink** ke `/www/wwwroot/laravelspine/`.
Composer memakai `type: path` dengan symlink, jadi **setiap edit di
`/www/wwwroot/laravelspine` langsung terpakai tanpa perlu `composer update`
maupun sync manual**.

> 🚫 **Jangan pernah membuat salinan package kedua.** Riwayat September 2026:
> `/www/wwwroot/laravelspine/public_html/` pernah ada sebagai clone terpisah
> (gitlink) dan ikut_symlink oleh host, sehingga edit di root tidak pernah
> sampai ke host — tiap perubahan harus dicopy manual. Clone tersebut sudah
> dipindahkan ke `/www/wwwroot/old-laravelspine/` (arsip, jangan dipakai) dan
> gitlink-nya sudah dilepas dari repo root.

Commit & push ke `laravelspine` setelah fitur core selesai.

### Aplikasi (`/www/wwwroot/crm-api.lan/`)
Hanya yang benar-benar spesifik host:
- `config/` — override config package bila perlu
- `bootstrap/app.php` — middleware/exception wiring
- `database/migrations/` — migrasi yang benar-benar host-specific saja
  (duplikat `personal_access_tokens` pernah terjadi di sini — sudah dihapus)
- `routes/api.php` — sengaja kosong; route dari package

### Frontend (`/www/wwwroot/crm-web/`)
Inertia + React — hanya frontend:
- `resources/js/` — semua komponen React
- `resources/css/` — Tailwind + design tokens
- `app/Http/Controllers/` — controller Inertia (Welcome, About, dll)

## Modul
- Folder `/Modules/` di-.gitignore — tidak di-push ke repo utama.
- **TIDAK mengerjakan module sampai user minta.**

## Git
```bash
cd /www/wwwroot/crm-api.lan
git remote -v
```

## Referensi PRD
- Source: `/home/aapanel/perfex-porting-docs/docs/prd/`
- Commit terakhir: `ce7829a` (v1.6b) → sekarang v1.6c

## Referensi package
- Source of truth: `/www/wwwroot/laravelspine/`
- Commit: `9e73a48`
- Arsip clone lama (jangan dipakai): `/www/wwwroot/old-laravelspine/`
