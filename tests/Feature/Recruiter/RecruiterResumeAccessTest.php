<?php

namespace Tests\Feature\Recruiter;

use App\Models\Recruiter;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecruiterResumeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $recruiterUser;

    protected User $candidate;

    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = Template::create([
            'name' => 'Test',
            'description' => 'Test template',
            'category' => 'Corporate',
        ]);

        $this->recruiterUser = User::factory()->create([
            'is_recruiter' => true,
            'email_verified_at' => now(),
        ]);
        Recruiter::create([
            'user_id' => $this->recruiterUser->id,
            'status' => 'approved',
            'company_name' => 'Acme',
            'industry_focus' => 'Tech',
            'compliance_accepted' => true,
        ]);

        $this->candidate = User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_recruiter_cannot_see_non_opted_in_resume(): void
    {
        $resume = $this->makeResume(['open_to_recruiters' => false]);

        Sanctum::actingAs($this->recruiterUser);

        $this->getJson('/api/recruiter/resumes/'.$resume->id)
            ->assertStatus(403);

        $this->getJson('/api/recruiter/resumes')
            ->assertOk()
            ->assertJsonPath('data.total', 0);
    }

    public function test_recruiter_sees_opted_in_resume_without_email_in_list(): void
    {
        $resume = $this->makeResume([
            'open_to_recruiters' => true,
            'recruiter_visible_at' => now(),
        ]);

        Sanctum::actingAs($this->recruiterUser);

        $response = $this->getJson('/api/recruiter/resumes')
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $row = $response->json('data.data.0');
        $this->assertNull($row['user']['email'] ?? null);
    }

    public function test_recruiter_sees_resume_after_share_grant(): void
    {
        $resume = $this->makeResume(['open_to_recruiters' => false]);

        app(RecruiterResumeAccessService::class)->grantAccess(
            $resume->id,
            $this->recruiterUser->id,
            $this->candidate->id,
            'share',
        );

        Sanctum::actingAs($this->recruiterUser);

        $this->getJson('/api/recruiter/resumes/'.$resume->id)
            ->assertOk()
            ->assertJsonPath('data.id', $resume->id);
    }

    public function test_candidate_can_toggle_recruiter_visibility(): void
    {
        $resume = $this->makeResume(['open_to_recruiters' => false]);

        Sanctum::actingAs($this->candidate);

        $this->patchJson('/api/resumes/'.$resume->id.'/recruiter-visibility', [
            'open_to_recruiters' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.open_to_recruiters', true);

        $this->assertDatabaseHas('resumes', [
            'id' => $resume->id,
            'open_to_recruiters' => 1,
        ]);
    }

    public function test_recruiter_dashboard_returns_counts(): void
    {
        $this->makeResume(['open_to_recruiters' => true]);

        Sanctum::actingAs($this->recruiterUser);

        $this->getJson('/api/recruiter/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'talent_pool_count',
                    'visible_resumes_count',
                    'applications_today',
                    'open_jobs_count',
                    'shortlists_count',
                ],
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeResume(array $overrides = []): Resume
    {
        return Resume::create(array_merge([
            'user_id' => $this->candidate->id,
            'template_id' => $this->template->id,
            'name' => 'Test CV',
            'open_to_recruiters' => false,
        ], $overrides));
    }
}
