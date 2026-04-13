<?php

namespace App\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumEnsureFrontendRequestsAreStateful;

/**
 * Extends Sanctum so session.same_site / secure come from config (e.g. none + Secure for cross-origin SPAs).
 * The stock middleware always forces same_site=lax, which breaks credentialed XHR from another registrable domain.
 */
class EnsureSpaRequestsAreStateful extends SanctumEnsureFrontendRequestsAreStateful
{
    protected function configureSecureCookieSessions(): void
    {
        config([
            'session.http_only' => true,
            'session.same_site' => config('session.same_site', 'lax'),
        ]);
    }
}
