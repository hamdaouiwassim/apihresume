<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Refresh cached sitemap (served at GET /sitemap.xml)';

    public function handle(SitemapService $sitemap): int
    {
        $sitemap->refresh();
        $count = count($sitemap->entries());
        $this->info("Sitemap cache refreshed ({$count} URLs). Served at /sitemap.xml on this app.");

        return self::SUCCESS;
    }
}
