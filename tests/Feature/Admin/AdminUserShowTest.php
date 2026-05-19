<?php

namespace Tests\Feature\Admin;

use App\Models\AiUsageLog;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_show_includes_ai_usage_and_all_resumes(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $template = Template::create([
            'name' => 'Classic',
            'description' => 'Test',
            'category' => 'professional',
        ]);
        $resume = Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Test CV',
        ]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'kind' => 'enhance_text',
            'provider' => 'groq',
            'total_tokens' => 120,
        ]);

        $response = $this->actingAs($admin)->getJson("/api/admin/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.ai_tokens.tokens_used', 120)
            ->assertJsonPath('data.ai_usage.totals.calls', 1)
            ->assertJsonStructure([
                'data' => [
                    'ai_usage' => ['totals', 'by_kind'],
                    'recent_ai_logs',
                    'resumes',
                ],
            ]);

        $resumeIds = collect($response->json('data.resumes'))->pluck('id')->all();
        $this->assertContains($resume->id, $resumeIds);
    }

    public function test_admin_can_delete_resume(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $template = Template::create([
            'name' => 'Classic',
            'description' => 'Test',
            'category' => 'professional',
        ]);
        $resume = Resume::create([
            'user_id' => User::factory()->create()->id,
            'template_id' => $template->id,
            'name' => 'To delete',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/resumes/{$resume->id}")
            ->assertOk();

        $this->assertDatabaseMissing('resumes', ['id' => $resume->id]);
    }
}
