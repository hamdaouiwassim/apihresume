<?php

namespace App\Http\Middleware;

use App\Support\LocalizedUrls;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages with an English ("/...") and a French ("/fr/...") URL: the URL decides the language,
 * so crawlers (no session) always get the language of the URL.
 * Visitors who chose French and open an English URL are sent to its /fr version.
 */
class PageLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        $session = $request->hasSession() ? $request->session() : null;

        if ($locale === 'fr') {
            $session?->put('locale', 'fr');
        } elseif ($session?->get('locale') === 'fr' && $request->isMethod('GET')) {
            $query = $request->getQueryString();

            return redirect()->to(LocalizedUrls::pathFor($request->getPathInfo(), 'fr').($query ? '?'.$query : ''));
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
