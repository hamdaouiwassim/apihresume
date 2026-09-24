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

    public function test_home_page_has_a_single_descriptive_h1(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('>Create a CV Recruiters Actually Read</h1>', $html);
        $this->assertStringContainsString('Free AI-powered CV builder for ATS-friendly resumes, professional templates, cover letters, and PDF export.', $html);

        $this->flushSession();
        $this->get('/fr')->assertSee('>Créez un CV que les recruteurs lisent vraiment</h1>', false);
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

    public function test_home_page_links_web_application_to_organization_and_website(): void
    {
        config(['app.frontend_url' => 'https://hresume.pro']);

        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $m);
        $graph = collect(json_decode($m[1], true)['@graph'])->keyBy('@type');

        $this->assertSame('https://hresume.pro/#organization', $graph['Organization']['@id']);
        $this->assertSame('https://hresume.pro/logo.png', $graph['Organization']['logo']['url']);
        $this->assertSame('https://hresume.pro/#website', $graph['WebSite']['@id']);
        $this->assertSame(['@id' => 'https://hresume.pro/#organization'], $graph['WebSite']['publisher']);
        $this->assertSame(['@id' => 'https://hresume.pro/#organization'], $graph['WebApplication']['publisher']);
        $this->assertSame(['@id' => 'https://hresume.pro/#website'], $graph['WebApplication']['isPartOf']);
    }

    public function test_template_pages_have_slug_urls_and_server_rendered_content(): void
    {
        config(['app.frontend_url' => 'https://hresume.pro']);
        $classic = Template::create(['name' => 'Classic', 'category' => 'Corporate', 'description' => 'Traditional layout for corporate roles.']);
        $split = Template::create(['name' => 'Nordic Split', 'category' => 'Corporate', 'description' => 'Split-column layout.']);

        $this->assertSame('classic', $classic->slug);
        $this->assertSame('nordic-split', $split->slug);

        $this->get('/templates/classic')->assertOk()
            ->assertSee('<h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">Classic resume template</h1>', false)
            ->assertSee('Traditional layout for corporate roles.', false)
            ->assertSee('<link rel="canonical" href="https://hresume.pro/templates/classic">', false)
            ->assertSee('<link rel="alternate" hreflang="fr" href="https://hresume.pro/fr/templates/classic">', false)
            ->assertSee('BreadcrumbList', false)
            ->assertSee('data-island="template-preview"', false)
            ->assertSee('/resume/start?template='.$classic->id, false)
            ->assertSee('/templates/nordic-split', false);

        $this->flushSession();
        $this->get('/fr/templates/classic')->assertOk()
            ->assertSee('Modèle de CV Classic', false)
            ->assertSee('<link rel="canonical" href="https://hresume.pro/fr/templates/classic">', false);

        $this->flushSession();
        $this->get('/templates/does-not-exist')->assertNotFound()->assertSee('noindex', false);
        $this->get('/templates/public')->assertOk()->assertSee('/templates/classic', false)->assertSee('/templates/nordic-split', false);
    }

    public function test_old_template_preview_urls_redirect_permanently(): void
    {
        $template = Template::create(['name' => 'Classic', 'category' => 'Corporate']);

        $this->get('/templates/public/preview/'.$template->id)->assertStatus(301)->assertRedirectContains('/templates/classic');
        $this->flushSession();
        $this->get('/fr/templates/public/preview/'.$template->id)->assertStatus(301)->assertRedirectContains('/fr/templates/classic');
        $this->flushSession();
        $this->get('/templates/public/preview/999')->assertNotFound();

        // The signed-in app preview keeps its id URL.
        $this->actingAs($this->user());
        $this->get('/templates/preview/'.$template->id)->assertOk();
    }

    public function test_slugs_are_unique_and_never_collide_with_fixed_paths(): void
    {
        $this->assertSame('classic', Template::create(['name' => 'Classic'])->slug);
        $this->assertSame('classic-2', Template::create(['name' => 'Classic'])->slug);
        $this->assertSame('public-template', Template::create(['name' => 'Public'])->slug);

        $renamed = Template::where('slug', 'classic')->first();
        $renamed->update(['name' => 'Classic Pro']);
        $this->assertSame('classic', $renamed->fresh()->slug, 'Renaming keeps the indexed URL');
    }

    public function test_home_template_cards_have_descriptive_alt_and_dimensions(): void
    {
        Template::create(['name' => 'Classic', 'category' => 'Corporate', 'preview_image_url' => 'https://cdn.example.com/classic.png']);

        $this->get('/')->assertOk()
            ->assertSee('alt="Classic ATS-friendly resume template"', false)
            ->assertSee('width="600" height="848"', false)
            ->assertSee('/templates/classic', false);
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
        // Private pages follow the session language.
        $this->actingAs($this->user());
        $this->get('/locale/fr')->assertRedirect();
        $this->get('/profile')->assertOk()->assertSee('<html lang="fr">', false);

        $this->get('/locale/en');
        $this->get('/profile')->assertSee('<html lang="en">', false);
    }

    public function test_french_pages_have_their_own_urls_with_reciprocal_hreflang(): void
    {
        config(['app.frontend_url' => 'https://hresume.pro']);

        $alternates = [
            '<link rel="alternate" hreflang="en" href="https://hresume.pro/pricing">',
            '<link rel="alternate" hreflang="fr" href="https://hresume.pro/fr/pricing">',
            '<link rel="alternate" hreflang="x-default" href="https://hresume.pro/pricing">',
        ];

        $en = $this->get('/pricing')->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('<link rel="canonical" href="https://hresume.pro/pricing">', false)
            ->assertDontSee('name="language"', false);
        $fr = $this->get('/fr/pricing')->assertOk()
            ->assertSee('<html lang="fr">', false)
            ->assertSee('<link rel="canonical" href="https://hresume.pro/fr/pricing">', false);

        foreach ($alternates as $tag) {
            $en->assertSee($tag, false);
            $fr->assertSee($tag, false);
        }

        $this->flushSession();
        $this->get('/fr')->assertOk()
            ->assertSee('<link rel="canonical" href="https://hresume.pro/fr">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="https://hresume.pro/">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="https://hresume.pro/">', false);
    }

    public function test_crawlers_get_the_language_of_the_url_and_visitors_their_chosen_language(): void
    {
        // No session (crawler): each URL renders in its own language, no redirect.
        $this->get('/faq')->assertOk()->assertSee('<html lang="en">', false);
        $this->flushSession();
        $this->get('/fr/faq')->assertOk()->assertSee('<html lang="fr">', false);

        // A visitor who chose French is sent from the English URL to the French one.
        $this->get('/faq?x=1')->assertRedirect('/fr/faq?x=1');

        // The toggle goes to the other language's URL of the same page.
        $this->from('/fr/faq')->get('/locale/en')->assertRedirect('/faq');
        $this->get('/faq')->assertOk()->assertSee('<html lang="en">', false);
        $this->from('/faq')->get('/locale/fr')->assertRedirect('/fr/faq');

        // Pages without a French URL keep theirs.
        $this->from('/blog')->get('/locale/fr')->assertRedirect('/blog');
    }

    public function test_french_pages_have_french_meta_and_titles_are_escaped_once(): void
    {
        $this->get('/fr')->assertOk()
            ->assertSee('<title>HResume - CV, lettre de motivation et attestation de travail gratuits</title>', false)
            ->assertSee('Créez gratuitement des CV compatibles ATS', false);

        $this->flushSession();
        $this->get('/fr/terms')->assertOk()
            ->assertSee('<title>Conditions générales d&#039;utilisation | HResume</title>', false)
            ->assertDontSee('&amp;#039;', false);
    }

    /** Paths of the page's <a href> links to this site (host-agnostic). */
    private function linkPaths(string $html): array
    {
        preg_match_all('#<a\s[^>]*href="https?://[^/"]+(/[^"?\#]*)#', $html, $m);

        return array_values(array_unique($m[1]));
    }

    public function test_french_pages_link_to_french_urls(): void
    {
        $links = $this->linkPaths($this->get('/fr')->assertOk()->getContent());

        foreach (['/fr/pricing', '/fr/faq', '/fr/contact', '/fr/terms', '/fr/privacy', '/fr/refund', '/fr/templates/public', '/fr/cover-letter-builder', '/fr/work-certificate'] as $path) {
            $this->assertContains($path, $links);
        }
        // No English URL of a page that has a French version; pages without one keep their URL.
        foreach (['/pricing', '/faq', '/contact', '/terms', '/privacy', '/refund', '/templates/public', '/cover-letter-builder', '/work-certificate'] as $path) {
            $this->assertNotContains($path, $links);
        }
        $this->assertContains('/blog', $links);
        $this->assertNotContains('/fr/blog', $links);

        $this->flushSession();
        $links = $this->linkPaths($this->get('/pricing')->assertOk()->getContent());
        $this->assertContains('/faq', $links);
        $this->assertEmpty(array_filter($links, fn ($path) => str_starts_with($path, '/fr')));
    }

    public function test_single_language_pages_have_no_hreflang(): void
    {
        $this->get('/blog')->assertOk()->assertDontSee('hreflang', false);
        $this->get('/fr/blog')->assertRedirect('/');
    }

    public function test_sitemap_lists_french_urls_with_alternates(): void
    {
        config(['sitemap.base_url' => 'https://hresume.pro']);
        app(\App\Services\SitemapService::class)->bustCache();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<loc>https://hresume.pro/fr/pricing</loc>', false)
            ->assertSee('<loc>https://hresume.pro/fr</loc>', false)
            ->assertSee('hreflang="fr" href="https://hresume.pro/fr/pricing"', false)
            ->assertSee('hreflang="x-default" href="https://hresume.pro/pricing"', false)
            ->assertDontSee('?lang=fr', false)
            ->assertDontSee('https://hresume.pro/fr/blog', false);
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

    public function test_old_spa_chunks_return_404_and_clear_the_browser_cache(): void
    {
        foreach (['/assets/login-BOnU3Ery.js', '/assets/welcome-DM_XirWM.jsx', '/registerSW.js', '/manifest.json'] as $path) {
            $this->get($path)
                ->assertNotFound()
                ->assertHeader('Clear-Site-Data', '"cache"')
                ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        $this->get('/old-spa-page')->assertRedirect('/')->assertHeaderMissing('Clear-Site-Data');
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
