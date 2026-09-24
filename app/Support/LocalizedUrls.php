<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Public marketing pages exist in English at "/..." and in French at "/fr/..." (real, indexable URLs
 * linked with hreflang). Private app pages keep the session language and have no /fr version.
 */
class LocalizedUrls
{
    public const FRENCH_PREFIX = 'fr';

    /** Route names of pages that have both an English and a French URL. */
    public const ROUTES = [
        'home', 'pricing', 'faq', 'contact', 'privacy', 'terms', 'refund',
        'landing.cover-letter', 'landing.work-certificate',
        'templates.public', 'templates.public.preview', 'templates.show',
    ];

    public static function isLocalizedRoute(?Route $route): bool
    {
        $name = $route?->getName();

        return $name !== null && in_array(preg_replace('/^fr\./', '', $name), self::ROUTES, true);
    }

    public static function isFrenchRoute(?Route $route): bool
    {
        return str_starts_with((string) $route?->getName(), 'fr.');
    }

    /** "/fr/pricing" -> "/pricing", "/fr" -> "/". */
    public static function stripPrefix(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        if ($path === '/'.self::FRENCH_PREFIX || str_starts_with($path, '/'.self::FRENCH_PREFIX.'/')) {
            $path = substr($path, strlen(self::FRENCH_PREFIX) + 1) ?: '/';
        }

        return $path;
    }

    /** English path -> path in $locale ("/pricing" -> "/fr/pricing", "/" -> "/fr"). */
    public static function pathFor(string $path, string $locale): string
    {
        $path = self::stripPrefix($path);

        if ($locale !== self::FRENCH_PREFIX) {
            return $path;
        }

        return '/'.self::FRENCH_PREFIX.($path === '/' ? '' : $path);
    }

    /** Whether a site path (with or without /fr) points at a page that has both language versions. */
    public static function isLocalizedPath(string $path): bool
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create(self::stripPrefix($path)));
        } catch (\Throwable) {
            return false;
        }

        return self::isLocalizedRoute($route);
    }

    /**
     * hreflang URLs for the current request, or null when the page has a single language.
     *
     * @return array{en: string, fr: string, x-default: string}|null
     */
    public static function alternates(string $siteOrigin): ?array
    {
        $request = request();

        if (! self::isLocalizedRoute($request->route())) {
            return null;
        }

        $path = self::stripPrefix($request->getPathInfo());
        $en = $siteOrigin.$path;

        return ['en' => $en, 'fr' => $siteOrigin.self::pathFor($path, 'fr'), 'x-default' => $en];
    }
}
