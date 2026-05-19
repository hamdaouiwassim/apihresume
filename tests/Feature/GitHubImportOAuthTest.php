<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubImportOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.github.client_id' => 'test-client-id',
            'services.github.client_secret' => 'test-client-secret',
            'services.github.redirect_uri' => 'http://localhost/api/auth/github/import/callback',
        ]);
        config(['app.frontend_url' => 'http://frontend.test']);
    }

    public function test_import_url_returns_503_when_oauth_not_configured(): void
    {
        config([
            'services.github.client_id' => null,
            'services.github.client_secret' => null,
            'services.github.redirect_uri' => null,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/auth/github/import/url')
            ->assertStatus(503)
            ->assertJsonPath('status', false);
    }

    public function test_import_url_returns_authorize_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/github/import/url?return_to=/profile');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('oauth_callback_url', 'http://localhost/api/auth/github/import/callback');

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringContainsString('https://github.com/login/oauth/authorize?', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('scope=repo', $url);
        $this->assertStringContainsString(
            'redirect_uri='.urlencode('http://localhost/api/auth/github/import/callback'),
            $url
        );
    }

    public function test_callback_exchanges_code_and_redirects_with_success(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            if (str_contains($url, 'github.com/login/oauth/access_token')) {
                return Http::response(['access_token' => 'gho_test_token'], 200);
            }
            if (str_contains($url, 'api.github.com/user')) {
                return Http::response(['login' => 'octocat'], 200);
            }

            return Http::response(['message' => 'unexpected: '.$url], 500);
        });

        $user = User::factory()->create();
        $state = Crypt::encryptString(json_encode([
            'uid' => $user->id,
            't' => time(),
            'return' => '/profile',
        ], JSON_THROW_ON_ERROR));

        $response = $this->get('/api/auth/github/import/callback?code=test-code&state='.urlencode($state));

        $response->assertRedirect();
        $this->assertStringContainsString('http://frontend.test/profile', $response->headers->get('Location'));
        $this->assertStringContainsString('github_import=success', $response->headers->get('Location'));

        $user->refresh();
        $this->assertSame('gho_test_token', $user->github_import_token);
        $this->assertSame('octocat', $user->github_import_login);
        $this->assertNotNull($user->github_import_connected_at);
    }

    public function test_disconnect_clears_github_import_fields(): void
    {
        $user = User::factory()->create([
            'github_import_token' => 'secret',
            'github_import_login' => 'octocat',
            'github_import_connected_at' => now(),
        ]);

        $this->actingAs($user)->postJson('/api/auth/github/import/disconnect')
            ->assertOk()
            ->assertJsonPath('status', true);

        $user->refresh();
        $this->assertNull($user->github_import_token);
        $this->assertNull($user->github_import_login);
        $this->assertNull($user->github_import_connected_at);
    }
}
