<?php

namespace Tests\Feature\Admin;

use App\Models\Recruiter;
use App\Models\User;
use App\Services\UserBanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserBanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_ban_user_for_seven_days(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/ban", [
                'duration' => UserBanService::DURATION_7_DAYS,
                'reason' => 'Spam resumes',
            ])
            ->assertOk()
            ->assertJsonPath('data.ban.is_banned', true);

        $user->refresh();
        $this->assertTrue(app(UserBanService::class)->isBanned($user));
        $this->assertFalse($user->banned_permanently);
        $this->assertNotNull($user->banned_until);
    }

    public function test_admin_can_permanently_ban_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/ban", [
                'duration' => UserBanService::DURATION_PERMANENT,
            ])
            ->assertOk();

        $user->refresh();
        $this->assertTrue($user->banned_permanently);
        $this->assertNull($user->banned_until);
    }

    public function test_banned_user_cannot_access_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        app(UserBanService::class)->ban($user, $this->admin, UserBanService::DURATION_3_DAYS);

        Sanctum::actingAs($user->fresh());

        $this->getJson('/api/me')
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_banned');
    }

    public function test_banned_recruiter_cannot_access_recruiter_api(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_recruiter' => true,
        ]);
        Recruiter::query()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'company_name' => 'Acme Hiring',
            'industry_focus' => 'Technology',
            'compliance_accepted' => true,
        ]);

        app(UserBanService::class)->ban($user, $this->admin, UserBanService::DURATION_7_DAYS);

        Sanctum::actingAs($user->fresh());

        $this->getJson('/api/recruiter/resumes')
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_banned');
    }

    public function test_login_rejects_banned_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);
        app(UserBanService::class)->ban($user, $this->admin, UserBanService::DURATION_15_DAYS);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_banned');
    }

    public function test_admin_can_unban_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        app(UserBanService::class)->ban($user, $this->admin, UserBanService::DURATION_1_MONTH);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/unban")
            ->assertOk()
            ->assertJsonPath('data.ban.is_banned', false);

        $this->assertFalse(app(UserBanService::class)->isBanned($user->fresh()));
    }

    public function test_cannot_ban_admin(): void
    {
        $otherAdmin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$otherAdmin->id}/ban", [
                'duration' => UserBanService::DURATION_7_DAYS,
            ])
            ->assertStatus(422);
    }
}
