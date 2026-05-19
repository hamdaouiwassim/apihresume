<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Regenerate sitemap.xml (cache + optional public file paths)';

    public function handle(SitemapService $sitemap): int
    {
        $sitemap->regenerateFiles();
        $count = count($sitemap->entries());
        $this->info("Sitemap generated with {$count} URLs.");

        foreach (config('sitemap.write_paths', []) as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                $this->line("  → {$path}");
            }
        }

        return self::SUCCESS;
    }
}
