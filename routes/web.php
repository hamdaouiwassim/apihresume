<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BlogSocialPreviewController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Web\Admin\AdminPageController;
use App\Http\Controllers\Web\AppPageController;
use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\BlogPageController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PlaceholderImageController;
use App\Http\Controllers\Web\PublicResumeController;
use App\Http\Controllers\Web\TemplatePageController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Pulse\Facades\Pulse;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/** Open Graph HTML for social crawlers (kept for old links; /blog/{slug} now renders real HTML). */
Route::get('/blog/{slug}/social', [BlogSocialPreviewController::class, 'show'])
    ->name('blog.social-preview');

Route::get('/resume-template-preview', [PDFController::class, 'preview']);

// Local placeholder images (replaces via.placeholder.com).
Route::get('/placeholder/{width}x{height}', PlaceholderImageController::class)
    ->whereNumber(['width', 'height'])
    ->name('placeholder');

Route::get('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', ['en', 'fr'])
    ->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Public pages (server-rendered for SEO)
|--------------------------------------------------------------------------
*/
// Marketing pages exist in English ("/pricing") and French ("/fr/pricing"), linked with hreflang.
// Keep this list in sync with App\Support\LocalizedUrls::ROUTES.
$localizedPages = function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/refund', [PageController::class, 'refund'])->name('refund');
    Route::get('/cover-letter-builder', [PageController::class, 'coverLetterBuilder'])->name('landing.cover-letter');
    Route::get('/work-certificate', [PageController::class, 'workCertificate'])->name('landing.work-certificate');
    Route::get('/templates/public', [TemplatePageController::class, 'gallery'])->name('templates.public');
    // Old id URL -> 301 to the template page.
    Route::get('/templates/public/preview/{id}', [TemplatePageController::class, 'legacyPreview'])->name('templates.public.preview');
    Route::get('/templates/{slug}', [TemplatePageController::class, 'show'])
        ->where('slug', '(?!(?:public|preview)$)[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('templates.show');
};
Route::middleware('page.locale:en')->group($localizedPages);
Route::prefix('fr')->name('fr.')->middleware('page.locale:fr')->group($localizedPages);

Route::get('/blog', [BlogPageController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogPageController::class, 'show'])->name('blog.show');


Route::get('/share/{token}', [PublicResumeController::class, 'share'])->name('share.view');
Route::get('/website/{token}', [PublicResumeController::class, 'website'])->name('website.token');
Route::get('/u/{slug}', [PublicResumeController::class, 'website'])->name('website.slug');
Route::get('/collaborate/accept/{token}', [PublicResumeController::class, 'acceptCollaboration'])->name('collaborate.accept');

// Guest resume builder entry (anonymous draft, claimed after sign-up).
Route::get('/resume/start', [AppPageController::class, 'createResume'])->name('resume.start');

/*
|--------------------------------------------------------------------------
| Authentication pages
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthPageController::class, 'login'])->name('login');
    Route::get('/register', [AuthPageController::class, 'register'])->name('register');
});
Route::get('/auth/social-callback', [AuthPageController::class, 'socialCallback'])->name('auth.social-callback');
Route::post('/logout', [AuthPageController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Signed-in user area (former PrivateRoute)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.banned', 'track.activity'])->group(function () {
    Route::get('/resumes', [AppPageController::class, 'resumes'])->name('resumes.index');
    Route::get('/shared-with-me', [AppPageController::class, 'sharedWithMe'])->name('shared-with-me');
    Route::get('/templates', [TemplatePageController::class, 'index'])->name('templates.index');
    Route::get('/templates/preview/{id}', [TemplatePageController::class, 'preview'])->name('templates.preview');
    Route::get('/resume/create', [AppPageController::class, 'createResume'])->name('resume.create');
    Route::get('/resume/claim-draft', [AppPageController::class, 'claimDraft'])->name('resume.claim-draft');
    Route::get('/resume/edit/{id}', [AppPageController::class, 'editResume'])->name('resume.edit');
    Route::get('/profile', [AppPageController::class, 'profile'])->name('profile');
    Route::get('/review', [AppPageController::class, 'review'])->name('review');
    Route::get('/pricing/success', [AppPageController::class, 'pricingSuccess'])->name('pricing.success');

    Route::get('/cover-letters', [AppPageController::class, 'coverLetters'])->name('cover-letters.index');
    Route::get('/cover-letter/create', [AppPageController::class, 'editCoverLetter'])->name('cover-letter.create');
    Route::get('/cover-letter/edit/{id}', [AppPageController::class, 'editCoverLetter'])->name('cover-letter.edit');

    Route::get('/work-certificates', [AppPageController::class, 'workCertificates'])->name('work-certificates.index');
    Route::get('/work-certificate/create', [AppPageController::class, 'editWorkCertificate'])->name('work-certificate.create');
    Route::get('/work-certificate/edit/{id}', [AppPageController::class, 'editWorkCertificate'])->name('work-certificate.edit');
});

// Recruiter role is disabled for now: send old links to the regular dashboard.
Route::redirect('/register/recruiter', '/register');
Route::redirect('/track-request', '/');
Route::redirect('/403', '/');
Route::get('/recruiter/{any?}', fn () => redirect()->route('resumes.index'))->where('any', '.*');

/*
|--------------------------------------------------------------------------
| Admin area (former AdminRoute)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin', 'track.activity'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminPageController::class, 'users'])->name('users');
    Route::get('/users/{id}', [AdminPageController::class, 'userDetails'])->whereNumber('id')->name('users.show');
    Route::get('/users/{id}/cvs', [AdminPageController::class, 'userCvs'])->whereNumber('id')->name('users.cvs');
    Route::get('/cvs', [AdminPageController::class, 'generatedCvs'])->name('cvs');
    Route::get('/cover-letters', [AdminPageController::class, 'coverLetters'])->name('cover-letters');
    Route::get('/work-certificates', [AdminPageController::class, 'workCertificates'])->name('work-certificates');
    Route::get('/templates', [AdminPageController::class, 'templates'])->name('templates');
    Route::get('/cover-letter-templates', [AdminPageController::class, 'coverLetterTemplates'])->name('cover-letter-templates');
    Route::get('/ai-usage', [AdminPageController::class, 'aiUsage'])->name('ai-usage');
    Route::get('/emails', [AdminPageController::class, 'emails'])->name('emails');
    Route::get('/blog', [AdminPageController::class, 'blog'])->name('blog');
    Route::get('/blog/new', [AdminPageController::class, 'blogEditor'])->name('blog.new');
    Route::get('/blog/edit/{id}', [AdminPageController::class, 'blogEditor'])->name('blog.edit');
    Route::get('/fonts', [AdminPageController::class, 'fonts'])->name('fonts');
    Route::get('/reviews', [AdminPageController::class, 'reviews'])->name('reviews');
    Route::get('/profile', [AdminPageController::class, 'profile'])->name('profile');
});

/*
|--------------------------------------------------------------------------
| Local debugging helpers
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'testing'])) {
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
}

/*
|--------------------------------------------------------------------------
| Laravel Pulse (separate admin login)
|--------------------------------------------------------------------------
*/
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

/*
| Unknown pages go home, like the former React catch-all (<Navigate to="/" />).
*/
Route::fallback(function () {
    if (request()->is('api/*')) {
        abort(404);
    }

    // Missing static files get a real 404, never a redirect to an HTML page. Browsers still running a
    // cached copy of the old React SPA request its chunks (/assets/login-xxxx.js, *.jsx): tell them to
    // drop their HTTP cache so the next load gets the Blade site.
    if (request()->is('assets/*') || preg_match('/\.(?:m?jsx?|css|map|json|webmanifest)$/i', request()->path())) {
        return response('Not Found', 404, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
            'Clear-Site-Data' => '"cache"',
        ]);
    }

    return redirect('/');
});
