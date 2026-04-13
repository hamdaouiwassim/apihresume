<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exposes the session CSRF token in JSON for SPAs on another registrable domain,
 * which cannot read the API host's XSRF-TOKEN cookie via document.cookie.
 * Call after GET /sanctum/csrf-cookie with credentials; token matches the session cookie.
 */
class CsrfTokenController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }
}
