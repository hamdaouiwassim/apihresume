<?php

namespace Tests\Feature\Admin;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiUsageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
        $this->member = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_list_ai_usage_logs(): void
    {
        AiUsageLog::create([
            'user_id' => $this->member->id,
            'kind' => 'enhance_text',
            'resume_id' => null,
            'provider' => 'groq',
            'model' => 'test-model',
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/ai-usage/logs?per_page=10');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.data.0.kind', 'enhance_text')
            ->assertJsonPath('data.data.0.total_tokens', 30);
    }

    public function test_non_admin_cannot_access_ai_usage(): void
    {
        $this->actingAs($this->member)
            ->getJson('/api/admin/ai-usage/logs')
            ->assertForbidden();

        $this->actingAs($this->member)
            ->getJson('/api/admin/ai-usage/summary')
            ->assertForbidden();
    }

    public function test_admin_summary_returns_totals(): void
    {
        AiUsageLog::create([
            'user_id' => $this->member->id,
            'kind' => 'tailor_resume',
            'resume_id' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'total_tokens' => 150,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/admin/ai-usage/summary')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.totals.calls', 1)
            ->assertJsonPath('data.totals.total_tokens', 150);
    }
}
