<?php

return [

    /*
    | International self-serve checkout: paddle
    | Tunisia: no online checkout (admin / local payment)
    */
    'international_gateway' => env('BILLING_INTERNATIONAL_GATEWAY', 'paddle'),

];
