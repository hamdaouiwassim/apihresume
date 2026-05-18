<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubProjectImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Resume}
     */
    private function makeOwnerResume(): array
    {
        $user = User::factory()->create();
        $template = Template::create(['name' => 'Test Template']);
        $resume = Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Test Resume',
        ]);

        return [$user, $resume];
    }

    public function test_preview_returns_draft_from_github(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            if (str_ends_with($url, '/repos/octocat/Hello-World/languages')) {
                return Http::response([
                    'Ruby' => 12_345,
                    'HTML' => 3000,
                ], 200);
            }
            if (str_contains($url, '/repos/octocat/Hello-World') && ! str_contains($url, '/languages')) {
                return Http::response([
                    'name' => 'Hello-World',
                    'html_url' => 'https://github.com/octocat/Hello-World',
                    'description' => 'My first repository on GitHub.',
                    'created_at' => '2011-01-26T19:06:43Z',
                    'default_branch' => 'main',
                ], 200);
            }

            return Http::response(['message' => 'unexpected URL: '.$url], 500);
        });

        [$user, $resume] = $this->makeOwnerResume();

        $response = $this->actingAs($user)->postJson("/api/resumes/{$resume->id}/github-repo-preview", [
            'repo_url' => 'https://github.com/octocat/Hello-World',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.name', 'Hello-World')
            ->assertJsonPath('data.url', 'https://github.com/octocat/Hello-World')
            ->assertJsonPath('data.description', 'My first repository on GitHub.')
            ->assertJsonPath('data.technologies', 'Ruby, HTML')
            ->assertJsonPath('data.startDate', '2011-01-26')
            ->assertJsonPath('data.endDate', null)
            ->assertJsonPath('data.experience_id', null);
    }

    public function test_invalid_url_returns_422(): void
    {
        [$user, $resume] = $this->makeOwnerResume();
        Http::fake();

        $response = $this->actingAs($user)->postJson("/api/resumes/{$resume->id}/github-repo-preview", [
            'repo_url' => 'https://evil.example.com/hook',
        ]);

        $response->assertStatus(422)->assertJsonPath('status', false);
    }

    public function test_other_users_resume_returns_403(): void
    {
        Http::fake();
        [$user, $resume] = $this->makeOwnerResume();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->postJson("/api/resumes/{$resume->id}/github-repo-preview", [
            'repo_url' => 'octocat/Hello-World',
        ]);

        $response->assertForbidden()->assertJsonPath('status', false);
    }

    public function test_experience_must_belong_to_resume(): void
    {
        Http::fake();
        [$user, $resume] = $this->makeOwnerResume();
        $template = Template::create(['name' => 'T2']);
        $otherResume = Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Other',
        ]);
        $exp = Experience::create([
            'resume_id' => $otherResume->id,
            'company' => 'Acme',
            'position' => 'Dev',
            'startDate' => '2020-01-01',
            'endDate' => null,
            'description' => 'Work',
            'is_present' => true,
        ]);

        $response = $this->actingAs($user)->postJson("/api/resumes/{$resume->id}/github-repo-preview", [
            'repo_url' => 'octocat/Hello-World',
            'experience_id' => $exp->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('status', false);
    }

    public function test_preview_prefers_user_github_oauth_token_over_server_pat(): void
    {
        config(['services.github.token' => 'server-pat']);

        $seenTokens = [];
        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$seenTokens) {
            $url = $request->url();
            $auth = $request->header('Authorization');
            $seenTokens[] = is_array($auth) ? ($auth[0] ?? '') : (string) $auth;

            if (str_ends_with($url, '/repos/me/priv/languages')) {
                return Http::response(['Go' => 100], 200);
            }
            if (str_contains($url, '/repos/me/priv') && ! str_contains($url, '/languages')) {
                return Http::response([
                    'name' => 'priv',
                    'html_url' => 'https://github.com/me/priv',
                    'description' => 'Private repo',
                    'created_at' => '2020-01-01T00:00:00Z',
                    'default_branch' => null,
                ], 200);
            }

            return Http::response(['message' => 'unexpected URL: '.$url], 500);
        });

        [$user, $resume] = $this->makeOwnerResume();
        $user->github_import_token = 'user-oauth';
        $user->save();

        $response = $this->actingAs($user)->postJson("/api/resumes/{$resume->id}/github-repo-preview", [
            'repo_url' => 'https://github.com/me/priv',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'priv');

        $this->assertNotEmpty($seenTokens);
        $this->assertStringContainsString('Bearer user-oauth', $seenTokens[0]);
    }
}
