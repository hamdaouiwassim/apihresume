<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\BlogImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Converts featured images uploaded before optimization existed into WebP variants.
 * External image URLs are left untouched.
 */
class OptimizeBlogImages extends Command
{
    protected $signature = 'blog:optimize-images
        {--delete-originals : Delete the original uploaded files after conversion}
        {--dry-run : List what would be converted without writing anything}';

    protected $description = 'Create optimized WebP variants for existing uploaded blog featured images';

    public function handle(BlogImageOptimizer $optimizer): int
    {
        $marker = '/storage/'.BlogImageOptimizer::DIRECTORY.'/';
        $converted = 0;

        BlogPost::query()
            ->whereNull('featured_image_meta')
            ->where('featured_image', 'like', '%'.$marker.'%')
            ->each(function (BlogPost $post) use ($optimizer, $marker, &$converted) {
                $path = BlogImageOptimizer::DIRECTORY.'/'.basename(parse_url($post->featured_image, PHP_URL_PATH));
                $disk = Storage::disk('public');

                if (! $disk->exists($path)) {
                    $this->warn("#{$post->id} missing file {$path}");

                    return;
                }
                if ($this->option('dry-run')) {
                    $this->line("#{$post->id} would convert {$path}");

                    return;
                }

                try {
                    $meta = $optimizer->optimize($disk->get($path));
                } catch (\Throwable $e) {
                    $this->error("#{$post->id} failed: {$e->getMessage()}");

                    return;
                }

                $base = substr($post->featured_image, 0, strpos($post->featured_image, $marker));
                $post->forceFill([
                    'featured_image' => $base.'/storage/'.$meta['path'],
                    'featured_image_meta' => $meta,
                ])->saveQuietly();

                if ($this->option('delete-originals')) {
                    $disk->delete($path);
                }

                $converted++;
                $this->info("#{$post->id} converted ({$meta['width']}x{$meta['height']}, ".count($meta['variants']).' variants)');
            });

        $this->info("Done. {$converted} image(s) converted.");

        return self::SUCCESS;
    }
}
