# E-Commerce API Documentation

**Base URL:** `https://your-domain.com/api/v1`

**Version:** 1.0

**Last Updated:** January 30, 2026

---

## Table of Contents

1. [Authentication](#authentication)
2. [Products](#products)
3. [Categories](#categories)
4. [Cart](#cart)
5. [Orders](#orders)
6. [Wishlist](#wishlist)
7. [Reviews](#reviews)
8. [Addresses](#addresses)
9. [User Profile](#user-profile)
10. [Flash Sales](#flash-sales)
11. [Loyalty Program](#loyalty-program)
12. [Related Products](#related-products)
13. [Returns & Refunds](#returns--refunds)
14. [Order Tracking](#order-tracking)
15. [Coupons](#coupons)
16. [Payment Methods](#payment-methods)
17. [Payments](#payments)
18. [Shipping Methods](#shipping-methods)
19. [Settings](#settings)
20. [Error Handling](#error-handling)

---

## Response Format

All API responses follow this standard format:

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

## Authentication

All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

### Register

**POST** `/auth/register`

Create a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+8801712345678"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+8801712345678",
      "role": "customer",
      "loyalty_points": 0,
      "lifetime_points": 0,
      "loyalty_tier": null,
      "created_at": "2026-01-30T10:00:00.000000Z"
    },
    "token": "1|abc123xyz..."
  }
}
```

---

### Login

**POST** `/auth/login`

Authenticate user and get access token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+8801712345678",
      "role": "customer",
      "loyalty_points": 500,
      "lifetime_points": 1500,
      "loyalty_tier": "silver",
      "created_at": "2026-01-30T10:00:00.000000Z"
    },
    "token": "2|def456xyz..."
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### Logout

**POST** `/auth/logout`

🔒 **Requires Authentication**

Revoke the current access token.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

### Get Current User

**GET** `/auth/me`

🔒 **Requires Authentication**

Get the authenticated user's information.

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+8801712345678",
    "role": "customer",
    "loyalty_points": 500,
    "lifetime_points": 1500,
    "loyalty_tier": "silver",
    "created_at": "2026-01-30T10:00:00.000000Z"
  }
}
```

---

## Products

### List Products

**GET** `/products`

Get paginated list of all active products.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 15 | Items per page (max 100) |
| page | int | 1 | Page number |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "category_id": 5,
      "name": "Premium Wireless Headphones",
      "slug": "premium-wireless-headphones",
      "description": "High-quality wireless headphones with noise cancellation",
      "short_description": "Wireless headphones with ANC",
      "regular_price": 5999.00,
      "sale_price": 4999.00,
      "current_price": 4999.00,
      "is_on_sale": true,
      "sku": "WH-001",
      "stock_quantity": 50,
      "total_stock": 50,
      "in_stock": true,
      "image": "products/headphones-main.jpg",
      "image_url": "https://your-domain.com/storage/products/headphones-main.jpg",
      "is_active": true,
      "is_featured": true,
      "is_new": false,
      "is_bestseller": true,
      "sales_count": 245,
      "has_variants": false,
      "category": {
        "id": 5,
        "name": "Electronics",
        "slug": "electronics"
      },
      "created_at": "2026-01-15T10:00:00.000000Z",
      "updated_at": "2026-01-29T15:30:00.000000Z"
    }
  ],
  "links": {
    "first": "/api/v1/products?page=1",
    "last": "/api/v1/products?page=10",
    "prev": null,
    "next": "/api/v1/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

---

### Get Product by ID

**GET** `/products/{id}`

Get detailed product information.

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "category_id": 5,
    "name": "Premium Wireless Headphones",
    "slug": "premium-wireless-headphones",
    "description": "High-quality wireless headphones with noise cancellation...",
    "short_description": "Wireless headphones with ANC",
    "regular_price": 5999.00,
    "sale_price": 4999.00,
    "current_price": 4999.00,
    "is_on_sale": true,
    "sku": "WH-001",
    "stock_quantity": 50,
    "total_stock": 150,
    "in_stock": true,
    "image": "products/headphones-main.jpg",
    "image_url": "https://your-domain.com/storage/products/headphones-main.jpg",
    "is_active": true,
    "is_featured": true,
    "is_new": false,
    "is_bestseller": true,
    "sales_count": 245,
    "has_variants": true,
    "category": {
      "id": 5,
      "name": "Electronics",
      "slug": "electronics",
      "image": "categories/electronics.jpg"
    },
    "images": [
      {
        "id": 1,
        "image": "products/headphones-main.jpg",
        "url": "https://your-domain.com/storage/products/headphones-main.jpg",
        "alt": "Headphones front view",
        "is_primary": true,
        "sort_order": 0
      },
      {
        "id": 2,
        "image": "products/headphones-side.jpg",
        "url": "https://your-domain.com/storage/products/headphones-side.jpg",
        "alt": "Headphones side view",
        "is_primary": false,
        "sort_order": 1
      }
    ],
    "variants": [
      {
        "id": 1,
        "sku": "WH-001-BLK",
        "price": 4999.00,
        "stock_quantity": 50,
        "in_stock": true,
        "image": "products/headphones-black.jpg",
        "image_url": "https://your-domain.com/storage/products/headphones-black.jpg",
        "attributes": [
          {
            "attribute": "Color",
            "value": "Black"
          }
        ]
      },
      {
        "id": 2,
        "sku": "WH-001-WHT",
        "price": 4999.00,
        "stock_quantity": 100,
        "in_stock": true,
        "image": "products/headphones-white.jpg",
        "image_url": "https://your-domain.com/storage/products/headphones-white.jpg",
        "attributes": [
          {
            "attribute": "Color",
            "value": "White"
          }
        ]
      }
    ],
    "created_at": "2026-01-15T10:00:00.000000Z",
    "updated_at": "2026-01-29T15:30:00.000000Z"
  }
}
```

---

### Get Product by Slug

**GET** `/products/slug/{slug}`

Get product by URL-friendly slug.

**Response:** Same as Get Product by ID

---

### Featured Products

**GET** `/products/featured`

Get featured products for homepage.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Premium Wireless Headphones",
      "slug": "premium-wireless-headphones",
      "regular_price": 5999.00,
      "sale_price": 4999.00,
      "current_price": 4999.00,
      "is_on_sale": true,
      "image_url": "https://your-domain.com/storage/products/headphones-main.jpg",
      "in_stock": true,
      "is_featured": true
    }
  ]
}
```

---

### New Products

**GET** `/products/new`

Get newly added products.

**Response:** Same structure as Featured Products

---

### Bestsellers

**GET** `/products/bestsellers`

Get best-selling products.

**Response:** Same structure as Featured Products

---

### Products by Category

**GET** `/products/category/{categoryId}`

Get products in a specific category.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 15 | Items per page |
| page | int | 1 | Page number |

**Response:** Same structure as List Products

---

### Search Products

**GET** `/products/search`

Search products by name, description, or SKU.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | Yes | Search query (min 2 characters) |
| per_page | int | No | Items per page |

**Request Example:**
```
GET /products/search?q=headphones&per_page=10
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Premium Wireless Headphones",
      "slug": "premium-wireless-headphones",
      "regular_price": 5999.00,
      "sale_price": 4999.00,
      "current_price": 4999.00,
      "image_url": "https://your-domain.com/storage/products/headphones-main.jpg",
      "in_stock": true,
      "category": {
        "id": 5,
        "name": "Electronics"
      }
    }
  ],
  "meta": {
    "total": 5,
    "query": "headphones"
  }
}
```

---

### Get Product Variants

**GET** `/products/{id}/variants`

Get all variants of a product.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "WH-001-BLK",
      "price": 4999.00,
      "stock_quantity": 50,
      "in_stock": true,
      "image": "products/headphones-black.jpg",
      "image_url": "https://your-domain.com/storage/products/headphones-black.jpg",
      "attributes": [
        {
          "attribute": "Color",
          "value": "Black"
        }
      ]
    }
  ]
}
```

---

## Categories

### List Categories

**GET** `/categories`

Get all categories.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Electronic devices and accessories",
      "image": "categories/electronics.jpg",
      "image_url": "https://your-domain.com/storage/categories/electronics.jpg",
      "parent_id": null,
      "is_active": true,
      "product_count": 45,
      "children": [
        {
          "id": 5,
          "name": "Headphones",
          "slug": "headphones",
          "parent_id": 1,
          "product_count": 12
        },
        {
          "id": 6,
          "name": "Speakers",
          "slug": "speakers",
          "parent_id": 1,
          "product_count": 8
        }
      ]
    }
  ]
}
```

---

### Get Category by ID

**GET** `/categories/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Electronics",
    "slug": "electronics",
    "description": "Electronic devices and accessories",
    "image": "categories/electronics.jpg",
    "image_url": "https://your-domain.com/storage/categories/electronics.jpg",
    "parent_id": null,
    "is_active": true,
    "product_count": 45
  }
}
```

---

### Get Category by Slug

**GET** `/categories/slug/{slug}`

**Response:** Same as Get Category by ID

---

### Get Category Children

**GET** `/categories/{id}/children`

Get subcategories of a category.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Headphones",
      "slug": "headphones",
      "parent_id": 1,
      "product_count": 12
    }
  ]
}
```

---

## Cart

🔒 **All cart endpoints require authentication**

### Get Cart

**GET** `/cart`

Get the current user's shopping cart.

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_variant_id": null,
        "quantity": 2,
        "product": {
          "id": 1,
          "name": "Premium Wireless Headphones",
          "slug": "premium-wireless-headphones",
          "regular_price": 5999.00,
          "sale_price": 4999.00,
          "current_price": 4999.00,
          "image_url": "https://your-domain.com/storage/products/headphones-main.jpg",
          "in_stock": true,
          "stock_quantity": 50
        },
        "variant": null,
        "unit_price": 4999.00,
        "subtotal": 9998.00
      }
    ],
    "subtotal": 9998.00,
    "discount_amount": 0,
    "coupon_code": null,
    "total": 9998.00,
    "item_count": 1,
    "total_quantity": 2,
    "created_at": "2026-01-30T10:00:00.000000Z",
    "updated_at": "2026-01-30T10:30:00.000000Z"
  }
}
```

---

### Add Item to Cart

**POST** `/cart/items`

Add a product to the cart.

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2,
  "product_variant_id": null
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "id": 1,
    "items": [...],
    "subtotal": 9998.00,
    "total": 9998.00,
    "item_count": 1,
    "total_quantity": 2
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Insufficient stock. Only 5 items available."
}
```

---

### Update Cart Item

**PUT** `/cart/items/{productId}`

Update quantity of an item in the cart.

**Request Body:**
```json
{
  "quantity": 3
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Cart updated",
  "data": {
    "id": 1,
    "items": [...],
    "subtotal": 14997.00,
    "total": 14997.00
  }
}
```

---

### Remove Cart Item

**DELETE** `/cart/items/{productId}`

Remove an item from the cart.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Item removed from cart",
  "data": {
    "id": 1,
    "items": [...],
    "subtotal": 0,
    "total": 0,
    "item_count": 0
  }
}
```

---

### Clear Cart

**DELETE** `/cart`

Remove all items from the cart.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Cart cleared",
  "data": null
}
```

---

### Apply Coupon

**POST** `/cart/coupon`

Apply a coupon code to the cart.

**Request Body:**
```json
{
  "code": "SAVE20"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Coupon applied successfully",
  "data": {
    "coupon": {
      "code": "SAVE20",
      "type": "percentage",
      "value": 20,
      "description": "20% off your order"
    },
    "discount_amount": 1999.60,
    "subtotal": 9998.00,
    "total": 7998.40
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Coupon is not valid or has expired"
}
```

---

### Remove Coupon

**DELETE** `/cart/coupon`

Remove applied coupon from the cart.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Coupon removed",
  "data": {
    "subtotal": 9998.00,
    "discount_amount": 0,
    "total": 9998.00
  }
}
```

---

### Cart Recommendations

**POST** `/cart/recommendations`

Get product recommendations based on cart items.

**Request Body:**
```json
{
  "product_ids": [1, 5, 12],
  "limit": 6
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 8,
      "name": "Headphone Stand",
      "slug": "headphone-stand",
      "regular_price": 999.00,
      "sale_price": null,
      "image_url": "https://your-domain.com/storage/products/stand.jpg",
      "category": "Accessories"
    }
  ]
}
```

---

## Orders

🔒 **All order endpoints require authentication**

### List Orders

**GET** `/orders`

Get the current user's orders.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 15 | Items per page |
| page | int | 1 | Page number |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20260130-001",
      "user_id": 1,
      "status": "delivered",
      "payment_status": "paid",
      "subtotal": 9998.00,
      "shipping_cost": 60.00,
      "discount_amount": 1999.60,
      "total": 8058.40,
      "currency": "BDT",
      "shipping_address": {
        "name": "John Doe",
        "phone": "+8801712345678",
        "address_line_1": "House 123, Road 5",
        "city": "Dhaka",
        "postal_code": "1205"
      },
      "billing_address": {
        "name": "John Doe",
        "phone": "+8801712345678",
        "address_line_1": "House 123, Road 5",
        "city": "Dhaka",
        "postal_code": "1205"
      },
      "shipping_method": "standard",
      "payment_method": "bkash",
      "notes": "Please call before delivery",
      "items_count": 1,
      "created_at": "2026-01-25T10:00:00.000000Z",
      "updated_at": "2026-01-28T15:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5
  }
}
```

---

### Get Order by ID

**GET** `/orders/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20260130-001",
    "user_id": 1,
    "status": "delivered",
    "payment_status": "paid",
    "subtotal": 9998.00,
    "shipping_cost": 60.00,
    "discount_amount": 1999.60,
    "total": 8058.40,
    "currency": "BDT",
    "shipping_address": {
      "name": "John Doe",
      "phone": "+8801712345678",
      "address_line_1": "House 123, Road 5",
      "address_line_2": "Block A",
      "city": "Dhaka",
      "state": "Dhaka",
      "postal_code": "1205",
      "country": "Bangladesh"
    },
    "billing_address": {
      "name": "John Doe",
      "phone": "+8801712345678",
      "address_line_1": "House 123, Road 5",
      "city": "Dhaka",
      "postal_code": "1205",
      "country": "Bangladesh"
    },
    "shipping_method": "standard",
    "payment_method": "bkash",
    "notes": "Please call before delivery",
    "coupon_code": "SAVE20",
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_variant_id": null,
        "product_name": "Premium Wireless Headphones",
        "product_sku": "WH-001",
        "quantity": 2,
        "unit_price": 4999.00,
        "subtotal": 9998.00,
        "product": {
          "id": 1,
          "name": "Premium Wireless Headphones",
          "slug": "premium-wireless-headphones",
          "image_url": "https://your-domain.com/storage/products/headphones-main.jpg"
        }
      }
    ],
    "payment": {
      "id": 1,
      "payment_method": "bkash",
      "amount": 8058.40,
      "status": "completed",
      "transaction_id": "TRX123456789",
      "paid_at": "2026-01-25T10:15:00.000000Z"
    },
    "created_at": "2026-01-25T10:00:00.000000Z",
    "updated_at": "2026-01-28T15:30:00.000000Z"
  }
}
```

---

### Get Order by Order Number

**GET** `/orders/number/{orderNumber}`

**Response:** Same as Get Order by ID

---

### Create Order

**POST** `/orders`

Create a new order from the cart.

**Request Body:**
```json
{
  "shipping_address_id": 1,
  "billing_address_id": 1,
  "shipping_method": "standard",
  "payment_method": "bkash",
  "notes": "Please call before delivery",
  "coupon_code": "SAVE20"
}
```

**Alternative with inline address:**
```json
{
  "shipping_address": {
    "name": "John Doe",
    "phone": "+8801712345678",
    "address_line_1": "House 123, Road 5",
    "city": "Dhaka",
    "postal_code": "1205"
  },
  "billing_same_as_shipping": true,
  "shipping_method": "standard",
  "payment_method": "cod"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 2,
    "order_number": "ORD-20260130-002",
    "status": "pending",
    "payment_status": "pending",
    "total": 8058.40,
    "payment_url": "https://checkout.sandbox.bka.sh/...",
    "items": [...],
    "created_at": "2026-01-30T11:00:00.000000Z"
  }
}
```

---

### Cancel Order

**POST** `/orders/{id}/cancel`

Cancel a pending order.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Order cancelled",
  "data": {
    "id": 2,
    "order_number": "ORD-20260130-002",
    "status": "cancelled",
    "cancelled_at": "2026-01-30T11:30:00.000000Z"
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Order cannot be cancelled. Current status: shipped"
}
```

---

## Wishlist

🔒 **All wishlist endpoints require authentication**

### Get Wishlist

**GET** `/wishlist`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 20 | Items per page |
| page | int | 1 | Page number |

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "product_variant_id": null,
      "product": {
        "id": 1,
        "name": "Premium Wireless Headphones",
        "slug": "premium-wireless-headphones",
        "regular_price": 5999.00,
        "sale_price": 4999.00,
        "current_price": 4999.00,
        "image_url": "https://your-domain.com/storage/products/headphones-main.jpg",
        "in_stock": true,
        "category": {
          "id": 5,
          "name": "Electronics"
        }
      },
      "variant": null,
      "added_at": "2026-01-29T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 3
  }
}
```

---

### Add to Wishlist

**POST** `/wishlist`

**Request Body:**
```json
{
  "product_id": 1,
  "product_variant_id": null
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Product added to wishlist",
  "data": {
    "id": 1,
    "product_id": 1,
    "product": {...},
    "added_at": "2026-01-30T10:00:00.000000Z"
  }
}
```

**Error Response (409 Conflict):**
```json
{
  "success": false,
  "message": "Product is already in your wishlist"
}
```

---

### Toggle Wishlist

**POST** `/wishlist/toggle`

Add or remove product from wishlist.

**Request Body:**
```json
{
  "product_id": 1,
  "product_variant_id": null
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "added": true,
  "message": "Product added to wishlist"
}
```

Or when removed:
```json
{
  "success": true,
  "added": false,
  "message": "Product removed from wishlist"
}
```

---

### Check if in Wishlist

**GET** `/wishlist/check`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product_id | int | Yes | Product ID |
| product_variant_id | int | No | Variant ID |

**Response (200 OK):**
```json
{
  "success": true,
  "in_wishlist": true,
  "wishlist_id": 1
}
```

---

### Get Wishlist Count

**GET** `/wishlist/count`

**Response (200 OK):**
```json
{
  "success": true,
  "count": 5
}
```

---

### Remove from Wishlist

**DELETE** `/wishlist/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product removed from wishlist"
}
```

---

### Remove by Product ID

**DELETE** `/wishlist/product`

**Request Body:**
```json
{
  "product_id": 1,
  "product_variant_id": null
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product removed from wishlist"
}
```

---

### Clear Wishlist

**DELETE** `/wishlist/clear`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Wishlist cleared"
}
```

---

### Move to Cart

**POST** `/wishlist/{id}/move-to-cart`

Move wishlist item to shopping cart.

**Request Body (optional):**
```json
{
  "quantity": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Product moved to cart",
  "data": {
    "cart": {...}
  }
}
```

---

## Reviews

### Get Product Reviews

**GET** `/products/{productId}/reviews`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| rating | int | - | Filter by star rating (1-5) |
| verified_only | bool | false | Show only verified purchases |
| with_images | bool | false | Show only reviews with images |
| sort | string | recent | Sort: recent, helpful, rating_high, rating_low |
| per_page | int | 10 | Items per page |

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "John D.",
        "avatar": null
      },
      "rating": 5,
      "title": "Excellent headphones!",
      "comment": "Best wireless headphones I've ever owned. The noise cancellation is amazing.",
      "pros": ["Great sound quality", "Comfortable", "Long battery life"],
      "cons": ["Price is a bit high"],
      "images": [
        "https://your-domain.com/storage/reviews/review1-img1.jpg"
      ],
      "is_verified_purchase": true,
      "helpful_count": 24,
      "unhelpful_count": 2,
      "user_vote": null,
      "is_featured": true,
      "created_at": "2026-01-20T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 45
  }
}
```

---

### Get Review Summary

**GET** `/products/{productId}/reviews/summary`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_reviews": 45,
    "average_rating": 4.5,
    "rating_distribution": {
      "5": {
        "count": 25,
        "percentage": 55.6
      },
      "4": {
        "count": 12,
        "percentage": 26.7
      },
      "3": {
        "count": 5,
        "percentage": 11.1
      },
      "2": {
        "count": 2,
        "percentage": 4.4
      },
      "1": {
        "count": 1,
        "percentage": 2.2
      }
    },
    "verified_count": 38,
    "with_images_count": 15
  }
}
```

---

### Get Featured Reviews

**GET** `/products/{productId}/reviews/featured`

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user": {"name": "John D."},
      "rating": 5,
      "title": "Excellent headphones!",
      "comment": "Best wireless headphones I've ever owned...",
      "is_featured": true,
      "helpful_count": 24
    }
  ]
}
```

---

### Create Review

**POST** `/reviews`

🔒 **Requires Authentication**

**Request Body:**
```json
{
  "product_id": 1,
  "order_id": 1,
  "rating": 5,
  "title": "Excellent headphones!",
  "comment": "Best wireless headphones I've ever owned. The noise cancellation is amazing.",
  "pros": ["Great sound quality", "Comfortable", "Long battery life"],
  "cons": ["Price is a bit high"],
  "images": [
    "reviews/my-review-img1.jpg",
    "reviews/my-review-img2.jpg"
  ]
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Review submitted successfully. It will be visible after approval.",
  "data": {
    "id": 46,
    "rating": 5,
    "title": "Excellent headphones!",
    "status": "pending",
    "created_at": "2026-01-30T10:00:00.000000Z"
  }
}
```

---

### Check if Can Review

**GET** `/reviews/can-review/{productId}`

🔒 **Requires Authentication**

**Response (200 OK):**
```json
{
  "success": true,
  "can_review": true,
  "reason": null,
  "has_purchased": true,
  "existing_review_id": null
}
```

Or if already reviewed:
```json
{
  "success": true,
  "can_review": false,
  "reason": "You have already reviewed this product",
  "existing_review_id": 5
}
```

---

### Get My Reviews

**GET** `/reviews/my`

🔒 **Requires Authentication**

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Premium Wireless Headphones",
        "image_url": "..."
      },
      "rating": 5,
      "title": "Excellent headphones!",
      "status": "approved",
      "helpful_count": 24,
      "created_at": "2026-01-20T10:00:00.000000Z"
    }
  ]
}
```

---

### Update Review

**PUT** `/reviews/{id}`

🔒 **Requires Authentication** (Owner only)

**Request Body:**
```json
{
  "rating": 4,
  "title": "Good headphones",
  "comment": "Updated review...",
  "pros": ["Great sound"],
  "cons": ["Battery could be better"]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Review updated successfully",
  "data": {...}
}
```

---

### Delete Review

**DELETE** `/reviews/{id}`

🔒 **Requires Authentication** (Owner only)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Review deleted successfully"
}
```

---

### Vote on Review

**POST** `/reviews/{id}/vote`

🔒 **Requires Authentication**

**Request Body:**
```json
{
  "vote": "helpful"
}
```

Options: `helpful`, `unhelpful`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Vote recorded",
  "data": {
    "helpful_count": 25,
    "unhelpful_count": 2,
    "user_vote": "helpful"
  }
}
```

---

### Remove Vote

**DELETE** `/reviews/{id}/vote`

🔒 **Requires Authentication**

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Vote removed"
}
```

---

## Addresses

🔒 **All address endpoints require authentication**

### List Addresses

**GET** `/addresses`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| type | string | Filter: shipping, billing |

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "label": "Home",
      "type": "both",
      "is_default": true,
      "name": "John Doe",
      "phone": "+8801712345678",
      "email": "john@example.com",
      "address_line_1": "House 123, Road 5",
      "address_line_2": "Block A",
      "city": "Dhaka",
      "state": "Dhaka",
      "postal_code": "1205",
      "country": "Bangladesh",
      "instructions": "Call when near",
      "latitude": 23.8103,
      "longitude": 90.4125,
      "formatted_address": "House 123, Road 5, Block A, Dhaka 1205, Bangladesh"
    }
  ]
}
```

---

### Get Address

**GET** `/addresses/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "label": "Home",
    "type": "both",
    "is_default": true,
    "name": "John Doe",
    "phone": "+8801712345678",
    "address_line_1": "House 123, Road 5",
    "city": "Dhaka",
    "postal_code": "1205",
    "country": "Bangladesh"
  }
}
```

---

### Create Address

**POST** `/addresses`

**Request Body:**
```json
{
  "label": "Office",
  "type": "both",
  "is_default": false,
  "name": "John Doe",
  "phone": "+8801712345678",
  "email": "john@company.com",
  "address_line_1": "Tower 5, Floor 10",
  "address_line_2": "Gulshan Circle 1",
  "city": "Dhaka",
  "state": "Dhaka",
  "postal_code": "1212",
  "country": "Bangladesh",
  "instructions": "Ask for reception"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Address added successfully",
  "data": {
    "id": 2,
    "label": "Office",
    ...
  }
}
```

---

### Update Address

**PUT** `/addresses/{id}`

**Request Body:** Same fields as create (all optional)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Address updated successfully",
  "data": {...}
}
```

---

### Delete Address

**DELETE** `/addresses/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Address deleted successfully"
}
```

---

### Set Default Address

**POST** `/addresses/{id}/set-default`

**Request Body:**
```json
{
  "type": "shipping"
}
```

Options: `shipping`, `billing`, `both`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Default address updated",
  "data": {...}
}
```

---

### Get Default Shipping Address

**GET** `/addresses/default/shipping`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "label": "Home",
    ...
  }
}
```

---

### Get Default Billing Address

**GET** `/addresses/default/billing`

**Response:** Same structure as default shipping

---

## User Profile

🔒 **All profile endpoints require authentication**

### Get Profile

**GET** `/profile`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+8801712345678",
    "role": "customer",
    "loyalty_points": 500,
    "lifetime_points": 1500,
    "loyalty_tier": "silver",
    "email_verified_at": "2026-01-01T10:00:00.000000Z",
    "created_at": "2026-01-01T10:00:00.000000Z"
  }
}
```

---

### Update Profile

**PUT** `/profile`

**Request Body:**
```json
{
  "name": "John Smith",
  "phone": "+8801798765432",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "+8801798765432"
  }
}
```

---

## Flash Sales

### Get Active Flash Sales

**GET** `/flash-sales`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Weekend Flash Sale",
      "slug": "weekend-flash-sale",
      "description": "Massive discounts this weekend only!",
      "banner_image": "https://your-domain.com/storage/flash-sales/banner1.jpg",
      "starts_at": "2026-01-30T00:00:00.000000Z",
      "ends_at": "2026-02-01T23:59:59.000000Z",
      "status": "active",
      "time_remaining": {
        "days": 1,
        "hours": 23,
        "minutes": 45,
        "seconds": 30,
        "total_seconds": 172530
      },
      "products": [
        {
          "id": 1,
          "product_id": 1,
          "product": {
            "id": 1,
            "name": "Premium Wireless Headphones",
            "slug": "premium-wireless-headphones",
            "image_url": "https://your-domain.com/storage/products/headphones-main.jpg"
          },
          "flash_price": 3999.00,
          "original_price": 5999.00,
          "discount_percentage": 33,
          "quantity_limit": 100,
          "sold_count": 45,
          "stock_remaining": 55,
          "is_sold_out": false,
          "per_user_limit": 2
        }
      ]
    }
  ]
}
```

---

### Get Featured Flash Sale

**GET** `/flash-sales/featured`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Weekend Flash Sale",
    "slug": "weekend-flash-sale",
    "banner_image": "https://your-domain.com/storage/flash-sales/banner1.jpg",
    "time_remaining": {
      "days": 1,
      "hours": 23,
      "minutes": 45,
      "total_seconds": 172530
    },
    "products": [...]
  }
}
```

---

### Get Upcoming Flash Sales

**GET** `/flash-sales/upcoming`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Valentine's Day Sale",
      "slug": "valentines-day-sale",
      "description": "Special discounts for Valentine's Day",
      "banner_image": "https://your-domain.com/storage/flash-sales/valentine.jpg",
      "starts_at": "2026-02-14T00:00:00.000000Z",
      "time_until_start": "in 2 weeks"
    }
  ]
}
```

---

### Get Flash Sale Details

**GET** `/flash-sales/{slug}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Weekend Flash Sale",
    "slug": "weekend-flash-sale",
    "description": "Massive discounts this weekend only!",
    "banner_image": "https://your-domain.com/storage/flash-sales/banner1.jpg",
    "starts_at": "2026-01-30T00:00:00.000000Z",
    "ends_at": "2026-02-01T23:59:59.000000Z",
    "status": "active",
    "time_remaining": {...},
    "products": [...]
  }
}
```

---

### Check Product Flash Sale

**GET** `/flash-sales/product/{productId}`

Check if a product is currently in an active flash sale.

**Response (200 OK):**
```json
{
  "success": true,
  "in_flash_sale": true,
  "data": {
    "flash_sale_id": 1,
    "flash_sale_name": "Weekend Flash Sale",
    "flash_price": 3999.00,
    "original_price": 5999.00,
    "discount_percentage": 33,
    "quantity_limit": 100,
    "sold_count": 45,
    "stock_remaining": 55,
    "ends_at": "2026-02-01T23:59:59.000000Z",
    "time_remaining": {...}
  }
}
```

Or if not in flash sale:
```json
{
  "success": true,
  "in_flash_sale": false,
  "data": null
}
```

---

### Validate Flash Sale Purchase

**POST** `/flash-sales/validate-purchase`

🔒 **Requires Authentication**

Validate before adding to cart.

**Request Body:**
```json
{
  "product_id": 1,
  "flash_sale_id": 1,
  "quantity": 2
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "allowed": true,
  "flash_price": 3999.00,
  "max_quantity": 2,
  "message": "Purchase allowed"
}
```

**Error Response (400):**
```json
{
  "success": false,
  "allowed": false,
  "reason": "You have already purchased the maximum allowed quantity (2) for this flash sale"
}
```

---

## Loyalty Program

🔒 **All loyalty endpoints require authentication**

### Get Loyalty Summary

**GET** `/loyalty/summary`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_points": 500,
    "lifetime_points": 1500,
    "current_tier": {
      "name": "Silver",
      "slug": "silver",
      "min_points": 1000,
      "points_multiplier": 1.25,
      "birthday_bonus": 100,
      "free_shipping": false,
      "exclusive_discount": 5,
      "benefits": [
        "Earn 1.25x points on every purchase",
        "100 bonus points on your birthday",
        "5% exclusive member discount"
      ]
    },
    "next_tier": {
      "name": "Gold",
      "min_points": 5000,
      "points_needed": 3500
    },
    "points_expiring_soon": 200,
    "expiring_date": "2026-03-30T00:00:00.000000Z",
    "points_rate": "1 point per ৳1 spent"
  }
}
```

---

### Get Loyalty Tiers

**GET** `/loyalty/tiers`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "name": "Bronze",
      "slug": "bronze",
      "min_points": 0,
      "points_multiplier": 1.0,
      "birthday_bonus": 50,
      "free_shipping": false,
      "exclusive_discount": 0,
      "badge_image": "https://your-domain.com/storage/tiers/bronze.png",
      "benefits": ["Earn 1x points on every purchase", "50 bonus points on birthday"]
    },
    {
      "name": "Silver",
      "slug": "silver",
      "min_points": 1000,
      "points_multiplier": 1.25,
      "birthday_bonus": 100,
      "free_shipping": false,
      "exclusive_discount": 5,
      "badge_image": "https://your-domain.com/storage/tiers/silver.png",
      "benefits": [...]
    },
    {
      "name": "Gold",
      "slug": "gold",
      "min_points": 5000,
      "points_multiplier": 1.5,
      "birthday_bonus": 200,
      "free_shipping": true,
      "exclusive_discount": 10,
      "badge_image": "https://your-domain.com/storage/tiers/gold.png",
      "benefits": [...]
    },
    {
      "name": "Platinum",
      "slug": "platinum",
      "min_points": 10000,
      "points_multiplier": 2.0,
      "birthday_bonus": 500,
      "free_shipping": true,
      "exclusive_discount": 15,
      "badge_image": "https://your-domain.com/storage/tiers/platinum.png",
      "benefits": [...]
    }
  ]
}
```

---

### Get Transaction History

**GET** `/loyalty/transactions`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 20 | Items per page |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "type": "earned",
        "points": 100,
        "balance_after": 600,
        "description": "Points earned from order #ORD-20260125-001",
        "order_id": 1,
        "expires_at": "2027-01-25T00:00:00.000000Z",
        "created_at": "2026-01-25T10:00:00.000000Z"
      },
      {
        "id": 2,
        "type": "redeemed",
        "points": -200,
        "balance_after": 400,
        "description": "Redeemed for 10% discount coupon",
        "expires_at": null,
        "created_at": "2026-01-28T15:00:00.000000Z"
      },
      {
        "id": 3,
        "type": "bonus",
        "points": 100,
        "balance_after": 500,
        "description": "Birthday bonus points",
        "created_at": "2026-01-29T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 15
    }
  }
}
```

---

### Get Available Rewards

**GET** `/loyalty/rewards`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "৳100 Discount",
      "description": "Get ৳100 off your next order",
      "image": "https://your-domain.com/storage/rewards/discount100.png",
      "points_required": 200,
      "reward_type": "discount_fixed",
      "reward_value": 100.00,
      "reward_description": "৳100 off your order",
      "quantity_remaining": null,
      "can_redeem": true,
      "points_needed": 0,
      "ends_at": null
    },
    {
      "id": 2,
      "name": "10% Off Coupon",
      "description": "Get 10% off your entire order",
      "image": "https://your-domain.com/storage/rewards/10percent.png",
      "points_required": 500,
      "reward_type": "discount_percentage",
      "reward_value": 10.00,
      "reward_description": "10% off your order",
      "quantity_remaining": 50,
      "can_redeem": true,
      "points_needed": 0,
      "ends_at": "2026-02-28T23:59:59.000000Z"
    },
    {
      "id": 3,
      "name": "Free Shipping",
      "description": "Free shipping on your next order",
      "points_required": 300,
      "reward_type": "free_shipping",
      "reward_value": 0,
      "reward_description": "Free shipping",
      "can_redeem": true,
      "points_needed": 0
    },
    {
      "id": 4,
      "name": "Premium Headphones",
      "description": "Get free Premium Wireless Headphones",
      "points_required": 10000,
      "reward_type": "free_product",
      "reward_value": 1,
      "product_id": 1,
      "reward_description": "Free Premium Wireless Headphones",
      "can_redeem": false,
      "points_needed": 9500
    }
  ]
}
```

---

### Redeem Reward

**POST** `/loyalty/redeem`

**Request Body:**
```json
{
  "reward_id": 2
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Reward redeemed successfully",
  "data": {
    "redemption_id": 1,
    "coupon_code": "LOYALTY-ABC123XYZ",
    "expires_at": "2026-02-28T23:59:59.000000Z",
    "points_remaining": 0
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Insufficient points. You need 500 points but only have 400."
}
```

---

### Get My Redemptions

**GET** `/loyalty/redemptions`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "reward": {
        "name": "10% Off Coupon",
        "type": "discount_percentage",
        "value": 10.00
      },
      "points_spent": 500,
      "coupon_code": "LOYALTY-ABC123XYZ",
      "status": "pending",
      "expires_at": "2026-02-28T23:59:59.000000Z",
      "created_at": "2026-01-30T10:00:00.000000Z"
    }
  ]
}
```

---

### Get Active Redemptions

**GET** `/loyalty/redemptions/active`

Get unused/pending redemptions.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "reward": {
        "name": "10% Off Coupon",
        "type": "discount_percentage",
        "value": 10.00
      },
      "coupon_code": "LOYALTY-ABC123XYZ",
      "status": "pending",
      "expires_at": "2026-02-28T23:59:59.000000Z"
    }
  ]
}
```

---

### Cancel Redemption

**POST** `/loyalty/redemptions/{id}/cancel`

Cancel unused redemption and refund points.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Redemption cancelled. 500 points have been refunded.",
  "data": {
    "points_refunded": 500,
    "current_points": 500
  }
}
```

---

### Validate Loyalty Coupon

**POST** `/loyalty/validate-coupon`

**Request Body:**
```json
{
  "coupon_code": "LOYALTY-ABC123XYZ",
  "cart_total": 5000
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "valid": true,
  "data": {
    "reward_type": "discount_percentage",
    "reward_value": 10.00,
    "discount_amount": 500.00,
    "description": "10% Off Coupon"
  }
}
```

---

### Get Leaderboard

**GET** `/loyalty/leaderboard`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | int | 10 | Number of top earners |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "name": "Jane S.",
      "lifetime_points": 15000,
      "tier": "platinum"
    },
    {
      "rank": 2,
      "name": "Mike T.",
      "lifetime_points": 12500,
      "tier": "platinum"
    }
  ]
}
```

---

## Related Products

### Get Related Products

**GET** `/products/{productId}/related`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | int | 8 | Number of products |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Wireless Earbuds",
      "slug": "wireless-earbuds",
      "regular_price": 2999.00,
      "sale_price": null,
      "image": "https://your-domain.com/storage/products/earbuds.jpg",
      "category": "Electronics"
    }
  ]
}
```

---

### Get Frequently Bought Together

**GET** `/products/{productId}/frequently-bought-together`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | int | 4 | Number of products |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Headphone Case",
      "slug": "headphone-case",
      "regular_price": 599.00,
      "sale_price": null,
      "image": "https://your-domain.com/storage/products/case.jpg"
    }
  ]
}
```

---

### Get Upsell Products

**GET** `/products/{productId}/upsell`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "name": "Premium Wireless Headphones Pro",
      "slug": "premium-wireless-headphones-pro",
      "regular_price": 8999.00,
      "sale_price": 7999.00,
      "image": "https://your-domain.com/storage/products/headphones-pro.jpg"
    }
  ]
}
```

---

### Get Cross-Sell Products

**GET** `/products/{productId}/cross-sell`

**Response:** Same structure as Related Products

---

## Returns & Refunds

🔒 **All return endpoints require authentication**

### List Returns

**GET** `/returns`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "return_number": "RET-20260130-001",
      "order_id": 1,
      "order_number": "ORD-20260125-001",
      "status": "approved",
      "type": "refund",
      "reason": "defective",
      "reason_details": "The left speaker stopped working after 2 days",
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "Premium Wireless Headphones",
          "quantity": 1,
          "unit_price": 4999.00,
          "refund_amount": 4999.00,
          "reason": "Defective product"
        }
      ],
      "subtotal": 4999.00,
      "refund_amount": 4999.00,
      "refund_method": "original_payment",
      "admin_notes": "Approved. Product confirmed defective.",
      "images": [
        "https://your-domain.com/storage/returns/defect1.jpg"
      ],
      "created_at": "2026-01-28T10:00:00.000000Z",
      "processed_at": "2026-01-29T15:00:00.000000Z"
    }
  ]
}
```

---

### Check Return Eligibility

**GET** `/returns/check-eligibility`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| order_id | int | Yes | Order ID |
| product_id | int | Yes | Product ID |

**Response (200 OK):**
```json
{
  "success": true,
  "eligible": true,
  "reason": null,
  "return_window_days": 14,
  "days_remaining": 10,
  "max_quantity": 2
}
```

Or if not eligible:
```json
{
  "success": false,
  "eligible": false,
  "reason": "Return window has expired (14 days)",
  "return_window_days": 14,
  "days_remaining": 0
}
```

---

### Create Return Request

**POST** `/returns`

**Request Body:**
```json
{
  "order_id": 1,
  "type": "refund",
  "reason": "defective",
  "reason_details": "The left speaker stopped working after 2 days",
  "items": [
    {
      "order_item_id": 1,
      "quantity": 1,
      "reason": "Defective product"
    }
  ],
  "refund_method": "original_payment"
}
```

**Return Types:** `refund`, `exchange`, `store_credit`

**Return Reasons:** `defective`, `wrong_item`, `not_as_described`, `damaged`, `changed_mind`, `other`

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Return request submitted successfully",
  "data": {
    "id": 2,
    "return_number": "RET-20260130-002",
    "status": "pending",
    "type": "refund",
    "created_at": "2026-01-30T10:00:00.000000Z"
  }
}
```

---

### Get Return Details

**GET** `/returns/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "return_number": "RET-20260130-001",
    "order": {
      "id": 1,
      "order_number": "ORD-20260125-001"
    },
    "status": "approved",
    "type": "refund",
    "reason": "defective",
    "reason_details": "The left speaker stopped working after 2 days",
    "items": [...],
    "subtotal": 4999.00,
    "refund_amount": 4999.00,
    "refund_method": "original_payment",
    "tracking_number": null,
    "admin_notes": "Approved. Product confirmed defective.",
    "images": [...],
    "timeline": [
      {
        "status": "pending",
        "date": "2026-01-28T10:00:00.000000Z",
        "note": "Return request submitted"
      },
      {
        "status": "approved",
        "date": "2026-01-29T15:00:00.000000Z",
        "note": "Return approved by admin"
      }
    ],
    "created_at": "2026-01-28T10:00:00.000000Z"
  }
}
```

---

### Upload Return Images

**POST** `/returns/{id}/upload-images`

**Request Body (multipart/form-data):**
```
images[]: (file) image1.jpg
images[]: (file) image2.jpg
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": {
    "images": [
      "https://your-domain.com/storage/returns/img1.jpg",
      "https://your-domain.com/storage/returns/img2.jpg"
    ]
  }
}
```

---

### Cancel Return

**POST** `/returns/{id}/cancel`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Return request cancelled"
}
```

---

## Order Tracking

### Track by Order Number

**GET** `/track/order/{orderNumber}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-20260125-001",
    "status": "shipped",
    "tracking_number": "TRACK123456",
    "carrier": "Pathao",
    "estimated_delivery": "2026-02-01",
    "shipping_address": {
      "name": "John Doe",
      "city": "Dhaka"
    },
    "timeline": [
      {
        "status": "pending",
        "title": "Order Placed",
        "description": "Your order has been placed successfully",
        "date": "2026-01-25T10:00:00.000000Z",
        "completed": true
      },
      {
        "status": "confirmed",
        "title": "Order Confirmed",
        "description": "Your order has been confirmed",
        "date": "2026-01-25T10:15:00.000000Z",
        "completed": true
      },
      {
        "status": "processing",
        "title": "Processing",
        "description": "Your order is being prepared",
        "date": "2026-01-26T09:00:00.000000Z",
        "completed": true
      },
      {
        "status": "shipped",
        "title": "Shipped",
        "description": "Your order has been shipped",
        "date": "2026-01-27T14:00:00.000000Z",
        "completed": true,
        "tracking_url": "https://pathao.com/track/TRACK123456"
      },
      {
        "status": "out_for_delivery",
        "title": "Out for Delivery",
        "description": "Your order is out for delivery",
        "date": null,
        "completed": false
      },
      {
        "status": "delivered",
        "title": "Delivered",
        "description": "Your order has been delivered",
        "date": null,
        "completed": false
      }
    ]
  }
}
```

---

### Track by Tracking Number

**GET** `/track/tracking/{trackingNumber}`

**Response:** Same structure as Track by Order Number

---

### Get Order Tracking (Authenticated)

**GET** `/orders/{id}/tracking`

🔒 **Requires Authentication**

**Response:** Same structure as Track by Order Number with additional order details

---

## Coupons

### Get Available Coupons

**GET** `/coupons/available`

🔒 **Requires Authentication**

Get coupons available for the current user.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "code": "WELCOME10",
      "type": "percentage",
      "value": 10,
      "description": "10% off for new customers",
      "min_order_amount": 500,
      "max_discount": 1000,
      "valid_until": "2026-12-31T23:59:59.000000Z",
      "usage_limit": 1,
      "times_used": 0
    }
  ]
}
```

---

### Validate Coupon

**GET** `/coupons/validate`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| code | string | Yes | Coupon code |
| cart_total | number | Yes | Cart subtotal |

**Response (200 OK):**
```json
{
  "success": true,
  "valid": true,
  "data": {
    "code": "SAVE20",
    "type": "percentage",
    "value": 20,
    "discount_amount": 1999.60,
    "description": "20% off your order"
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "valid": false,
  "message": "Minimum order amount is ৳1000"
}
```

---

## Payment Methods

### List Payment Methods

**GET** `/payment-methods`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "code": "cod",
      "name": "Cash on Delivery",
      "description": "Pay when you receive your order",
      "icon": "https://your-domain.com/storage/payments/cod.png",
      "is_active": true,
      "min_amount": 0,
      "max_amount": 50000,
      "fee": 0,
      "fee_type": "fixed"
    },
    {
      "code": "bkash",
      "name": "bKash",
      "description": "Pay with bKash mobile wallet",
      "icon": "https://your-domain.com/storage/payments/bkash.png",
      "is_active": true,
      "min_amount": 10,
      "max_amount": null,
      "fee": 1.5,
      "fee_type": "percentage"
    },
    {
      "code": "stripe",
      "name": "Credit/Debit Card",
      "description": "Pay with Visa, Mastercard, etc.",
      "icon": "https://your-domain.com/storage/payments/card.png",
      "is_active": true,
      "min_amount": 100,
      "max_amount": null,
      "fee": 2.9,
      "fee_type": "percentage"
    }
  ]
}
```

---

### Get Payment Method Details

**GET** `/payment-methods/{code}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "code": "bkash",
    "name": "bKash",
    "description": "Pay with bKash mobile wallet",
    "instructions": "You will be redirected to bKash to complete payment",
    "icon": "https://your-domain.com/storage/payments/bkash.png",
    "is_active": true,
    "fee": 1.5,
    "fee_type": "percentage"
  }
}
```

---

## Payments

🔒 **All payment endpoints require authentication**

### Create Payment

**POST** `/payments`

**Request Body:**
```json
{
  "order_id": 1,
  "payment_method": "bkash"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Payment initiated",
  "data": {
    "payment_id": 1,
    "payment_url": "https://checkout.sandbox.bka.sh/...",
    "expires_at": "2026-01-30T11:30:00.000000Z"
  }
}
```

---

### Get Payment Status

**GET** `/payments/order/{orderId}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 1,
    "payment_method": "bkash",
    "amount": 8058.40,
    "status": "completed",
    "transaction_id": "TRX123456789",
    "paid_at": "2026-01-30T11:15:00.000000Z",
    "metadata": {
      "bkash_trx_id": "ABC123XYZ"
    }
  }
}
```

---

### Process Payment Callback

**POST** `/payments/{paymentId}/process`

Used internally for payment gateway callbacks.

---

### Request Refund

**POST** `/payments/{paymentId}/refund`

Admin only endpoint.

---

## bKash Integration

### Get bKash Config

**GET** `/bkash/config`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "app_key": "your-app-key",
    "sandbox": true
  }
}
```

---

### Create bKash Payment

**POST** `/bkash/create-payment`

🔒 **Requires Authentication**

**Request Body:**
```json
{
  "order_id": 1,
  "amount": 8058.40
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "payment_id": "TR001234",
    "bkash_url": "https://checkout.sandbox.bka.sh/v1.2.0-beta/checkout/payment/create",
    "merchant_invoice_number": "ORD-20260130-001"
  }
}
```

---

### bKash Callback

**GET** `/bkash/callback`

Called by bKash after payment completion.

---

### Check bKash Status

**GET** `/bkash/check-status`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| payment_id | string | Yes | bKash payment ID |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "status": "completed",
    "transaction_id": "TRX123456789"
  }
}
```

---

## Stripe Integration

### Get Stripe Config

**GET** `/stripe/config`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "publishable_key": "pk_test_..."
  }
}
```

---

### Create Payment Intent

**POST** `/stripe/create-payment-intent`

🔒 **Requires Authentication**

**Request Body:**
```json
{
  "order_id": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_xxx_secret_xxx",
    "payment_intent_id": "pi_xxx",
    "amount": 805840,
    "currency": "bdt"
  }
}
```

---

### Confirm Payment

**POST** `/stripe/confirm-payment`

🔒 **Requires Authentication**

**Request Body:**
```json
{
  "payment_intent_id": "pi_xxx",
  "order_id": 1
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Payment confirmed",
  "data": {
    "status": "succeeded",
    "order_status": "confirmed"
  }
}
```

---

## Shipping Methods

### List Shipping Methods

**GET** `/shipping-methods`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "code": "standard",
      "name": "Standard Delivery",
      "description": "Delivered within 3-5 business days",
      "base_cost": 60.00,
      "free_shipping_threshold": 2000,
      "estimated_days_min": 3,
      "estimated_days_max": 5,
      "is_active": true
    },
    {
      "code": "express",
      "name": "Express Delivery",
      "description": "Delivered within 1-2 business days",
      "base_cost": 120.00,
      "free_shipping_threshold": null,
      "estimated_days_min": 1,
      "estimated_days_max": 2,
      "is_active": true
    },
    {
      "code": "same_day",
      "name": "Same Day Delivery",
      "description": "Delivered today (order before 2 PM)",
      "base_cost": 200.00,
      "free_shipping_threshold": null,
      "estimated_days_min": 0,
      "estimated_days_max": 0,
      "is_active": true,
      "conditions": "Available in Dhaka only. Order before 2 PM."
    }
  ]
}
```

---

### Get Shipping Method

**GET** `/shipping-methods/{code}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "code": "standard",
    "name": "Standard Delivery",
    "description": "Delivered within 3-5 business days",
    "base_cost": 60.00,
    "free_shipping_threshold": 2000,
    "estimated_days_min": 3,
    "estimated_days_max": 5
  }
}
```

---

### Calculate Shipping

**POST** `/shipping-methods/calculate`

**Request Body:**
```json
{
  "city": "Dhaka",
  "cart_total": 5000,
  "weight": 0.5
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "code": "standard",
      "name": "Standard Delivery",
      "cost": 0,
      "original_cost": 60.00,
      "is_free": true,
      "free_shipping_reason": "Free shipping on orders over ৳2000",
      "estimated_delivery": "February 2-4, 2026"
    },
    {
      "code": "express",
      "name": "Express Delivery",
      "cost": 120.00,
      "is_free": false,
      "estimated_delivery": "January 31 - February 1, 2026"
    },
    {
      "code": "same_day",
      "name": "Same Day Delivery",
      "cost": 200.00,
      "is_free": false,
      "estimated_delivery": "Today"
    }
  ]
}
```

---

## Settings

### Get All Settings

**GET** `/settings`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "general": {...},
    "hero": {...},
    "banner": {...},
    "footer": {...},
    "social": {...},
    "seo": {...}
  }
}
```

---

### Get General Settings

**GET** `/settings/general`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "site_name": "My E-Commerce Store",
    "site_tagline": "Best deals on electronics and more",
    "logo": "https://your-domain.com/storage/settings/logo.png",
    "favicon": "https://your-domain.com/storage/settings/favicon.ico",
    "currency": "BDT",
    "currency_symbol": "৳",
    "phone": "+8801712345678",
    "email": "support@example.com",
    "address": "123 Main Street, Dhaka, Bangladesh"
  }
}
```

---

### Get Hero Settings

**GET** `/settings/hero`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "slides": [
      {
        "title": "Summer Sale",
        "subtitle": "Up to 50% off on selected items",
        "image": "https://your-domain.com/storage/hero/slide1.jpg",
        "button_text": "Shop Now",
        "button_link": "/products?sale=true"
      }
    ]
  }
}
```

---

### Get Banner Settings

**GET** `/settings/banner`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "announcement_bar": {
      "enabled": true,
      "text": "Free shipping on orders over ৳2000!",
      "link": "/shipping-info",
      "background_color": "#FF5733"
    },
    "promotional_banners": [
      {
        "image": "https://your-domain.com/storage/banners/promo1.jpg",
        "link": "/flash-sales/weekend-sale",
        "position": "homepage_top"
      }
    ]
  }
}
```

---

### Get Footer Settings

**GET** `/settings/footer`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "about_text": "Your trusted online shopping destination...",
    "copyright": "© 2026 My E-Commerce Store. All rights reserved.",
    "payment_methods": ["bkash", "nagad", "visa", "mastercard"],
    "quick_links": [
      {"title": "About Us", "url": "/about"},
      {"title": "Contact", "url": "/contact"},
      {"title": "FAQs", "url": "/faqs"}
    ]
  }
}
```

---

### Get Social Settings

**GET** `/settings/social`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "facebook": "https://facebook.com/mystore",
    "instagram": "https://instagram.com/mystore",
    "twitter": "https://twitter.com/mystore",
    "youtube": "https://youtube.com/mystore",
    "linkedin": null
  }
}
```

---

### Get SEO Settings

**GET** `/settings/seo`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "meta_title": "My E-Commerce Store - Best Deals Online",
    "meta_description": "Shop the best electronics, fashion, and more at great prices.",
    "meta_keywords": ["ecommerce", "online shopping", "electronics", "bangladesh"],
    "og_image": "https://your-domain.com/storage/seo/og-image.jpg",
    "google_analytics_id": "G-XXXXXXXXXX",
    "facebook_pixel_id": "123456789"
  }
}
```

---

### Get Settings by Group

**GET** `/settings/{group}`

Get settings for a specific group (general, hero, banner, footer, social, seo).

**Response:** Varies by group

---

## Product Attributes

### List Attributes

**GET** `/attributes`

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Color",
      "slug": "color",
      "type": "color",
      "values": [
        {"id": 1, "value": "Black", "color_code": "#000000"},
        {"id": 2, "value": "White", "color_code": "#FFFFFF"},
        {"id": 3, "value": "Red", "color_code": "#FF0000"}
      ]
    },
    {
      "id": 2,
      "name": "Size",
      "slug": "size",
      "type": "select",
      "values": [
        {"id": 4, "value": "S"},
        {"id": 5, "value": "M"},
        {"id": 6, "value": "L"},
        {"id": 7, "value": "XL"}
      ]
    }
  ]
}
```

---

### Get Attribute

**GET** `/attributes/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Color",
    "slug": "color",
    "type": "color",
    "values": [...]
  }
}
```

---

## Abandoned Cart Tracking

### Track Checkout Progress

**POST** `/checkout/track`

Track user's checkout progress for abandoned cart recovery.

**Request Body:**
```json
{
  "email": "john@example.com",
  "step": "shipping",
  "cart_data": {
    "items": [...],
    "total": 5000
  }
}
```

**Steps:** `cart`, `shipping`, `payment`, `review`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Progress tracked"
}
```

---

### Mark Cart Recovered

**POST** `/checkout/recovered`

Mark abandoned cart as recovered (usually called after order placement).

**Request Body:**
```json
{
  "abandoned_cart_id": 1,
  "order_id": 5
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Cart marked as recovered"
}
```

---

## Health Check

### API Health

**GET** `/health`

**Response (200 OK):**
```json
{
  "status": "ok",
  "timestamp": "2026-01-30T10:00:00.000000Z",
  "version": "1.0"
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found |
| 409 | Conflict - Resource already exists |
| 422 | Validation Error |
| 429 | Too Many Requests - Rate limited |
| 500 | Internal Server Error |

### Validation Error Response

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required.",
      "The email must be a valid email address."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

### Rate Limiting

API requests are rate limited to 60 requests per minute per user/IP.

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1706612400
```

**Rate Limited Response (429):**
```json
{
  "success": false,
  "message": "Too many requests. Please try again in 45 seconds."
}
```

---

## Pagination

Paginated endpoints support the following query parameters:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | int | 1 | Page number |
| per_page | int | 15 | Items per page (max 100) |

---

## Filtering & Sorting

Many list endpoints support filtering and sorting. Common parameters:

| Parameter | Description |
|-----------|-------------|
| sort | Sort field (prefix with `-` for descending) |
| search / q | Search query |
| status | Filter by status |
| category_id | Filter by category |
| date_from | Filter from date |
| date_to | Filter to date |

Example:
```
GET /products?sort=-created_at&category_id=5&per_page=20
```

---

## Webhooks

For integration with external services, configure webhooks in the admin panel. Available events:

- `order.created`
- `order.paid`
- `order.shipped`
- `order.delivered`
- `order.cancelled`
- `payment.completed`
- `payment.failed`
- `return.requested`
- `return.approved`

---

## SDK & Libraries

Official SDKs coming soon for:
- JavaScript/TypeScript
- React Native
- Flutter

---

## Support

For API support, contact:
- Email: api-support@example.com
- Documentation: https://docs.example.com
