<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        if ($request->is('api/*')) {
            return "default-src 'self'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; connect-src 'self' https: http:;";
        }

        // Blade pages: Alpine.js evaluates x-* expressions ('unsafe-eval'), Google Analytics,
        // blob: previews (avatars, fonts, PDFs), pdf.js worker and YouTube walkthrough embeds.
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.googleapis.com";
        $connectSrc = "'self' https: http:";

        if (app()->environment('local')) {
            // Vite dev server (npm run dev) + HMR websocket.
            $dev = 'http://localhost:5173 http://127.0.0.1:5173';
            $scriptSrc .= ' '.$dev;
            $styleSrc .= ' '.$dev;
            $connectSrc .= ' ws: '.$dev;
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self' https:",
            "img-src 'self' data: blob: https:",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' https://fonts.gstatic.com data: blob:",
            "connect-src {$connectSrc}",
            "worker-src 'self' blob:",
            "frame-src 'self' blob: https://www.youtube.com https://www.youtube-nocookie.com",
            "object-src 'self' blob:",
        ]).';';
    }
}
