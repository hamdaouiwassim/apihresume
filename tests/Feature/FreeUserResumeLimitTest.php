<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeUserResumeLimitTest extends TestCase
{
    use RefreshDatabase;

    private function template(): Template
    {
        return Template::create(['name' => 'Test Template']);
    }

    public function test_free_user_can_create_first_resume(): void
    {
        $user = User::factory()->create(['is_pro' => false]);
        $template = $this->template();

        $response = $this->actingAs($user)->postJson('/api/resumes', [
            'name' => 'My Resume',
            'template_id' => $template->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', true);
        $this->assertSame(1, $user->resumes()->count());
    }

    public function test_free_user_cannot_create_second_resume(): void
    {
        $user = User::factory()->create(['is_pro' => false]);
        $template = $this->template();
        Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Existing',
        ]);

        $response = $this->actingAs($user)->postJson('/api/resumes', [
            'name' => 'Second Resume',
            'template_id' => $template->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'resume_limit_reached')
            ->assertJsonPath('status', false);
        $this->assertSame(1, $user->resumes()->count());
    }

    public function test_pro_user_can_create_multiple_resumes(): void
    {
        $user = User::factory()->create(['is_pro' => true]);
        $template = $this->template();
        Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'First',
        ]);

        $response = $this->actingAs($user)->postJson('/api/resumes', [
            'name' => 'Second',
            'template_id' => $template->id,
        ]);

        $response->assertCreated();
        $this->assertSame(2, $user->resumes()->count());
    }

    public function test_resume_index_includes_creation_limits(): void
    {
        $user = User::factory()->create(['is_pro' => false]);
        $template = $this->template();
        Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Only one',
        ]);

        $response = $this->actingAs($user)->getJson('/api/resumes');

        $response->assertOk()
            ->assertJsonPath('limits.owned_count', 1)
            ->assertJsonPath('limits.owned_limit', 1)
            ->assertJsonPath('limits.can_create', false);
    }
}
