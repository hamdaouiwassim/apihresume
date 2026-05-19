<?php

namespace App\Observers;

use App\Models\Template;

class TemplateObserver
{
    use RegeneratesSitemap;

    public function saved(Template $template): void
    {
        $this->regenerateSitemap();
    }

    public function deleted(Template $template): void
    {
        $this->regenerateSitemap();
    }
}
