# Laravel eCommerce REST API

A full-featured eCommerce REST API built with **Laravel 12**, designed with clean architecture principles. Includes product management, order processing, multiple payment gateways (Stripe & bKash), loyalty programs, flash sales, and a complete admin panel.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running the Application](#running-the-application)
- [Default Users](#default-users)
- [API Overview](#api-overview)
- [Authentication](#authentication)
- [Scheduled Tasks](#scheduled-tasks)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [License](#license)

## Features

### Core eCommerce
- Product catalog with variants, attributes, and image management
- Hierarchical categories with parent/child relationships
- Shopping cart with quantity management
- Order management with status tracking and order notes
- Product search with filtering and pagination

### Payments
- **Stripe** integration (credit/debit cards, international payments)
- **bKash** integration (mobile payments, Bangladesh-specific)
- Payment webhook handlers for confirmation
- Refund processing

### Customer Features
- Token-based authentication (Laravel Sanctum)
- User profiles with address management (shipping/billing)
- Wishlist with move-to-cart functionality
- Product reviews with ratings, voting, and moderation
- Return/refund request management
- Abandoned cart tracking with email recovery
- Loyalty points program with tiered rewards
- Order history and tracking
- In-app notifications

### Promotions & Marketing
- Flash sales with time-limited promotions
- Coupon/discount code system
- Product recommendations (related, frequently bought together, upsell, cross-sell)

### Admin Features
- Dashboard with analytics and business intelligence
- Product and category management with bulk actions
- Order management with status updates and CSV export
- Customer management with status toggling
- Review moderation (approve/reject/featured)
- Payment gateway configuration
- Shipping method management
- CMS settings (hero banners, social links, SEO, footer)
- Coupon and flash sale management
- Loyalty program and tier management
- Audit log tracking

## Architecture

This project follows **clean architecture** principles with a layered structure:

```
Request → Controller → Service → Repository → Model → Database
```

- **Controllers** - Handle HTTP requests/responses and validation
- **Services** - Business logic layer (16 service classes)
- **Repositories** - Data access layer with interface contracts (8 repositories)
- **Models** - Eloquent ORM models (35 models)
- **Form Requests** - Input validation layer
- **Resources** - API response transformation layer

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Authentication | Laravel Sanctum |
| Database | MySQL 8.0+ / MariaDB 10.6+ |
| Cache & Queue | Redis |
| Payments | Stripe PHP SDK, bKash Tokenized |
| Testing | PHPUnit 11 |
| Code Style | Laravel Pint |

## Requirements

- PHP 8.2 or higher
- MySQL 8.0+ or MariaDB 10.6+
- Redis server
- Composer 2.x
- PHP Extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML

## Installation

1. **Clone the repository:**

```bash
git clone https://github.com/devrkb21/backend-ecom.git
cd backend-ecom
```

2. **Install PHP dependencies:**

```bash
composer install
```

3. **Copy the environment file:**

```bash
cp .env.example .env
```

4. **Generate the application key:**

```bash
php artisan key:generate
```

## Configuration

Edit the `.env` file to configure your environment:

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Redis

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Payment Gateways

```env
# Stripe
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret

# bKash
BKASH_SANDBOX=true
BKASH_APP_KEY=your_bkash_app_key
BKASH_APP_SECRET=your_bkash_app_secret
BKASH_USERNAME=your_bkash_username
BKASH_PASSWORD=your_bkash_password
BKASH_CALLBACK_URL="${APP_URL}/api/v1/bkash/callback"
```

### Mail (for notifications)

```env
MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Application URLs

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
ADMIN_URL=http://localhost:3001
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

## Database Setup

1. **Create the database:**

```sql
CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Run migrations:**

```bash
php artisan migrate
```

3. **Seed the database** (optional - creates demo data):

```bash
php artisan db:seed
```

## Running the Application

### Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api/v1/`.

### Storage Link (for file uploads)

```bash
php artisan storage:link
```

### Queue Worker (for background jobs)

```bash
php artisan queue:work redis
```

### Task Scheduler (for scheduled jobs)

Add to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Default Users

After running the database seeder, the following accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Customer | customer@example.com | password |

## API Overview

**Base URL:** `/api/v1`

All endpoints are prefixed with `/api/v1`. A health check is available at `/api/health`.

### Public Endpoints (No Authentication)

| Group | Endpoints | Description |
|-------|-----------|-------------|
| Products | `GET /products`, `/products/featured`, `/products/new`, `/products/bestsellers`, `/products/search`, `/products/{id}`, `/products/slug/{slug}` | Product catalog browsing |
| Categories | `GET /categories`, `/categories/{id}`, `/categories/slug/{slug}`, `/categories/{id}/children` | Category browsing |
| Reviews | `GET /products/{id}/reviews`, `/reviews/summary`, `/reviews/featured` | Product reviews |
| Flash Sales | `GET /flash-sales`, `/flash-sales/featured`, `/flash-sales/upcoming`, `/flash-sales/{slug}` | Active promotions |
| Related Products | `GET /products/{id}/related`, `/frequently-bought-together`, `/upsell`, `/cross-sell` | Product recommendations |
| Settings | `GET /settings`, `/settings/hero`, `/settings/general`, `/settings/social`, `/settings/seo` | CMS content |
| Payment Config | `GET /payment-methods`, `/shipping-methods`, `/stripe/config`, `/bkash/config` | Checkout configuration |
| Order Tracking | `GET /track/order/{orderNumber}`, `/track/tracking/{trackingNumber}` | Public order tracking |
| Attributes | `GET /attributes`, `/attributes/{id}` | Product filter options |

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register a new user |
| POST | `/auth/login` | Login and get token |
| POST | `/auth/forgot-password` | Request password reset |
| POST | `/auth/reset-password` | Reset password with token |
| GET | `/auth/email/verify/{id}/{hash}` | Verify email (signed URL) |

### Protected Endpoints (Bearer Token Required)

| Group | Key Endpoints | Description |
|-------|---------------|-------------|
| Auth | `POST /auth/logout`, `GET /auth/me`, `POST /auth/change-password` | Session management |
| Profile | `GET/PUT /profile` | User profile management |
| Cart | `GET /cart`, `POST /cart/items`, `PUT/DELETE /cart/items/{id}` | Shopping cart |
| Coupons | `POST /cart/coupon`, `GET /coupons/validate`, `GET /coupons/available` | Discount codes |
| Orders | `GET /orders`, `POST /orders`, `GET /orders/{id}`, `POST /orders/{id}/cancel` | Order management |
| Payments | `POST /payments`, `POST /payments/{id}/process` | Payment processing |
| Stripe | `POST /stripe/create-payment-intent`, `/stripe/confirm-payment` | Stripe payments |
| bKash | `POST /bkash/create-payment`, `GET /bkash/check-status` | bKash payments |
| Wishlist | `GET /wishlist`, `POST /wishlist`, `POST /wishlist/toggle` | Saved products |
| Reviews | `POST /reviews`, `PUT /reviews/{id}`, `POST /reviews/{id}/vote` | Product reviews |
| Addresses | `GET /addresses`, `POST /addresses`, `POST /{id}/set-default` | Address book |
| Returns | `GET /returns`, `POST /returns`, `GET /returns/check-eligibility` | Return requests |
| Loyalty | `GET /loyalty/summary`, `/loyalty/rewards`, `POST /loyalty/redeem` | Loyalty program |
| Notifications | `GET /notifications`, `POST /notifications/{id}/read` | In-app notifications |

### Admin Endpoints (Authenticated + Admin Role)

| Group | Key Endpoints | Description |
|-------|---------------|-------------|
| Users | `GET /users`, `POST /users`, `PUT /users/{id}`, `DELETE /users/{id}` | User management |
| Products | `POST /products`, `PUT /products/{id}`, `DELETE /products/{id}`, `POST /products/bulk-action` | Product CRUD |
| Categories | `POST /categories`, `PUT /categories/{id}`, `DELETE /categories/{id}` | Category CRUD |
| Orders | `PUT /orders/{id}/status`, `GET /orders/status/{status}` | Order management |
| Payments | `POST /payments/{id}/refund`, `POST /bkash/refund` | Refund processing |
| Export | `GET /admin/orders/export`, `/admin/orders/export/download/{file}` | CSV export |
| Audit | `GET /admin/audit-logs`, `/admin/audit-logs/{id}` | Audit trail |

> For complete API documentation with request/response examples, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

## Authentication

All protected endpoints require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

Obtain a token by sending a POST request to `/api/v1/auth/login`:

```json
{
    "email": "user@example.com",
    "password": "your_password"
}
```

### Response Format

**Success:**
```json
{
    "success": true,
    "message": "Success message",
    "data": { }
}
```

**Error:**
```json
{
    "success": false,
    "message": "Error message",
    "errors": { }
}
```

## Scheduled Tasks

The following tasks run automatically when the scheduler is configured:

| Schedule | Task | Description |
|----------|------|-------------|
| Every 2 hours (9 AM - 9 PM) | Abandoned cart reminders | Email users who left items in their cart |
| Every 15 minutes | Flash sale expiry | Automatically expire ended flash sales |
| Daily at 9 AM | Low stock check | Alert admins about products below 5 units |
| Weekly | Token cleanup | Prune expired Sanctum tokens (24h+) |

## Project Structure

```
app/
├── Console/Commands/        # Artisan CLI commands
├── Exceptions/              # Exception handlers
├── Http/
│   ├── Controllers/
│   │   ├── Api/             # 28+ API controllers
│   │   └── Admin/           # Admin panel controllers
│   ├── Middleware/           # Custom middleware (auth, logging)
│   ├── Requests/            # Form request validation
│   └── Resources/           # API response transformations
├── Models/                  # 35 Eloquent models
├── Notifications/           # Email notification classes
├── Repositories/            # Data access layer (8 repositories)
├── Services/                # Business logic (16 services)
│   └── Payment/             # Stripe & bKash services
├── Providers/               # Service providers
└── Traits/                  # Reusable traits

config/                      # Application configuration
database/
├── migrations/              # 39 database migrations
├── seeders/                 # Database seeders
└── factories/               # Model factories
routes/
├── api.php                  # API routes (200+ endpoints)
├── web.php                  # Admin panel routes
└── console.php              # Console command routes
```

## API Documentation

For comprehensive API documentation with full request/response examples for every endpoint, see **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)**.

## License

This project is open-sourced under the [MIT License](https://opensource.org/licenses/MIT).
