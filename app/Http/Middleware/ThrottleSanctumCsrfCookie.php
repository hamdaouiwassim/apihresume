<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a named rate limiter only to Sanctum's web CSRF priming route.
 */
class ThrottleSanctumCsrfCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('sanctum/csrf-cookie')) {
            return $next($request);
        }

        return app(ThrottleRequests::class)->handle($request, $next, 'sanctum-csrf-cookie');
    }
}
