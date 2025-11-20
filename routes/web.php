<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-env', function () {
    return [
        'NODE_BINARY_PATH' => getenv('NODE_BINARY_PATH'),
        'PATH' => getenv('PATH'),
    ];
});
