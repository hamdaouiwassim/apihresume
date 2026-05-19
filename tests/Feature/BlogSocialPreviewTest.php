<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSocialPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://hresume.pro']);
    }

    public function test_social_preview_includes_post_og_image(): void
    {
        $user = User::factory()->create();

        BlogPost::create([
            'user_id' => $user->id,
            'title' => 'My Post',
            'slug' => 'my-post',
            'excerpt' => 'Short excerpt',
            'content' => '<p>Body</p>',
            'featured_image' => 'https://apihresume.hamdaouiacademy.com/storage/blog-images/test.png',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/blog/my-post/social');

        $response->assertOk();
        $response->assertSee('property="og:title" content="My Post | HResume Blog"', false);
        $response->assertSee('property="og:image" content="https://apihresume.hamdaouiacademy.com/storage/blog-images/test.png"', false);
        $response->assertSee('property="og:url" content="https://hresume.pro/blog/my-post"', false);
    }

    public function test_draft_post_returns_404(): void
    {
        $user = User::factory()->create();

        BlogPost::create([
            'user_id' => $user->id,
            'title' => 'Draft',
            'slug' => 'draft',
            'content' => '<p>x</p>',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->get('/blog/draft/social')->assertNotFound();
    }
}
