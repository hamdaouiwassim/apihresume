<?php

namespace App\Http\Middleware;

use App\Support\Translations;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the UI language stored in the session (set via GET /locale/{locale}).
 * Replaces the former React LanguageContext.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        app()->setLocale(Translations::normalize($locale ?? config('app.locale', 'en')));

        return $next($request);
    }
}
