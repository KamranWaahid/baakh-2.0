<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        /*
         * Stripped runtime paths (see resources/js/admin/api/axios.js). CORS must apply
         * when the SPA calls these from another origin (e.g. Vite dev or split hosts).
         */
        'admin/*',
        'auth/*',
        'v1/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:5174,http://127.0.0.1:8000,https://baakh.com,https://baakh-2-0-one.vercel.app')),

    /*
    | Vercel preview / production (*.vercel.app) when SPA calls API from another origin.
    | Patterns are passed to preg_match — include delimiters (e.g. #pattern#).
    */
    'allowed_origins_patterns' => array_values(array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGIN_PATTERNS',
        '#^https://.*\\.vercel\\.app$#'
    ))))),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
