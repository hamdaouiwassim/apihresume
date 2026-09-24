<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\BlogHtmlSanitizer;
use App\Support\BlogSeo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public blog (ports of pages/Blog.jsx and pages/BlogPost.jsx), same queries as Api BlogController.
 */
class BlogPageController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = BlogPost::published()
            ->with('user:id,name,avatar')
            ->orderBy('published_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $query->paginate(12)->withQueryString();

        return view('pages.blog.index', compact('posts', 'search'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->with('user:id,name,avatar')
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            return response()->view('pages.blog.not-found', [], Response::HTTP_NOT_FOUND);
        }

        $post->incrementViews();
        $post->setAttribute('content', BlogHtmlSanitizer::clean($post->content));

        return view('pages.blog.show', [
            'post' => $post,
            'description' => BlogSeo::description($post),
            'ogImage' => $post->featured_image ? BlogSeo::ogImage($post) : null,
        ]);
    }
}
