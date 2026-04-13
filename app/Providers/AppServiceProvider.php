<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (?User $user) {
            return (bool) ($user?->is_admin);
        });

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(config('rate-limit.login_per_minute', 5))
                ->by($request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(config('rate-limit.register_per_minute', 5))
                ->by($request->ip());
        });

        RateLimiter::for('oauth-google-url', function (Request $request) {
            return Limit::perMinute(config('rate-limit.oauth_google_url_per_minute', 20))
                ->by($request->ip());
        });

        RateLimiter::for('sanctum-csrf-cookie', function (Request $request) {
            return Limit::perMinute(config('rate-limit.sanctum_csrf_cookie_per_minute', 120))
                ->by($request->ip());
        });

        RateLimiter::for('csrf-token', function (Request $request) {
            return Limit::perMinute(config('rate-limit.csrf_token_per_minute', 120))
                ->by($request->ip());
        });

        RateLimiter::for('oauth-callback', function (Request $request) {
            return Limit::perMinute(config('rate-limit.oauth_callback_per_minute', 60))
                ->by($request->ip());
        });

        RateLimiter::for('public-read', function (Request $request) {
            return Limit::perMinute(config('rate-limit.public_read_per_minute', 120))
                ->by($request->ip());
        });

        RateLimiter::for('subscribers', function (Request $request) {
            return Limit::perMinute(config('rate-limit.subscribers_per_minute', 10))
                ->by($request->ip());
        });

        RateLimiter::for('email-verify', function (Request $request) {
            return Limit::perMinute(config('rate-limit.email_verify_per_minute', 30))
                ->by($request->ip());
        });

        RateLimiter::for('collaborate-accept', function (Request $request) {
            return Limit::perMinute(config('rate-limit.collaborate_accept_per_minute', 30))
                ->by($request->ip());
        });

        RateLimiter::for('api-authenticated', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(config('rate-limit.authenticated_per_minute', 120))
                ->by($user ? 'user:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('pdf-generate', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return Limit::perMinute(config('rate-limit.pdf_generate_per_minute', 15))->by($key);
        });

        RateLimiter::for('verification-resend', function (Request $request) {
            return Limit::perMinute(config('rate-limit.verification_resend_per_minute', 6))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
