<?php

namespace App\Observers;

use App\Services\SitemapService;

trait RegeneratesSitemap
{
    protected function regenerateSitemap(): void
    {
        app(SitemapService::class)->regenerateFiles();
    }
}
