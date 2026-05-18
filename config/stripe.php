<?php

$frontend = rtrim((string) env('FRONTEND_APP_URL', 'http://localhost:5173'), '/');

return [

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    | Recurring Price IDs from Stripe Dashboard (mode: subscription).
    | tunisia → TND 10/mo | international → USD 5/mo
    */
    'price_ids' => [
        'tunisia' => env('STRIPE_PRICE_ID_TUNISIA'),
        'international' => env('STRIPE_PRICE_ID_INTERNATIONAL'),
    ],

    'checkout' => [
        'success_url' => env('STRIPE_CHECKOUT_SUCCESS_URL', $frontend.'/pricing/success'),
        'cancel_url' => env('STRIPE_CHECKOUT_CANCEL_URL', $frontend.'/pricing'),
    ],

];
