<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'https://innercollection.com.bd'),
        env('ADMIN_URL', 'https://admin.innercollection.com.bd'),
        env('CORS_EXTRA_ORIGIN'),
        env('APP_ENV') === 'local' ? 'http://localhost:3000' : null,
        env('APP_ENV') === 'local' ? 'http://localhost:5173' : null,
    ]),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'Origin', 'X-XSRF-TOKEN'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
