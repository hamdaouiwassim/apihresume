<?php

namespace Tests\Feature\Web;

use App\Models\BlogPost;
use App\Models\Candidate;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade pages that replaced the React SPA: routing, middleware, SEO output and the recruiter removal.
 */
class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
        Candidate::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_public_pages_render_with_server_side_seo(): void
    {
        $pages = [
            '/' => 'HResume - Free CV, Cover Letter',
            '/pricing' => '<title>',
            '/faq' => 'FAQPage',
            '/contact' => '<title>',
            '/privacy' => '<title>',
            '/terms' => '<title>',
            '/refund' => '<title>',
            '/cover-letter-builder' => 'Cover letter',
            '/work-certificate' => '<title>',
            '/templates/public' => '<title>',
            '/blog' => '<title>',
            '/login' => 'Welcome Back',
            '/register' => 'Create your account',
            '/resume/start' => '<title>',
        ];

        foreach ($pages as $path => $needle) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<link rel="canonical"', false)
                ->assertSee('<meta property="og:title"', false)
                ->assertSee($needle, false);
        }
    }

    public function test_blog_post_is_rendered_with_article_metadata(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        BlogPost::create([
            'user_id' => $admin->id,
            'title' => 'Server rendered post',
            'slug' => 'server-rendered-post',
            'excerpt' => 'An excerpt for crawlers.',
            'content' => '<p>Hello <strong>world</strong></p><script>alert(1)</script>',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->get('/blog/server-rendered-post')
            ->assertOk()
            ->assertSee('<title>Server rendered post | HResume Blog</title>', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('BlogPosting', false)
            ->assertSee('<strong>world</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get('/blog/does-not-exist')->assertNotFound();
    }

    public function test_template_preview_returns_404_for_unknown_template(): void
    {
        $this->get('/templates/public/preview/999')->assertNotFound();

        $template = Template::create(['name' => 'Classic', 'category' => 'Corporate', 'description' => 'Test']);
        $this->get('/templates/public/preview/'.$template->id)
            ->assertOk()
            ->assertSee('data-island="template-preview"', false);
    }

    public function test_private_pages_redirect_guests_to_login(): void
    {
        foreach (['/resumes', '/profile', '/review', '/cover-letters', '/work-certificates', '/resume/edit/1', '/admin'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_signed_in_user_pages_render(): void
    {
        $this->actingAs($this->user());

        foreach (['/resumes', '/shared-with-me', '/templates', '/resume/create', '/profile', '/review', '/cover-letters', '/work-certificates'] as $path) {
            $this->get($path)->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }

        $this->get('/resume/edit/1')->assertOk()->assertSee('data-island="resume-editor"', false);
        $this->get('/cover-letter/create')->assertOk()->assertSee('data-island="cover-letter-editor"', false);
        $this->get('/work-certificate/create')->assertOk()->assertSee('data-island="work-certificate-editor"', false);
    }

    public function test_signed_in_user_is_redirected_away_from_login_and_guest_builder(): void
    {
        $this->actingAs($this->user());

        $this->get('/login')->assertRedirectContains('/resumes');
        $this->get('/resume/start')->assertRedirectContains('/resume/create');
    }

    public function test_non_admin_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->user());

        $this->get('/admin')->assertRedirectContains('/resumes');
        $this->get('/admin/users')->assertRedirectContains('/resumes');
    }

    public function test_admin_pages_render_for_admins(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]));

        foreach (['/admin', '/admin/users', '/admin/users/1', '/admin/users/1/cvs', '/admin/cvs', '/admin/cover-letters', '/admin/work-certificates', '/admin/templates', '/admin/cover-letter-templates', '/admin/ai-usage', '/admin/emails', '/admin/blog', '/admin/blog/new', '/admin/fonts', '/admin/reviews', '/admin/profile'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_banned_user_is_logged_out_of_web_pages(): void
    {
        $user = $this->user(['banned_permanently' => true, 'banned_at' => now()]);

        $this->actingAs($user)->get('/resumes')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->user());

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_locale_switch_persists_in_session(): void
    {
        $this->get('/locale/fr')->assertRedirect();
        $this->get('/pricing')->assertOk()->assertSee('<html lang="fr">', false);

        $this->get('/locale/en');
        $this->get('/pricing')->assertSee('<html lang="en">', false);
    }

    public function test_recruiter_pages_are_removed(): void
    {
        $this->get('/register/recruiter')->assertRedirect('/register');
        $this->get('/track-request')->assertRedirect('/');

        $this->actingAs($this->user());
        $this->get('/recruiter/resumes')->assertRedirectContains('/resumes');
    }

    public function test_recruiter_sign_up_is_rejected_by_the_api(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Recruiter',
            'email' => 'recruiter@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'recruiter',
            'company_name' => 'Acme',
            'industry_focus' => 'Tech',
            'compliance_accepted' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('account_type');
    }

    public function test_unknown_pages_redirect_home_but_unknown_api_routes_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertRedirect('/');
        $this->getJson('/api/this-does-not-exist')->assertNotFound();
    }

    public function test_placeholder_images_are_served_locally(): void
    {
        $this->get('/placeholder/600x800?bg=0f172a&fg=ffffff&text=Classic')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('Classic', false)
            ->assertSee('#0f172a', false);
    }

    public function test_pages_do_not_load_third_party_fonts_avatars_or_placeholders(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]));

        foreach (['/', '/pricing', '/resumes', '/profile', '/admin'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            foreach (['fonts.googleapis.com', 'fonts.gstatic.com', 'fonts.bunny.net', 'api.dicebear.com', 'via.placeholder.com'] as $host) {
                $this->assertStringNotContainsString($host, $html, "{$path} references {$host}");
            }
        }
    }

    public function test_route_names_are_unique_so_page_links_never_point_to_the_api(): void
    {
        $names = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            // Unnamed routes inside a named group only carry the group prefix (e.g. "api.") and are never used by name.
            ->filter(fn ($name) => $name && ! str_ends_with($name, '.'));

        $this->assertSame([], $names->duplicates()->values()->all(), 'Duplicate route names found');

        foreach (['blog.index', 'blog.show', 'resumes.index', 'templates.index', 'cover-letters.index', 'work-certificates.index'] as $name) {
            $this->assertStringStartsNotWith('/api/', route($name, ['slug' => 'x', 'id' => 1], false), $name);
        }
    }

    public function test_blog_list_links_to_public_post_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        BlogPost::create([
            'user_id' => $admin->id, 'title' => 'Linked post', 'slug' => 'linked-post', 'content' => '<p>x</p>',
            'status' => 'published', 'published_at' => now()->subHour(),
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('href="/blog/linked-post"', false)
            ->assertDontSee('/api/admin/blog', false);
    }
}
