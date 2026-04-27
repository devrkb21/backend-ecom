# Laravel eCommerce Backend

Production-ready Laravel 12 backend for eCommerce with a strict security-first API model.

## Highlights
- REST API under `/api/v1` for web/mobile clients
- Session-based Admin Panel under `/admin`
- Strict API security: default-deny on `/api/v1` (Bearer token required unless explicitly exempted)
- Dynamic admin roles and permissions for admin web routes
- Stripe + bKash payment integrations
- Loyalty, flash sales, returns, abandoned carts, analytics, and BI modules

## Table of Contents
- Overview
- Security Model (Strict)
- Core Capabilities
- Architecture
- Tech Stack
- Requirements
- Installation
- Environment Configuration
- Database and Seed Data
- Running the Application
- Queue and Scheduler
- API Access Map
- Admin Panel and RBAC
- Testing and Security Guardrails
- Deployment Security Checklist
- Project Structure
- Documentation

## Overview
This project is a layered Laravel backend that powers:
- Storefront API operations
- Authenticated customer flows (cart, orders, payments, loyalty, returns)
- Admin operations (catalog writes, user management, export, audit)
- Admin web interface for internal management

## Security Model (Strict)
Security is enforced as baseline behavior.

### API default-deny policy
- All `/api/v1` routes are authenticated by default (`auth:sanctum`)
- Only explicitly whitelisted endpoints are public
- This prevents accidental exposure when adding new routes

### Public exceptions (only 7 under `/api/v1`)
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `GET /api/v1/auth/email/verify/{id}/{hash}`
- `POST /api/v1/stripe/webhook`
- `GET /api/v1/bkash/callback`

### Additional guards
- Admin-sensitive API routes use `admin_permission:*` or `is_admin` middleware
- Many API admin operations additionally enforce strict admin checks in controller/service logic
- Ownership checks are applied on user/order/payment scoped resources

## Core Capabilities

### Catalog and Merchandising
- Products, categories, attributes, variants, media
- Dynamic quantity-tier pricing
- Product-level free delivery flag
- Flash sales and coupon workflows

### Customer and Orders
- Sanctum authentication
- Cart and coupon workflows
- Checkout and order placement
- Order tracking and invoice endpoint
- Wishlist, reviews, addresses

### Payments
- Stripe payment intent + confirm flow
- Stripe webhook endpoint
- bKash create payment + status + refund + callback

### Engagement and Retention
- Loyalty tiers, rewards, redemptions, leaderboard
- Abandoned cart tracking and recovery markers
- Notifications and unread counters

### Returns and After-sales
- Return eligibility, request, cancellation, image upload
- Admin-side return/refund handling

### Admin and Governance
- Users menu with add/list/status/role updates
- Roles and permissions management in admin panel
- Integration settings (GTM, GA4, Facebook Pixel, TikTok Pixel, SMS)
- OTP-based password reset support

## Architecture
Layered application flow:

`Request -> Controller -> Service -> Repository -> Model`

Key layers:
- Controllers: transport and orchestration
- Form Requests: validation and normalization
- Services: business logic
- Repositories: data access abstraction
- Resources: response serialization
- Models: domain entities (Eloquent)

## Tech Stack
- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- MySQL/MariaDB
- Redis (cache, queue, session)
- Stripe SDK (`stripe/stripe-php`)
- bKash SDK (`karim007/laravel-bkash-tokenize`)
- PHPUnit 11

## Requirements
- PHP 8.2+
- Composer 2+
- MySQL 8+ or MariaDB 10.6+
- Redis
- PHP extensions:
  - BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

## Installation
```bash
git clone <your-repository-url>
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

## Environment Configuration
Update `.env` values for your environment.

### Core
```env
APP_NAME=EcommerceAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
ADMIN_URL=http://localhost:3001
```

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=
```

### Redis / Session / Queue
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Sanctum
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

### Stripe
```env
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
```

### bKash
```env
BKASH_SANDBOX=true
BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=
BKASH_CALLBACK_URL="${APP_URL}/api/v1/bkash/callback"
```

### Mail
```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Database and Seed Data
```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Default seeded users:
- Admin: `admin@example.com` / `password`
- Customer: `customer@example.com` / `password`

## Running the Application
### API + Admin web
```bash
php artisan serve
```

- API base: `http://localhost:8000/api/v1`
- Admin login: `http://localhost:8000/admin/login`

### Queue worker
```bash
php artisan queue:work
```

## Queue and Scheduler
Configured scheduled jobs include:
- Abandoned cart reminders (every 2 hours, daytime window)
- Flash sale expiry (every 15 minutes)
- Low-stock checks (daily)
- Sanctum token pruning (weekly)

Crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## API Access Map

### Public endpoints
- `GET /api/health` (outside v1)
- 7 whitelisted `/api/v1` exceptions listed in Security Model section

### Authenticated endpoints (`Authorization: Bearer <token>`)
- All remaining `/api/v1` routes (102 endpoints currently)
- Includes products, categories, settings, payment method discovery, shipping calculation, tracking, and all customer features

### Admin-sensitive endpoints
- Protected by route middleware (`admin_permission:*` or `is_admin`)
- Some also enforce strict admin in controller/service checks

## Admin Panel and RBAC
Admin web access is dynamic and role-driven:
- Login permission by role (`can_access_admin_panel`)
- Module permission mapping via `EnsureAdminPermission`
- Role management UI under `Admin -> Users -> Role`

Common permission keys:
- `dashboard.view`
- `catalog.manage`
- `orders.manage`
- `payments.view`
- `returns.manage`
- `abandoned_carts.manage`
- `marketing.manage`
- `users.manage`
- `roles.manage`
- `settings.manage`

## Testing and Security Guardrails
Run all tests:
```bash
php artisan test
```

Security-focused tests are in:
- `tests/Feature/ApiSecurityTest.php`

These tests enforce:
- `/api/v1` stays default-authenticated
- Only explicit whitelist routes stay public
- `/api/v1/admin/*` routes keep admin middleware

Useful checks:
```bash
php artisan route:list --path=api/v1
php artisan route:list --path=api/v1 --json
```

## Deployment Security Checklist
- Set `APP_ENV=production` and `APP_DEBUG=false`
- Configure HTTPS and trusted proxy settings
- Configure CORS and Sanctum domains correctly
- Keep webhook/callback endpoints reachable but verify signatures
- Run migrations with backup/rollback policy
- Configure Redis + queue workers + scheduler
- Rotate and secure API/payment/SMS credentials
- Monitor failed auth attempts and rate-limit events

## Project Structure
```text
app/
  Http/
    Controllers/
      Api/
      Admin/
    Middleware/
    Requests/
    Resources/
  Models/
  Services/
  Repositories/
  Notifications/
  Console/
bootstrap/
config/
database/
  migrations/
  seeders/
routes/
  api.php
  web.php
resources/views/
tests/
```

## Documentation
- API reference: `API_DOCUMENTATION.md`
- Route inventory: `php artisan route:list --path=api/v1`
