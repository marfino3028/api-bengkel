<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    // Comma-separated origins via FRONTEND_URLS, atau '*' untuk semua (token auth, tanpa cookie).
    'allowed_origins' => array_filter(
        explode(',', env('FRONTEND_URLS', '*'))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Token-based (Bearer) — tidak pakai cookie, jadi false.
    'supports_credentials' => false,
];
