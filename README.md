# CRM API — Backend API

## Overview

CRM API adalah backend API untuk sistem CRM yang membangun di atas `spine/laravel-spine` package. API melayani semua request dari `crm-web.lan` frontend.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 |
| Auth | Laravel Sanctum (session-based) |
| RBAC | spatie/laravel-permission (37 permissions) |
| Database | MySQL (spine database) |
| Package | spine/laravel-spine (core features) |

## API Endpoints

### Auth (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login — returns token or 2fa_required challenge |
| POST | `/api/v1/auth/register` | Register new staff |
| POST | `/api/v1/auth/2fa/verify` | Verify 2FA code (TOTP or email) |
| POST | `/api/v1/auth/2fa/email/send` | Send 2FA email code |

### Auth (Authenticated)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/logout` | Revoke current token |
| GET | `/api/v1/auth/me` | Current user + permissions + roles |
| PUT | `/api/v1/auth/me` | Update profile (name, email, language) |

### Settings & Profile
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/settings/schema` | Get settings tabs & fields |
| POST | `/api/v1/settings/bulk` | Save multiple settings |
| GET | `/api/v1/profile/schema` | Get profile tabs & fields |

### Menus
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/menus/sidebar` | Permission-filtered sidebar tree |
| GET | `/api/v1/menus/quick-actions` | Permission-filtered quick actions |

### Translations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/translations/{locale}` | Get translations for locale |

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/health` | Health check |
| GET | `/api/v1/meta/settings-tabs` | Settings tabs config |

## Database

- Host: `127.0.0.1:3306`
- Database: `spine`
- User: `spine`
- Password: `spine_dev_2026`

## Installation

```bash
cd /www/wwwroot/crm-api.lan
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan rbac:sync-core
```

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name crm-api.lan;
    root /www/wwwroot/crm-api.lan/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-84.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Project Structure

```
/www/wwwroot/crm-api.lan/
├── app/Http/Controllers/
│   ├── AuthController.php
│   ├── SettingController.php
│   └── TranslationController.php
├── config/menus.php           # Menu configuration
├── routes/api.php             # API routes
└── vendor/spine/              # Symlink to laravelspine package
```

## GitHub

- Repo: https://github.com/wasnaker/crm-api (private)
