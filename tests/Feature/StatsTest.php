<?php

namespace Tests\Feature;

use App\Models\Recruiter;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_stats_include_resumes_recruiters_and_open_jobs(): void
    {
        $recruiterUser = User::factory()->create(['is_recruiter' => true]);
        Recruiter::create([
            'user_id' => $recruiterUser->id,
            'status' => 'approved',
            'company_name' => 'Acme Hiring',
            'industry_focus' => 'Tech',
            'compliance_accepted' => true,
        ]);

        $candidate = User::factory()->create();
        $template = Template::create([
            'name' => 'Test',
            'description' => 'Test',
            'category' => 'Corporate',
        ]);
        foreach (range(1, 3) as $i) {
            Resume::create([
                'user_id' => $candidate->id,
                'template_id' => $template->id,
                'name' => "Resume {$i}",
            ]);
        }

        RecruiterJob::create([
            'created_by_user_id' => $recruiterUser->id,
            'title' => 'Open Role',
            'description' => str_repeat('Role description. ', 5),
            'status' => 'open',
            'slug' => 'open-role',
        ]);

        RecruiterJob::create([
            'created_by_user_id' => $recruiterUser->id,
            'title' => 'Closed Role',
            'description' => str_repeat('Closed role. ', 5),
            'status' => 'closed',
            'slug' => 'closed-role',
        ]);

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('data.total_resumes', 3)
            ->assertJsonPath('data.recruiter_partners', 1)
            ->assertJsonPath('data.open_jobs', 1);
    }
}
