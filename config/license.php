<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Licensing Server
    |--------------------------------------------------------------------------
    |
    | This installation checks in with the Coder Zone BD licensing server
    | (the Go/Fiber Sync API) to activate and periodically re-verify its
    | license key. The public key is the Ed25519 key the licensing server
    | prints to its own startup log once, on first boot — not a secret.
    |
    */
    'server_url' => env('LICENSE_SERVER_URL', 'https://li.czbd.dev'),
    'public_key' => env('LICENSE_PUBLIC_KEY', '0pKowydaNJTJ+LUlacTkUU8KehPSxD3ERXpV8wPcwgs='),
    'license_key' => env('LICENSE_KEY', ''),
    'product_slug' => env('LICENSE_PRODUCT_SLUG', 'backend-ecom'),

    /*
    |--------------------------------------------------------------------------
    | Offline Grace Period
    |--------------------------------------------------------------------------
    |
    | How many hours this install may keep running on its last-known-good
    | verification if the licensing server is unreachable.
    |
    */
    'grace_period_hours' => (int) env('LICENSE_GRACE_PERIOD_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache_path' => env('LICENSE_CACHE_PATH', storage_path('app/license/cache.json')),

    /*
    |--------------------------------------------------------------------------
    | Enforcement
    |--------------------------------------------------------------------------
    |
    | When the license is expired/invalid beyond the grace period, this
    | install degrades instead of hard-locking: existing data stays fully
    | readable/editable, new admin-created resources (users, categories,
    | products) are blocked, and orders placed after the license expired
    | become invisible/locked to the admin surface (storefront checkout
    | keeps working normally either way). See App\Services\LicenseService.
    |
    */
    'settings_group' => 'license',
];
