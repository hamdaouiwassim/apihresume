<?php

namespace App\Support;

/**
 * Files uploaded while the API ran on its own domain were saved with that domain in the URL
 * (e.g. https://apihresume.hamdaouiacademy.com/storage/blog-images/x.png). Those files are served by
 * this app now; rewrite such URLs to APP_URL (config app.legacy_urls lists the old origins).
 */
class LegacyUrls
{
    /** @return list<string> old origins without trailing slash, e.g. "https://api.example.com" */
    public static function origins(): array
    {
        $current = self::currentOrigin();

        return array_values(array_filter(
            array_map(fn ($url) => rtrim(trim((string) $url), '/'), (array) config('app.legacy_urls', [])),
            fn ($url) => $url !== '' && $url !== $current
        ));
    }

    public static function currentOrigin(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Rewrites old-origin URLs in a URL or an HTML/text value; other values are returned unchanged. */
    public static function rewrite(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        foreach (self::origins() as $origin) {
            $value = str_replace($origin.'/', self::currentOrigin().'/', $value);
        }

        return $value;
    }
}
