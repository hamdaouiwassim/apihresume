<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * API requests get JSON errors; Blade pages are redirected like the former React AdminRoute.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return redirect()->guest(route('login'));
            }

            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $request->user()->is_admin) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return redirect()->route('resumes.index');
            }

            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
