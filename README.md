# Laravel eCommerce Backend API

> Production-ready Laravel 12 backend powering a full-featured eCommerce platform with REST API, server-rendered Admin Panel, multi-courier logistics, and business intelligence — built with a strict security-first architecture.

---

## Table of Contents

- [Overview](#overview)
- [Key Highlights](#key-highlights)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Security Model (Strict)](#security-model-strict)
- [Core Modules](#core-modules)
  - [Catalog & Merchandising](#1-catalog--merchandising)
  - [Cart & Checkout](#2-cart--checkout)
  - [Orders & Fulfillment](#3-orders--fulfillment)
  - [Payments](#4-payments)
  - [Shipping & Couriers](#5-shipping--couriers)
  - [Customer Management](#6-customer-management)
  - [Marketing & Promotions](#7-marketing--promotions)
  - [Loyalty Program](#8-loyalty-program)
  - [Returns & Refunds](#9-returns--refunds)
  - [Reviews & Ratings](#10-reviews--ratings)
  - [Media Library](#11-media-library)
  - [Content Management](#12-content-management)
  - [Notifications & SMS](#13-notifications--sms)
  - [Business Intelligence](#14-business-intelligence)
  - [Fraud Prevention](#15-fraud-prevention)
  - [Audit Logging](#16-audit-logging)
- [Admin Panel (Server-Rendered)](#admin-panel-server-rendered)
- [Admin RBAC System](#admin-rbac-system)
- [API Endpoint Reference](#api-endpoint-reference)
- [Middleware Stack](#middleware-stack)
- [Services Layer](#services-layer)
- [Repository Pattern](#repository-pattern)
- [Eloquent Models](#eloquent-models)
- [Form Requests (Validation)](#form-requests-validation)
- [API Resources (Serialization)](#api-resources-serialization)
- [Database Schema](#database-schema)
- [Artisan Commands](#artisan-commands)
- [Scheduled Jobs](#scheduled-jobs)
- [Email & Notification System](#email--notification-system)
- [Observers & Event Listeners](#observers--event-listeners)
- [Configuration Files](#configuration-files)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Database & Seed Data](#database--seed-data)
- [Running the Application](#running-the-application)
- [Testing](#testing)
- [Deployment Security Checklist](#deployment-security-checklist)
- [Documentation](#documentation)

---

## Overview

This project is a layered Laravel 12 backend that powers:

- **Storefront REST API** (`/api/v1`) — consumed by a Next.js frontend and mobile clients
- **Authenticated customer flows** — cart, checkout (guest + registered), orders, payments, loyalty, returns, wishlists, reviews
- **Admin operations** — catalog CRUD, order management, courier dispatch, analytics, media library, user & role management
- **Server-rendered Admin Panel** (`/admin`) — full internal management dashboard with Blade templates and RBAC

### At a Glance

| Metric | Count |
|---|---|
| Eloquent Models | 50 |
| API Controllers | 35 |
| Admin Controllers | 38 |
| Services | 20 |
| Repositories | 8 (with interfaces) |
| API Resources | 19 |
| Form Requests | 17 |
| Database Migrations | 83 |
| Database Seeders | 7 |
| Artisan Commands | 7 |
| Notifications | 8 |
| Middleware | 4 custom |
| Feature Tests | 7 |
| Admin View Directories | 25 |
| Config Files | 13 |

---

## Key Highlights

- **Strict API Security**: Default-deny on `/api/v1` — Bearer token required unless explicitly exempted
- **Internal API Secret**: Public frontend data routes require `X-Internal-Secret` header validation
- **Session-based Admin Panel** with full RBAC and dynamic role/permission management
- **Refactored Settings Dashboards**: Split into Storefront & SEO settings vs. unified System Settings dashboard (General, Checkout Field Manager, Invoice, Integrations, Couriers, SMS)
- **Consolidated Courier Dashboard**: Steadfast & Pathao integrations under one page with sub-tab URL state (`?group=couriers&sub=steadfast|pathao`)
- **Media Library with Picker**: Logo, Favicon, Invoice Logo inputs support selecting from the Media Library; automatic WebP conversion on upload
- **Guest Checkout**: Full guest order placement with optional guest access tokens
- **Multi-Courier Logistics**: Steadfast + Pathao courier APIs with bulk dispatch, webhook status sync, and Pathao zone/area lookups
- **Bangladesh Location Dataset**: Built-in divisions, districts, upazilas, unions with sync command from BBS API
- **Dynamic Checkout Form Schema**: Admin-configurable checkout fields with reorder/toggle/required validation
- **Stripe + bKash Payments**: Full payment intent flows, webhooks, refunds, saved payment methods
- **Loyalty Points System**: Tiers, rewards, redemptions, leaderboard, admin point adjustments
- **Flash Sales Engine**: Scheduled start/end, per-product stock limits, purchase validation, early end/extend/duplicate
- **Business Intelligence**: Sales reports, inventory alerts, customer analytics, product performance, CSV exports
- **Fraud Blocker**: Phone/email/IP-based blocking with auto-block thresholds and quick block/unblock from orders

---

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | ^8.2 |
| Framework | Laravel | 12.x |
| Authentication | Laravel Sanctum | ^4.0 |
| Database | MySQL / MariaDB | 8+ / 10.6+ |
| Cache / Queue / Session | Redis (via Predis) | ^2.2 |
| Payments | Stripe SDK | ^19.2 |
| Payments | bKash PGW | `devrkb21/bkash-pgw-laravel` ^1.0 |
| Courier | Pathao | `devrkb21/pathao-laravel` ^1.0.1 |
| Courier | Steadfast | `steadfast-courier/steadfast-courier-laravel-package` ^1.1 |
| PDF Generation | DomPDF | `barryvdh/laravel-dompdf` ^3.1 |
| Code Style | Laravel Pint | ^1.13 |
| Testing | PHPUnit | ^11.0 |
| Mocking | Mockery | ^1.6 |
| Fixture Data | Faker | ^1.23 |

---

## Architecture

The application follows a strict **layered architecture** with clear separation of concerns:

```
Request → Middleware → Controller → FormRequest → Service → Repository → Model
                                                                    ↓
                                                              API Resource → Response
```

### Layer Responsibilities

| Layer | Responsibility | Location |
|---|---|---|
| **Controllers** | HTTP transport, orchestration, response building | `app/Http/Controllers/Api/`, `app/Http/Controllers/Admin/` |
| **Form Requests** | Input validation & normalization | `app/Http/Requests/{Admin,Auth,Cart,Category,Order,Payment,Product,User}/` |
| **Services** | Business logic, calculations, third-party integrations | `app/Services/`, `app/Services/Payment/` |
| **Repositories** | Data access abstraction via interface contracts | `app/Repositories/`, `app/Repositories/Interfaces/` |
| **Models** | Domain entities, relationships, scopes, accessors, mutators (Eloquent) | `app/Models/` |
| **Resources** | JSON response serialization for API endpoints | `app/Http/Resources/` |
| **Middleware** | Request guards, logging, permission enforcement | `app/Http/Middleware/` |
| **Notifications** | Email/database notification dispatch | `app/Notifications/` |
| **Observers** | Model lifecycle event hooks | `app/Observers/` |
| **Listeners** | External event handling (e.g., Pathao webhook) | `app/Listeners/` |
| **Traits** | Reusable model behaviors (e.g., `Auditable`) | `app/Traits/` |
| **Providers** | Service container bindings and app bootstrapping | `app/Providers/` |
| **Exceptions** | Custom exception handling and error responses | `app/Exceptions/` |
| **Console** | Artisan commands and scheduled task definitions | `app/Console/` |

---

## Project Structure

```
ecommerce-api/
├── app/
│   ├── Console/
│   │   ├── Kernel.php                          # Scheduled task definitions
│   │   └── Commands/
│   │       ├── CheckLowStock.php               # Daily low-stock inventory check
│   │       ├── ExpireFlashSales.php             # Auto-expire ended flash sales
│   │       ├── ExportOrders.php                 # CSV order export command
│   │       ├── ReconcileOrderUsersAndAddresses.php  # Guest→registered user order sync
│   │       ├── RepairMediaPublicPaths.php       # Fix broken media public paths
│   │       ├── SendAbandonedCartReminders.php   # Abandoned cart email reminders
│   │       └── SyncBangladeshLocations.php      # Sync BD divisions/districts/upazilas/unions
│   ├── Exceptions/
│   │   └── Handler.php                         # Global exception handling
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php                  # Base controller
│   │   │   ├── Api/                            # 35 REST API controllers
│   │   │   │   ├── AbandonedCartController.php
│   │   │   │   ├── AddressController.php
│   │   │   │   ├── AttributeController.php
│   │   │   │   ├── AuditLogController.php
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── BangladeshLocationController.php
│   │   │   │   ├── BkashController.php
│   │   │   │   ├── CartController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ContactController.php
│   │   │   │   ├── CouponController.php
│   │   │   │   ├── CustomerGroupController.php
│   │   │   │   ├── FlashSaleController.php
│   │   │   │   ├── FrontendSettingController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── LandingPageController.php
│   │   │   │   ├── LoyaltyController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── OrderExportController.php
│   │   │   │   ├── OrderNoteController.php
│   │   │   │   ├── OrderTrackingController.php
│   │   │   │   ├── PageController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── PaymentGatewayController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── RelatedProductController.php
│   │   │   │   ├── ReturnController.php
│   │   │   │   ├── ReviewController.php
│   │   │   │   ├── SavedPaymentMethodController.php
│   │   │   │   ├── ShippingMethodController.php
│   │   │   │   ├── SteadfastWebhookController.php
│   │   │   │   ├── StripeController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── WishlistController.php
│   │   │   └── Admin/                          # 38 admin panel controllers
│   │   │       ├── AbandonedCartController.php
│   │   │       ├── AdminRoleController.php
│   │   │       ├── AttributeController.php
│   │   │       ├── AuthController.php
│   │   │       ├── BusinessIntelligenceController.php
│   │   │       ├── CancellationReasonController.php
│   │   │       ├── CategoryController.php
│   │   │       ├── ContactMessageController.php
│   │   │       ├── CouponController.php
│   │   │       ├── CourierController.php
│   │   │       ├── CustomerController.php
│   │   │       ├── CustomerGroupController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── FlashSaleController.php
│   │   │       ├── FraudBlockController.php
│   │   │       ├── GlobalSearchController.php
│   │   │       ├── IntegrationSettingController.php
│   │   │       ├── LandingPageController.php
│   │   │       ├── LoyaltyController.php
│   │   │       ├── MediaController.php
│   │   │       ├── OrderController.php
│   │   │       ├── OrderStatusController.php
│   │   │       ├── OrderTrackingController.php
│   │   │       ├── PageController.php
│   │   │       ├── PathaoController.php
│   │   │       ├── PathaoCourierController.php
│   │   │       ├── PaymentController.php
│   │   │       ├── PaymentGatewayController.php
│   │   │       ├── ProductController.php
│   │   │       ├── ReturnController.php
│   │   │       ├── ReviewController.php
│   │   │       ├── SavedPaymentMethodController.php
│   │   │       ├── ShippingMethodController.php
│   │   │       ├── SiteSettingController.php
│   │   │       ├── SmsTemplateController.php
│   │   │       ├── SteadfastController.php
│   │   │       ├── SteadfastCourierController.php
│   │   │       └── UserController.php
│   │   ├── Middleware/
│   │   │   ├── EnsureAdminPermission.php       # Route-based RBAC permission check
│   │   │   ├── InternalApiOnly.php             # X-Internal-Secret header validation
│   │   │   ├── IsAdmin.php                     # Admin panel access gate
│   │   │   └── LogApiRequests.php              # API request/response logging
│   │   ├── Requests/                           # 19 form request validators
│   │   │   ├── Admin/                          # 5 admin form requests
│   │   │   ├── Auth/                           # 5 auth form requests
│   │   │   ├── Cart/                           # 2 cart form requests
│   │   │   ├── Category/                       # 2 category form requests
│   │   │   ├── Order/                          # 2 order form requests
│   │   │   ├── Payment/                        # 2 payment form requests
│   │   │   ├── Product/                        # 2 product form requests
│   │   │   └── User/                           # 1 user form request
│   │   └── Resources/                          # 19 API resource serializers
│   │       ├── AddressResource.php
│   │       ├── CartItemResource.php
│   │       ├── CartResource.php
│   │       ├── CategoryResource.php
│   │       ├── OrderItemResource.php
│   │       ├── OrderResource.php
│   │       ├── OrderTrackingResource.php
│   │       ├── PaymentResource.php
│   │       ├── ProductAttributeResource.php
│   │       ├── ProductAttributeValueResource.php
│   │       ├── ProductImageResource.php
│   │       ├── ProductResource.php
│   │       ├── ProductVariantResource.php
│   │       ├── PublicSettingResource.php
│   │       ├── ReviewResource.php
│   │       ├── SavedPaymentMethodResource.php
│   │       ├── SettingResource.php
│   │       ├── UserResource.php
│   │       └── WishlistResource.php
│   ├── Listeners/
│   │   └── PathaoWebhookListener.php           # Handles Pathao courier status webhooks
│   ├── Mail/
│   │   └── ContactFormMail.php                 # Contact form email template
│   ├── Models/                                 # 50 Eloquent models
│   │   ├── AbandonedCart.php
│   │   ├── Address.php
│   │   ├── AdminRole.php
│   │   ├── AuditLog.php
│   │   ├── BdDistrict.php
│   │   ├── BdDivision.php
│   │   ├── BdUnion.php
│   │   ├── BdUpazila.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Category.php
│   │   ├── ContactMessage.php
│   │   ├── Coupon.php
│   │   ├── CouponUsage.php
│   │   ├── CustomerGroup.php
│   │   ├── FlashSale.php
│   │   ├── FlashSaleProduct.php
│   │   ├── FraudBlock.php
│   │   ├── LandingPage.php
│   │   ├── LoyaltyRedemption.php
│   │   ├── LoyaltyReward.php
│   │   ├── LoyaltyTier.php
│   │   ├── LoyaltyTransaction.php
│   │   ├── Media.php
│   │   ├── Order.php
│   │   ├── OrderActivityLog.php
│   │   ├── OrderItem.php
│   │   ├── OrderNote.php
│   │   ├── OrderStatus.php
│   │   ├── OrderTrackingHistory.php
│   │   ├── Page.php
│   │   ├── Payment.php
│   │   ├── PaymentGateway.php
│   │   ├── Product.php
│   │   ├── ProductAttribute.php
│   │   ├── ProductAttributeValue.php
│   │   ├── ProductImage.php
│   │   ├── ProductVariant.php
│   │   ├── RelatedProduct.php
│   │   ├── ReturnItem.php
│   │   ├── ReturnRequest.php
│   │   ├── Review.php
│   │   ├── ReviewVote.php
│   │   ├── SavedPaymentMethod.php
│   │   ├── Setting.php
│   │   ├── ShippingMethod.php
│   │   ├── ShippingMethodDistrictRate.php
│   │   ├── ShippingMethodLocationRule.php
│   │   ├── User.php
│   │   └── Wishlist.php
│   ├── Notifications/                          # 8 notification classes
│   │   ├── AbandonedCartReminder.php
│   │   ├── LowStockAlert.php
│   │   ├── OrderConfirmation.php
│   │   ├── OrderShipped.php
│   │   ├── OrderStatusUpdated.php
│   │   ├── ResetPasswordNotification.php
│   │   ├── VerifyEmailNotification.php
│   │   └── WelcomeNotification.php
│   ├── Observers/
│   │   └── UserObserver.php                    # Auto-syncs guest orders on user create/update
│   ├── Providers/
│   │   ├── AppServiceProvider.php              # Mail/Pathao config from DB settings at boot
│   │   └── RepositoryServiceProvider.php       # Interface→Implementation bindings
│   ├── Repositories/
│   │   ├── Interfaces/                         # 8 repository interfaces
│   │   │   ├── BaseRepositoryInterface.php
│   │   │   ├── CartRepositoryInterface.php
│   │   │   ├── CategoryRepositoryInterface.php
│   │   │   ├── OrderRepositoryInterface.php
│   │   │   ├── PaymentRepositoryInterface.php
│   │   │   ├── ProductRepositoryInterface.php
│   │   │   ├── SettingRepositoryInterface.php
│   │   │   └── UserRepositoryInterface.php
│   │   ├── BaseRepository.php
│   │   ├── CartRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── OrderRepository.php
│   │   ├── PaymentRepository.php
│   │   ├── ProductRepository.php
│   │   ├── SettingRepository.php
│   │   └── UserRepository.php
│   ├── Services/
│   │   ├── AuthService.php                     # Registration, login, password reset, OTP
│   │   ├── BangladeshLocationResolver.php      # BD location hierarchy resolver
│   │   ├── BusinessIntelligenceService.php     # Sales, inventory, customer analytics engine
│   │   ├── CartService.php                     # Cart operations and calculations
│   │   ├── CategoryService.php                 # Category tree operations
│   │   ├── CheckoutAddressConfigService.php    # Dynamic checkout form field configuration
│   │   ├── CheckoutTaxService.php              # Tax calculation logic
│   │   ├── CouponService.php                   # Coupon validation and application
│   │   ├── FlashSaleService.php                # Flash sale scheduling and stock management
│   │   ├── LoyaltyService.php                  # Points earning, redemption, tier management
│   │   ├── OrderCustomerSyncService.php        # Guest-to-registered order ownership sync
│   │   ├── OrderService.php                    # Core order lifecycle (75KB of business logic)
│   │   ├── PaymentService.php                  # Payment orchestration
│   │   ├── ProductService.php                  # Product queries and catalog operations
│   │   ├── RefundService.php                   # Refund processing across payment gateways
│   │   ├── RelatedProductService.php           # Related/upsell/cross-sell product engine
│   │   ├── SettingService.php                  # Settings CRUD with group management
│   │   ├── SmsService.php                      # SMS dispatch with template support
│   │   ├── UserService.php                     # User management operations
│   │   └── Payment/
│   │       ├── BkashPaymentService.php         # bKash create/execute/query/refund
│   │       └── StripePaymentService.php        # Stripe intent/confirm/webhook/refund
│   └── Traits/
│       └── Auditable.php                       # Auto-logs create/update/delete to audit_logs
├── bootstrap/
├── config/
│   ├── app.php                                 # Application config
│   ├── auth.php                                # Authentication guards
│   ├── bkash.php                               # bKash PGW credentials
│   ├── cache.php                               # Cache stores
│   ├── cors.php                                # CORS configuration (frontend + admin origins)
│   ├── database.php                            # Database connections
│   ├── filesystems.php                         # Filesystem disks
│   ├── logging.php                             # Log channels (including `api` channel)
│   ├── pathao.php                              # Pathao courier API credentials
│   ├── sanctum.php                             # Sanctum token configuration
│   ├── session.php                             # Session configuration
│   ├── shop.php                                # Shop-specific config (currency, tax, limits, etc.)
│   └── steadfast-courier.php                   # Steadfast courier API credentials
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/                             # 83 migration files
│   └── seeders/
│       ├── DatabaseSeeder.php                  # Master seeder (admin user + sub-seeders)
│       ├── CouponSeeder.php                    # Sample coupons
│       ├── LoyaltyTierSeeder.php               # Default loyalty tiers
│       ├── PagesTableSeeder.php                # Default static pages
│       ├── PaymentGatewaySeeder.php            # COD, Stripe, bKash gateway setup
│       ├── SettingSeeder.php                   # All default settings (general, SEO, social, etc.)
│       └── ShippingMethodSeeder.php            # Default shipping methods
├── postman/
│   └── E-Commerce-API-A-Z.postman_collection.json  # Full Postman collection
├── public/
├── resources/
│   └── views/
│       ├── admin/                              # 25 admin view directories + dashboard
│       │   ├── layouts/                        # Master layout with sidebar/header
│       │   ├── partials/                       # Reusable components (modals, tables)
│       │   ├── dashboard.blade.php             # Admin dashboard view
│       │   ├── abandoned-carts/                # Abandoned cart management views
│       │   ├── analytics/                      # Business intelligence views
│       │   ├── attributes/                     # Product attribute management views
│       │   ├── auth/                           # Admin login view
│       │   ├── categories/                     # Category CRUD views
│       │   ├── contact-messages/               # Contact message views
│       │   ├── coupons/                        # Coupon management views
│       │   ├── customer-groups/                # Customer group views
│       │   ├── customers/                      # Customer management views
│       │   ├── flash-sales/                    # Flash sale management views
│       │   ├── fraud-blocks/                   # Fraud blocker views
│       │   ├── landing-pages/                  # Landing page builder views
│       │   ├── loyalty/                        # Loyalty program views
│       │   ├── media/                          # Media library views
│       │   ├── orders/                         # Order management views
│       │   ├── pages/                          # CMS page views
│       │   ├── payments/                       # Payment views
│       │   ├── products/                       # Product management views
│       │   ├── returns/                        # Return request views
│       │   ├── reviews/                        # Review moderation views
│       │   ├── roles/                          # Role & permission views
│       │   ├── settings/                       # Storefront + system settings views
│       │   └── users/                          # User management views
│       └── emails/
│           └── contact-form.blade.php          # Contact form email template
├── routes/
│   ├── api.php                                 # ~175 API route definitions
│   ├── web.php                                 # ~215 admin web route definitions
│   └── console.php                             # Console route definitions
├── tests/
│   ├── TestCase.php
│   ├── CreatesApplication.php
│   └── Feature/
│       ├── ApiSecurityTest.php                 # API security guard enforcement tests
│       ├── CheckoutFieldManagerTest.php        # Checkout form configuration tests
│       ├── LandingPageTest.php                 # Landing page CRUD tests
│       ├── MediaDeletionTest.php               # Media deletion integrity tests
│       ├── PathaoIntegrationTest.php           # Pathao courier integration tests
│       ├── SiteSettingsDashboardTest.php        # Settings dashboard tests
│       └── SmokeTest.php                       # Basic app smoke test
├── .env.example                                # Environment variable template
├── .gitignore
├── API_DOCUMENTATION.md                        # Full API endpoint documentation
├── composer.json                               # PHP dependencies
├── artisan                                     # Laravel CLI entry point
└── phpunit.xml.dist                            # PHPUnit configuration
```

---

## Security Model (Strict)

Security is enforced as **baseline behavior**, not opt-in.

### API Default-Deny Policy

All `/api/v1` routes are authenticated by default (`auth:sanctum`). Only explicitly whitelisted endpoints are public. This prevents accidental exposure when adding new routes.

### Public API Endpoints (No Auth Required)

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/v1/auth/register` | User registration |
| `POST` | `/api/v1/auth/login` | User login |
| `POST` | `/api/v1/auth/forgot-password` | Password reset request |
| `POST` | `/api/v1/auth/reset-password` | Password reset execution |
| `GET` | `/api/v1/auth/email/verify/{id}/{hash}` | Email verification (signed URL) |
| `POST` | `/api/v1/stripe/webhook` | Stripe webhook (signature verified) |
| `GET` | `/api/v1/bkash/callback` | bKash payment callback |
| `POST` | `/api/v1/contact` | Contact form submission |
| `POST` | `/api/v1/orders` | Order placement (guest + auth) |
| `POST` | `/api/webhooks/steadfast` | Steadfast courier webhook |

### Internal API (Frontend-Only) Routes

Public-facing data routes (products, categories, settings, etc.) are protected by the `InternalApiOnly` middleware — they require a valid `X-Internal-Secret` header matching the configured `INTERNAL_API_SECRET`. This prevents direct public access while allowing the Next.js frontend to fetch data server-side.

### Admin Guards

- **API admin routes**: Protected by `admin_permission:*` or `is_admin` middleware
- **Web admin routes**: Triple-guarded with `auth:web` + `is_admin` + `admin_permission` middleware stack
- **Controller-level checks**: Many admin operations additionally enforce strict admin checks in service logic
- **Ownership checks**: Applied on user/order/payment scoped resources

### Hardening Notes (Applied)

The following are enforced in the current codebase, not just documented intent:

| Area | Fix |
|---|---|
| **Payments** | Removed the legacy generic "create/process/refund payment" endpoints and their simulated gateway fallback (`PaymentService::simulatePaymentGateway`). All real money movement now goes exclusively through `StripeController`/`BkashController`, which talk to the actual gateway SDKs. |
| **Stock integrity** | Product/variant stock decrements use an atomic conditional `UPDATE ... WHERE stock_quantity >= ?` (`decrementStockIfAvailable()`), and flash-sale/coupon usage reservation uses `lockForUpdate()` row locks — closes race conditions where concurrent checkouts could oversell limited stock or a limited-use coupon. |
| **Webhooks** | Steadfast webhook signature comparison uses `hash_equals()` (fail-closed, timing-safe) instead of `===`; unrecognized status values are logged instead of silently ignored. |
| **Uploaded SVGs** | Sanitized server-side via `DOMDocument`/`DOMXPath` before storage — strips `<script>`, event handler attributes, and `javascript:` URIs to prevent stored XSS through the media library. |
| **Category trees** | `getFullPathAttribute()`/`getDepthAttribute()` and admin category-update validation both guard against parent-cycle corruption (a category set as its own ancestor). |
| **CSV/Excel exports** | Values are prefixed to neutralize formula injection (`=`, `+`, `-`, `@` leading characters) before being written to order/report exports. |
| **Rate limiting** | `throttle:8,1` on register/login/reset-password, `throttle:4,1` on forgot-password, `throttle:20,1` on public order-tracking lookups. |
| **Error responses** | Exception → JSON rendering is scoped to API/JSON requests only, so admin panel (Blade) errors don't leak stack traces as JSON and vice versa. |
| **Trusted proxies** | `bootstrap/app.php` reads `TRUSTED_PROXIES` from env (private-network default) instead of trusting `*`, which previously allowed spoofed `X-Forwarded-For` headers to fake the client IP. |
| **Session cookies** | `SESSION_SECURE_COOKIE` defaults to `true` automatically when `APP_ENV=production` and unset, instead of silently defaulting to `false`. |

---

## Core Modules

### 1. Catalog & Merchandising

**Models**: `Product`, `Category`, `ProductAttribute`, `ProductAttributeValue`, `ProductImage`, `ProductVariant`, `RelatedProduct`

| Feature | Description |
|---|---|
| Products | Full CRUD with rich attributes, images, variants, SEO fields |
| Categories | Hierarchical tree with parent/child relationships, menu endpoint |
| Variants | Matrix generation from attributes, individual pricing & stock per variant |
| Attributes | Configurable attributes (size, color, etc.) with display styles (dropdown, button, swatch) |
| Images | Multi-image upload, primary image selection, media library integration |
| Related Products | Related, frequently-bought-together, upsell, cross-sell recommendation engines |
| Pricing | Regular price, sale price, buy price, quantity-tier pricing |
| Flags | Featured, new, active, free delivery per product |
| Bulk Actions | Bulk activate/deactivate/delete products |
| Product Export | CSV export with filters |

### 2. Cart & Checkout

**Models**: `Cart`, `CartItem`, `AbandonedCart`

| Feature | Description |
|---|---|
| Cart CRUD | Add/update/remove items with variant support |
| Coupon Application | Apply/remove coupons to cart with validation |
| Abandoned Cart Tracking | Client-side checkout progress tracking via `POST /checkout/track` |
| Recovery Markers | Mark abandoned carts as recovered/follow-up/cancelled |
| Guest Checkout | Full order placement without registration |
| Dynamic Checkout Form | Admin-configurable checkout fields (reorder, toggle, required) |
| Cart Recommendations | Related product suggestions based on cart contents |

### 3. Orders & Fulfillment

**Models**: `Order`, `OrderItem`, `OrderNote`, `OrderStatus`, `OrderActivityLog`, `OrderTrackingHistory`

| Feature | Description |
|---|---|
| Order Placement | Support for guest and registered user orders |
| Dynamic Order Statuses | Admin-configurable order status workflow |
| Order Tracking | Tracking number, carrier, events timeline with public tracking URLs |
| Invoices | PDF invoice generation via DomPDF |
| Order Notes | Internal admin notes per order |
| Activity Logs | Full order lifecycle activity tracking |
| Status Updates | Admin status transitions with SMS notifications |
| Bulk Actions | Bulk status update, courier dispatch |
| Discount Management | Post-order discount application/removal |
| Customer Info Edit | Update shipping/billing info on existing orders |
| Item Editing | Add/remove/update items on pending orders |
| Order Source | Track order origin (storefront, admin, landing page) |
| CSV Export | Export filtered orders to CSV |
| Guest Access Token | Hashed token for guest order lookups |
| Cancellation | Customer and admin cancellation with reason tracking |
| Soft Delete & Restore | Orders can be soft-deleted and restored |

### 4. Payments

**Models**: `Payment`, `PaymentGateway`, `SavedPaymentMethod`

| Gateway | Features |
|---|---|
| **Cash on Delivery** | Default gateway, always available |
| **Stripe** | Payment intent creation, confirmation, webhook handling, refunds, saved cards |
| **bKash** | Create payment, execute, query status, refund, callback handling, multi-merchant support (4 credential sets) |

| Feature | Description |
|---|---|
| Gateway Management | Admin CRUD for payment gateways with enable/disable/reorder |
| Saved Payment Methods | Stripe saved cards with default selection |
| Payment Summary | Order payment status summary endpoint |
| Refund Processing | Cross-gateway refund handling via `RefundService` |
| Webhook Processing | Stripe webhook signature verification, bKash callback validation |

### 5. Shipping & Couriers

**Models**: `ShippingMethod`, `ShippingMethodDistrictRate`, `ShippingMethodLocationRule`

| Feature | Description |
|---|---|
| Shipping Methods | Admin CRUD with enable/disable/reorder and rate calculation |
| District-Based Rates | Per-district shipping rate overrides |
| Location Rules | Conditional shipping availability by location |
| Rate Calculator | `POST /shipping-methods/calculate` for real-time rate calculation |
| **Steadfast Courier** | Single/bulk order dispatch, balance check, webhook status sync |
| **Pathao Courier** | Single/bulk dispatch, zone/area lookups, store creation, test connection, webhook listener |
| Consolidated Dashboard | Both couriers managed under unified settings page with sub-tabs |

### 6. Customer Management

**Models**: `User`, `Address`, `CustomerGroup`, `FraudBlock`

| Feature | Description |
|---|---|
| User Profiles | Registration, login, profile update, password change |
| Addresses | Multiple shipping/billing addresses with default selection |
| Customer Groups | Segment customers into groups with manual phone number lists |
| Customer Analytics | Order history, total spent, order count per customer |
| Customer Search | Admin search by phone, email, name |
| BD Locations | Full Bangladesh location hierarchy (division → district → upazila → union) |
| OTP Password Reset | OTP-based password reset support |

### 7. Marketing & Promotions

**Models**: `Coupon`, `CouponUsage`, `FlashSale`, `FlashSaleProduct`, `LandingPage`

| Feature | Description |
|---|---|
| **Coupons** | Percentage/fixed discount, min order, max discount, usage limits, date range, product/category targeting, guest checkout support |
| Coupon Operations | Validate, apply, remove, duplicate, toggle status |
| **Flash Sales** | Scheduled start/end times, per-product stock limits, featured badges |
| Flash Sale Operations | Create, edit, add/remove products, toggle products, end early, extend, duplicate |
| Purchase Validation | Real-time flash sale stock and eligibility validation |
| **Landing Pages** | Slug-based landing pages with location display option and product associations |
| Landing Page Tracking | Track abandoned carts originating from landing pages |

### 8. Loyalty Program

**Models**: `LoyaltyTier`, `LoyaltyReward`, `LoyaltyRedemption`, `LoyaltyTransaction`

| Feature | Description |
|---|---|
| Tiers | Configurable loyalty tiers with point thresholds |
| Points Earning | Automatic points on order completion (configurable rate) |
| Rewards | Admin-defined rewards with point costs |
| Redemptions | Customer reward redemption with coupon generation |
| Leaderboard | Top customers by points |
| Admin Controls | Point adjustments, member management, transaction history, CSV export |
| Coupon Validation | Validate loyalty-generated coupons |

### 9. Returns & Refunds

**Models**: `ReturnRequest`, `ReturnItem`

| Feature | Description |
|---|---|
| Return Eligibility | Check eligibility based on configurable return period (default 30 days) |
| Return Requests | Customer-initiated return with reason and item selection |
| Image Upload | Evidence image upload for return requests |
| Admin Workflow | Approve → Mark received → Process refund pipeline |
| Refund Methods | Configurable refund method per return |
| Admin Notes | Internal notes on return requests |
| Return Export | CSV export of return requests |
| Auto/Manual Refund | Configurable auto-refund vs. manual approval |

### 10. Reviews & Ratings

**Models**: `Review`, `ReviewVote`

| Feature | Description |
|---|---|
| Customer Reviews | Create/update/delete reviews with ratings |
| Review Votes | Helpful/not helpful voting system |
| Featured Reviews | Admin can feature reviews on product pages |
| Admin Replies | Admin can reply to reviews |
| Moderation | Approve/reject/bulk approve/bulk delete workflow |
| Review Summary | Aggregated rating distribution per product |
| Can Review Check | Verify purchase before allowing review |

### 11. Media Library

**Model**: `Media`

| Feature | Description |
|---|---|
| Upload | File upload with metadata extraction |
| WebP Conversion | Automatic WebP conversion on upload for raster images |
| Bulk Convert | Bulk convert existing images to WebP |
| Picker Modal | Embeddable picker for settings inputs (logo, favicon, etc.) |
| CRUD | View, update metadata, delete with bulk operations |
| Path Repair | Artisan command to fix broken public paths |

### 12. Content Management

**Models**: `Page`, `ContactMessage`, `Setting`

| Feature | Description |
|---|---|
| Static Pages | CMS pages with slug-based routing (About, Terms, Privacy, etc.) |
| Contact Form | Public contact form with email dispatch |
| Contact Messages | Admin view, mark read, delete |
| Site Settings | Grouped settings for storefront, SEO, social, hero, banner, footer, checkout, invoice, general |
| Frontend API | Public endpoints to fetch settings by group |

### 13. Notifications & SMS

**Notifications**: 8 classes | **Service**: `SmsService`

| Channel | Notifications |
|---|---|
| **Email** | Welcome, Email Verification, Password Reset, Order Confirmation, Order Shipped, Order Status Updated, Abandoned Cart Reminder, Low Stock Alert |
| **SMS** | Order status updates, custom SMS from admin order page |

| Feature | Description |
|---|---|
| SMS Templates | Admin-configurable SMS templates with variable placeholders |
| SMS Balance | Check SMS API balance from admin panel |
| Dynamic Mail Config | Mail settings (SMTP host, port, credentials) configurable from admin panel, loaded from DB at boot |

### 14. Business Intelligence

**Service**: `BusinessIntelligenceService` (37KB)

| Report | Description |
|---|---|
| Sales Reports | Revenue, order count, average order value by date range |
| Inventory Alerts | Low stock, out of stock product lists |
| Customer Analytics | Top customers, new vs returning, geographic distribution |
| Product Performance | Best sellers, revenue by product, trend analysis |
| Product Trends | Time-series product performance data |
| Frequently Bought Together | Co-purchase analysis |
| CSV Exports | Sales, inventory, and customer reports exportable to CSV |

### 15. Fraud Prevention

**Model**: `FraudBlock`

| Feature | Description |
|---|---|
| Block Types | Block by phone number, email, or IP address |
| Custom Messages | Custom block messages per entry |
| Auto-Block Settings | Configurable thresholds for automatic blocking |
| Quick Block/Unblock | One-click block/unblock from order management |
| Block Check | API to verify if customer is blocked |
| Toggle | Enable/disable individual blocks without deletion |

### 16. Audit Logging

**Model**: `AuditLog` | **Trait**: `Auditable`

| Feature | Description |
|---|---|
| Automatic Logging | Models using the `Auditable` trait auto-log create/update/delete events |
| Change Tracking | Records old and new values for updates |
| Sensitive Field Filtering | Passwords and tokens excluded from logs |
| Admin API | View audit logs with filtering and detail views |

---

## Admin Panel (Server-Rendered)

The admin panel is a full server-rendered Blade application under `/admin` with 25 module view directories.

### Admin Modules

| Module | Route Prefix | Description |
|---|---|---|
| Dashboard | `/admin` | Sales overview, recent orders, key metrics |
| Global Search | `/admin/global-search` | Cross-module search (orders, products, customers) |
| Products | `/admin/products` | Full CRUD, variant matrix, image management |
| Categories | `/admin/categories` | Hierarchical category management |
| Attributes | `/admin/attributes` | Product attribute & value management |
| Orders | `/admin/orders` | Order management, status updates, courier dispatch, SMS, invoice print |
| Payments | `/admin/payments` | Payment list, saved methods, transaction details |
| Users | `/admin/users` | User list, create, role assignment, status toggle |
| Roles | `/admin/roles` | Role CRUD with granular permission assignment |
| Media | `/admin/media` | Upload, browse, WebP convert, bulk operations |
| Coupons | `/admin/coupons` | Full CRUD, duplicate, toggle status |
| Flash Sales | `/admin/flash-sales` | Create, manage, products, extend/end/duplicate |
| Reviews | `/admin/reviews` | Moderation workflow, replies, featured toggle |
| Abandoned Carts | `/admin/abandoned-carts` | View, follow-up, recover, bulk actions, export |
| Returns | `/admin/returns` | Approve, reject, mark received, process refund |
| Loyalty | `/admin/loyalty` | Rewards, tiers, members, transactions, redemptions |
| BI Analytics | `/admin/bi` | Sales reports, inventory, customer, product analytics |
| Customers | `/admin/customers` | Customer list, details, group assignment |
| Customer Groups | `/admin/customer-groups` | Segment management |
| Fraud Blocks | `/admin/fraud-blocks` | Block/unblock management with settings |
| Pages | `/admin/pages` | CMS page management |
| Landing Pages | `/admin/landing-pages` | Marketing landing page builder |
| Contact Messages | `/admin/contact-messages` | Inbox with read tracking |
| **Storefront Settings** | `/admin/settings/site` | Navigation, hero sliders, banners, appearance, SEO |
| **System Settings** | `/admin/settings/system` | General, Checkout, Invoice, Integrations, Couriers, SMS Templates, Order Statuses, Cancellation Reasons, Payment Gateways, Shipping Methods |

---

## Admin RBAC System

**Models**: `AdminRole` | **Middleware**: `EnsureAdminPermission`

The admin panel uses a dynamic role-based access control system:

### How It Works

1. Users have a `staff_role_id` pointing to an `AdminRole`
2. Each `AdminRole` has a JSON `permissions` array
3. The `EnsureAdminPermission` middleware maps route names to permission keys
4. Permission is checked on every admin request

### Permission Keys

| Permission Key | Modules |
|---|---|
| `dashboard.view` | Dashboard |
| `catalog.manage` | Categories, Products, Attributes |
| `media.manage` | Media Library |
| `orders.manage` | Orders, Tracking |
| `payments.view` | Payments |
| `returns.manage` | Returns & Refunds |
| `abandoned_carts.manage` | Abandoned Carts |
| `marketing.manage` | Coupons, Reviews, Flash Sales, Loyalty |
| `analytics.view` | Business Intelligence |
| `users.manage` | Users |
| `roles.manage` | Roles & Permissions |
| `settings.manage` | All Settings |

### Route-to-Permission Mapping

The middleware auto-resolves permissions from route names:

```php
'admin.dashboard'        → 'dashboard.view'
'admin.categories.*'     → 'catalog.manage'
'admin.products.*'       → 'catalog.manage'
'admin.orders.*'         → 'orders.manage'
'admin.settings.*'       → 'settings.manage'
// ... and so on
```

---

## API Endpoint Reference

> Full API documentation is available in [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

### Public Endpoints (No Auth)

#### Authentication
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/auth/register` | Register new user |
| `POST` | `/api/v1/auth/login` | Login and receive token |
| `POST` | `/api/v1/auth/forgot-password` | Request password reset |
| `POST` | `/api/v1/auth/reset-password` | Reset password with token |
| `GET` | `/api/v1/auth/email/verify/{id}/{hash}` | Verify email (signed) |

#### Payments (Webhooks/Callbacks)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/stripe/config` | Get Stripe publishable key |
| `POST` | `/api/v1/stripe/webhook` | Stripe webhook handler |
| `POST` | `/api/v1/stripe/create-payment-intent` | Create Stripe payment intent |
| `POST` | `/api/v1/stripe/confirm-payment` | Confirm Stripe payment |
| `GET` | `/api/v1/bkash/config` | Get bKash config |
| `GET` | `/api/v1/bkash/callback` | bKash payment callback |
| `POST` | `/api/v1/bkash/create-payment` | Create bKash payment |

#### Public Order Tracking
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/track/order/{orderNumber}` | Track by order number |
| `GET` | `/api/v1/track/tracking/{trackingNumber}` | Track by tracking number |

#### Checkout (Guest + Auth)
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/orders` | Place order |
| `POST` | `/api/v1/cart/coupon` | Apply coupon |
| `POST` | `/api/v1/checkout/track` | Track checkout progress |
| `GET` | `/api/v1/orders/{id}/payment-summary` | Payment summary |
| `GET` | `/api/v1/orders/number/{orderNumber}` | Get order by number |
| `GET` | `/api/v1/orders/number/{orderNumber}/guest` | Get order (guest access) |
| `POST` | `/api/v1/contact` | Submit contact form |

### Internal API Routes (X-Internal-Secret Required)

These routes serve the Next.js storefront via server-side rendering:

- **Categories**: List, menu, show, by slug, children
- **Products**: List, featured, new, bestsellers, search, by slug, by category, variants, related/upsell/cross-sell
- **Reviews**: Product reviews, summary, featured (public read)
- **Attributes**: List, show
- **Payment/Shipping Methods**: List, show, calculate
- **BD Locations**: Divisions, districts, upazilas, unions
- **Settings**: All groups (general, hero, social, SEO, footer, banner, checkout)
- **Flash Sales**: List, featured, upcoming, show, check product, validate purchase
- **Pages**: List, show by slug
- **Landing Pages**: Show by slug

### Authenticated Endpoints (Bearer Token)

#### User & Profile
- `POST /auth/logout` | `GET /auth/me` | `POST /auth/email/resend` | `POST /auth/change-password`
- `GET /profile` | `PUT /profile`

#### Cart
- `GET /cart` | `POST /cart/items` | `PUT /cart/items/{id}` | `DELETE /cart/items/{id}` | `DELETE /cart`
- `DELETE /cart/coupon` | `POST /cart/recommendations`

#### Wishlist
- Full CRUD + toggle, check, count, clear, move-to-cart

#### Reviews
- My reviews, create, update, delete, vote, can-review check

#### Addresses
- Full CRUD + default shipping/billing selection

#### Notifications
- List, unread count, mark read, mark all read, delete

#### Returns
- List, create, check eligibility, show, cancel, upload images

#### Loyalty
- Tiers, summary, transactions, rewards, redeem, redemptions, leaderboard

#### Orders
- List, show, cancel, tracking, invoice, notes

#### Payments
- Show by order (`GET /payments/order/{orderId}`) — read-only lookup. Payment creation/processing/refunding happens exclusively through the gateway-specific controllers (`StripeController`, `BkashController`), never through a generic "create payment" endpoint.

#### bKash
- Check status, refund

#### Saved Payment Methods
- List, set default, remove

### Admin API Endpoints

- `GET /admin/orders/export` | `GET /admin/orders/export/download/{filename}`
- `GET /admin/audit-logs` | `GET /admin/audit-logs/{id}`

---

## Middleware Stack

| Middleware | Class | Purpose |
|---|---|---|
| `is_admin` | `IsAdmin` | Checks `canAccessAdminPanel()` on the user model; rejects with 403 |
| `admin_permission` | `EnsureAdminPermission` | Route-name-based RBAC permission check with configurable mapping |
| `internal.api` | `InternalApiOnly` | Validates `X-Internal-Secret` header for frontend data routes |
| `log.api` | `LogApiRequests` | Logs method, URL, IP, user ID, status, duration to `api` log channel |

---

## Services Layer

| Service | Description | Size |
|---|---|---|
| `AuthService` | Registration, login, password reset, email verification, OTP | 6KB |
| `BangladeshLocationResolver` | Resolves BD location hierarchy from address data | 10KB |
| `BusinessIntelligenceService` | Sales reports, inventory alerts, customer & product analytics, exports | 37KB |
| `CartService` | Cart CRUD, item management, totals calculation | 6KB |
| `CategoryService` | Category tree operations and queries | 3KB |
| `CheckoutAddressConfigService` | Dynamic checkout form field configuration and validation | 18KB |
| `CheckoutTaxService` | Tax calculation logic | 1KB |
| `CouponService` | Coupon validation, eligibility checks, application logic | 10KB |
| `FlashSaleService` | Flash sale scheduling, stock tracking, eligibility validation | 10KB |
| `LoyaltyService` | Points earning/redemption, tier management, leaderboard | 15KB |
| `OrderCustomerSyncService` | Syncs guest orders to registered users by phone/email | 11KB |
| `OrderService` | **Core order lifecycle** — placement, status, discounts, items, courier dispatch | **75KB** |
| `PaymentService` | Payment orchestration and status management | 4KB |
| `ProductService` | Product queries, catalog operations, search | 4KB |
| `RefundService` | Cross-gateway refund processing (Stripe, bKash, manual) | 9KB |
| `RelatedProductService` | Related, upsell, cross-sell, frequently-bought-together engines | 10KB |
| `SettingService` | Settings CRUD with group management and caching | 11KB |
| `SmsService` | SMS dispatch with template rendering and provider integration | 11KB |
| `UserService` | User CRUD and management operations | 2KB |
| `BkashPaymentService` | bKash tokenize, create, execute, query, refund | 13KB |
| `StripePaymentService` | Stripe intent, confirm, webhook processing, refund, saved cards | 15KB |

---

## Repository Pattern

All repositories implement interface contracts registered in `RepositoryServiceProvider`:

| Interface | Implementation | Entity |
|---|---|---|
| `BaseRepositoryInterface` | `BaseRepository` | Generic CRUD operations |
| `UserRepositoryInterface` | `UserRepository` | User queries |
| `CategoryRepositoryInterface` | `CategoryRepository` | Category tree queries |
| `ProductRepositoryInterface` | `ProductRepository` | Product search/filter/list |
| `CartRepositoryInterface` | `CartRepository` | Cart and item operations |
| `OrderRepositoryInterface` | `OrderRepository` | Order queries with relations |
| `PaymentRepositoryInterface` | `PaymentRepository` | Payment record access |
| `SettingRepositoryInterface` | `SettingRepository` | Setting group operations |

---

## Eloquent Models

50 models organized by domain:

### Core Commerce
| Model | Key Features |
|---|---|
| `Product` | Relationships to categories, images, variants, attributes, reviews; scopes for active, featured, search |
| `Category` | Self-referencing parent/child hierarchy, many-to-many with products |
| `ProductVariant` | Per-variant pricing, stock, SKU, images |
| `ProductAttribute` | Configurable attributes with display styles |
| `ProductAttributeValue` | Attribute value options with optional images |
| `ProductImage` | Multi-image with primary flag |
| `RelatedProduct` | Typed relationships (related, upsell, cross-sell, frequently-bought-together) |

### Orders & Payments
| Model | Key Features |
|---|---|
| `Order` | Soft deletes, comprehensive relationships, status management, guest token hashing |
| `OrderItem` | Line items with variant support |
| `OrderNote` | Admin internal notes |
| `OrderStatus` | Dynamic configurable statuses |
| `OrderActivityLog` | Lifecycle event tracking |
| `OrderTrackingHistory` | Carrier tracking events timeline |
| `Payment` | Multi-gateway payment records |
| `PaymentGateway` | Gateway configuration with enable/disable |
| `SavedPaymentMethod` | Stripe saved cards |

### Customer & Auth
| Model | Key Features |
|---|---|
| `User` | Roles (customer/admin/staff), admin permissions, loyalty integration |
| `Address` | Shipping/billing with BD location support, defaults |
| `Cart` / `CartItem` | Shopping cart with variant support |
| `Wishlist` | Product wishlist per user |
| `CustomerGroup` | Customer segmentation with manual phone lists |

### Marketing
| Model | Key Features |
|---|---|
| `Coupon` | Complex eligibility rules, usage tracking |
| `CouponUsage` | Per-user coupon usage records |
| `FlashSale` | Scheduled sales with status management |
| `FlashSaleProduct` | Per-product sale config with stock limits |
| `LandingPage` | Slug-based pages with product associations |

### Loyalty
| Model | Key Features |
|---|---|
| `LoyaltyTier` | Point thresholds and benefits |
| `LoyaltyReward` | Redeemable rewards with point costs |
| `LoyaltyRedemption` | Redemption records with coupon generation |
| `LoyaltyTransaction` | Points earned/spent transaction log |

### Returns & Reviews
| Model | Key Features |
|---|---|
| `ReturnRequest` | Full return lifecycle with approval workflow |
| `ReturnItem` | Per-item return details |
| `Review` | Ratings, admin replies, featured flag, moderation status |
| `ReviewVote` | Helpful/not helpful votes |

### Content & Settings
| Model | Key Features |
|---|---|
| `Setting` | Grouped key-value settings with types |
| `Page` | CMS static pages |
| `ContactMessage` | Contact form submissions |
| `Media` | File uploads with WebP conversion support |

### System
| Model | Key Features |
|---|---|
| `AdminRole` | JSON permissions array, user assignments |
| `AuditLog` | Automatic change tracking via `Auditable` trait |
| `FraudBlock` | Phone/email/IP blocking |
| `AbandonedCart` | Checkout progress tracking with follow-up management |

### Bangladesh Locations
| Model | Key Features |
|---|---|
| `BdDivision` | Top-level administrative divisions |
| `BdDistrict` | Districts within divisions |
| `BdUpazila` | Upazilas within districts |
| `BdUnion` | Unions within upazilas |
| `ShippingMethod` | Shipping options with rates |
| `ShippingMethodDistrictRate` | Per-district rate overrides |
| `ShippingMethodLocationRule` | Location-based availability rules |

---

## Form Requests (Validation)

17 form request classes organized by domain:

| Domain | Requests | Validations |
|---|---|---|
| **Auth** | `RegisterRequest`, `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`, `ChangePasswordRequest` | Email uniqueness, password rules, token validation |
| **Admin** | `StoreCategoryRequest`, `UpdateCategoryRequest`, `StoreProductRequest`, `UpdateProductRequest`, `UpdateOrderStatusRequest` | Slug uniqueness, image validation, status transitions |
| **Cart** | `AddToCartRequest`, `UpdateCartItemRequest` | Product existence, stock validation, variant support |
| **Category** | `StoreCategoryRequest`, `UpdateCategoryRequest` | Name/slug rules, parent validation |
| **Order** | `StoreOrderRequest`, `UpdateOrderStatusRequest` | Complex checkout validation (12KB), address rules, payment method |
| **Product** | `StoreProductRequest`, `UpdateProductRequest` | Price rules, image limits, variant validation |
| **User** | `UpdateUserRequest` | Profile field validation |

---

## API Resources (Serialization)

19 API resource classes for consistent JSON responses:

| Resource | Serializes | Key Fields |
|---|---|---|
| `UserResource` | User profile data | name, email, role, phone |
| `ProductResource` | Full product data | prices, images, variants, categories, reviews |
| `ProductVariantResource` | Variant details | SKU, price, stock, attribute values |
| `ProductImageResource` | Image data | URL, alt text, primary flag |
| `ProductAttributeResource` | Attribute metadata | name, display style, values |
| `ProductAttributeValueResource` | Attribute values | label, value, image |
| `CategoryResource` | Category tree | name, slug, parent, children |
| `CartResource` | Cart with items | items, totals, coupon |
| `CartItemResource` | Cart line items | product, variant, quantity, subtotal |
| `OrderResource` | Order details | items, status, payment, tracking, addresses |
| `OrderItemResource` | Order line items | product snapshot, variant, quantity, prices |
| `OrderTrackingResource` | Tracking info | carrier, tracking number, events |
| `PaymentResource` | Payment details | gateway, amount, status, transaction ID |
| `SavedPaymentMethodResource` | Saved cards | last 4 digits, brand, expiry, default flag |
| `AddressResource` | Address data | full address with BD location IDs |
| `ReviewResource` | Review data | rating, comment, author, votes, admin reply |
| `WishlistResource` | Wishlist items | product details, date added |
| `SettingResource` | Admin settings | key, value, group, type |
| `PublicSettingResource` | Public settings | sanitized settings for frontend |

---

## Database Schema

83 migrations covering:

### Core Tables
- `users` — with role, staff_role_id, stripe_customer_id, email_verified_at
- `password_reset_tokens` — password reset with OTP support
- `personal_access_tokens` — Sanctum API tokens
- `categories` — hierarchical with parent_id, slug, is_active, sort_order
- `category_product` — many-to-many pivot
- `products` — comprehensive product table with pricing, inventory, SEO, flags
- `product_images` — multi-image with is_primary
- `product_variants` — per-variant pricing, stock, SKU, image
- `product_attribute_values` — with optional image support
- `related_products` — typed product relationships

### Commerce Tables
- `carts` / `cart_items` — with product_variant_id support
- `orders` — with guest support, soft deletes, checkout_fields_payload, guest_access_token_hash, order_source, loyalty fields
- `order_items` — with product_variant_id
- `order_statuses` — dynamic configurable statuses
- `order_activity_logs` — lifecycle events
- `order_tracking_history` — carrier tracking events
- `order_notes` — admin internal notes
- `payments` — multi-gateway with transaction tracking
- `payment_gateways` — gateway configuration
- `saved_payment_methods` — Stripe saved cards

### Shipping Tables
- `shipping_methods` — with rate types and rules
- `shipping_method_district_rates` — per-district overrides
- `shipping_method_location_rules` — location-based availability

### Marketing Tables
- `coupons` — complex rules with guest checkout support
- `coupon_usages` — usage tracking
- `flash_sales` / `flash_sale_products` — scheduled sales
- `landing_pages` — with product_ids and location display

### Engagement Tables
- `wishlists` — user product wishlists
- `reviews` / `review_votes` — review system
- `abandoned_carts` — full checkout tracking with follow-up dates, reminder tracking, landing page slug
- `loyalty_tiers` / `loyalty_rewards` / `loyalty_redemptions` / `loyalty_transactions`

### System Tables
- `settings` — grouped key-value with checkout form schema
- `admin_roles` — JSON permissions
- `audit_logs` / `notifications`
- `media` — file uploads
- `pages` — CMS content
- `contact_messages` — contact form submissions
- `fraud_blocks` — with custom messages
- `customer_groups` — with manual phone numbers
- `addresses` — with BD location columns

### Bangladesh Location Tables
- `bd_divisions` / `bd_districts` / `bd_upazilas` / `bd_unions`

---

## Artisan Commands

| Command | Signature | Description |
|---|---|---|
| **Send Reminders** | `cart:send-reminders --hours=2` | Send email reminders for abandoned carts older than N hours |
| **Expire Flash Sales** | `flash-sales:expire` | Auto-expire flash sales past their end time |
| **Low Stock Check** | `inventory:check-low-stock --threshold=5` | Notify admins of products below stock threshold |
| **Export Orders** | `orders:export` | Export orders to CSV with date/status filters |
| **Reconcile Users** | `orders:reconcile-users` | Sync guest orders to registered users by phone/email match |
| **Repair Media** | `media:repair-public-paths` | Fix broken media public URLs |
| **Sync BD Locations** | `locations:sync-bd` | Sync Bangladesh location data from BBS API |

---

## Scheduled Jobs

Defined in `app/Console/Kernel.php`:

| Job | Schedule | Window | Description |
|---|---|---|---|
| `cart:send-reminders --hours=2` | Every 2 hours | 9:00–21:00 | Abandoned cart email reminders |
| `flash-sales:expire` | Every 15 minutes | All day | Auto-expire ended flash sales |
| `inventory:check-low-stock --threshold=5` | Daily at 09:00 | — | Low stock notification alerts |
| `auth:clear-resets` | Daily | — | Clean expired password reset tokens |
| `sanctum:prune-expired --hours=24` | Weekly | — | Prune expired Sanctum API tokens |

### Crontab Entry
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Email & Notification System

### Notification Classes

| Notification | Channel | Trigger |
|---|---|---|
| `WelcomeNotification` | Email | User registration |
| `VerifyEmailNotification` | Email | Email verification request |
| `ResetPasswordNotification` | Email | Password reset request |
| `OrderConfirmation` | Email | Order placement |
| `OrderShipped` | Email | Order shipped status |
| `OrderStatusUpdated` | Email | Any order status change |
| `AbandonedCartReminder` | Email | Scheduled abandoned cart job |
| `LowStockAlert` | Email | Daily low stock check |

### Mail Classes

| Mail | Template | Description |
|---|---|---|
| `ContactFormMail` | `emails/contact-form.blade.php` | Contact form submission forwarding |

### Dynamic Mail Configuration

Mail settings (SMTP host, port, encryption, credentials, from address) are loaded from the `settings` database table at application boot via `AppServiceProvider`, allowing admin panel configuration without `.env` changes.

---

## Observers & Event Listeners

### Observers

| Observer | Model | Actions |
|---|---|---|
| `UserObserver` | `User` | On `created`, `updated`, `restored` — syncs guest orders to the user via `OrderCustomerSyncService` (matches by phone/email) |

### Event Listeners

| Listener | Event | Actions |
|---|---|---|
| `PathaoWebhookListener` | Pathao webhook events | Processes Pathao courier status updates and syncs order tracking |

### Trait-Based Hooks

| Trait | Models | Actions |
|---|---|---|
| `Auditable` | Any model using the trait | Auto-logs `created`, `updated`, `deleted` events to `audit_logs` with old/new values, excluding sensitive fields |

---

## Configuration Files

| Config File | Purpose |
|---|---|
| `config/app.php` | Application name, environment, providers |
| `config/auth.php` | Authentication guards and providers (web + sanctum) |
| `config/bkash.php` | bKash PGW credentials (sandbox/production) |
| `config/cache.php` | Cache stores (Redis) |
| `config/cors.php` | CORS origins (frontend + admin + dev) |
| `config/database.php` | Database connections (MySQL + Redis) |
| `config/filesystems.php` | Filesystem disks (local, public) |
| `config/logging.php` | Log channels (stack, single, `api` channel) |
| `config/pathao.php` | Pathao courier API credentials and settings |
| `config/sanctum.php` | Sanctum token configuration |
| `config/session.php` | Session driver and lifetime |
| `config/shop.php` | **Shop-specific settings** — company info, currency, tax, inventory, orders, returns, pagination, cart, loyalty, internal API secret |
| `config/steadfast-courier.php` | Steadfast courier API credentials |

---

## Requirements

- **PHP** 8.2+
- **Composer** 2+
- **MySQL** 8+ or **MariaDB** 10.6+
- **Redis** (for cache, queue, and session)
- **PHP Extensions**: BCMath, Ctype, cURL, DOM, Fileinfo, GD/Imagick (for WebP), JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

---

## Installation

```bash
# Clone the repository
git clone <your-repository-url>
cd ecommerce-api

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

---

## Environment Configuration

Update `.env` values for your environment. Full template available in `.env.example`.

### Core Application
```env
APP_NAME=EcommerceAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
ADMIN_URL=http://localhost:3001
CORS_EXTRA_ORIGIN=
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

### Redis / Cache / Queue / Session
```env
CACHE_DRIVER=redis
CACHE_PREFIX=ecommerce_cache_
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PREFIX=ecommerce_
```

> **No Redis available locally?** Set `CACHE_DRIVER=file`, `QUEUE_CONNECTION=sync`, and `SESSION_DRIVER=file` instead. Everything works identically for local development — queued jobs just run synchronously instead of via a worker. Use Redis in staging/production for real queue workers and shared cache.

### Sanctum Authentication
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

### Shop Configuration
```env
SHOP_COMPANY_NAME="Inner Collection"
SHOP_COMPANY_ADDRESS="Dhaka, Bangladesh"
SHOP_COMPANY_PHONE=+8801700000000
SHOP_COMPANY_EMAIL=info@innercollection.com.bd
SHOP_CURRENCY=BDT
SHOP_CURRENCY_SYMBOL=Tk
SHOP_TAX_RATE=0
SHOP_TAX_INCLUDED=true
SHOP_LOW_STOCK_THRESHOLD=5
SHOP_ORDER_PREFIX=ORD
SHOP_RETURN_PERIOD_DAYS=30
SHOP_LOYALTY_POINTS_PER_CURRENCY=1
SHOP_LOYALTY_CURRENCY_PER_POINT=0.10
```

### Internal API Security
```env
INTERNAL_API_SECRET=your-secure-random-secret
```

### Stripe
```env
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
```

### bKash (Multi-Merchant Support)
```env
BKASH_SANDBOX=true
BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=
BKASH_CALLBACK_URL="${APP_URL}/api/v1/bkash/callback"

# Additional bKash merchant credentials (optional)
BKASH_APP_KEY_2=
BKASH_APP_SECRET_2=
BKASH_USERNAME_2=
BKASH_PASSWORD_2=
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

> **Note**: Mail settings can also be configured from the admin panel (Settings → System → Integrations). Admin-configured values override `.env` values at runtime.

---

## Database & Seed Data

```bash
# Run all migrations (83 migration files)
php artisan migrate

# Seed default data
php artisan db:seed

# Create storage symlink
php artisan storage:link
```

### What Gets Seeded

| Seeder | Data |
|---|---|
| `DatabaseSeeder` | Admin user (`admin@example.com` / `password`) |
| `PaymentGatewaySeeder` | COD, Stripe, and bKash payment gateways |
| `SettingSeeder` | Default settings for all groups (general, SEO, social, hero, banner, footer, checkout, invoice, integration) |
| `LoyaltyTierSeeder` | Default loyalty tiers |
| `PagesTableSeeder` | Default static pages (About, Terms, Privacy, etc.) |

### Default Admin Credentials
```
Email:    admin@example.com
Password: password
```

### Optional Seeders (commented out by default)

- `ShippingMethodSeeder` — Default shipping methods
- `CouponSeeder` — Sample coupons

---

## Running the Application

### Development Server
```bash
# Start the Laravel development server
php artisan serve
```

- **API Base URL**: `http://localhost:8000/api/v1`
- **Admin Panel**: `http://localhost:8000/admin/login`
- **Health Check**: `http://localhost:8000/api/health`

### Queue Worker
```bash
# Start the queue worker (required for async jobs)
php artisan queue:work
```

### Sync Bangladesh Locations
```bash
# First-time setup: sync BD location data
php artisan locations:sync-bd
```

---

## Testing

### Run All Tests
```bash
php artisan test
```

### Test Suite

| Test File | Coverage |
|---|---|
| `ApiSecurityTest` | Enforces default-deny API policy, whitelist route publicity, admin middleware presence |
| `CheckoutFieldManagerTest` | Checkout form field configuration, reorder, toggle, validation |
| `LandingPageTest` | Landing page CRUD, slug routing, product associations |
| `MediaDeletionTest` | Media file deletion integrity |
| `PathaoIntegrationTest` | Pathao courier integration, zone/area lookups, dispatch |
| `SiteSettingsDashboardTest` | Settings dashboard rendering, group editing |
| `SmokeTest` | Basic application health |

### Route Inspection
```bash
# List all API routes
php artisan route:list --path=api/v1

# List all admin web routes
php artisan route:list --path=admin

# JSON output for scripting
php artisan route:list --path=api/v1 --json
```

---

## Deployment Security Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate a strong `APP_KEY`
- [ ] Set a strong, unique `INTERNAL_API_SECRET`
- [ ] Configure HTTPS and trusted proxy settings
- [ ] Configure CORS origins to match production domains only
- [ ] Configure `SANCTUM_STATEFUL_DOMAINS` correctly
- [ ] Keep webhook/callback endpoints reachable but verify signatures
- [ ] Run migrations with backup/rollback policy
- [ ] Configure Redis for production (auth, persistence)
- [ ] Set up queue workers with supervisor
- [ ] Configure the scheduler crontab
- [ ] Rotate and secure all API/payment/SMS/courier credentials
- [ ] Set proper file permissions on `storage/` and `bootstrap/cache/`
- [ ] Monitor failed auth attempts and rate-limit events
- [ ] Configure log rotation for `storage/logs/`
- [ ] Review and restrict admin panel access by IP if needed
- [ ] Enable `MAIL_ENCRYPTION=tls` in production

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Frontend shows only the header — no products/categories/settings load | Frontend `.env.local` is missing or `INTERNAL_API_SECRET` is empty. The `/api/internal/*` proxy hard-fails with HTTP 500 whenever the secret isn't set, and everything data-driven on the storefront goes through that proxy. | Ensure `INTERNAL_API_SECRET` is set in **both** `backend/.env` and `frontend/.env.local`, and that the two values match exactly. |
| `composer install` fails with `ext-fileinfo` requirement not satisfied | The `fileinfo` PHP extension is disabled (common on a fresh Windows PHP install). | Uncomment `extension=fileinfo` in `php.ini` and restart `php artisan serve`. Verify with `php -m | grep fileinfo`. |
| App errors on boot referencing Redis (`Connection refused` on `127.0.0.1:6379`) | `CACHE_DRIVER`/`QUEUE_CONNECTION`/`SESSION_DRIVER` are set to `redis` but no Redis server is running. | For local development without Redis, set all three to `file`/`sync`/`file` in `.env`. Use `redis` in staging/production as documented above. |
| `php artisan test` fails immediately with a missing `Unit` testsuite error | `phpunit.xml.dist` declares a `Unit` suite but `tests/Unit` doesn't exist in this project (only `tests/Feature` is used). | Run `php artisan test --testsuite=Feature` explicitly, or add an empty `tests/Unit` directory. |
| `LandingPageTest` fails locally but not elsewhere | `INTERNAL_API_SECRET` is missing from `.env`. Because `config/shop.php` always registers the config key via `env('INTERNAL_API_SECRET', '')`, the key resolves to an empty string rather than being "missing", so `config('shop.internal_api_secret', 'fallback')`-style fallbacks never kick in. | Set an explicit `INTERNAL_API_SECRET` value in `.env` — there is no working default. |
| MySQL connection fails with `SQLSTATE[HY000] [2054] ... auth_gssapi_client` | Wrong or empty `DB_PASSWORD` for the configured `DB_USERNAME`, often from copying `.env.example` verbatim. | Set the real root/user password for your local MySQL/MariaDB instance in `DB_PASSWORD`. |

---

## Documentation

| Resource | Location |
|---|---|
| API Documentation | [API_DOCUMENTATION.md](API_DOCUMENTATION.md) |
| Postman Collection | `postman/E-Commerce-API-A-Z.postman_collection.json` |
| Route Inventory | `php artisan route:list` |
| Environment Template | `.env.example` |

---

## License

MIT
