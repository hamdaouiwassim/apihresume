<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * UI translations shared by Blade views and React islands.
 *
 * Source files: resources/translations/{locale}.json (same nested structure the
 * former React SPA used, so keys like "nav.myResumes" keep working).
 */
class Translations
{
    public const SUPPORTED = ['en', 'fr'];

    /** @var array<string, array> */
    private static array $cache = [];

    public static function all(?string $locale = null): array
    {
        $locale = self::normalize($locale ?? app()->getLocale());

        if (! isset(self::$cache[$locale])) {
            $path = resource_path("translations/{$locale}.json");
            self::$cache[$locale] = is_file($path)
                ? (json_decode(file_get_contents($path), true) ?: [])
                : [];
        }

        return self::$cache[$locale];
    }

    public static function get(string $key, array $replace = [], mixed $default = null, ?string $locale = null): mixed
    {
        $value = Arr::get(self::all($locale), $key);

        if ($value === null && self::normalize($locale ?? app()->getLocale()) !== 'en') {
            $value = Arr::get(self::all('en'), $key);
        }

        if ($value === null) {
            $value = $default ?? $key;
        }

        if (is_string($value) && $replace !== []) {
            foreach ($replace as $name => $replacement) {
                $value = str_replace(
                    ['{{'.$name.'}}', '{'.$name.'}', ':'.$name],
                    (string) $replacement,
                    $value
                );
            }
        }

        return $value;
    }

    public static function normalize(?string $locale): string
    {
        $locale = strtolower((string) $locale);

        return in_array($locale, self::SUPPORTED, true) ? $locale : 'en';
    }
}
