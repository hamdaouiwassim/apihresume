<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sitemap.base_url' => 'https://hresume.pro',
            'sitemap.cache_ttl' => 3600,
        ]);
        Cache::forget(SitemapService::CACHE_KEY);
    }

    public function test_sitemap_route_returns_xml_with_static_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('<loc>https://hresume.pro/</loc>', $response->getContent());
        $this->assertStringContainsString('<loc>https://hresume.pro/blog</loc>', $response->getContent());
        $this->assertStringContainsString('<loc>https://hresume.pro/pricing</loc>', $response->getContent());
    }

    public function test_sitemap_includes_published_blog_posts(): void
    {
        $user = User::factory()->create();

        BlogPost::create([
            'user_id' => $user->id,
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => '<p>Test</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        BlogPost::create([
            'user_id' => $user->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => '<p>Draft</p>',
            'status' => 'draft',
            'published_at' => null,
        ]);

        Cache::forget(SitemapService::CACHE_KEY);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('<loc>https://hresume.pro/blog/hello-world</loc>', $response->getContent());
        $this->assertStringNotContainsString('draft-post', $response->getContent());
    }

    public function test_sitemap_includes_public_profiles_and_templates(): void
    {
        $user = User::factory()->create();

        $template = Template::create([
            'name' => 'Modern',
            'description' => 'A template',
            'category' => 'professional',
        ]);

        Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Jane Doe CV',
            'public_profile_enabled' => true,
            'public_profile_slug' => 'jane-doe',
        ]);

        Cache::forget(SitemapService::CACHE_KEY);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('<loc>https://hresume.pro/u/jane-doe</loc>', $response->getContent());
        $this->assertStringContainsString(
            '<loc>https://hresume.pro/templates/public/preview/'.$template->id.'</loc>',
            $response->getContent()
        );
    }

    public function test_blog_post_save_busts_sitemap_cache(): void
    {
        $user = User::factory()->create();

        $service = app(SitemapService::class);
        $service->xml();
        $this->assertTrue(Cache::has(SitemapService::CACHE_KEY));

        BlogPost::create([
            'user_id' => $user->id,
            'title' => 'New Post',
            'slug' => 'new-post',
            'content' => '<p>Hi</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertTrue(Cache::has(SitemapService::CACHE_KEY));
        $this->assertStringContainsString('new-post', Cache::get(SitemapService::CACHE_KEY));
    }
}
