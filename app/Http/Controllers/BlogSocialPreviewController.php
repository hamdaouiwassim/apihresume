<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\BlogSeo;
use Illuminate\View\View;

/**
 * Minimal HTML with Open Graph tags for social crawlers (SPA does not SSR meta).
 */
class BlogSocialPreviewController extends Controller
{
    public function show(string $slug): View
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('blog.social-preview', [
            'post' => $post,
            'pageUrl' => BlogSeo::postUrl($post),
            'title' => $post->title.' | HResume Blog',
            'description' => BlogSeo::description($post),
            'image' => BlogSeo::ogImage($post),
        ]);
    }
}
