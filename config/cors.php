<?php

$origins = env('CORS_ALLOWED_ORIGINS');

$allowedOrigins = $origins
    ? array_values(array_filter(array_map('trim', explode(',', $origins))))
    : [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ];

$frontend = rtrim((string) env('FRONTEND_APP_URL', ''), '/');
if ($frontend !== '') {
    $allowedOrigins[] = $frontend;
}

$allowedOrigins = array_values(array_unique(array_filter($allowedOrigins)));

return [

    'paths' => ['api/*', 'sanctum/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
