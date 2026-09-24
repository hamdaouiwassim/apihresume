<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBlogPostNotifications;
use App\Models\BlogPost;
use App\Support\AdminPagination;
use App\Support\ApiJson;
use App\Services\BlogImageOptimizer;
use App\Support\BlogHtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            $perPage = AdminPagination::resolve($request);
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
                'data' => $posts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(array_merge([
                'status' => false,
                'message' => 'Failed to fetch blog posts',
            ], ApiJson::debugError($e)), 500);
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
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(array_merge([
                'status' => false,
                'message' => 'Blog post not found',
            ], ApiJson::debugError($e)), 404);
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
                    'errors' => $validator->errors(),
                ], 422);
            }

            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $count = 1;
            while (BlogPost::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$count;
                $count++;
            }

            // Handle featured image upload (resized to WebP variants for SEO / page speed)
            $featuredImageUrl = $request->featured_image;
            $featuredImageMeta = null;
            if ($request->hasFile('featured_image_file') && $request->file('featured_image_file')->isValid()) {
                [$featuredImageUrl, $featuredImageMeta] = $this->storeFeaturedImage($request);
            }

            $post = BlogPost::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'slug' => $slug,
                'excerpt' => $request->excerpt,
                'content' => BlogHtmlSanitizer::clean($request->content),
                'featured_image' => $featuredImageUrl,
                'featured_image_meta' => $featuredImageMeta,
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
                'data' => $post,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(array_merge([
                'status' => false,
                'message' => 'Failed to create blog post',
            ], ApiJson::debugError($e)), 500);
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
                    'errors' => $validator->errors(),
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

            if (array_key_exists('content', $updateData)) {
                $updateData['content'] = BlogHtmlSanitizer::clean($updateData['content']);
            }

            // Handle featured image upload (resized to WebP variants for SEO / page speed)
            if ($request->hasFile('featured_image_file') && $request->file('featured_image_file')->isValid()) {
                $this->deleteFeaturedImage($post);
                [$updateData['featured_image'], $updateData['featured_image_meta']] = $this->storeFeaturedImage($request);
            } elseif (array_key_exists('featured_image', $updateData) && $updateData['featured_image'] !== $post->featured_image) {
                // Switched to an external URL (or removed the image): old optimized variants no longer apply.
                $this->deleteFeaturedImage($post);
                $updateData['featured_image_meta'] = null;
            }

            // Handle slug if title changed
            if ($request->has('title') && $request->title !== $post->title) {
                $slug = Str::slug($request->title);
                $originalSlug = $slug;
                $count = 1;
                while (BlogPost::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug.'-'.$count;
                    $count++;
                }
                $updateData['slug'] = $slug;
            }

            // Handle published_at
            $wasDraft = $post->status === 'draft';
            $wasAlreadyPublished = $post->status === 'published' && $post->published_at !== null;
            $isNowPublished = false;

            if ($request->has('status')) {
                if ($request->status === 'published' && ! $post->published_at) {
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
            if ($isNowPublished && $wasDraft && ! $wasAlreadyPublished) {
                SendBlogPostNotifications::dispatch($post);
            }

            return response()->json([
                'status' => true,
                'message' => 'Blog post updated successfully',
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(array_merge([
                'status' => false,
                'message' => 'Failed to update blog post',
            ], ApiJson::debugError($e)), 500);
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
                'message' => 'Blog post deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(array_merge([
                'status' => false,
                'message' => 'Failed to delete blog post',
            ], ApiJson::debugError($e)), 500);
        }
    }

    /**
     * Store an uploaded featured image as optimized WebP variants.
     * Falls back to storing the original file when it cannot be decoded.
     *
     * @return array{0: string|null, 1: array|null} [public URL of the largest variant, meta]
     */
    private function storeFeaturedImage(Request $request): array
    {
        $file = $request->file('featured_image_file');
        $baseUrl = $request->getScheme().'://'.$request->getHost()
            .($request->getPort() && ! in_array($request->getPort(), [80, 443]) ? ':'.$request->getPort() : '');

        try {
            $meta = app(BlogImageOptimizer::class)->optimize($file);

            return [$baseUrl.'/storage/'.$meta['path'], $meta];
        } catch (Throwable $e) {
            Log::warning('Blog image optimization failed, storing original', ['error' => $e->getMessage()]);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $imagePath = $file->storeAs(BlogImageOptimizer::DIRECTORY, time().'_'.uniqid().'.'.$extension, 'public');

        return [$imagePath ? $baseUrl.'/storage/'.$imagePath : null, null];
    }

    /** Remove the post's stored featured image files (optimized variants and legacy originals). */
    private function deleteFeaturedImage(BlogPost $post): void
    {
        app(BlogImageOptimizer::class)->delete($post->featured_image_meta);

        if ($post->featured_image && str_contains($post->featured_image, '/storage/'.BlogImageOptimizer::DIRECTORY.'/')) {
            $path = BlogImageOptimizer::DIRECTORY.'/'.basename(parse_url($post->featured_image, PHP_URL_PATH));
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
