<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Jobs\SendBlogPostNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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
                'content' => 'required|string|max:50000',
                'featured_image' => 'nullable|string|url',
                'featured_image_file' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
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

            // Handle featured image upload
            $featuredImageUrl = $request->featured_image;
            if ($request->hasFile('featured_image_file')) {
                $file = $request->file('featured_image_file');
                if ($file->isValid()) {
                    // Ensure blog-images directory exists
                    $blogImagesDir = Storage::disk('public')->path('blog-images');
                    if (!is_dir($blogImagesDir)) {
                        Storage::disk('public')->makeDirectory('blog-images', 0755, true);
                    }
                    
                    // Store image with unique name
                    $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $imagePath = $file->storeAs('blog-images', $filename, 'public');
                    
                    if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                        $scheme = $request->getScheme();
                        $host = $request->getHost();
                        $port = $request->getPort();
                        $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                        $featuredImageUrl = $baseUrl . '/storage/' . $imagePath;
                    }
                }
            }

            $post = BlogPost::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'slug' => $slug,
                'excerpt' => $request->excerpt,
                'content' => $request->content,
                'featured_image' => $featuredImageUrl,
                'status' => $request->status,
                'published_at' => $request->status === 'published' 
                    ? ($request->published_at ?? now()) 
                    : null,
            ]);

            $post->load('user:id,name,avatar');

            // Queue email notification to all users only when post is first published
            if ($request->status === 'published') {
                SendBlogPostNotifications::dispatch($post);
            }

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
                'content' => 'sometimes|string|max:50000',
                'featured_image' => 'nullable|string|url',
                'featured_image_file' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
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

            // Handle featured image upload
            if ($request->hasFile('featured_image_file')) {
                $file = $request->file('featured_image_file');
                if ($file->isValid()) {
                    // Delete old image if exists
                    if ($post->featured_image) {
                        $storageUrl = Storage::disk('public')->url('');
                        if (str_contains($post->featured_image, $storageUrl)) {
                            $oldImagePath = str_replace($storageUrl, '', $post->featured_image);
                            $oldImagePath = ltrim($oldImagePath, '/');
                            if (!empty($oldImagePath) && Storage::disk('public')->exists($oldImagePath)) {
                                Storage::disk('public')->delete($oldImagePath);
                            }
                        }
                    }
                    
                    // Ensure blog-images directory exists
                    $blogImagesDir = Storage::disk('public')->path('blog-images');
                    if (!is_dir($blogImagesDir)) {
                        Storage::disk('public')->makeDirectory('blog-images', 0755, true);
                    }
                    
                    // Store new image
                    $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $imagePath = $file->storeAs('blog-images', $filename, 'public');
                    
                    if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                        $scheme = $request->getScheme();
                        $host = $request->getHost();
                        $port = $request->getPort();
                        $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                        $updateData['featured_image'] = $baseUrl . '/storage/' . $imagePath;
                    }
                }
            }

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
            $wasDraft = $post->status === 'draft';
            $wasAlreadyPublished = $post->status === 'published' && $post->published_at !== null;
            $isNowPublished = false;
            
            if ($request->has('status')) {
                if ($request->status === 'published' && !$post->published_at) {
                    // Only set published_at if it wasn't already published
                    $updateData['published_at'] = $request->published_at ?? now();
                    $isNowPublished = true;
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

            // Queue email notification only when post transitions from draft to published (first time only)
            // Don't send if post was already published before this update
            if ($isNowPublished && $wasDraft && !$wasAlreadyPublished) {
                SendBlogPostNotifications::dispatch($post);
            }

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
