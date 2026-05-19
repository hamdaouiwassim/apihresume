<?php

namespace Tests\Feature\Admin;

use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AiTokenLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiTokenLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['monetization.default_monthly_token_limit' => 1000]);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_token_limit_service_blocks_when_over_cap(): void
    {
        $user = User::factory()->create([
            'is_pro' => false,
            'ai_monthly_token_limit' => 500,
        ]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'kind' => 'enhance_text',
            'provider' => 'groq',
            'prompt_tokens' => 400,
            'completion_tokens' => 200,
            'total_tokens' => 600,
        ]);

        $service = app(AiTokenLimitService::class);
        $this->assertFalse($service->hasTokenBudget($user));
    }

    public function test_admin_can_update_user_token_limit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/ai-usage/users/{$user->id}/token-limit", [
                'ai_monthly_token_limit' => 25000,
            ])
            ->assertOk()
            ->assertJsonPath('data.ai_monthly_token_limit', 25000);

        $this->assertSame(25000, $user->fresh()->ai_monthly_token_limit);
    }

    public function test_admin_can_reset_limit_to_default_with_null(): void
    {
        $user = User::factory()->create(['ai_monthly_token_limit' => 100]);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/ai-usage/users/{$user->id}/token-limit", [
                'ai_monthly_token_limit' => null,
            ])
            ->assertOk();

        $this->assertNull($user->fresh()->ai_monthly_token_limit);
    }

    public function test_pro_user_default_token_limit_is_fifty_thousand(): void
    {
        config(['monetization.pro_monthly_token_limit' => 50000]);

        $pro = User::factory()->create([
            'is_pro' => true,
            'is_admin' => false,
            'ai_monthly_token_limit' => null,
        ]);

        $service = app(AiTokenLimitService::class);

        $this->assertSame(50000, $service->monthlyLimit($pro));
        $this->assertFalse($service->isUnlimitedByRole($pro));
        $snap = $service->snapshot($pro);
        $this->assertFalse($snap['is_unlimited']);
        $this->assertSame('pro', $snap['plan']);
        $this->assertSame(50000, $snap['credits_total']);
    }

    public function test_summary_includes_by_day(): void
    {
        $user = User::factory()->create();
        AiUsageLog::create([
            'user_id' => $user->id,
            'kind' => 'enhance_text',
            'provider' => 'openai',
            'total_tokens' => 100,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/ai-usage/summary');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.by_day'));
    }
}
