<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneUnverifiedAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_unverified_account_past_grace_period(): void
    {
        config(['auth.unverified_account_deletion_days' => 7]);

        $stale = User::factory()->unverified()->create([
            'created_at' => now()->subDays(10),
        ]);

        $recent = User::factory()->unverified()->create([
            'created_at' => now()->subDays(2),
        ]);

        $verified = User::factory()->create([
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('accounts:prune-unverified')
            ->assertSuccessful()
            ->expectsOutputToContain('Soft-deleted 1');

        $this->assertSoftDeleted('users', ['id' => $stale->id]);
        $this->assertDatabaseHas('users', ['id' => $recent->id]);
        $this->assertDatabaseHas('users', ['id' => $verified->id]);
    }

    public function test_dry_run_does_not_delete(): void
    {
        config(['auth.unverified_account_deletion_days' => 1]);

        $user = User::factory()->unverified()->create([
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('accounts:prune-unverified', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('would be soft-deleted');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_never_prunes_admin_accounts(): void
    {
        config(['auth.unverified_account_deletion_days' => 1]);

        $admin = User::factory()->unverified()->create([
            'is_admin' => true,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('accounts:prune-unverified')->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_pruning_soft_deletes_user_resumes(): void
    {
        config(['auth.unverified_account_deletion_days' => 1]);

        $user = User::factory()->unverified()->create([
            'created_at' => now()->subDays(10),
        ]);

        $template = Template::create([
            'name' => 'Classic',
            'description' => 'Test',
            'category' => 'professional',
        ]);

        $resume = Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Draft',
        ]);

        $this->artisan('accounts:prune-unverified')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSoftDeleted('resumes', ['id' => $resume->id]);
    }

    public function test_admin_can_restore_soft_deleted_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
        $user = User::factory()->unverified()->create();

        $user->delete();

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$user->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertNull($user->fresh()->deleted_at);
    }
}
