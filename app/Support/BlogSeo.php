<?php

namespace App\Support;

use App\Models\BlogPost;

class BlogSeo
{
    public static function frontendBaseUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    public static function postUrl(BlogPost $post): string
    {
        return self::frontendBaseUrl().'/blog/'.$post->slug;
    }

    public static function description(BlogPost $post, int $maxLen = 200): string
    {
        $excerpt = trim((string) ($post->excerpt ?? ''));
        if ($excerpt !== '') {
            return self::limit($excerpt, $maxLen);
        }

        $plain = trim(strip_tags((string) ($post->content ?? '')));
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? '';

        return self::limit($plain, $maxLen);
    }

    public static function ogImage(BlogPost $post): string
    {
        $image = trim((string) ($post->featured_image ?? ''));
        if ($image === '') {
            return self::defaultOgImage();
        }
        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }
        if (str_starts_with($image, '//')) {
            return 'https:'.$image;
        }
        $base = self::frontendBaseUrl();

        return str_starts_with($image, '/')
            ? $base.$image
            : $base.'/'.$image;
    }

    public static function defaultOgImage(): string
    {
        return self::frontendBaseUrl().'/og-image.png';
    }

    private static function limit(string $text, int $maxLen): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLen - 1)).'…';
    }
}
