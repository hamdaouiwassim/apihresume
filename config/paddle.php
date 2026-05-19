<?php

$frontend = rtrim((string) env('FRONTEND_APP_URL', 'http://localhost:5173'), '/');

return [

    'environment' => env('PADDLE_ENVIRONMENT', 'sandbox'),

    'api_key' => env('PADDLE_API_KEY'),

    /** Client-side token for Paddle.js (safe to expose in SPA) */
    'client_token' => env('PADDLE_CLIENT_TOKEN'),

    'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),

    /** Recurring price for HResume Pro (international, USD) — prefixed with pri_ */
    'price_id' => env('PADDLE_PRICE_ID_INTERNATIONAL'),

    'api_base' => env('PADDLE_ENVIRONMENT', 'sandbox') === 'production'
        ? 'https://api.paddle.com'
        : 'https://sandbox-api.paddle.com',

    'checkout' => [
        'success_url' => env('PADDLE_CHECKOUT_SUCCESS_URL', $frontend.'/pricing/success'),
    ],

];
