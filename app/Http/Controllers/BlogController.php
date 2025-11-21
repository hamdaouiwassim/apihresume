<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Get all published blog posts
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 12);
            $search = $request->input('search');

            $query = BlogPost::published()
                ->with('user:id,name,avatar')
                ->orderBy('published_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            $posts = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Blog posts fetched successfully',
                'data' => $posts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch blog posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single blog post by slug
     */
    public function show($slug)
    {
        try {
            $post = BlogPost::published()
                ->with('user:id,name,avatar')
                ->where('slug', $slug)
                ->firstOrFail();

            // Increment views
            $post->incrementViews();

            return response()->json([
                'status' => true,
                'message' => 'Blog post fetched successfully',
                'data' => $post
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Blog post not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
