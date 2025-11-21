<?php

use App\Http\Controllers\BasicInfoController;
use App\Http\Controllers\EducationContoller;
use App\Http\Controllers\ExperienceContoller;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\HobbieController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\ShareableLinkController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Recruiter\ResumeController as RecruiterResumeController;
use App\Http\Controllers\Recruiter\TemplateProposalController as RecruiterTemplateProposalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/auth/google/url', [AuthController::class, 'getGoogleAuthUrl']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/share/{token}', [ShareableLinkController::class, 'view']);
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum', 'track.activity', 'throttle:120,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1');

    Route::middleware(['verified'])->group(function () {
        Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
        Route::apiResource("experiences" , ExperienceContoller::class);
        Route::apiResource("educations" , EducationContoller::class);
        Route::apiResource('skills', SkillController::class);
        Route::apiResource('hobbies', HobbieController::class);
        Route::apiResource('certificates', CertificateController::class);
        Route::apiResource('templates', TemplateController::class);
        Route::apiResource('resumes', ResumeController::class);
        Route::apiResource('basic-info', BasicInfoController::class);
        Route::get('/my-resumes', [UserController::class, 'myResumes']);
        Route::post('/generate-pdf', [PDFController::class, 'generate']);
        
        // Shareable links routes (protected)
        Route::post('/resumes/{resumeId}/shareable-link/generate', [ShareableLinkController::class, 'generate']);
        Route::get('/resumes/{resumeId}/shareable-link', [ShareableLinkController::class, 'getCurrentLink']);
        Route::post('/resumes/{resumeId}/shareable-link/deactivate', [ShareableLinkController::class, 'deactivate']);
    });
});

// Admin routes
Route::middleware(['auth:sanctum', 'verified', 'track.activity', 'admin', 'throttle:120,1'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('users', AdminUserController::class);
    Route::apiResource('templates', AdminTemplateController::class);
});

// Recruiter routes
Route::middleware(['auth:sanctum', 'verified', 'track.activity', 'recruiter', 'throttle:120,1'])->prefix('recruiter')->group(function () {
    Route::get('/resumes', [RecruiterResumeController::class, 'index']);
    Route::get('/resumes/{resume}', [RecruiterResumeController::class, 'show']);
    Route::get('/templates/proposals', [RecruiterTemplateProposalController::class, 'index']);
    Route::post('/templates/proposals', [RecruiterTemplateProposalController::class, 'store']);
});


