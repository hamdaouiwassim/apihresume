<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Template extends Model
{
    //
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'preview_image_url'
    ];

    /** Path segments that already exist under /templates/ and can never be a template slug. */
    public const RESERVED_SLUGS = ['public', 'preview'];

    protected static function booted(): void
    {
        // Public page URL /templates/{slug}: generated from the name, unique, kept when the name changes
        // (so indexed URLs stay valid) unless it was never set.
        static::saving(function (Template $template) {
            if (blank($template->slug)) {
                $template->slug = static::uniqueSlug($template->name, $template->id);
            }
        });
    }

    public static function uniqueSlug(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $name) ?: 'template';
        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-template';
        }

        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /**
     * Intrinsic size of the preview image when it is stored on the public disk (for width/height
     * attributes that prevent layout shift). Cached per image URL.
     *
     * @return array{width: int, height: int}|null
     */
    public function previewImageSize(): ?array
    {
        $url = $this->preview_image_url;
        $path = $url ? Str::after((string) parse_url($url, PHP_URL_PATH), '/storage/') : null;

        if (! $path || $path === (string) parse_url($url, PHP_URL_PATH)) {
            return null;
        }

        return Cache::rememberForever('template-image-size:'.$path, function () use ($path) {
            $disk = Storage::disk('public');
            $size = $disk->exists($path) ? @getimagesize($disk->path($path)) : false;

            return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
        });
    }

    /**
     * Get all resumes using this template
     */
    public function resumes()
    {
        return $this->hasMany(\App\Models\Resume::class);
    }

    public function getPreviewImageUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return \App\Support\LegacyUrls::rewrite($value);
        }

        return rtrim(config('app.url'), '/') . $value;
    }
}
