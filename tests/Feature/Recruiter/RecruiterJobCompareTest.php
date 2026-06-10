<?php

namespace Tests\Feature\Recruiter;

use App\Models\JobApplication;
use App\Models\JobCompareRun;
use App\Models\Recruiter;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterJobCompareTest extends TestCase
{
    use RefreshDatabase;

    private function approvedRecruiter(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_recruiter' => true,
            'ai_monthly_token_limit' => 100000,
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

    private function candidateWithResume(array $skillNames = ['Laravel', 'React']): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $template = Template::create([
            'name' => 'T',
            'description' => 'D',
            'category' => 'Corporate',
        ]);
        $resume = Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Dev CV',
            'open_to_recruiters' => true,
        ]);
        foreach ($skillNames as $name) {
            Skill::create(['resume_id' => $resume->id, 'name' => $name]);
        }

        return [$user, $resume];
    }

    public function test_standard_compare_ranks_resumes_and_persists_scores(): void
    {
        $recruiter = $this->approvedRecruiter();
        [, $resumeA] = $this->candidateWithResume(['Laravel', 'React', 'Docker']);
        [, $resumeB] = $this->candidateWithResume(['Word']);

        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Full Stack Dev',
            'description' => str_repeat('We need Laravel and React developers. ', 5),
            'status' => 'open',
            'slug' => 'full-stack',
            'required_skills' => ['Laravel', 'React'],
        ]);

        JobApplication::create([
            'job_id' => $job->id,
            'resume_id' => $resumeA->id,
            'user_id' => $resumeA->user_id,
            'status' => 'new',
            'applied_at' => now(),
        ]);
        JobApplication::create([
            'job_id' => $job->id,
            'resume_id' => $resumeB->id,
            'user_id' => $resumeB->user_id,
            'status' => 'new',
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($recruiter)
            ->postJson("/api/recruiter/jobs/{$job->id}/compare", [
                'resume_ids' => [$resumeA->id, $resumeB->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.mode', 'standard');

        $results = $response->json('data.results');
        $this->assertCount(2, $results);
        $this->assertGreaterThanOrEqual($results[1]['match_score'], $results[0]['match_score']);

        $this->assertDatabaseHas('job_applications', [
            'job_id' => $job->id,
            'resume_id' => $resumeA->id,
            'match_score' => $results[0]['match_score'],
        ]);

        $runId = $response->json('data.run_id');
        $this->assertNotNull($runId);
        $this->assertDatabaseHas('job_compare_runs', [
            'id' => $runId,
            'job_id' => $job->id,
            'mode' => 'standard',
        ]);

        $this->actingAs($recruiter)
            ->getJson("/api/recruiter/jobs/{$job->id}/compare/runs")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $runId);

        $this->actingAs($recruiter)
            ->getJson("/api/recruiter/jobs/{$job->id}/compare/runs/{$runId}")
            ->assertOk()
            ->assertJsonPath('data.mode', 'standard')
            ->assertJsonCount(2, 'data.results');
    }

    public function test_deep_compare_rejects_more_than_four_resumes(): void
    {
        $recruiter = $this->approvedRecruiter();
        $job = RecruiterJob::create([
            'created_by_user_id' => $recruiter->id,
            'title' => 'Role',
            'description' => str_repeat('Description. ', 5),
            'status' => 'open',
            'slug' => 'role-1',
        ]);

        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            [, $r] = $this->candidateWithResume();
            $ids[] = $r->id;
        }

        $this->actingAs($recruiter)
            ->postJson("/api/recruiter/jobs/{$job->id}/compare/deep", [
                'resume_ids' => $ids,
            ])
            ->assertStatus(422);
    }
}
