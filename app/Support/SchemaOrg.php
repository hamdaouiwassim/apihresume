<?php

namespace App\Support;

/**
 * Shared schema.org entities (JSON-LD). Pages link to them by @id so search engines see one
 * Organization and one WebSite for the whole site:
 *   Organization <- WebSite.publisher, WebApplication.publisher/provider, BlogPosting.publisher
 */
class SchemaOrg
{
    public static function siteUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/').'/';
    }

    public static function organizationId(): string
    {
        return self::siteUrl().'#organization';
    }

    public static function websiteId(): string
    {
        return self::siteUrl().'#website';
    }

    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => self::organizationId(),
            'name' => 'HResume',
            'url' => self::siteUrl(),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::siteUrl().'logo.png',
                'width' => 512,
                'height' => 512,
            ],
        ];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => self::siteUrl(),
            'name' => 'HResume',
            'inLanguage' => ['en', 'fr'],
            'publisher' => ['@id' => self::organizationId()],
        ];
    }

    /** Wraps entities in one JSON-LD document; Organization and WebSite are always included. */
    public static function graph(array ...$entities): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [self::organization(), self::website(), ...$entities],
        ];
    }
}
