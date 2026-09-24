<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploads saved while the API ran on apihresume.hamdaouiacademy.com must be served from APP_URL.
 */
class LegacyUrlsTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = 'https://apihresume.hamdaouiacademy.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.url' => 'https://hresume.pro', 'app.legacy_urls' => [self::OLD]]);
    }

    private function createPost(): BlogPost
    {
        return BlogPost::create([
            'user_id' => User::factory()->create(['is_admin' => true])->id,
            'title' => 'Old image post',
            'slug' => 'old-image-post',
            'content' => '<p>x</p><img src="'.self::OLD.'/storage/blog-images/inline.png">',
            'featured_image' => self::OLD.'/storage/blog-images/1779456142_6a10588eaf3d1.png',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);
    }

    public function test_pages_never_reference_the_former_domain(): void
    {
        $this->createPost();

        foreach (['/blog', '/blog/old-image-post'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('https://hresume.pro/storage/blog-images/1779456142_6a10588eaf3d1.png', false)
                ->assertDontSee('hamdaouiacademy', false);
        }
    }

    public function test_avatars_are_rewritten_on_read(): void
    {
        $user = User::factory()->create(['avatar' => self::OLD.'/storage/avatars/me.png']);

        $this->assertSame('https://hresume.pro/storage/avatars/me.png', $user->fresh()->avatar);
    }

    public function test_command_rewrites_stored_urls(): void
    {
        $post = $this->createPost();
        $user = User::factory()->create(['avatar' => self::OLD.'/storage/avatars/me.png', 'google_avatar' => 'https://lh3.googleusercontent.com/a/x']);

        $this->artisan('app:rewrite-legacy-urls', ['--dry-run' => true])->expectsOutputToContain('3 value(s) would change')->assertSuccessful();
        $this->assertStringStartsWith(self::OLD, DB::table('blog_posts')->where('id', $post->id)->value('featured_image'));

        $this->artisan('app:rewrite-legacy-urls')->expectsOutputToContain('3 value(s) updated')->assertSuccessful();

        $row = DB::table('blog_posts')->where('id', $post->id)->first();
        $this->assertSame('https://hresume.pro/storage/blog-images/1779456142_6a10588eaf3d1.png', $row->featured_image);
        $this->assertStringContainsString('src="https://hresume.pro/storage/blog-images/inline.png"', $row->content);
        $this->assertSame('https://hresume.pro/storage/avatars/me.png', DB::table('users')->where('id', $user->id)->value('avatar'));
        $this->assertSame('https://lh3.googleusercontent.com/a/x', DB::table('users')->where('id', $user->id)->value('google_avatar'));
    }

    public function test_optimizing_an_old_domain_image_points_the_post_to_webp_on_app_url(): void
    {
        Storage::fake('public');
        $png = UploadedFile::fake()->image('cover.png', 1600, 900);
        Storage::disk('public')->putFileAs('blog-images', $png, '1779456142_6a10588eaf3d1.png');
        $post = $this->createPost();

        $this->artisan('blog:optimize-images', ['--delete-originals' => true])->assertSuccessful();

        $row = DB::table('blog_posts')->where('id', $post->id)->first();
        $this->assertMatchesRegularExpression('#^https://hresume\.pro/storage/blog-images/[a-z0-9_]+-1200\.webp$#', $row->featured_image);
        $this->assertNotNull($row->featured_image_meta);
        Storage::disk('public')->assertMissing('blog-images/1779456142_6a10588eaf3d1.png');

        $this->get('/blog/old-image-post')->assertOk()->assertSee('-800.webp 800w', false)->assertDontSee('6a10588eaf3d1.png', false);
    }
}
