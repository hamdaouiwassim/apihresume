<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default region when country cannot be detected (local/private IPs).
    | tunisia | international
    |--------------------------------------------------------------------------
    */
    'default_region' => env('PRICING_DEFAULT_REGION', 'international'),

    'tunisia_country_code' => 'TN',

    'regions' => [
        'tunisia' => [
            'currency' => 'TND',
            'free' => [
                'amount' => 0,
                'formatted' => '0 TND',
            ],
            'pro' => [
                'amount' => 10,
                'formatted' => '10 TND',
            ],
        ],
        'international' => [
            'currency' => 'USD',
            'free' => [
                'amount' => 0,
                'formatted' => '$0',
            ],
            'pro' => [
                'amount' => 5,
                'formatted' => '$5',
            ],
        ],
    ],

];
