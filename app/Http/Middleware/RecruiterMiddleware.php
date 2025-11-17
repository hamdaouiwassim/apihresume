<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecruiterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user = $request->user()->load('recruiter');

        if (!$user->is_recruiter || !$user->recruiter) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Recruiter access required.'
            ], 403);
        }

        if ($user->recruiter->status !== 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Your recruiter account is not yet approved or has been revoked.'
            ], 403);
        }

        return $next($request);
    }
}

