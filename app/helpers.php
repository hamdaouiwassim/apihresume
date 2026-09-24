<?php

use App\Support\Translations;

if (! function_exists('t')) {
    /**
     * Translate a UI key from resources/translations/{locale}.json.
     *
     * Usage: t('nav.myResumes', [], 'My Resumes')  or  t('resumes.limit', ['count' => 1])
     * Placeholders supported: {{name}}, {name}, :name
     */
    function t(string $key, array $replace = [], mixed $default = null): mixed
    {
        return Translations::get($key, $replace, $default);
    }
}

if (! function_exists('localized_route')) {
    /**
     * route() in the current page language: "/fr/..." for pages that have a French URL when the page is
     * French (App\Support\LocalizedUrls::ROUTES), the normal URL otherwise.
     */
    function localized_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        if (app()->getLocale() === 'fr' && in_array($name, \App\Support\LocalizedUrls::ROUTES, true)) {
            $name = 'fr.'.$name;
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('localized_url')) {
    /** url() in the current page language ("/pricing" -> "/fr/pricing" on French pages). */
    function localized_url(string $path): string
    {
        static $localized = [];

        if (app()->getLocale() === 'fr') {
            $localized[$path] ??= \App\Support\LocalizedUrls::isLocalizedPath($path);
            if ($localized[$path]) {
                $path = \App\Support\LocalizedUrls::pathFor($path, 'fr');
            }
        }

        return url($path);
    }
}

if (! function_exists('nav_active')) {
    /**
     * True when the current path matches any of the given patterns (Request::is syntax).
     */
    function nav_active(string ...$patterns): bool
    {
        return request()->is(...$patterns);
    }
}

if (! function_exists('pricing_region')) {
    /**
     * Visitor pricing region (former usePricingRegion hook). Cached in the session for 24h,
     * like the SPA cached it in sessionStorage, because resolving may call an IP geo service.
     *
     * @return array{region:string,isTunisia:bool,countryCode:?string,currency:string,freePrice:string,proPrice:string,proAmount:int|float,raw:array}
     */
    function pricing_region(): array
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }

        $request = request();
        $data = null;

        if ($request->hasSession()) {
            $cached = $request->session()->get('pricing_region');
            if (is_array($cached) && ($cached['cached_at'] ?? 0) > now()->subDay()->getTimestamp()) {
                $data = $cached['data'] ?? null;
            }
        }

        if (! is_array($data)) {
            try {
                $data = app(\App\Services\PricingRegionService::class)->resolve($request);
            } catch (\Throwable) {
                $data = [
                    'region' => 'international',
                    'currency' => 'USD',
                    'country_code' => null,
                    'free' => ['amount' => 0, 'formatted' => '$0'],
                    'pro' => ['amount' => 5, 'formatted' => '$5'],
                ];
            }
            if ($request->hasSession()) {
                $request->session()->put('pricing_region', ['data' => $data, 'cached_at' => now()->getTimestamp()]);
            }
        }

        $isTunisia = ($data['region'] ?? '') === 'tunisia';

        return $resolved = [
            'region' => $data['region'] ?? 'international',
            'isTunisia' => $isTunisia,
            'countryCode' => $data['country_code'] ?? null,
            'currency' => $data['currency'] ?? ($isTunisia ? 'TND' : 'USD'),
            'freePrice' => $data['free']['formatted'] ?? ($isTunisia ? '0 TND' : '$0'),
            'proPrice' => $data['pro']['formatted'] ?? ($isTunisia ? '10 TND' : '$5'),
            'proAmount' => $data['pro']['amount'] ?? ($isTunisia ? 10 : 5),
            'raw' => $data,
        ];
    }
}

if (! function_exists('ui_date')) {
    /**
     * Date formatting matching the React pages' toLocaleDateString calls.
     *   short: "Sep 24, 2026" / "24 sept. 2026"      long: "September 24, 2026" / "24 septembre 2026"
     */
    function ui_date($value, string $style = 'short', string $fallback = 'N/A'): string
    {
        if (! $value) {
            return $fallback;
        }
        try {
            $date = $value instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($value) : \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
        $fr = app()->getLocale() === 'fr';
        $date = $date->locale($fr ? 'fr' : 'en');

        return match ($style) {
            'long' => $fr ? $date->translatedFormat('j F Y') : $date->format('F j, Y'),
            'datetime' => $fr ? $date->translatedFormat('j M Y H:i') : $date->format('M j, Y, g:i A'),
            default => $fr ? $date->translatedFormat('j M Y') : $date->format('M j, Y'),
        };
    }
}

if (! function_exists('default_avatar')) {
    /**
     * Locally hosted default avatar (public/images/avatars). Same algorithm as window.defaultAvatar() in JS:
     * known seeds have their own file, anything else maps deterministically to a1..a12.
     */
    function default_avatar(?string $seed = 'default'): string
    {
        $seed = (string) ($seed ?: 'default');
        if (in_array($seed, ['default', 'Admin', 'alex-morgan'], true)) {
            return "/images/avatars/{$seed}.svg";
        }
        $sum = 0;
        foreach (mb_str_split($seed) as $char) {
            $sum += mb_ord($char);
        }

        return '/images/avatars/a'.(($sum % 12) + 1).'.svg';
    }
}

if (! function_exists('placeholder_image')) {
    /** Local SVG placeholder (replaces the discontinued via.placeholder.com service). */
    function placeholder_image(int $width, int $height, string $bg, string $fg, string $text = ''): string
    {
        return "/placeholder/{$width}x{$height}?".http_build_query(['bg' => $bg, 'fg' => $fg, 'text' => $text]);
    }
}

if (! function_exists('user_avatar')) {
    function user_avatar($user, string $seed = 'default'): string
    {
        return $user?->avatar ?: default_avatar($seed);
    }
}
