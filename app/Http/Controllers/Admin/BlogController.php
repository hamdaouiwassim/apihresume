<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Get all blog posts (including drafts)
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $status = $request->input('status');

            $query = BlogPost::with('user:id,name,avatar')
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->where('status', $status);
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
     * Get a single blog post
     */
    public function show($id)
    {
        try {
            $post = BlogPost::with('user:id,name,avatar')->findOrFail($id);

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

    /**
     * Create a new blog post
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'excerpt' => 'nullable|string|max:500',
                'content' => 'required|string',
                'featured_image' => 'nullable|string|url',
                'status' => 'required|in:draft,published',
                'published_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $count = 1;
            while (BlogPost::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            $post = BlogPost::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'slug' => $slug,
                'excerpt' => $request->excerpt,
                'content' => $request->content,
                'featured_image' => $request->featured_image,
                'status' => $request->status,
                'published_at' => $request->status === 'published' 
                    ? ($request->published_at ?? now()) 
                    : null,
            ]);

            $post->load('user:id,name,avatar');

            return response()->json([
                'status' => true,
                'message' => 'Blog post created successfully',
                'data' => $post
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create blog post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a blog post
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'excerpt' => 'nullable|string|max:500',
                'content' => 'sometimes|string',
                'featured_image' => 'nullable|string|url',
                'status' => 'sometimes|in:draft,published',
                'published_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $post = BlogPost::findOrFail($id);

            $updateData = $request->only([
                'title',
                'excerpt',
                'content',
                'featured_image',
                'status',
            ]);

            // Handle slug if title changed
            if ($request->has('title') && $request->title !== $post->title) {
                $slug = Str::slug($request->title);
                $originalSlug = $slug;
                $count = 1;
                while (BlogPost::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }
                $updateData['slug'] = $slug;
            }

            // Handle published_at
            if ($request->has('status')) {
                if ($request->status === 'published' && !$post->published_at) {
                    $updateData['published_at'] = $request->published_at ?? now();
                } elseif ($request->status === 'draft') {
                    $updateData['published_at'] = null;
                } elseif ($request->has('published_at')) {
                    $updateData['published_at'] = $request->published_at;
                }
            } elseif ($request->has('published_at')) {
                $updateData['published_at'] = $request->published_at;
            }

            $post->update($updateData);
            $post->load('user:id,name,avatar');

            return response()->json([
                'status' => true,
                'message' => 'Blog post updated successfully',
                'data' => $post
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update blog post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a blog post
     */
    public function destroy($id)
    {
        try {
            $post = BlogPost::findOrFail($id);
            $post->delete();

            return response()->json([
                'status' => true,
                'message' => 'Blog post deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete blog post',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
