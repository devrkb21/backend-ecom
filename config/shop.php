<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    */
    'company_name' => env('SHOP_COMPANY_NAME', 'Inner Collection'),
    'company_address' => env('SHOP_COMPANY_ADDRESS', 'Dhaka, Bangladesh'),
    'company_phone' => env('SHOP_COMPANY_PHONE', '+880 1700-000000'),
    'company_email' => env('SHOP_COMPANY_EMAIL', 'info@innercollection.com.bd'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('SHOP_CURRENCY', 'BDT'),
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '৳'),

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    */
    'tax_rate' => env('SHOP_TAX_RATE', 0),
    'tax_included' => env('SHOP_TAX_INCLUDED', true),

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */
    'low_stock_threshold' => env('SHOP_LOW_STOCK_THRESHOLD', 5),
    'track_inventory' => true,

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */
    'order_prefix' => env('SHOP_ORDER_PREFIX', 'ORD'),
    'guest_checkout_user_email' => env('SHOP_GUEST_CHECKOUT_USER_EMAIL', 'guest.checkout@innercollection.local'),
    'guest_checkout_user_name' => env('SHOP_GUEST_CHECKOUT_USER_NAME', 'Guest Checkout'),
    'guest_checkout_user_phone' => env('SHOP_GUEST_CHECKOUT_USER_PHONE', null),

    /*
    |--------------------------------------------------------------------------
    | Returns & Refunds
    |--------------------------------------------------------------------------
    */
    'return_period_days' => env('SHOP_RETURN_PERIOD_DAYS', 30),
    'return_requires_approval' => true,
    'auto_refund' => false,

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    */
    'per_page' => 15,
    'max_per_page' => 100,

    /*
    |--------------------------------------------------------------------------
    | Product Settings
    |--------------------------------------------------------------------------
    */
    'max_images_per_product' => 10,
    'featured_products_limit' => 12,
    'new_products_days' => 30,
    'bestsellers_limit' => 12,

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    */
    'max_cart_items' => 50,
    'abandoned_cart_hours' => 2,

    /*
    |--------------------------------------------------------------------------
    | Internal API
    |--------------------------------------------------------------------------
    */
    'internal_api_secret' => env('INTERNAL_API_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Loyalty Points
    |--------------------------------------------------------------------------
    */
    'loyalty_points_per_currency' => env('SHOP_LOYALTY_POINTS_PER_CURRENCY', 1),
    'loyalty_currency_per_point' => env('SHOP_LOYALTY_CURRENCY_PER_POINT', 0.10),
];
