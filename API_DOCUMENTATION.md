# E-Commerce API Documentation

## Metadata
- Version: 2.1
- Last updated: April 11, 2026
- Base URL (production): https://api.innercollection.com.bd/api/v1
- Base URL (local): http://localhost:8000/api/v1
- Health check: GET /api/health
- Total API routes (including /api/health): 168
- Total /api/v1 routes: 167
- Authenticated /api/v1 routes: 102
- Internal-secret /api/v1 routes: 51
- Public /api/v1 routes: 14

## Table of Contents
1. API Conventions
2. Authentication and Authorization
3. Rate Limits
4. Response Formats
5. Pagination
6. Error Handling
7. Resource Field Reference
8. Public Endpoint Whitelist
9. Complete Route Inventory (A-Z)
10. Detailed Request and Response Contracts (A-Z)

## 1) API Conventions

### Required Headers
For JSON requests:
- Accept: application/json
- Content-Type: application/json

For auth-protected routes:
- Authorization: Bearer <sanctum_token>

For internal frontend-data routes (middleware: internal.api):
- X-Internal-Secret: <INTERNAL_API_SECRET>

### API Versioning
- All business APIs are under /api/v1.
- /api/health is outside /api/v1.

## 2) Authentication and Authorization

### Authentication
API access is split into 3 models:
- `Auth` routes: require Sanctum bearer token.
- `Internal Secret` routes: require `X-Internal-Secret` header and are intended for server-to-server/frontend proxy usage.
- `Public` routes: no Sanctum token and no internal secret required.

Current route distribution under `/api/v1`:
- Auth routes: 102
- Internal-secret routes: 51
- Public routes: 14

### Authorization Model
- User ownership checks are done in controller/service for user-scoped resources (orders, payments, profile, returns, addresses, wishlist, reviews).
- Admin-only access is enforced by middleware and/or explicit controller checks.

### Key Middleware Used
- is_admin
- admin_permission:catalog.manage
- admin_permission:orders.manage
- admin_permission:returns.manage
- admin_permission:users.manage
- internal.api (InternalApiOnly)

## 3) Rate Limits

- Global API: configured via Laravel API throttle.
- Auth routes (register/login/forgot/reset): throttle:auth middleware.
- Order creation (`POST /api/v1/orders`): throttle:10,1.

## 4) Response Formats

This codebase currently uses 3 response styles.

### Style A: Standard success wrapper (most endpoints)
```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

### Style B: Raw JSON with explicit keys (some controllers)
```json
{
  "success": true,
  "data": {},
  "message": "Optional"
}
```

### Style C: Laravel Resource Collection direct (without success/message wrapper)
Used by:
- GET /api/v1/wishlist
- GET /api/v1/products/{productId}/reviews
- GET /api/v1/products/{productId}/reviews/featured
- GET /api/v1/reviews/my

Typical shape:
```json
{
  "data": [],
  "links": {},
  "meta": {}
}
```

## 5) Pagination

Pagination is used on many list endpoints.

Common query param:
- per_page (default usually 15, max typically 100)

Common paginated response fields:
- data
- links
- meta

## 6) Error Handling

### Common Status Codes
- 200 OK
- 201 Created
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 409 Conflict
- 422 Validation Error
- 500 Server Error

### Validation Error Shape (Laravel)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Error message"]
  }
}
```

### App-level Error Wrapper
```json
{
  "success": false,
  "message": "Error message"
}
```

## 7) Resource Field Reference

### ProductResource (key fields)
- id, category_id, name, slug, description
- regular_price, sale_price, current_price
- dynamic_price_for_quantity_1
- has_dynamic_discount, dynamic_discount_tiers
- free_delivery
- sku, stock_quantity, total_stock, in_stock
- image, image_url
- is_active, is_featured, is_new, is_bestseller
- category, images, variants

### OrderResource (key fields)
- id, order_number, status
- payment_method, payment_status, transaction_id
- shipping_method
- subtotal, tax, shipping, total
- shipping_name, shipping_phone, shipping_address, shipping_* ids and names
- items, payment, user, tracking_history
- tracking_number, carrier, tracking_progress

### CartResource (key fields)
- id, user_id
- items
- item_count
- subtotal, discount, total
- coupon_code, coupon

### UserResource
- id, name, email, phone, address, role, email_verified_at

### AddressResource
- id, label, type, is_default
- name, phone, email
- address_line_1, address_line_2, area
- division_id, district_id, upazila_id, union_id
- city, state, postal_code, country
- instructions, latitude, longitude
- full_address, formatted_address

### ReviewResource
- id, rating, title, comment
- pros, cons, images, image_urls
- is_verified_purchase, is_approved, is_featured
- helpful_count, unhelpful_count, helpfulness_percentage
- user
- user_vote, is_own_review

### WishlistResource
- id, product_id, product_variant_id
- product
- variant
- added_at

## 8) Public Endpoint Whitelist

These `/api/v1` endpoints are fully public (no Sanctum, no internal secret):
- POST /api/v1/auth/register
- POST /api/v1/auth/login
- POST /api/v1/auth/forgot-password
- POST /api/v1/auth/reset-password
- GET /api/v1/auth/email/verify/{id}/{hash}
- GET /api/v1/bkash/config
- POST /api/v1/stripe/webhook
- GET /api/v1/stripe/config
- GET /api/v1/bkash/callback
- POST /api/v1/orders
- GET /api/v1/orders/number/{orderNumber}
- GET /api/v1/track/order/{orderNumber}
- GET /api/v1/track/tracking/{trackingNumber}

Internal-secret storefront endpoints (no Sanctum, but require `X-Internal-Secret`) include:
- Product/catalog read APIs
- Payment-method and shipping-method discovery APIs
- Bangladesh location datasets
- Frontend settings APIscd 
- Product-page reviews (read)
- Flash sales read/validation APIs

All remaining `/api/v1` endpoints require `auth:sanctum`.

## 9) Complete Route Inventory (A-Z)

The following table is generated from current route:list output.

| Method | Path | Access | Action |
|---|---|---|---|
| GET|HEAD | /api/health | Public | Closure |
| POST | /api/v1/auth/register | Public + throttle:auth | Auth@register |
| POST | /api/v1/auth/login | Public + throttle:auth | Auth@login |
| POST | /api/v1/auth/forgot-password | Public + throttle:auth | Auth@forgotPassword |
| POST | /api/v1/auth/reset-password | Public + throttle:auth | Auth@resetPassword |
| GET|HEAD | /api/v1/auth/email/verify/{id}/{hash} | Public + signed | Auth@verifyEmail |
| GET|HEAD | /api/v1/stripe/config | Public | Stripe@config |
| POST | /api/v1/stripe/webhook | Public | Stripe@webhook |
| POST | /api/v1/stripe/create-payment-intent | Auth | Stripe@createPaymentIntent |
| POST | /api/v1/stripe/confirm-payment | Auth | Stripe@confirmPayment |
| GET|HEAD | /api/v1/bkash/config | Public | Bkash@config |
| GET|HEAD | /api/v1/bkash/callback | Public | Bkash@callback |
| POST | /api/v1/bkash/create-payment | Auth | Bkash@createPayment |
| GET|HEAD | /api/v1/track/order/{orderNumber} | Public | OrderTracking@trackByOrderNumber |
| GET|HEAD | /api/v1/track/tracking/{trackingNumber} | Public | OrderTracking@trackByTrackingNumber |
| GET|HEAD | /api/v1/categories | Internal Secret | Category@index |
| GET|HEAD | /api/v1/categories/menu | Internal Secret | Category@menu |
| GET|HEAD | /api/v1/categories/{id} | Internal Secret | Category@show |
| GET|HEAD | /api/v1/categories/slug/{slug} | Internal Secret | Category@showBySlug |
| GET|HEAD | /api/v1/categories/{id}/children | Internal Secret | Category@children |
| GET|HEAD | /api/v1/products | Internal Secret | Product@index |
| GET|HEAD | /api/v1/products/featured | Internal Secret | Product@featured |
| GET|HEAD | /api/v1/products/new | Internal Secret | Product@newProducts |
| GET|HEAD | /api/v1/products/bestsellers | Internal Secret | Product@bestsellers |
| GET|HEAD | /api/v1/products/search | Internal Secret | Product@search |
| GET|HEAD | /api/v1/products/{id} | Internal Secret | Product@show |
| GET|HEAD | /api/v1/products/slug/{slug} | Internal Secret | Product@showBySlug |
| GET|HEAD | /api/v1/products/category/{categoryId} | Internal Secret | Product@byCategory |
| GET|HEAD | /api/v1/products/{id}/variants | Internal Secret | Product@variants |
| GET|HEAD | /api/v1/products/{product}/related | Internal Secret | RelatedProduct@index |
| GET|HEAD | /api/v1/products/{product}/frequently-bought-together | Internal Secret | RelatedProduct@frequentlyBoughtTogether |
| GET|HEAD | /api/v1/products/{product}/upsell | Internal Secret | RelatedProduct@upsell |
| GET|HEAD | /api/v1/products/{product}/cross-sell | Internal Secret | RelatedProduct@crossSell |
| GET|HEAD | /api/v1/products/{productId}/reviews | Internal Secret | Review@index |
| GET|HEAD | /api/v1/products/{productId}/reviews/summary | Internal Secret | Review@summary |
| GET|HEAD | /api/v1/products/{productId}/reviews/featured | Internal Secret | Review@featured |
| GET|HEAD | /api/v1/attributes | Internal Secret | Attribute@index |
| GET|HEAD | /api/v1/attributes/{id} | Internal Secret | Attribute@show |
| GET|HEAD | /api/v1/payment-methods | Internal Secret | PaymentGateway@index |
| GET|HEAD | /api/v1/payment-methods/{code} | Internal Secret | PaymentGateway@show |
| GET|HEAD | /api/v1/shipping-methods | Internal Secret | ShippingMethod@index |
| GET|HEAD | /api/v1/shipping-methods/{code} | Internal Secret | ShippingMethod@show |
| POST | /api/v1/shipping-methods/calculate | Internal Secret | ShippingMethod@calculate |
| GET|HEAD | /api/v1/locations/bd/divisions | Internal Secret | BangladeshLocation@divisions |
| GET|HEAD | /api/v1/locations/bd/districts | Internal Secret | BangladeshLocation@districts |
| GET|HEAD | /api/v1/locations/bd/upazilas | Internal Secret | BangladeshLocation@upazilas |
| GET|HEAD | /api/v1/locations/bd/unions | Internal Secret | BangladeshLocation@unions |
| GET|HEAD | /api/v1/locations/divisions | Internal Secret | BangladeshLocation@divisions |
| GET|HEAD | /api/v1/locations/districts | Internal Secret | BangladeshLocation@districts |
| GET|HEAD | /api/v1/locations/upazilas | Internal Secret | BangladeshLocation@upazilas |
| GET|HEAD | /api/v1/locations/unions | Internal Secret | BangladeshLocation@unions |
| GET|HEAD | /api/v1/settings | Internal Secret | FrontendSetting@index |
| GET|HEAD | /api/v1/settings/hero | Internal Secret | FrontendSetting@hero |
| GET|HEAD | /api/v1/settings/general | Internal Secret | FrontendSetting@general |
| GET|HEAD | /api/v1/settings/social | Internal Secret | FrontendSetting@social |
| GET|HEAD | /api/v1/settings/seo | Internal Secret | FrontendSetting@seo |
| GET|HEAD | /api/v1/settings/footer | Internal Secret | FrontendSetting@footer |
| GET|HEAD | /api/v1/settings/banner | Internal Secret | FrontendSetting@banner |
| GET|HEAD | /api/v1/settings/checkout | Internal Secret | FrontendSetting@checkout |
| GET|HEAD | /api/v1/settings/{group} | Internal Secret | FrontendSetting@showGroup |
| GET|HEAD | /api/v1/flash-sales | Internal Secret | FlashSale@index |
| GET|HEAD | /api/v1/flash-sales/featured | Internal Secret | FlashSale@featured |
| GET|HEAD | /api/v1/flash-sales/upcoming | Internal Secret | FlashSale@upcoming |
| GET|HEAD | /api/v1/flash-sales/{slug} | Internal Secret | FlashSale@show |
| GET|HEAD | /api/v1/flash-sales/product/{productId} | Internal Secret | FlashSale@checkProduct |
| POST | /api/v1/flash-sales/validate-purchase | Internal Secret | FlashSale@validatePurchase |
| POST | /api/v1/orders | Public | Order@store |
| POST | /api/v1/cart/coupon | Auth | Coupon@apply |
| POST | /api/v1/checkout/track | Auth | AbandonedCart@track |
| GET|HEAD | /api/v1/orders/{id}/payment-summary | Auth | Order@paymentSummary |
| GET|HEAD | /api/v1/orders/number/{orderNumber} | Public | Order@showByNumber |
| POST | /api/v1/auth/logout | Auth | Auth@logout |
| GET|HEAD | /api/v1/auth/me | Auth | Auth@me |
| POST | /api/v1/auth/email/resend | Auth | Auth@resendVerification |
| POST | /api/v1/auth/change-password | Auth | Auth@changePassword |
| GET|HEAD | /api/v1/profile | Auth | User@profile |
| PUT | /api/v1/profile | Auth | User@updateProfile |
| GET|HEAD | /api/v1/users | Auth + perm:users.manage | User@index |
| POST | /api/v1/users | Auth + perm:users.manage | User@store |
| DELETE | /api/v1/users/{id} | Auth + perm:users.manage | User@destroy |
| POST | /api/v1/users/{id}/toggle-status | Auth + perm:users.manage | User@toggleStatus |
| GET|HEAD | /api/v1/users/{id} | Auth | User@show |
| PUT | /api/v1/users/{id} | Auth | User@update |
| POST | /api/v1/categories | Auth + perm:catalog.manage | Category@store |
| PUT | /api/v1/categories/{id} | Auth + perm:catalog.manage | Category@update |
| DELETE | /api/v1/categories/{id} | Auth + perm:catalog.manage | Category@destroy |
| POST | /api/v1/products | Auth + perm:catalog.manage | Product@store |
| PUT | /api/v1/products/{id} | Auth + perm:catalog.manage | Product@update |
| DELETE | /api/v1/products/{id} | Auth + perm:catalog.manage | Product@destroy |
| POST | /api/v1/products/bulk-action | Auth + perm:catalog.manage | Product@bulkAction |
| GET|HEAD | /api/v1/cart | Auth | Cart@index |
| POST | /api/v1/cart/items | Auth | Cart@addItem |
| PUT | /api/v1/cart/items/{productId} | Auth | Cart@updateItem |
| DELETE | /api/v1/cart/items/{productId} | Auth | Cart@removeItem |
| DELETE | /api/v1/cart | Auth | Cart@clear |
| DELETE | /api/v1/cart/coupon | Auth | Coupon@remove |
| GET|HEAD | /api/v1/coupons/validate | Auth | Coupon@validate |
| GET|HEAD | /api/v1/coupons/available | Auth | Coupon@available |
| POST | /api/v1/cart/recommendations | Auth | RelatedProduct@cartRecommendations |
| GET|HEAD | /api/v1/wishlist | Auth | Wishlist@index |
| POST | /api/v1/wishlist | Auth | Wishlist@store |
| POST | /api/v1/wishlist/toggle | Auth | Wishlist@toggle |
| GET|HEAD | /api/v1/wishlist/check | Auth | Wishlist@check |
| GET|HEAD | /api/v1/wishlist/count | Auth | Wishlist@count |
| DELETE | /api/v1/wishlist/clear | Auth | Wishlist@clear |
| DELETE | /api/v1/wishlist/{wishlist} | Auth | Wishlist@destroy |
| DELETE | /api/v1/wishlist/product | Auth | Wishlist@removeByProduct |
| POST | /api/v1/wishlist/{wishlist}/move-to-cart | Auth | Wishlist@moveToCart |
| GET|HEAD | /api/v1/reviews/my | Auth | Review@myReviews |
| POST | /api/v1/reviews | Auth | Review@store |
| PUT | /api/v1/reviews/{review} | Auth | Review@update |
| DELETE | /api/v1/reviews/{review} | Auth | Review@destroy |
| POST | /api/v1/reviews/{review}/vote | Auth | Review@vote |
| DELETE | /api/v1/reviews/{review}/vote | Auth | Review@removeVote |
| GET|HEAD | /api/v1/reviews/can-review/{productId} | Auth | Review@canReview |
| GET|HEAD | /api/v1/addresses | Auth | Address@index |
| POST | /api/v1/addresses | Auth | Address@store |
| GET|HEAD | /api/v1/addresses/default/shipping | Auth | Address@defaultShipping |
| GET|HEAD | /api/v1/addresses/default/billing | Auth | Address@defaultBilling |
| GET|HEAD | /api/v1/addresses/{address} | Auth | Address@show |
| PUT | /api/v1/addresses/{address} | Auth | Address@update |
| DELETE | /api/v1/addresses/{address} | Auth | Address@destroy |
| POST | /api/v1/addresses/{address}/set-default | Auth | Address@setDefault |
| POST | /api/v1/checkout/recovered | Auth | AbandonedCart@markRecovered |
| GET|HEAD | /api/v1/notifications | Auth | Notification@index |
| GET|HEAD | /api/v1/notifications/unread-count | Auth | Notification@unreadCount |
| POST | /api/v1/notifications/{id}/read | Auth | Notification@markAsRead |
| POST | /api/v1/notifications/read-all | Auth | Notification@markAllAsRead |
| DELETE | /api/v1/notifications/{id} | Auth | Notification@destroy |
| GET|HEAD | /api/v1/returns | Auth | Return@index |
| POST | /api/v1/returns | Auth | Return@store |
| GET|HEAD | /api/v1/returns/check-eligibility | Auth | Return@checkEligibility |
| GET|HEAD | /api/v1/returns/{return} | Auth | Return@show |
| POST | /api/v1/returns/{return}/cancel | Auth | Return@cancel |
| POST | /api/v1/returns/{return}/upload-images | Auth | Return@uploadImages |
| GET|HEAD | /api/v1/loyalty/tiers | Auth | Loyalty@tiers |
| GET|HEAD | /api/v1/loyalty/summary | Auth | Loyalty@summary |
| GET|HEAD | /api/v1/loyalty/transactions | Auth | Loyalty@transactions |
| GET|HEAD | /api/v1/loyalty/rewards | Auth | Loyalty@rewards |
| POST | /api/v1/loyalty/redeem | Auth | Loyalty@redeem |
| GET|HEAD | /api/v1/loyalty/redemptions | Auth | Loyalty@redemptions |
| GET|HEAD | /api/v1/loyalty/redemptions/active | Auth | Loyalty@activeRedemptions |
| POST | /api/v1/loyalty/redemptions/{redemption}/cancel | Auth | Loyalty@cancelRedemption |
| POST | /api/v1/loyalty/validate-coupon | Auth | Loyalty@validateCoupon |
| GET|HEAD | /api/v1/loyalty/leaderboard | Auth | Loyalty@leaderboard |
| GET|HEAD | /api/v1/orders | Public | Order@index |
| GET|HEAD | /api/v1/orders/{id} | Auth | Order@show |
| POST | /api/v1/orders/{id}/cancel | Auth | Order@cancel |
| GET|HEAD | /api/v1/orders/{id}/tracking | Auth | OrderTracking@show |
| GET|HEAD | /api/v1/orders/{id}/invoice | Auth | Invoice@show |
| GET|HEAD | /api/v1/orders/{id}/notes | Auth | OrderNote@index |
| POST | /api/v1/orders/{id}/notes | Auth + perm:orders.manage | OrderNote@store |
| DELETE | /api/v1/orders/{id}/notes/{noteId} | Auth + perm:orders.manage | OrderNote@destroy |
| PUT | /api/v1/orders/{id}/status | Auth + perm:orders.manage | Order@updateStatus |
| GET|HEAD | /api/v1/orders/status/{status} | Auth + perm:orders.manage | Order@byStatus |
| GET|HEAD | /api/v1/payments/order/{orderId} | Auth | Payment@show |
| POST | /api/v1/payments | Auth | Payment@store |
| POST | /api/v1/payments/{paymentId}/process | Auth | Payment@process |
| POST | /api/v1/payments/{paymentId}/refund | Auth + perm:returns.manage | Payment@refund |
| GET|HEAD | /api/v1/bkash/check-status | Auth | Bkash@checkStatus |
| POST | /api/v1/bkash/refund | Auth + perm:returns.manage | Bkash@refund |
| GET|HEAD | /api/v1/saved-payment-methods | Auth | SavedPaymentMethod@index |
| POST | /api/v1/saved-payment-methods/{savedPaymentMethod}/set-default | Auth | SavedPaymentMethod@setDefault |
| POST | /api/v1/saved-payment-methods/{savedPaymentMethod}/remove | Auth | SavedPaymentMethod@remove |
| GET|HEAD | /api/v1/admin/orders/export | Auth + is_admin | OrderExport@export |
| GET|HEAD | /api/v1/admin/orders/export/download/{filename} | Auth + is_admin | OrderExport@download |
| GET|HEAD | /api/v1/admin/audit-logs | Auth + is_admin | AuditLog@index |
| GET|HEAD | /api/v1/admin/audit-logs/{id} | Auth + is_admin | AuditLog@show |

## 10) Detailed Request and Response Contracts (A-Z)

All paths below are absolute API paths.

### 10.1 Health

#### GET /api/health
- Auth: Public
- Request: none
- Success:
```json
{
  "status": "ok",
  "timestamp": "2026-04-10T10:00:00.000000Z"
}
```

### 10.2 Addresses

#### GET /api/v1/addresses
- Query:
  - type: optional, shipping|billing
- Success: AddressResource[]

#### POST /api/v1/addresses
- Body:
  - name, phone, address_line_1, division_id, district_id, upazila_id: required
  - label, type(shipping|billing|both), is_default, email, address_line_2, union_id, area, city, state, postal_code, instructions, latitude, longitude: optional
  - country: optional, Bangladesh|BD
- Success 201:
  - success, message, data (AddressResource)

#### GET /api/v1/addresses/{address}
#### PUT /api/v1/addresses/{address}
#### DELETE /api/v1/addresses/{address}
#### POST /api/v1/addresses/{address}/set-default
- Path param: address (Address model binding)
- Update body: partial address fields (same family as create)
- Success:
  - show/update/set-default: success + AddressResource
  - delete: success + message
- Errors:
  - 403 unauthorized (ownership)

#### GET /api/v1/addresses/default/shipping
#### GET /api/v1/addresses/default/billing
- Success: AddressResource
- Error 404: no default found

### 10.3 Admin Utilities

#### GET /api/v1/admin/audit-logs
- Access: is_admin
- Query:
  - action, user_id, model_type, model_id, from, to, per_page
- Success: success + paginated audit logs

#### GET /api/v1/admin/audit-logs/{id}
- Access: is_admin
- Success: success + audit log detail

#### GET /api/v1/admin/orders/export
- Access: is_admin
- Query:
  - status: pending|processing|shipped|delivered|cancelled
  - from: date
  - to: date
- Success:
  - success + { filename, total_orders, download_url }
- Errors:
  - 404 no orders

#### GET /api/v1/admin/orders/export/download/{filename}
- Access: is_admin
- Success: CSV file stream
- Error 404: file not found

### 10.4 Attributes

#### GET /api/v1/attributes
#### GET /api/v1/attributes/{id}
- Success:
  - success + ProductAttributeResource
  - values include id, value, color_code, image, image_url

### 10.5 Authentication

#### POST /api/v1/auth/register
- Public + throttle:auth
- Body:
  - name, email, password, password_confirmation: required
  - phone, address: optional
- Success 201:
  - success, message
  - data.user (UserResource)
  - data.token

#### POST /api/v1/auth/login
- Public + throttle:auth
- Body: email, password required
- Success:
  - success + { user, token }
- Error 401: Invalid credentials

#### POST /api/v1/auth/forgot-password
- Public + throttle:auth
- Body: email required and must exist
- Success: generic message (email + SMS OTP flow)

#### POST /api/v1/auth/reset-password
- Public + throttle:auth
- Body:
  - email required
  - password + password_confirmation required
  - token required_without otp OR otp(4 digits) required_without token
- Success: password reset confirmation
- Error 400: invalid/expired reset token or otp

#### GET /api/v1/auth/email/verify/{id}/{hash}
- Public + signed URL
- Path: id, hash
- Success: verified or already verified
- Error 400: invalid verification link

#### POST /api/v1/auth/logout
#### GET /api/v1/auth/me
#### POST /api/v1/auth/email/resend
#### POST /api/v1/auth/change-password
- Auth required
- change-password body:
  - current_password required
  - password required, confirmed, min complexity
- Success: success wrapper

### 10.6 bKash

#### GET /api/v1/bkash/config
- Success: { available, sandbox_mode, currency }
- Errors:
  - 404 gateway unavailable
  - 503 not configured

#### POST /api/v1/bkash/create-payment
- Body: order_id required
- Checks:
  - order ownership
  - payment_method == bkash
  - payment_status != paid
- Success:
  - payment_id, bkash_url, order_id, amount
- Errors: 400/403

#### GET /api/v1/bkash/check-status
- Query/body: order_id required
- Success:
  - paid status payload OR current status + bkash status
- Errors: 400/403

#### POST /api/v1/bkash/refund
- Access: admin_permission returns.manage + admin check
- Body:
  - order_id required
  - amount optional numeric >=1
  - reason optional string
- Success:
  - success + refund result
- Errors:
  - unauthorized, wrong payment method, non-paid order, missing payment refs

#### GET /api/v1/bkash/callback
- Public redirect callback endpoint
- Query from bKash: paymentID, status
- Response: HTTP redirect to frontend success/failed/cancelled URLs

### 10.7 Cart and Coupons

#### GET /api/v1/cart
- Success: success + CartResource

#### POST /api/v1/cart/items
- Body:
  - product_id required exists
  - quantity required integer 1..100
- Success: updated CartResource

#### PUT /api/v1/cart/items/{productId}
- Body: quantity required integer 0..100
- Success: updated CartResource

#### DELETE /api/v1/cart/items/{productId}
#### DELETE /api/v1/cart
- Success: cart updated/cleared

#### POST /api/v1/cart/coupon
- Body: code required
- Success:
  - success + { coupon, discount, cart }
- Errors:
  - 400 cart empty or invalid coupon

#### DELETE /api/v1/cart/coupon
- Success:
  - success + { cart }

#### GET /api/v1/coupons/validate
- Query/body:
  - code required
  - order_total optional
- Success:
  - { valid: true, data: { coupon, discount } }
- Error 400:
  - { valid: false, message }

#### GET /api/v1/coupons/available
- Success:
  - success + { coupons, cart_total }

### 10.8 Categories

#### GET /api/v1/categories
#### GET /api/v1/categories/menu
#### GET /api/v1/categories/{id}
#### GET /api/v1/categories/slug/{slug}
#### GET /api/v1/categories/{id}/children
- Success: success + CategoryResource or CategoryResource[]
- Error 404: category not found (slug)

#### POST /api/v1/categories
- Access: admin_permission catalog.manage
- Body:
  - name required
  - slug, description, parent_id, is_active, sort_order optional
- Success 201: created category

#### PUT /api/v1/categories/{id}
- Access: admin_permission catalog.manage
- Body: partial category fields
- Success: updated category

#### DELETE /api/v1/categories/{id}
- Access: admin_permission catalog.manage + admin check
- Success: delete message

### 10.9 Checkout Tracking (Abandoned Cart)

#### POST /api/v1/checkout/track
- Body:
  - checkout_step required: cart|shipping|payment
  - email, phone, name, shipping_*, payment_method, shipping_method optional
- Header:
  - X-Session-ID optional (fallback: session id)
- Success:
  - success + data.abandoned_cart_id

#### POST /api/v1/checkout/recovered
- Body: order_id required
- Success: confirmation message

### 10.10 Flash Sales

#### GET /api/v1/flash-sales
#### GET /api/v1/flash-sales/featured
#### GET /api/v1/flash-sales/upcoming
#### GET /api/v1/flash-sales/{slug}
#### GET /api/v1/flash-sales/product/{productId}
- Success:
  - success + flash sale data
- show slug error 404 if not found

#### POST /api/v1/flash-sales/validate-purchase
- Body: product_id, quantity required
- Success:
  - success boolean derived from service validation
  - data validation payload

### 10.11 Bangladesh Locations

#### GET /api/v1/locations/bd/divisions
- Success: list of {id,name,bn_name}

#### GET /api/v1/locations/bd/districts
- Query: division_id optional
- Success: list of districts

#### GET /api/v1/locations/bd/upazilas
- Query: district_id optional
- Success: list of upazilas

#### GET /api/v1/locations/bd/unions
- Query: upazila_id optional
- Success: list of unions

#### Backward-compatible aliases
- GET /api/v1/locations/divisions
- GET /api/v1/locations/districts
- GET /api/v1/locations/upazilas
- GET /api/v1/locations/unions

Access for all Bangladesh location dataset routes:
- Internal Secret (`X-Internal-Secret` required)

### 10.12 Loyalty

#### GET /api/v1/loyalty/tiers
- Success: tier list {name,slug,min_points,points_multiplier,benefits,badge_image}

#### GET /api/v1/loyalty/summary
#### GET /api/v1/loyalty/transactions
#### GET /api/v1/loyalty/rewards
#### GET /api/v1/loyalty/redemptions
#### GET /api/v1/loyalty/redemptions/active
#### GET /api/v1/loyalty/leaderboard
- Success: success + data payload
- transactions uses pagination

#### POST /api/v1/loyalty/redeem
- Body: reward_id required
- Success:
  - redemption_id, coupon_code, expires_at, points_remaining
- Error 400 when reward cannot be redeemed

#### POST /api/v1/loyalty/redemptions/{redemption}/cancel
- Path: redemption
- Success: points_refunded, current_points
- Errors: 403 unauthorized, 400 non-pending

#### POST /api/v1/loyalty/validate-coupon
- Body: coupon_code required
- Success:
  - valid true + redemption details
- Invalid code response:
  - success false, valid false, message

### 10.13 Notifications

#### GET /api/v1/notifications
- Success: paginated notifications

#### GET /api/v1/notifications/unread-count
- Success: { unread_count }

#### POST /api/v1/notifications/{id}/read
#### POST /api/v1/notifications/read-all
#### DELETE /api/v1/notifications/{id}
- Success: confirmation messages

### 10.14 Orders, Tracking, Invoice, Notes

#### GET /api/v1/orders
- Success: paginated OrderResource list
- Behavior:
  - admin gets all orders
  - user gets own orders

#### POST /api/v1/orders
- Access: Public (guest and authenticated users)
- Body (dynamic by checkout config):
  - required core: shipping_name, shipping_address, shipping_method, payment_method
  - conditionally required: shipping_email, shipping_phone, shipping_area, shipping_zip, notes
  - location inputs:
    - shipping_location_text (normalized from shipping_address when omitted)
    - shipping_division_id, shipping_district_id, shipping_upazila_id, shipping_union_id
  - shipping_city, shipping_state, shipping_country optional
  - guest checkout requires items[] with product_id and quantity
- Location validation behavior:
  - when `enable_dropdown_location` is disabled, `shipping_division_id`, `shipping_district_id`, `shipping_upazila_id`, `shipping_union_id` are excluded by backend validation
  - legacy plain keys `division_id`, `district_id`, `upazila_id` are always excluded
- Success 201:
  - success + message + data { id, order_number, status, payment_status, payment_method, total }
- Errors:
  - 400 service/business errors
  - 422 validation and checkout config constraints

#### GET /api/v1/orders/{id}
#### GET /api/v1/orders/number/{orderNumber}
- GET /api/v1/orders/{id}: Auth required, success OrderResource
- GET /api/v1/orders/number/{orderNumber}: Public, returns summary { id, order_number, status, payment_status, payment_method, total }
- Errors: 403 unauthorized (id route), 404 not found

#### POST /api/v1/orders/{id}/cancel
- Success: OrderResource with cancelled status

#### PUT /api/v1/orders/{id}/status
- Access: admin_permission orders.manage + admin
- Body: status required in [pending,processing,shipped,delivered,cancelled]
- Success: updated OrderResource

#### GET /api/v1/orders/status/{status}
- Access: admin_permission orders.manage + admin
- Success: OrderResource[]

#### GET /api/v1/orders/{id}/tracking
- Auth user-scoped tracking endpoint
- Success:
  - success + data { order_number, status, progress, tracking_number, carrier, timeline, history }

#### GET /api/v1/track/order/{orderNumber}
#### GET /api/v1/track/tracking/{trackingNumber}
- Same tracking payload style
- Current route security: public (no auth)

#### GET /api/v1/orders/{id}/invoice
- Success:
  - success + invoice payload {
    - invoice_number, invoice_date
    - company
    - customer
    - order
    - items
    - totals
    - notes, currency
  }

#### GET /api/v1/orders/{id}/notes
- Success:
  - success + notes[]
  - non-admin sees only customer-visible notes on own order

#### POST /api/v1/orders/{id}/notes
- Access: admin_permission orders.manage + admin
- Body:
  - note required
  - type optional: internal|customer|system
  - is_customer_visible optional boolean
- Success 201: created note

#### DELETE /api/v1/orders/{id}/notes/{noteId}
- Access: admin_permission orders.manage + admin
- Success: delete message

### 10.15 Payment Methods and Payments

#### GET /api/v1/payment-methods
- Access: Internal Secret (`X-Internal-Secret` required)
- Query:
  - amount optional
  - currency optional (default BDT)
- Success:
  - success + active gateway list
  - includes code, name, description, instructions, icon, requires_redirect, min/max_amount, extra_charge

#### GET /api/v1/payment-methods/{code}
- Access: Internal Secret (`X-Internal-Secret` required)
- Success:
  - success + gateway details
- Error 404: inactive or not found

#### GET /api/v1/payments/order/{orderId}
- Success: PaymentResource
- Errors: 403 unauthorized, 404 payment not found

#### POST /api/v1/payments
- Body:
  - order_id required
  - payment_method required in [credit_card,paypal,bank_transfer,cash_on_delivery]
  - payment_details optional object
  - if credit_card: card_number, expiry_date, cvv required
- Success 201: PaymentResource
- Errors: 403 unauthorized, 400 service error

#### POST /api/v1/payments/{paymentId}/process
- Success: processed PaymentResource
- Errors: 403 unauthorized, 404 payment not found, 400 service error

#### POST /api/v1/payments/{paymentId}/refund
- Access: admin_permission returns.manage + admin
- Success: refunded PaymentResource

### 10.16 Products and Related Products

#### GET /api/v1/products
- Query:
  - per_page optional
- Success: paginated ProductResource collection payload

#### GET /api/v1/products/{id}
#### GET /api/v1/products/slug/{slug}
#### GET /api/v1/products/featured
#### GET /api/v1/products/new
#### GET /api/v1/products/bestsellers
#### GET /api/v1/products/category/{categoryId}
#### GET /api/v1/products/{id}/variants
- Success: product/product list/variant list via ProductResource or ProductVariantResource
- Errors: 404 product not found for slug/id variants where applicable

#### GET /api/v1/products/search
- Query: q required, min length 2
- Success: ProductResource[]

#### POST /api/v1/products
- Access: admin_permission catalog.manage
- Body required:
  - category_id, name, regular_price, sku, stock_quantity
- Optional:
  - slug, description, sale_price(lt regular_price), buy_price, image, is_active, is_featured
- Success 201: ProductResource

#### PUT /api/v1/products/{id}
- Access: admin_permission catalog.manage
- Body: partial product fields
- Success: ProductResource

#### DELETE /api/v1/products/{id}
- Access: admin_permission catalog.manage + admin check
- Success: delete message

#### POST /api/v1/products/bulk-action
- Access: admin_permission catalog.manage + admin check
- Body:
  - product_ids required array of valid ids
  - action required in [activate,deactivate,delete,update_price]
  - value required only for update_price
    - value.type: fixed|percentage
    - value.amount: numeric
- Success: success + message
- Error 400: invalid action

#### GET /api/v1/products/{product}/related
#### GET /api/v1/products/{product}/frequently-bought-together
#### GET /api/v1/products/{product}/upsell
#### GET /api/v1/products/{product}/cross-sell
- Query:
  - limit optional
- Success:
  - success + product recommendation arrays

#### POST /api/v1/cart/recommendations
- Body/query:
  - product_ids optional array
  - limit optional
- Success:
  - success + recommendation list

### 10.17 Profile and Users

#### GET /api/v1/profile
#### PUT /api/v1/profile
- PUT body: any UpdateUserRequest fields
  - name, email, password(+confirmation), phone, address
- Success: UserResource

#### GET /api/v1/users
- Access: admin_permission users.manage + admin check
- Success: paginated UserResource

#### POST /api/v1/users
- Access: admin_permission users.manage + admin check
- Body:
  - name, email, password required
  - phone optional
  - role optional, must be in allowed role options
- Success 201: UserResource

#### GET /api/v1/users/{id}
#### PUT /api/v1/users/{id}
- Access: self or admin
- PUT body: UpdateUserRequest fields
- Success: UserResource
- Error 403 unauthorized

#### DELETE /api/v1/users/{id}
- Access: admin_permission users.manage + admin
- Success: delete message

#### POST /api/v1/users/{id}/toggle-status
- Access: admin_permission users.manage + admin
- Success: UserResource + activation/deactivation message
- Error 400: cannot toggle own account

### 10.18 Returns and Refund Workflow

#### GET /api/v1/returns
- Success:
  - success + paginated return requests

#### POST /api/v1/returns
- Body:
  - order_id required, must belong to user and be delivered/completed
  - type required: return|refund
  - reason required in:
    - defective, wrong_item, not_as_described, changed_mind, damaged_shipping,
      missing_parts, damaged, size_issue, quality_issue, late_delivery, other
  - description required
  - refund_method optional: original|store_credit|bank_transfer
  - images optional array max 5 (jpeg/png/jpg, max 5MB each)
  - items required array min 1
    - items.*.order_item_id required
    - items.*.quantity required
    - items.*.reason optional
- Success 201:
  - return_number, status, estimated_review_time
- Errors:
  - 422 for period expiry, duplicate active return, invalid quantities/items
  - 500 on internal failure

#### GET /api/v1/returns/{return}
- Success:
  - detailed return payload including timeline and item breakdown
- Error 404 when not owned

#### POST /api/v1/returns/{return}/cancel
- Success: cancellation message
- Error 422 if not pending

#### GET /api/v1/returns/check-eligibility
- Query/body: order_id required
- Success:
  - success
  - eligible (boolean)
  - reason when ineligible
  - days_remaining
  - returnable_items
  - return_reasons dictionary

#### POST /api/v1/returns/{return}/upload-images
- Body:
  - images required array 1..5
  - images.* image file jpeg/png/jpg max 5MB
- Success:
  - success, message, images
- Errors: 404 ownership, 422 processed or over-limit

### 10.19 Reviews

#### GET /api/v1/products/{productId}/reviews
- Query optional:
  - rating
  - verified_only (boolean)
  - with_images (boolean)
  - sort: recent|helpful|rating_high|rating_low
- Success:
  - Resource collection (no success/message wrapper)

#### GET /api/v1/products/{productId}/reviews/summary
- Success:
  - success + {
    - total_reviews,
    - average_rating,
    - rating_distribution,
    - verified_count,
    - with_images_count
  }

#### GET /api/v1/products/{productId}/reviews/featured
- Success:
  - Resource collection (no success/message wrapper)

#### GET /api/v1/reviews/my
- Success:
  - Resource collection (no success/message wrapper)

#### POST /api/v1/reviews
- Body:
  - product_id required
  - order_id optional
  - rating required 1..5
  - title, comment optional
  - pros/cons optional arrays
  - images optional array max 5
- Success 201:
  - success, message, data (ReviewResource)
- Error 409 if already reviewed

#### PUT /api/v1/reviews/{review}
- Body: partial review fields
- Success: updated ReviewResource
- Error 403 ownership

#### DELETE /api/v1/reviews/{review}
- Success: delete message
- Error 403 ownership

#### POST /api/v1/reviews/{review}/vote
- Body: is_helpful required boolean
- Success: helpful_count, unhelpful_count
- Error 400 if voting own review

#### DELETE /api/v1/reviews/{review}/vote
- Success: helpful_count, unhelpful_count

#### GET /api/v1/reviews/can-review/{productId}
- Success:
  - success
  - can_review
  - reason when blocked
  - is_verified_purchase

### 10.20 Settings

#### GET /api/v1/settings
#### GET /api/v1/settings/hero
#### GET /api/v1/settings/general
#### GET /api/v1/settings/social
#### GET /api/v1/settings/seo
#### GET /api/v1/settings/footer
#### GET /api/v1/settings/banner
#### GET /api/v1/settings/checkout
#### GET /api/v1/settings/{group}
- Access for all settings endpoints above: Internal Secret (`X-Internal-Secret` required)
- Success: success + grouped key/value public settings

### 10.21 Shipping Methods

#### GET /api/v1/shipping-methods
- Access: Internal Secret (`X-Internal-Secret` required)
- Query optional:
  - amount, weight, item_count
  - division_id, district_id, upazila_id
  - location_text
- Notes:
  - Text-first checkout may send only `amount` + `location_text`.
  - If a method has location rules and location cannot be resolved, it may be filtered out from index results.
- Success:
  - success + method list with calculated cost and delivery estimate

#### GET /api/v1/shipping-methods/{code}
- Access: Internal Secret (`X-Internal-Secret` required)
- Success:
  - success + method detail including pricing rules and location rule ids
- Errors: 404 not found/inactive

#### POST /api/v1/shipping-methods/calculate
- Access: Internal Secret (`X-Internal-Secret` required)
- Body required:
  - shipping_method
  - amount
- Optional:
  - item_count, weight
  - division_id, district_id, upazila_id, location_text
- Success:
  - success + cost calculation payload
- Errors:
  - 404 method unavailable
  - 422 location not resolved where required (for methods with location rules)
  - 400 not available for order

### 10.22 Stripe

#### GET /api/v1/stripe/config
- Access: Public
- Success:
  - success + { public_key, test_mode }
- Errors:
  - 404 unavailable
  - 503 not configured

#### POST /api/v1/stripe/create-payment-intent
- Body: order_id required
- Success:
  - success + payment intent payload from service
- Errors:
  - 403 ownership
  - 400 wrong method/already paid/service issues

#### POST /api/v1/stripe/confirm-payment
- Body:
  - order_id required
  - payment_intent_id required
- Success:
  - success + { status, order_id/order_number }
- Errors:
  - 403 ownership
  - 400 mismatch or payment error

#### POST /api/v1/stripe/webhook
- Public endpoint for Stripe callbacks
- Body/headers:
  - raw Stripe event payload
  - Stripe-Signature header
- Success:
  - { "status": "success" }
- Errors:
  - 400 invalid payload/signature

### 10.23 Wishlist

#### GET /api/v1/wishlist
- Success:
  - Resource collection (no success/message wrapper)

#### POST /api/v1/wishlist
- Body:
  - product_id required
  - product_variant_id optional
- Success 201:
  - success + WishlistResource
- Error 409 if duplicate

#### POST /api/v1/wishlist/toggle
- Body:
  - product_id required
  - product_variant_id optional
- Success:
  - success, added, message

#### DELETE /api/v1/wishlist/{wishlist}
- Success: removal message
- Error 403 ownership

#### DELETE /api/v1/wishlist/product
- Body/query:
  - product_id required
  - product_variant_id optional
- Success: removal message
- Error 404 if not found in wishlist

#### GET /api/v1/wishlist/check
- Query/body:
  - product_id required
  - product_variant_id optional
- Success:
  - success, in_wishlist

#### GET /api/v1/wishlist/count
- Success:
  - success, count

#### DELETE /api/v1/wishlist/clear
- Success: wishlist cleared message

#### POST /api/v1/wishlist/{wishlist}/move-to-cart
- Success: moved message
- Errors:
  - 403 ownership
  - 400 unavailable product/cart add failure

## Frontend Integration Notes

1. Do not assume a single response envelope for all endpoints. Handle both success wrapper and pure resource collection responses.
2. For product, order, and cart totals, trust server-calculated values only.
3. Many storefront read endpoints use `internal.api`; call them via server-side proxy and include `X-Internal-Secret` (do not expose secret in browser client bundles).
4. For checkout and order creation, use `/api/v1/settings/checkout` to drive field visibility/required state.
5. Text-first checkout contract: shipping-method discovery can be called with `amount` + `location_text`, and order payload should use `shipping_location_text` (backend falls back to `shipping_address` if omitted).
6. For payments, treat create/confirm APIs as state machine steps and re-query order/payment status after completion.
7. For bKash callback flows, frontend must handle redirected query params from backend callback URLs.
8. For returns image uploads, use multipart/form-data. For reviews, send image path strings (media library URLs/paths) in the images array.
