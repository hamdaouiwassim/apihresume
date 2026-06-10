<?php

namespace Tests\Feature\Recruiter;

use App\Models\Recruiter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validRecruiterPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Alex Recruiter',
            'email' => 'alex@acme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'recruiter',
            'company_name' => 'Acme Corp',
            'company_size' => '51-200',
            'industry_focus' => 'SaaS',
            'recruiter_role' => 'Talent Lead',
            'hiring_focus' => 'Engineering',
            'recruiter_phone' => '+1 555 0100',
            'compliance_accepted' => true,
        ], $overrides);
    }

    public function test_full_recruiter_signup_persists_profile_fields(): void
    {
        $response = $this->postJson('/api/register', $this->validRecruiterPayload());

        $response->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('user.is_recruiter', true)
            ->assertJsonPath('user.company_name', 'Acme Corp')
            ->assertJsonPath('user.industry_focus', 'SaaS')
            ->assertJsonPath('user.recruiter_role', 'Talent Lead');

        $user = User::where('email', 'alex@acme.test')->first();
        $this->assertNotNull($user);

        $recruiter = Recruiter::where('user_id', $user->id)->first();
        $this->assertNotNull($recruiter);
        $this->assertSame('Acme Corp', $recruiter->company_name);
        $this->assertSame('51-200', $recruiter->company_size);
        $this->assertSame('SaaS', $recruiter->industry_focus);
        $this->assertSame('Talent Lead', $recruiter->recruiter_role);
        $this->assertTrue($recruiter->compliance_accepted);
        $this->assertSame('pending', $recruiter->status);
    }

    public function test_recruiter_signup_requires_company_name(): void
    {
        $response = $this->postJson('/api/register', $this->validRecruiterPayload([
            'company_name' => '',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    public function test_recruiter_signup_requires_profile_and_compliance_fields(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Alex Recruiter',
            'email' => 'minimal@acme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'recruiter',
            'company_name' => 'Acme Corp',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'company_size',
                'industry_focus',
                'recruiter_role',
                'compliance_accepted',
            ]);
    }
}
