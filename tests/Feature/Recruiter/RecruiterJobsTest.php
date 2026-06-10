<?php

namespace Tests\Feature\Recruiter;

use App\Models\Recruiter;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\JobCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterJobsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JobCatalogSeeder::class);
    }

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

    public function test_candidate_cannot_apply_twice_to_same_job(): void
    {
        $recruiter = $this->approvedRecruiter();
        $candidate = $this->candidate();
        $resume = $this->makeResume($candidate);
        $otherResume = $this->makeResume($candidate, ['name' => 'Other CV']);

        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Designer',
            'description' => str_repeat('Design role. ', 5),
            'status' => 'open',
            'slug' => 'designer-once',
        ]);

        $this->actingAs($candidate)
            ->postJson("/api/jobs/{$job->slug}/apply", ['resume_id' => $resume->id])
            ->assertCreated();

        $this->actingAs($candidate)
            ->postJson("/api/jobs/{$job->slug}/apply", ['resume_id' => $otherResume->id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'already_applied');

        $this->actingAs($candidate)
            ->getJson("/api/jobs/{$job->slug}/application-status")
            ->assertOk()
            ->assertJsonPath('data.has_applied', true);
    }

    public function test_candidate_can_apply_with_cover_letter(): void
    {
        $recruiter = $this->approvedRecruiter();
        $candidate = $this->candidate();
        $resume = $this->makeResume($candidate);

        $coverLetter = $candidate->coverLetters()->create([
            'title' => 'Engineer CL',
            'subject' => 'Application for Engineer',
            'content' => 'I am very interested in this role.',
        ]);

        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Engineer',
            'description' => str_repeat('Build great things. ', 5),
            'status' => 'open',
            'slug' => 'engineer-role-cl',
        ]);

        $this->actingAs($candidate)
            ->postJson("/api/jobs/{$job->slug}/apply", [
                'resume_id' => $resume->id,
                'cover_letter_id' => $coverLetter->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('job_applications', [
            'job_id' => $job->id,
            'resume_id' => $resume->id,
            'cover_letter_id' => $coverLetter->id,
        ]);

        $application = \App\Models\JobApplication::query()
            ->where('job_id', $job->id)
            ->where('resume_id', $resume->id)
            ->first();

        $this->assertStringContainsString('Application for Engineer', $application->cover_note);
        $this->assertStringContainsString('very interested', $application->cover_note);
    }

    public function test_open_job_closes_automatically_after_application_deadline(): void
    {
        $recruiter = $this->approvedRecruiter();
        $candidate = $this->candidate();
        $resume = $this->makeResume($candidate);

        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Expired Role',
            'description' => str_repeat('Past deadline role. ', 5),
            'status' => 'open',
            'slug' => 'expired-role',
            'application_closes_at' => now()->subDay(),
        ]);

        $this->actingAs($candidate)
            ->postJson("/api/jobs/{$job->slug}/apply", [
                'resume_id' => $resume->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('recruiter_jobs', [
            'id' => $job->id,
            'status' => 'closed',
        ]);
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

    public function test_recruiter_can_update_job_via_post_multipart(): void
    {
        $recruiter = $this->approvedRecruiter();

        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Original Title',
            'description' => str_repeat('Original description. ', 5),
            'status' => 'draft',
            'slug' => 'original-title',
        ]);

        $this->actingAs($recruiter)
            ->post("/api/recruiter/jobs/{$job->id}", [
                'title' => 'Updated Title',
                'description' => str_repeat('Updated description text. ', 5),
                'status' => 'draft',
                'company_name' => 'Acme Corp',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.company_name', 'Acme Corp');

        $this->assertDatabaseHas('recruiter_jobs', [
            'id' => $job->id,
            'title' => 'Updated Title',
            'company_name' => 'Acme Corp',
        ]);
    }

    public function test_recruiter_can_create_job(): void
    {
        $recruiter = $this->approvedRecruiter();

        $this->actingAs($recruiter)
            ->postJson('/api/recruiter/jobs', [
                'title' => 'Designer',
                'description' => str_repeat('Design systems. ', 5),
                'status' => 'open',
                'company_name' => 'Acme Design',
                'location_type' => 'remote',
                'employment_type' => 'full_time',
                'required_skills' => ['Figma', 'UI Design'],
                'experience_min_years' => 2,
                'experience_max_years' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Designer')
            ->assertJsonPath('data.company_name', 'Acme Design')
            ->assertJsonPath('data.employment_type_label', 'Full-time');
    }
}
