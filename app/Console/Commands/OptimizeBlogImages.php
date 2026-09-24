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
        if (! function_exists('imagewebp') || ! (gd_info()['WebP Support'] ?? false)) {
            $this->error('PHP GD has no WebP support: install/enable it (e.g. apt install php-gd, then restart PHP-FPM) and run again.');

            return self::FAILURE;
        }

        // Files must land in the folder the site serves as /storage (public/storage -> storage/app/public).
        $diskRoot = realpath(Storage::disk('public')->path(''));
        $served = realpath(public_path('storage'));
        if (! app()->runningUnitTests() && (! $diskRoot || $diskRoot !== $served)) {
            $this->error("The public disk writes to [{$diskRoot}] but the site serves /storage from [{$served}].");
            $this->line('Run artisan from the deployed app folder, then: php artisan config:clear && php artisan storage:link');

            return self::FAILURE;
        }

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

        $this->info("Done. {$converted} image(s) converted; their posts now point to the WebP files.");

        return self::SUCCESS;
    }
}
