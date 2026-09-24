<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Resume;
use App\Models\Template;
use App\Support\LocalizedUrls;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class SitemapService
{
    public const CACHE_KEY = 'sitemap.xml';

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string, alternates?: array<string, string>}>
     */
    public function entries(): array
    {
        $base = rtrim((string) config('sitemap.base_url'), '/');
        $entries = [];

        foreach (config('sitemap.static_paths', []) as $path => [$changefreq, $priority]) {
            array_push($entries, ...$this->localizedEntries($base, $path, now(), $changefreq, $priority));
        }

        BlogPost::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'published_at', 'updated_at'])
            ->each(function (BlogPost $post) use ($base, &$entries) {
                $lastmod = $post->updated_at ?? $post->published_at ?? now();
                $entries[] = $this->entry(
                    $base,
                    '/blog/'.$post->slug,
                    $lastmod,
                    'monthly',
                    '0.7'
                );
            });

        Template::query()
            ->orderBy('id')
            ->whereNotNull('slug')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Template $template) use ($base, &$entries) {
                array_push($entries, ...$this->localizedEntries(
                    $base,
                    '/templates/'.$template->slug,
                    $template->updated_at ?? now(),
                    'monthly',
                    '0.8'
                ));
            });

        Resume::query()
            ->where('public_profile_enabled', true)
            ->whereNotNull('public_profile_slug')
            ->where('public_profile_slug', '!=', '')
            ->orderByDesc('updated_at')
            ->get(['public_profile_slug', 'updated_at'])
            ->each(function (Resume $resume) use ($base, &$entries) {
                $entries[] = $this->entry(
                    $base,
                    '/u/'.$resume->public_profile_slug,
                    $resume->updated_at ?? now(),
                    'weekly',
                    '0.5'
                );
            });

        return $entries;
    }

    public function xml(): string
    {
        return Cache::remember(
            self::CACHE_KEY,
            config('sitemap.cache_ttl', 3600),
            fn () => $this->buildXml($this->entries())
        );
    }

    public function bustCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Clear cache and rebuild so the next /sitemap.xml request is fresh. */
    public function refresh(): void
    {
        $this->bustCache();
        $this->xml();
    }

    /**
     * @param  list<array{loc: string, lastmod: string, changefreq: string, priority: string}>  $entries
     */
    public function buildXml(array $entries): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
            '        xmlns:xhtml="http://www.w3.org/1999/xhtml">',
        ];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.e($entry['loc']).'</loc>';
            $lines[] = '    <lastmod>'.$entry['lastmod'].'</lastmod>';
            $lines[] = '    <changefreq>'.$entry['changefreq'].'</changefreq>';
            $lines[] = '    <priority>'.$entry['priority'].'</priority>';
            foreach ($entry['alternates'] ?? [] as $lang => $href) {
                $lines[] = '    <xhtml:link rel="alternate" hreflang="'.$lang.'" href="'.e($href).'" />';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /**
     * One entry per language for pages that have a /fr version (each listing both as alternates),
     * a single entry otherwise (blog posts, profiles, auth pages).
     *
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string, alternates?: array<string, string>}>
     */
    private function localizedEntries(
        string $base,
        string $path,
        CarbonInterface $lastmod,
        string $changefreq,
        string $priority,
    ): array {
        if (! LocalizedUrls::isLocalizedPath($path)) {
            return [$this->entry($base, $path, $lastmod, $changefreq, $priority)];
        }

        $en = $this->entry($base, $path, $lastmod, $changefreq, $priority);
        $fr = $this->entry($base, LocalizedUrls::pathFor($path, 'fr'), $lastmod, $changefreq, $priority);
        $alternates = ['en' => $en['loc'], 'fr' => $fr['loc'], 'x-default' => $en['loc']];

        return [$en + ['alternates' => $alternates], $fr + ['alternates' => $alternates]];
    }

    /**
     * @return array{loc: string, lastmod: string, changefreq: string, priority: string}
     */
    private function entry(
        string $base,
        string $path,
        CarbonInterface $lastmod,
        string $changefreq,
        string $priority,
    ): array {
        $path = $path === '/' ? '/' : '/'.ltrim($path, '/');
        $loc = $base.($path === '/' ? '/' : $path);

        return [
            'loc' => $loc,
            'lastmod' => $lastmod->toDateString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
