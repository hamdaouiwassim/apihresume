<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP rate limits (per minute unless noted)
    |--------------------------------------------------------------------------
    |
    | Used by named limiters in AppServiceProvider. Override via .env where noted.
    |
    */

    'login_per_minute' => (int) env('THROTTLE_LOGIN_PER_MINUTE', 5),

    'register_per_minute' => (int) env('THROTTLE_REGISTER_PER_MINUTE', 5),

    /** OAuth: start Google sign-in */
    'oauth_google_url_per_minute' => (int) env('THROTTLE_OAUTH_GOOGLE_URL_PER_MINUTE', 20),

    /** GET /sanctum/csrf-cookie (Sanctum web route) */
    'sanctum_csrf_cookie_per_minute' => (int) env('THROTTLE_SANCTUM_CSRF_COOKIE_PER_MINUTE', 120),

    /** GET /api/csrf-token (after /sanctum/csrf-cookie) */
    'csrf_token_per_minute' => (int) env('THROTTLE_CSRF_TOKEN_PER_MINUTE', 120),

    /** GET /api/auth/google/callback */
    'oauth_callback_per_minute' => (int) env('THROTTLE_OAUTH_CALLBACK_PER_MINUTE', 60),

    /** Public read endpoints (blog, templates, etc.) */
    'public_read_per_minute' => (int) env('THROTTLE_PUBLIC_READ_PER_MINUTE', 120),

    /** Newsletter / similar */
    'subscribers_per_minute' => (int) env('THROTTLE_SUBSCRIBERS_PER_MINUTE', 10),

    /** Signed email verification links */
    'email_verify_per_minute' => (int) env('THROTTLE_EMAIL_VERIFY_PER_MINUTE', 30),

    /** POST collaborate accept (guest) */
    'collaborate_accept_per_minute' => (int) env('THROTTLE_COLLABORATE_ACCEPT_PER_MINUTE', 30),

    /** Authenticated JSON API (per user when logged in) */
    'authenticated_per_minute' => (int) env('THROTTLE_AUTHENTICATED_PER_MINUTE', 120),

    /** Expensive PDF generation */
    'pdf_generate_per_minute' => (int) env('THROTTLE_PDF_GENERATE_PER_MINUTE', 15),

    /** Resend verification email */
    'verification_resend_per_minute' => (int) env('THROTTLE_VERIFICATION_RESEND_PER_MINUTE', 6),

];
