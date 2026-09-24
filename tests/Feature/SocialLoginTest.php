<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Google / LinkedIn sign-in: provider callback -> /auth/social-callback?code=... -> code exchange -> web session.
 */
class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config([
            'app.frontend_url' => 'http://localhost',
            'services.google.client_id' => 'google-id',
            'services.google.client_secret' => 'google-secret',
            'services.linkedin-openid.client_id' => 'linkedin-id',
            'services.linkedin-openid.client_secret' => 'linkedin-secret',
        ]);
    }

    private function fakeProvider(string $driver, string $email, string $id): void
    {
        $socialUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => 'Social Person',
            'email' => $email,
            'avatar' => null,
        ]);
        $socialUser->token = 'provider-token';

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless', 'scopes', 'with')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }

    /** Follows the provider callback and returns the one-time code from the redirect. */
    private function callbackCode(string $callbackPath): string
    {
        $location = $this->get($callbackPath)->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('http://localhost/auth/social-callback?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('success', $query['status']);
        $this->assertNotEmpty($query['code']);

        return $query['code'];
    }

    /** Same-origin XHR from the Blade social-callback page. */
    private function exchange(string $code)
    {
        return $this->withHeaders(['Referer' => 'http://localhost/auth/social-callback', 'Accept' => 'application/json'])
            ->postJson('/api/auth/social/exchange', ['code' => $code]);
    }

    public function test_google_sign_in_creates_a_web_session(): void
    {
        $this->fakeProvider('google', 'new-google@example.com', 'g-123');

        $code = $this->callbackCode('/api/auth/google/callback?code=provider-code');
        $this->assertGuest();

        // The browser lands on the Blade page, which exchanges the code server-side (no CSRF-protected XHR).
        $this->get('/auth/social-callback?status=success&provider=google&code='.$code)
            ->assertRedirect(route('resumes.index'))
            ->assertSessionHas('success', 'Signed in with Google');

        $user = User::where('email', 'new-google@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $user->tokens()->count(), 'The one-time API token is revoked once the session exists');
        $this->get('/resumes')->assertOk();
    }

    public function test_linkedin_sign_in_logs_in_an_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);
        $this->fakeProvider('linkedin-openid', 'member@example.com', 'li-456');

        $code = $this->callbackCode('/api/auth/linkedin/callback?code=provider-code');
        $this->exchange($code)->assertOk()->assertJsonPath('provider', 'linkedin');

        $this->assertAuthenticatedAs($existing);
    }

    public function test_admin_lands_on_the_admin_dashboard(): void
    {
        User::factory()->create(['email' => 'boss@example.com', 'is_admin' => true, 'email_verified_at' => now()]);
        $this->fakeProvider('google', 'boss@example.com', 'g-9');

        $code = $this->callbackCode('/api/auth/google/callback?code=provider-code');
        $this->get('/auth/social-callback?status=success&provider=google&code='.$code)
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_callback_page_shows_an_error_for_an_invalid_code(): void
    {
        $this->get('/auth/social-callback?status=success&provider=google&code='.str_repeat('x', 64))
            ->assertOk()
            ->assertSee('invalid or has expired', false);
        $this->assertGuest();
    }

    public function test_invalid_code_is_rejected(): void
    {
        $this->exchange(str_repeat('x', 64))->assertStatus(410);
        $this->assertGuest();
    }

    public function test_banned_user_is_not_signed_in(): void
    {
        User::factory()->create(['email' => 'banned@example.com', 'email_verified_at' => now(), 'banned_permanently' => true, 'banned_at' => now()]);
        $this->fakeProvider('google', 'banned@example.com', 'g-7');

        $location = $this->get('/api/auth/google/callback?code=provider-code')->assertRedirect()->headers->get('Location');
        $this->assertStringContainsString('status=error', $location);
        $this->assertGuest();
    }

    public function test_provider_error_redirects_to_the_callback_page_with_a_message(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless', 'scopes', 'with')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('invalid_grant'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $location = $this->get('/api/auth/google/callback?code=bad')->assertRedirect()->headers->get('Location');
        $this->assertStringContainsString('/auth/social-callback?status=error', $location);

        $this->get('/auth/social-callback?status=error&provider=google&message=Nope')->assertOk();
    }

    public function test_sign_in_urls_point_to_the_providers(): void
    {
        $this->getJson('/api/auth/google/url')->assertOk()->assertJsonStructure(['url']);
        $this->getJson('/api/auth/linkedin/url')->assertOk()->assertJsonStructure(['url']);
    }
}
