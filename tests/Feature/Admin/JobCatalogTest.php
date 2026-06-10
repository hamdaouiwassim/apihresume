<?php

namespace Tests\Feature\Admin;

use App\Models\JobEducationCatalog;
use App\Models\JobSkillCatalog;
use App\Models\User;
use Database\Seeders\JobCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
    }

    public function test_seeder_populates_catalogs(): void
    {
        $this->seed(JobCatalogSeeder::class);

        $this->assertGreaterThan(10, JobSkillCatalog::count());
        $this->assertGreaterThan(5, JobEducationCatalog::count());
    }

    public function test_public_lists_only_active_items(): void
    {
        $this->seed(JobCatalogSeeder::class);
        JobSkillCatalog::where('name', 'React')->update(['is_active' => false]);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->getJson('/api/job-catalog/skills')
            ->assertOk()
            ->assertJsonMissing(['name' => 'React']);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/job-catalog/skills')
            ->assertOk()
            ->assertJsonFragment(['name' => 'React']);
    }

    public function test_admin_can_create_skill(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/job-catalog/skills', ['name' => 'Rust'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Rust');

        $this->assertDatabaseHas('job_skill_catalog', ['name' => 'Rust', 'is_active' => true]);
    }
}
