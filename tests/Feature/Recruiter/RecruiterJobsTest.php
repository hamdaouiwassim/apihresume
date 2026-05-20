<?php

namespace Tests\Feature\Recruiter;

use App\Models\Recruiter;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterJobsTest extends TestCase
{
    use RefreshDatabase;

    private function approvedRecruiter(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_recruiter' => true,
        ]);
        Recruiter::create([
            'user_id' => $user->id,
            'status' => 'approved',
            'company_name' => 'Acme',
            'industry_focus' => 'Tech',
            'compliance_accepted' => true,
        ]);

        return $user;
    }

    private function candidate(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function makeResume(User $owner, array $attrs = []): Resume
    {
        $template = Template::create([
            'name' => 'Test',
            'description' => 'Test',
            'category' => 'Corporate',
        ]);

        return Resume::create(array_merge([
            'user_id' => $owner->id,
            'template_id' => $template->id,
            'name' => 'CV',
        ], $attrs));
    }

    public function test_candidate_can_apply_to_open_job(): void
    {
        $recruiter = $this->approvedRecruiter();
        $candidate = $this->candidate();
        $resume = $this->makeResume($candidate);

        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Engineer',
            'description' => str_repeat('Build great things. ', 5),
            'status' => 'open',
            'slug' => 'engineer-role',
        ]);

        $this->actingAs($candidate)
            ->postJson("/api/jobs/{$job->slug}/apply", [
                'resume_id' => $resume->id,
                'cover_note' => 'Interested!',
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->actingAs($recruiter)
            ->getJson("/api/recruiter/resumes/{$resume->id}")
            ->assertOk();
    }

    public function test_recruiter_can_create_job(): void
    {
        $recruiter = $this->approvedRecruiter();

        $this->actingAs($recruiter)
            ->postJson('/api/recruiter/jobs', [
                'title' => 'Designer',
                'description' => str_repeat('Design systems. ', 5),
                'status' => 'open',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Designer');
    }
}
