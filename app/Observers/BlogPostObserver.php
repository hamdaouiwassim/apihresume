<?php

namespace App\Observers;

use App\Models\BlogPost;

class BlogPostObserver
{
    use RegeneratesSitemap;

    public function saved(BlogPost $post): void
    {
        $this->regenerateSitemap();
    }

    public function deleted(BlogPost $post): void
    {
        $this->regenerateSitemap();
    }
}
