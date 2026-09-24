<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
        $this->withoutVite();
        $this->admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
    }

    private function createPost(UploadedFile $file): BlogPost
    {
        $this->actingAs($this->admin)->post('/api/admin/blog', [
            'title' => 'Optimized post',
            'content' => '<p>Body</p>',
            'status' => 'published',
            'featured_image_file' => $file,
        ], ['Accept' => 'application/json'])->assertCreated();

        return BlogPost::firstOrFail();
    }

    public function test_upload_is_converted_to_webp_variants_without_upscaling(): void
    {
        $post = $this->createPost(UploadedFile::fake()->image('photo.jpg', 2000, 1000));

        $meta = $post->featured_image_meta;
        $this->assertSame(['480', '800', '1200'], array_map('strval', array_keys($meta['variants'])));
        $this->assertSame(1200, $meta['width']);
        $this->assertSame(600, $meta['height']);
        foreach ($meta['variants'] as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringEndsWith('.webp', $path);
        }
        $this->assertStringEndsWith($meta['path'], $post->featured_image);

        // Small image: never upscaled, a single variant at its own width.
        $small = app(\App\Services\BlogImageOptimizer::class)->optimize(UploadedFile::fake()->image('small.png', 300, 200));
        $this->assertSame(['300'], array_map('strval', array_keys($small['variants'])));
        $this->assertSame(300, $small['width']);
    }

    public function test_blog_pages_serve_responsive_images_with_dimensions(): void
    {
        $post = $this->createPost(UploadedFile::fake()->image('photo.jpg', 1600, 900));

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('srcset="', false)
            ->assertSee('1200w', false)
            ->assertSee('width="1200"', false)
            ->assertSee('height="675"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('<link rel="preload" as="image"', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('ImageObject', false);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('480w', false)
            ->assertSee('loading="lazy"', false);
    }

    public function test_replacing_the_image_deletes_old_variants(): void
    {
        $post = $this->createPost(UploadedFile::fake()->image('first.jpg', 1400, 800));
        $oldVariants = $post->featured_image_meta['variants'];

        $this->actingAs($this->admin)->post('/api/admin/blog/'.$post->id.'?_method=PUT', [
            'featured_image_file' => UploadedFile::fake()->image('second.jpg', 1400, 800),
        ], ['Accept' => 'application/json'])->assertOk();

        foreach ($oldVariants as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertNotEquals($oldVariants, $post->fresh()->featured_image_meta['variants']);

        // Switching to an external URL clears the optimized metadata.
        $this->actingAs($this->admin)->putJson('/api/admin/blog/'.$post->id, [
            'featured_image' => 'https://images.example.com/cover.jpg',
        ])->assertOk();
        $this->assertNull($post->fresh()->featured_image_meta);
    }

    public function test_command_converts_legacy_uploads(): void
    {
        $file = UploadedFile::fake()->image('legacy.jpg', 1300, 700);
        Storage::disk('public')->putFileAs('blog-images', $file, 'legacy.jpg');
        $post = BlogPost::create([
            'user_id' => $this->admin->id,
            'title' => 'Legacy',
            'slug' => 'legacy',
            'content' => '<p>x</p>',
            'status' => 'published',
            'published_at' => now(),
            'featured_image' => 'http://localhost/storage/blog-images/legacy.jpg',
        ]);

        $this->artisan('blog:optimize-images', ['--delete-originals' => true])->assertSuccessful();

        $post->refresh();
        $this->assertSame(1200, $post->featured_image_meta['width']);
        $this->assertStringEndsWith('-1200.webp', $post->featured_image);
        Storage::disk('public')->assertMissing('blog-images/legacy.jpg');
    }
}
