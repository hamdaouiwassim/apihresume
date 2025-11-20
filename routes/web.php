<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-env', function () {
    $nodeBinaryPath = getenv('NODE_BINARY_PATH');
    $path = getenv('PATH');

    \Log::info('Debug env route', [
        'NODE_BINARY_PATH' => $nodeBinaryPath,
        'PATH' => $path,
    ]);

    return [
        'NODE_BINARY_PATH' => $nodeBinaryPath,
        'PATH' => $path,
    ];
});
