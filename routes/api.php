<?php

use App\Http\Controllers\Admin\AiUsageController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\CoverLetterController as AdminCoverLetterController;
use App\Http\Controllers\Admin\CoverLetterTemplateController as AdminCoverLetterTemplateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OutboundEmailController;
use App\Http\Controllers\Admin\ResumeController as AdminResumeController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WorkCertificateController as AdminWorkCertificateController;
use App\Http\Controllers\AiResumeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BasicInfoController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CoverLetterController;
use App\Http\Controllers\CoverLetterTemplateController;
use App\Http\Controllers\CsrfTokenController;
use App\Http\Controllers\EducationContoller;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ExperienceContoller;
use App\Http\Controllers\GitHubImportOAuthController;
use App\Http\Controllers\GitHubProjectImportController;
use App\Http\Controllers\HobbieController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LinkedInResumeImportController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\PricingRegionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Recruiter\ResumeController as RecruiterResumeController;
use App\Http\Controllers\Recruiter\TemplateProposalController as RecruiterTemplateProposalController;
use App\Http\Controllers\ResumeCollaboratorController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShareableLinkController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/csrf-token', [CsrfTokenController::class, 'show'])
    ->middleware('throttle:csrf-token');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])
    ->middleware('throttle:oauth-callback');
Route::get('/auth/linkedin/callback', [AuthController::class, 'handleLinkedInCallback'])
    ->middleware('throttle:oauth-callback');
Route::get('/auth/github/import/callback', [GitHubImportOAuthController::class, 'callback'])
    ->middleware('throttle:oauth-callback');
Route::post('/auth/social/exchange', [AuthController::class, 'exchangeSocialAuthCode'])
    ->middleware('throttle:auth-login');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
Route::get('/auth/google/url', [AuthController::class, 'getGoogleAuthUrl'])
    ->middleware('throttle:oauth-google-url');
Route::get('/auth/linkedin/url', [AuthController::class, 'getLinkedInAuthUrl'])
    ->middleware('throttle:oauth-linkedin-url');
Route::get('/share/{token}', [ShareableLinkController::class, 'view'])
    ->middleware('throttle:public-read');
Route::get('/website/{slugOrToken}', [ShareableLinkController::class, 'website'])
    ->middleware('throttle:public-read');
Route::get('/public/profile/{slug}/pdf', [PDFController::class, 'publicProfilePdf'])
    ->middleware('throttle:pdf-generate');
Route::post('/collaborate/accept/{token}', [ResumeCollaboratorController::class, 'accept'])
    ->middleware('throttle:collaborate-accept');
Route::get('/reviews', [ReviewController::class, 'index'])
    ->middleware('throttle:public-read');
Route::get('/blog', [BlogController::class, 'index'])
    ->middleware('throttle:public-read');
Route::get('/blog/{slug}', [BlogController::class, 'show'])
    ->middleware('throttle:public-read');
Route::get('/stats', [StatsController::class, 'index'])
    ->middleware('throttle:public-read');
Route::get('/pricing-region', [PricingRegionController::class, 'show'])
    ->middleware('throttle:public-read');
Route::post('/billing/paddle/webhook', [BillingController::class, 'paddleWebhook'])
    ->middleware('throttle:public-read');
Route::post('/billing/stripe/webhook', [BillingController::class, 'stripeWebhook'])
    ->middleware('throttle:public-read');
Route::get('/templates', [TemplateController::class, 'index'])
    ->middleware('throttle:public-read');
Route::post('/subscribers/subscribe', [SubscriberController::class, 'subscribe'])
    ->middleware('throttle:subscribers');
Route::post('/subscribers/unsubscribe', [SubscriberController::class, 'unsubscribe'])
    ->middleware('throttle:subscribers');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:email-verify'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum', 'not.banned', 'track.activity', 'throttle:api-authenticated'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/auth/github/import/url', [GitHubImportOAuthController::class, 'url'])
        ->middleware('throttle:oauth-github-import-url');
    Route::post('/auth/github/import/disconnect', [GitHubImportOAuthController::class, 'disconnect']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:verification-resend');

    Route::get('/billing/config', [BillingController::class, 'config']);
    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::post('/billing/checkout/confirm', [BillingController::class, 'confirmSession']);

    Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
    Route::apiResource('experiences', ExperienceContoller::class);
    Route::apiResource('educations', EducationContoller::class);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('skills', SkillController::class);
    Route::apiResource('hobbies', HobbieController::class);
    Route::apiResource('certificates', CertificateController::class);
    Route::apiResource('languages', LanguageController::class);
    Route::apiResource('templates', TemplateController::class)->except(['index']); // Exclude index from auth middleware
    Route::apiResource('resumes', ResumeController::class);
    Route::post('/resumes/{resume}/import/linkedin', LinkedInResumeImportController::class)
        ->middleware('throttle:linkedin-import');
    Route::post('/resumes/{resume}/github-repo-preview', GitHubProjectImportController::class)
        ->middleware('throttle:github-import');
    Route::patch('/resumes/{resume}/public-profile', [ResumeController::class, 'updatePublicProfile']);
    Route::apiResource('cover-letters', CoverLetterController::class)->except(['store']);
    Route::get('/cover-letter-templates', [CoverLetterTemplateController::class, 'index']);
    Route::get('cover-letters/{coverLetter}/pdf', [CoverLetterController::class, 'generatePDF']);

    Route::get('/work-certificates', [WorkCertificateController::class, 'index']);
    Route::post('/work-certificates', [WorkCertificateController::class, 'store']);
    Route::get('/work-certificates/{work_certificate}', [WorkCertificateController::class, 'show']);
    Route::put('/work-certificates/{work_certificate}', [WorkCertificateController::class, 'update']);
    Route::delete('/work-certificates/{work_certificate}', [WorkCertificateController::class, 'destroy']);
    Route::get('/work-certificates/{work_certificate}/pdf', [WorkCertificateController::class, 'generatePdf'])
        ->middleware('throttle:pdf-generate');
    Route::apiResource('basic-info', BasicInfoController::class);
    Route::post('resumes/{resumeId}/basic-info/avatar', [BasicInfoController::class, 'uploadAvatar']);
    Route::get('/my-resumes', [UserController::class, 'myResumes']);
    Route::get('/reviews/my-review', [ReviewController::class, 'myReview']);

    Route::middleware(['verified'])->group(function () {
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{review}', [ReviewController::class, 'update']);
        Route::post('/cover-letters', [CoverLetterController::class, 'store']);
    });

    // Shareable links routes (protected)
    Route::post('/resumes/{resumeId}/shareable-link/generate', [ShareableLinkController::class, 'generate']);
    Route::get('/resumes/{resumeId}/shareable-link', [ShareableLinkController::class, 'getCurrentLink']);
    Route::post('/resumes/{resumeId}/shareable-link/deactivate', [ShareableLinkController::class, 'deactivate']);

    // Resume collaboration routes (protected)
    Route::post('/resumes/{resumeId}/collaborators/invite', [ResumeCollaboratorController::class, 'invite']);
    Route::get('/resumes/{resumeId}/collaborators', [ResumeCollaboratorController::class, 'index']);
    Route::delete('/resumes/{resumeId}/collaborators/{collaboratorId}', [ResumeCollaboratorController::class, 'remove']);
    Route::get('/collaborations/pending', [ResumeCollaboratorController::class, 'getPendingInvitations']);
    Route::post('/collaborations/{invitationId}/accept', [ResumeCollaboratorController::class, 'acceptInvitation']);
    Route::post('/collaborations/{invitationId}/refuse', [ResumeCollaboratorController::class, 'refuseInvitation']);

    // Temporarily remove verified middleware for debugging
    Route::post('/generate-pdf', [PDFController::class, 'generate'])
        ->middleware('throttle:pdf-generate');
    Route::post('/ai/tailor-resume', [AiResumeController::class, 'tailor'])
        ->middleware('throttle:ai-tailor');
    Route::post('/ai/enhance-text', [AiResumeController::class, 'enhanceText'])
        ->middleware('throttle:ai-tailor');
    Route::post('/ai/ats-score', [AiResumeController::class, 'atsScore'])
        ->middleware('throttle:ai-tailor');

    // Active PDF fonts for font dropdown
    Route::get('/pdf-fonts/active', [\App\Http\Controllers\Admin\PdfFontController::class, 'activeFonts']);

    // Serve custom font file for preview (blob URL fetch)
    Route::get('/fonts/{id}/file', [\App\Http\Controllers\Admin\PdfFontController::class, 'serveFontFile']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'verified', 'track.activity', 'admin', 'throttle:api-authenticated'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('users/{user}/restore', [AdminUserController::class, 'restore']);
    Route::delete('users/{user}/force', [AdminUserController::class, 'forceDestroy']);
    Route::post('users/{user}/ban', [\App\Http\Controllers\Admin\UserBanController::class, 'ban']);
    Route::post('users/{user}/unban', [\App\Http\Controllers\Admin\UserBanController::class, 'unban']);
    Route::apiResource('users', AdminUserController::class);
    Route::apiResource('templates', AdminTemplateController::class);
    Route::apiResource('cover-letter-templates', AdminCoverLetterTemplateController::class);
    Route::apiResource('blog', AdminBlogController::class);
    Route::post('users/{user}/message', \App\Http\Controllers\Admin\UserMessageController::class)->name('admin.users.message');
    Route::post('users/{user}/emails/resume-reminder', [OutboundEmailController::class, 'sendResumeReminder']);
    Route::post('users/{user}/emails/verification-reminder', [OutboundEmailController::class, 'sendVerificationReminder']);
    Route::post('users/{user}/emails/new-features', [OutboundEmailController::class, 'sendNewFeaturesToUser']);
    Route::get('/outbound-emails/summary', [OutboundEmailController::class, 'summary']);
    Route::get('/outbound-emails', [OutboundEmailController::class, 'index']);
    Route::post('/outbound-emails/bulk', [OutboundEmailController::class, 'bulk']);
    Route::post('/outbound-emails/new-features', [OutboundEmailController::class, 'sendNewFeaturesBulk']);
    Route::get('/resumes', [AdminResumeController::class, 'index']);
    Route::get('/resumes/{id}', [AdminResumeController::class, 'show']);
    Route::delete('/resumes/{id}', [AdminResumeController::class, 'destroy']);
    Route::post('/resumes/{id}/restore', [AdminResumeController::class, 'restore']);
    Route::delete('/resumes/{id}/force', [AdminResumeController::class, 'forceDestroy']);
    Route::get('/cover-letters', [AdminCoverLetterController::class, 'index']);
    Route::get('/cover-letters/{id}', [AdminCoverLetterController::class, 'show']);
    Route::delete('/cover-letters/{id}', [AdminCoverLetterController::class, 'destroy']);

    Route::get('/work-certificates', [AdminWorkCertificateController::class, 'index']);
    Route::get('/work-certificates/{id}', [AdminWorkCertificateController::class, 'show']);
    Route::delete('/work-certificates/{id}', [AdminWorkCertificateController::class, 'destroy']);

    // PDF Font management
    Route::get('/fonts', [\App\Http\Controllers\Admin\PdfFontController::class, 'index']);
    Route::post('/fonts', [\App\Http\Controllers\Admin\PdfFontController::class, 'store']);
    Route::post('/fonts/{pdfFont}/toggle', [\App\Http\Controllers\Admin\PdfFontController::class, 'toggleActive']);
    Route::delete('/fonts/{pdfFont}', [\App\Http\Controllers\Admin\PdfFontController::class, 'destroy']);

    // Review management
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index']);
    Route::patch('/reviews/{review}/toggle-public', [\App\Http\Controllers\Admin\ReviewController::class, 'togglePublic']);
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy']);

    Route::get('/ai-usage/logs', [AiUsageController::class, 'logs']);
    Route::get('/ai-usage/summary', [AiUsageController::class, 'summary']);
    Route::get('/ai-usage/user-limits', [AiUsageController::class, 'userLimits']);
    Route::patch('/ai-usage/users/{user}/token-limit', [AiUsageController::class, 'updateUserTokenLimit']);
});

// Recruiter routes
Route::middleware(['auth:sanctum', 'not.banned', 'verified', 'track.activity', 'recruiter', 'throttle:api-authenticated'])->prefix('recruiter')->group(function () {
    Route::get('/resumes', [RecruiterResumeController::class, 'index']);
    Route::get('/resumes/{resume}', [RecruiterResumeController::class, 'show']);
    Route::get('/templates/proposals', [RecruiterTemplateProposalController::class, 'index']);
    Route::post('/templates/proposals', [RecruiterTemplateProposalController::class, 'store']);
});
