# E-Commerce API Documentation

## Metadata
- Version: 1.2
- Base URL (production): https://api.innercollection.com.bd/api/v1
- Base URL (local): http://localhost:8000/api/v1
- Public health check: /api/health
- Last updated: April 1, 2026
- Total `/api/v1` routes: 154
- Authenticated `/api/v1` routes: 147
- Public `/api/v1` routes: 7 (strict whitelist)

## Table of Contents
- API Conventions
- Security Model (Strict)
- Authentication and Access Control
- Rate Limiting
- Data Contracts and Important Fields
- Endpoint Catalog (Complete)
- Core Request Examples
- Validation Cheat Sheet (Key Endpoints)
- Status Codes and Error Handling
- Notes for Integrators

## API Conventions

### Required headers
For JSON requests:
- `Accept: application/json`
- `Content-Type: application/json`

For protected routes:
- `Authorization: Bearer <sanctum_token>`

### Standard success response
```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

### Standard error response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["validation message"]
  }
}
```

### Pagination
Many list endpoints return Laravel paginator structures (`data`, `links`, `meta`) when applicable.

## Security Model (Strict)

### Default-deny policy
- All `/api/v1` routes are protected by `auth:sanctum` by default.
- Only explicitly whitelisted routes are publicly accessible.
- This is enforced both in routing and by dedicated feature tests.

### Public `/api/v1` whitelist (only 7)
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/email/verify/{id}/{hash}`
- `POST /stripe/webhook`
- `GET /bkash/callback`

### Admin route hardening
Admin-sensitive API endpoints use one or more of:
- `admin_permission:*` middleware
- `is_admin` middleware
- Controller/service-level strict admin checks (`role = admin`) on specific actions

### Guardrail tests
Security behavior is enforced by tests in `tests/Feature/ApiSecurityTest.php`:
- Unexpected public `/api/v1` routes fail tests
- Missing admin middleware for `/api/v1/admin/*` fails tests

## Authentication and Access Control

### Public authentication routes
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/email/verify/{id}/{hash}`

### Authenticated authentication routes
- `POST /auth/logout`
- `GET /auth/me`
- `POST /auth/email/resend`
- `POST /auth/change-password`

### Password reset modes
`POST /auth/reset-password` supports either:
- reset token flow (`token`)
- OTP flow (`otp`, 4 digits)

Validation summary:
- `token`: required when `otp` is absent
- `otp`: required when `token` is absent, must be 4 digits
- `email`: required
- `password` + `password_confirmation`: required

### Ownership and authorization notes
- Several authenticated endpoints apply owner-scoped checks (orders, payments, returns, profile)
- Some admin-capable endpoints still require strict admin role inside controller logic

## Rate Limiting
- General API limiter (`api`):
  - Authenticated: 120 requests/minute per user
  - Guests: 60 requests/minute per IP
- Auth limiter (`auth`) for register/login/forgot/reset:
  - 5 requests/minute per IP

## Data Contracts and Important Fields

### Product payload highlights
Product responses include dynamic pricing fields:
- `dynamic_price_for_quantity_1`
- `has_dynamic_discount`
- `dynamic_discount_tiers` (from `meta_data.quantity_pricing`)
- `free_delivery`

### Variant attribute payload highlights
Variant attributes may include:
- `color_code`
- `image`
- `image_url`

### Integrations and SMS behavior
OTP message format:
- `Your <Brand> OTP is XXXX`

`forgot-password` triggers reset email and SMS OTP when phone/config are available.

## Endpoint Catalog (Complete)

All endpoints below are under `/api/v1` unless explicitly noted.

---

## 1) System (outside `/api/v1`)
Public:
- `GET /api/health` - Health status

---

## 2) Public Whitelist Endpoints (`/api/v1`)

### Authentication
- `POST /auth/register` - Register account
- `POST /auth/login` - Login and issue token
- `POST /auth/forgot-password` - Send reset email + SMS OTP trigger
- `POST /auth/reset-password` - Reset by token or OTP
- `GET /auth/email/verify/{id}/{hash}` - Signed email verification

### Payment provider callbacks
- `POST /stripe/webhook` - Stripe webhook callback
- `GET /bkash/callback` - bKash callback redirect target

---

## 3) Authenticated Endpoints (Bearer token required)

### Auth session/profile
- `POST /auth/logout`
- `GET /auth/me`
- `POST /auth/email/resend`
- `POST /auth/change-password`
- `GET /profile`
- `PUT /profile`

### Categories
- `GET /categories`
- `GET /categories/menu`
- `GET /categories/slug/{slug}`
- `GET /categories/{id}`
- `GET /categories/{id}/children`

Admin write operations (middleware + admin checks):
- `POST /categories`
- `PUT /categories/{id}`
- `DELETE /categories/{id}`

### Products
- `GET /products`
- `GET /products/featured`
- `GET /products/new`
- `GET /products/bestsellers`
- `GET /products/search`
- `GET /products/slug/{slug}`
- `GET /products/category/{categoryId}`
- `GET /products/{id}`
- `GET /products/{id}/variants`

Admin write operations (middleware + admin checks):
- `POST /products`
- `PUT /products/{id}`
- `DELETE /products/{id}`
- `POST /products/bulk-action`

### Product Reviews
Product page review reads (authenticated in current strict model):
- `GET /products/{productId}/reviews`
- `GET /products/{productId}/reviews/summary`
- `GET /products/{productId}/reviews/featured`

Authenticated user review operations:
- `GET /reviews/my`
- `POST /reviews`
- `PUT /reviews/{review}`
- `DELETE /reviews/{review}`
- `POST /reviews/{review}/vote`
- `DELETE /reviews/{review}/vote`
- `GET /reviews/can-review/{productId}`

### Attributes
- `GET /attributes`
- `GET /attributes/{id}`

### Payment/Shipping discovery (authenticated in strict mode)
- `GET /payment-methods`
- `GET /payment-methods/{code}`
- `GET /shipping-methods`
- `GET /shipping-methods/{code}`
- `POST /shipping-methods/calculate`

### Frontend Settings (authenticated in strict mode)
- `GET /settings`
- `GET /settings/hero`
- `GET /settings/general`
- `GET /settings/social`
- `GET /settings/seo`
- `GET /settings/footer`
- `GET /settings/banner`
- `GET /settings/{group}`

### Stripe and bKash operations (authenticated)
- `GET /stripe/config`
- `POST /stripe/create-payment-intent`
- `POST /stripe/confirm-payment`
- `GET /bkash/config`
- `POST /bkash/create-payment`
- `GET /bkash/check-status`

Admin refund operation:
- `POST /bkash/refund`

### Order Tracking and Recommendations
- `GET /track/order/{orderNumber}`
- `GET /track/tracking/{trackingNumber}`
- `GET /products/{product}/related`
- `GET /products/{product}/frequently-bought-together`
- `GET /products/{product}/upsell`
- `GET /products/{product}/cross-sell`
- `POST /cart/recommendations`

### Flash Sales
- `GET /flash-sales`
- `GET /flash-sales/featured`
- `GET /flash-sales/upcoming`
- `GET /flash-sales/{slug}`
- `GET /flash-sales/product/{productId}`
- `POST /flash-sales/validate-purchase`

### Loyalty
- `GET /loyalty/tiers`
- `GET /loyalty/summary`
- `GET /loyalty/transactions`
- `GET /loyalty/rewards`
- `POST /loyalty/redeem`
- `GET /loyalty/redemptions`
- `GET /loyalty/redemptions/active`
- `POST /loyalty/redemptions/{redemption}/cancel`
- `POST /loyalty/validate-coupon`
- `GET /loyalty/leaderboard`

### Users
- `GET /users/{id}` (self or admin)
- `PUT /users/{id}` (self or admin)

Admin user-management operations:
- `GET /users`
- `POST /users`
- `DELETE /users/{id}`
- `POST /users/{id}/toggle-status`

### Cart and Coupons
- `GET /cart`
- `POST /cart/items`
- `PUT /cart/items/{productId}`
- `DELETE /cart/items/{productId}`
- `DELETE /cart`
- `POST /cart/coupon`
- `DELETE /cart/coupon`
- `GET /coupons/validate`
- `GET /coupons/available`

### Wishlist
- `GET /wishlist`
- `POST /wishlist`
- `POST /wishlist/toggle`
- `GET /wishlist/check`
- `GET /wishlist/count`
- `DELETE /wishlist/clear`
- `DELETE /wishlist/{wishlist}`
- `DELETE /wishlist/product`
- `POST /wishlist/{wishlist}/move-to-cart`

### Addresses
- `GET /addresses`
- `POST /addresses`
- `GET /addresses/default/shipping`
- `GET /addresses/default/billing`
- `GET /addresses/{address}`
- `PUT /addresses/{address}`
- `DELETE /addresses/{address}`
- `POST /addresses/{address}/set-default`

### Checkout Tracking (Abandoned Cart)
- `POST /checkout/track`
- `POST /checkout/recovered`

### Notifications
- `GET /notifications`
- `GET /notifications/unread-count`
- `POST /notifications/{id}/read`
- `POST /notifications/read-all`
- `DELETE /notifications/{id}`

### Orders and Notes
- `GET /orders`
- `POST /orders`
- `GET /orders/{id}`
- `GET /orders/number/{orderNumber}`
- `POST /orders/{id}/cancel`
- `GET /orders/{id}/tracking`
- `GET /orders/{id}/invoice`
- `GET /orders/{id}/notes`

Admin order operations:
- `POST /orders/{id}/notes`
- `DELETE /orders/{id}/notes/{noteId}`
- `PUT /orders/{id}/status`
- `GET /orders/status/{status}`

### Payments
- `GET /payments/order/{orderId}`
- `POST /payments`
- `POST /payments/{paymentId}/process`

Admin payment operation:
- `POST /payments/{paymentId}/refund`

### Returns and Refunds
- `GET /returns`
- `POST /returns`
- `GET /returns/check-eligibility`
- `GET /returns/{return}`
- `POST /returns/{return}/cancel`
- `POST /returns/{return}/upload-images`

### Admin API Utilities
- `GET /admin/orders/export`
- `GET /admin/orders/export/download/{filename}`
- `GET /admin/audit-logs`
- `GET /admin/audit-logs/{id}`

---

## Core Request Examples

## 1) Register
`POST /auth/register`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "8801712345678"
}
```

## 2) Login
`POST /auth/login`

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

## 3) Authenticated request headers
```http
Authorization: Bearer <sanctum_token>
Accept: application/json
Content-Type: application/json
```

## 4) Forgot Password
`POST /auth/forgot-password`

```json
{
  "email": "john@example.com"
}
```

## 5) Reset Password (OTP mode)
`POST /auth/reset-password`

```json
{
  "email": "john@example.com",
  "otp": "1234",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

## 6) Add Item to Cart
`POST /cart/items`

```json
{
  "product_id": 10,
  "quantity": 2
}
```

## 7) Create Order
`POST /orders`

```json
{
  "shipping_name": "John Doe",
  "shipping_email": "john@example.com",
  "shipping_phone": "8801712345678",
  "shipping_address": "House 10, Road 5",
  "shipping_city": "Dhaka",
  "shipping_state": "Dhaka",
  "shipping_zip": "1207",
  "shipping_country": "Bangladesh",
  "notes": "Leave at front desk",
  "shipping_method": "inside_dhaka",
  "payment_method": "stripe"
}
```

## 8) Product Response Example (important fields)
`GET /products/{id}`

```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Example Product",
    "regular_price": 1200,
    "sale_price": 1000,
    "current_price": 1000,
    "dynamic_price_for_quantity_1": 1000,
    "has_dynamic_discount": true,
    "dynamic_discount_tiers": [
      {"min_quantity": 1, "unit_price": 1000},
      {"min_quantity": 3, "unit_price": 950},
      {"min_quantity": 5, "unit_price": 900}
    ],
    "free_delivery": true,
    "variants": [
      {
        "id": 501,
        "attributes": [
          {
            "attribute_name": "Color",
            "value": "Black",
            "color_code": "#000000",
            "image": "attributes/colors/black.png",
            "image_url": "https://api.innercollection.com.bd/storage/attributes/colors/black.png"
          }
        ]
      }
    ]
  }
}
```

## Validation Cheat Sheet (Key Endpoints)

## Auth
- `POST /auth/register`
  - Required: `name`, `email`, `password`, `password_confirmation`
  - Optional: `phone`
- `POST /auth/login`
  - Required: `email`, `password`
- `POST /auth/forgot-password`
  - Required: `email`
- `POST /auth/reset-password`
  - Required: `email`, `password`, `password_confirmation`
  - Required either: `token` or `otp` (4 digits)

## Cart
- `POST /cart/items`
  - Required: `product_id` (exists), `quantity` (1..100)
- `PUT /cart/items/{productId}`
  - Required: `quantity` (0..100)

## Orders
- `POST /orders`
  - Required shipping fields:
    - `shipping_name`
    - `shipping_email`
    - `shipping_address`
    - `shipping_city`
    - `shipping_zip`
    - `shipping_country`
    - `shipping_method` (active shipping method code)
    - `payment_method` (active payment gateway code)
  - Optional:
    - `shipping_phone`
    - `shipping_state`
    - `notes`

## Payments
- `POST /payments`
  - Required: `order_id`, `payment_method`
  - Allowed `payment_method` values:
    - `credit_card`
    - `paypal`
    - `bank_transfer`
    - `cash_on_delivery`
  - Optional: `payment_details` object

## Status Codes and Error Handling

Common status codes:
- `200` OK
- `201` Created
- `204` No Content
- `400` Bad Request
- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error
- `429` Too Many Requests
- `500` Server Error

Validation errors usually return:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Error message"]
  }
}
```

## Notes for Integrators
- Treat `/api/v1` as private API by default; authenticate first.
- Do not assume catalog/settings/track endpoints are public in this strict mode.
- Keep webhook/callback endpoints reachable from Stripe/bKash.
- Always trust server-calculated totals over client-side totals.
- Use retry/idempotency patterns for payment and checkout flows.
- Recheck live routes after deployment changes:
  - `php artisan route:list --path=api/v1`
- Run security guardrail tests before release:
  - `php artisan test --filter=ApiSecurityTest`
