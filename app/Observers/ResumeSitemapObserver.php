<?php

namespace App\Observers;

use App\Models\Resume;

class ResumeSitemapObserver
{
    use RegeneratesSitemap;

    public function saved(Resume $resume): void
    {
        if ($resume->wasChanged(['public_profile_enabled', 'public_profile_slug'])) {
            $this->regenerateSitemap();
        }
    }

    public function deleted(Resume $resume): void
    {
        if ($resume->public_profile_enabled && $resume->public_profile_slug) {
            $this->regenerateSitemap();
        }
    }
}
