<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Pulse\Facades\Pulse;

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

Route::get('/resume-template-preview', [PDFController::class, 'preview']);

Route::get('/test-email', function () {
    try {
        Mail::raw('This is a test email from HResume to verify the email sending configuration.', function ($message) {
            $message->to('hamdaouiwassim@gmail.com')
                ->subject('HResume Test Email');
        });

        return [
            'status' => true,
            'message' => 'Test email dispatched to hamdaouiwassim@gmail.com',
        ];
    } catch (\Throwable $exception) {
        \Log::error('Test email failed', ['error' => $exception->getMessage()]);

        return response()->json([
            'status' => false,
            'message' => 'Failed to send test email',
            'error' => $exception->getMessage(),
        ], 500);
    }
})->middleware('throttle:3,1');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('admin.logout');

if (class_exists(\Laravel\Pulse\Facades\Pulse::class) && method_exists(\Laravel\Pulse\Facades\Pulse::class, 'route')) {
    Route::middleware(['auth', 'can:viewPulse'])->group(function () {
        Pulse::route('/pulse');
    });
}
