<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserProTest extends TestCase
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

    public function test_admin_can_grant_and_revoke_pro(): void
    {
        $user = User::factory()->create(['is_pro' => false]);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/users/{$user->id}", ['is_pro' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.is_pro', true);

        $this->assertTrue($user->fresh()->is_pro);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/users/{$user->id}", ['is_pro' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.is_pro', false);

        $this->assertFalse($user->fresh()->is_pro);
    }

    public function test_admin_cannot_grant_pro_to_unverified_user(): void
    {
        $user = User::factory()->unverified()->create(['is_pro' => false]);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/users/{$user->id}", ['is_pro' => true])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only verified users can be granted Pro access.');

        $this->assertFalse((bool) ($user->fresh()->getAttributes()['is_pro'] ?? false));
    }

    public function test_unverified_user_with_pro_flag_has_no_pro_access(): void
    {
        $user = User::factory()->unverified()->create(['is_pro' => true]);

        $this->assertFalse($user->is_pro);
        $this->assertFalse($user->hasProAccess());
    }

    public function test_pro_is_revoked_when_email_becomes_unverified(): void
    {
        $user = User::factory()->create([
            'is_pro' => true,
            'email_verified_at' => now(),
        ]);

        $user->email_verified_at = null;
        $user->save();

        $this->assertFalse((bool) $user->fresh()->getAttributes()['is_pro']);
    }

    public function test_admin_can_filter_users_by_pro_role(): void
    {
        User::factory()->create([
            'is_pro' => true,
            'email_verified_at' => now(),
            'name' => 'Pro Member',
        ]);
        User::factory()->create(['is_pro' => false, 'name' => 'Free Member']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?role=pro');

        $response->assertStatus(200);
        $names = collect($response->json('data.data'))->pluck('name');
        $this->assertTrue($names->contains('Pro Member'));
        $this->assertFalse($names->contains('Free Member'));
    }

    public function test_non_admin_cannot_update_pro_status(): void
    {
        $user = User::factory()->create(['is_pro' => false]);
        $other = User::factory()->create(['is_pro' => false]);

        $this->actingAs($other)
            ->putJson("/api/admin/users/{$user->id}", ['is_pro' => true])
            ->assertStatus(403);
    }
}
