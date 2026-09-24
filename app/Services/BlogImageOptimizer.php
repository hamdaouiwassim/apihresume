<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Resizes uploaded blog images into WebP variants for fast, SEO-friendly pages.
 *
 * Output (public disk, blog-images/):  {name}-480.webp, {name}-800.webp, {name}-1200.webp
 * (widths larger than the original are skipped; the original width is used instead).
 *
 * Returned meta is stored on BlogPost::featured_image_meta and used for srcset + width/height.
 */
class BlogImageOptimizer
{
    /** Display widths: list cards (~400px), phones/tablets, article hero / Open Graph (1200px). */
    public const WIDTHS = [480, 800, 1200];

    public const QUALITY = 80;

    public const DIRECTORY = 'blog-images';

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new GdDriver);
    }

    /**
     * @return array{width:int,height:int,path:string,variants:array<string,string>}
     */
    public function optimize(UploadedFile|string $source, ?string $baseName = null): array
    {
        $contents = $source instanceof UploadedFile ? file_get_contents($source->getRealPath()) : $source;
        $image = $this->manager->read($contents);

        $originalWidth = $image->width();
        $baseName ??= time().'_'.Str::lower(Str::random(10));

        $widths = array_values(array_unique(array_map(
            fn (int $w) => min($w, $originalWidth),
            self::WIDTHS
        )));

        $variants = [];
        $largest = null;
        foreach ($widths as $width) {
            $variant = $this->manager->read($contents);
            if ($variant->width() > $width) {
                $variant->scaleDown(width: $width);
            }
            $path = self::DIRECTORY."/{$baseName}-{$width}.webp";
            Storage::disk('public')->put($path, (string) $variant->toWebp(self::QUALITY));
            $variants[(string) $width] = $path;
            $largest = ['path' => $path, 'width' => $variant->width(), 'height' => $variant->height()];
        }

        return [
            'width' => $largest['width'],
            'height' => $largest['height'],
            'path' => $largest['path'],
            'variants' => $variants,
        ];
    }

    /** Delete every file referenced by a stored meta array. */
    public function delete(?array $meta): void
    {
        foreach (($meta['variants'] ?? []) as $path) {
            if (is_string($path) && str_starts_with($path, self::DIRECTORY.'/')) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
