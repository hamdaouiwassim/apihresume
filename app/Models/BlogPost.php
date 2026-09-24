<?php

namespace App\Models;

use App\Support\LegacyUrls;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_meta',
        'status',
        'published_at',
        'views',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured_image_meta' => 'array',
    ];

    /**
     * Get the user that owns the blog post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate slug from title
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);

                // Ensure uniqueness
                $originalSlug = $post->slug;
                $count = 1;
                while (static::where('slug', $post->slug)->exists()) {
                    $post->slug = $originalSlug.'-'.$count;
                    $count++;
                }
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->slug = Str::slug($post->title);

                // Ensure uniqueness
                $originalSlug = $post->slug;
                $count = 1;
                while (static::where('slug', $post->slug)->where('id', '!=', $post->id)->exists()) {
                    $post->slug = $originalSlug.'-'.$count;
                    $count++;
                }
            }
        });
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Increment views
     */
    /**
     * Optimized featured image variants (see App\Services\BlogImageOptimizer), or null for
     * external URLs / images uploaded before optimization existed.
     */
    public function optimizedFeaturedImage(): ?array
    {
        $meta = $this->featured_image_meta;
        if (! is_array($meta) || empty($meta['variants'])) {
            return null;
        }

        $disk = Storage::disk('public');
        $variants = collect($meta['variants'])
            ->mapWithKeys(fn ($path, $width) => [(int) $width => $disk->url($path)])
            ->sortKeys();

        return [
            'src' => $variants->last(),
            'srcset' => $variants->map(fn ($url, $width) => "{$url} {$width}w")->implode(', '),
            'smallest' => $variants->first(),
            'width' => (int) ($meta['width'] ?? 0),
            'height' => (int) ($meta['height'] ?? 0),
        ];
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    /** Uploads saved under a former domain of this app are served from APP_URL now. */
    protected function featuredImage(): Attribute
    {
        return Attribute::get(fn ($value) => LegacyUrls::rewrite($value));
    }

    protected function content(): Attribute
    {
        return Attribute::get(fn ($value) => LegacyUrls::rewrite($value));
    }
}
