<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendOutboundEmailJob;
use App\Mail\EmailVerificationReminder;
use App\Models\OutboundEmail;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutboundEmailTest extends TestCase
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

    public function test_admin_custom_message_creates_outbound_log_and_dispatches_job(): void
    {
        Bus::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($this->admin)->postJson("/api/admin/users/{$user->id}/message", [
            'subject' => 'Hello',
            'message' => 'Please complete your profile.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', OutboundEmail::TYPE_ADMIN_CUSTOM)
            ->assertJsonPath('data.status', OutboundEmail::STATUS_QUEUED);

        $this->assertDatabaseHas('outbound_emails', [
            'user_id' => $user->id,
            'type' => OutboundEmail::TYPE_ADMIN_CUSTOM,
            'recipient_email' => $user->email,
        ]);

        Bus::assertDispatched(SendOutboundEmailJob::class);
    }

    public function test_verification_reminder_rejected_for_verified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/emails/verification-reminder")
            ->assertStatus(422);
    }

    public function test_verification_reminder_queues_for_unverified_user(): void
    {
        Bus::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/emails/verification-reminder")
            ->assertOk()
            ->assertJsonPath('data.type', OutboundEmail::TYPE_VERIFICATION_REMINDER);

        Bus::assertDispatched(SendOutboundEmailJob::class);
    }

    public function test_bulk_unverified_queues_emails(): void
    {
        Bus::fake();
        User::factory()->unverified()->count(2)->create();
        User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($this->admin)->postJson('/api/admin/outbound-emails/bulk', [
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'filter' => 'unverified',
        ]);

        $response->assertOk()->assertJsonPath('data.queued', 2);
        $this->assertSame(2, OutboundEmail::query()->count());
    }

    public function test_dashboard_includes_outbound_summary(): void
    {
        OutboundEmail::query()->create([
            'user_id' => User::factory()->create()->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_ADMIN_CUSTOM,
            'recipient_email' => 'test@example.com',
            'subject' => 'Test',
            'status' => OutboundEmail::STATUS_SENT,
            'sent_at' => now(),
            'queued_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'outbound_emails' => ['queued', 'sent', 'failed'],
                    'recent_outbound_emails',
                ],
            ]);
    }

    public function test_send_job_marks_sent(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'recipient_email' => $user->email,
            'subject' => 'Verify',
            'status' => OutboundEmail::STATUS_QUEUED,
            'queued_at' => now(),
            'meta' => [],
        ]);

        (new SendOutboundEmailJob($outbound->id))->handle();

        $this->assertSame(OutboundEmail::STATUS_SENT, $outbound->fresh()->status);
        Mail::assertSent(\App\Mail\EmailVerificationReminder::class);
    }

    public function test_verification_reminder_email_includes_deletion_notice_for_unverified_user(): void
    {
        Mail::fake();
        config(['auth.unverified_account_deletion_days' => 14]);

        $user = User::factory()->unverified()->create([
            'created_at' => now()->subDays(3),
        ]);

        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'recipient_email' => $user->email,
            'subject' => 'Verify',
            'status' => OutboundEmail::STATUS_QUEUED,
            'meta' => [],
            'queued_at' => now(),
        ]);

        (new SendOutboundEmailJob($outbound->id))->handle();

        Mail::assertSent(EmailVerificationReminder::class, function (EmailVerificationReminder $mail) {
            $html = $mail->render();

            return str_contains($html, 'Account removal notice')
                && str_contains($html, '14 days');
        });
    }

    public function test_resume_reminder_requires_resume(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/emails/resume-reminder")
            ->assertStatus(422);
    }

    public function test_resume_reminder_queues_for_incomplete_resume(): void
    {
        Bus::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $template = Template::create([
            'name' => 'Classic',
            'description' => 'Test',
            'category' => 'professional',
        ]);
        Resume::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'name' => 'Draft CV',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/emails/resume-reminder")
            ->assertOk()
            ->assertJsonPath('data.type', OutboundEmail::TYPE_RESUME_REMINDER);
    }

    /**
     * @return array<string, mixed>
     */
    private function newFeaturesPayload(): array
    {
        return [
            'subject' => 'New features to try',
            'headline' => 'We shipped something new',
            'message' => "Hi there,\n\nCheck out our latest updates and tell us what you think.",
            'links' => [
                ['label' => 'Open dashboard', 'url' => 'https://app.example.com/resumes'],
                ['label' => 'Try AI editor', 'url' => 'https://app.example.com/resume/create'],
            ],
        ];
    }

    public function test_new_features_announcement_queues_for_user(): void
    {
        Bus::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$user->id}/emails/new-features", $this->newFeaturesPayload())
            ->assertOk()
            ->assertJsonPath('data.type', OutboundEmail::TYPE_NEW_FEATURES);

        $this->assertDatabaseHas('outbound_emails', [
            'user_id' => $user->id,
            'type' => OutboundEmail::TYPE_NEW_FEATURES,
            'subject' => 'New features to try',
        ]);

        Bus::assertDispatched(SendOutboundEmailJob::class);
    }

    public function test_new_features_bulk_queues_for_verified_users(): void
    {
        Bus::fake();
        User::factory()->count(2)->create(['email_verified_at' => now()]);
        User::factory()->unverified()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/outbound-emails/new-features', [
                ...$this->newFeaturesPayload(),
                'filter' => 'verified',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.queued', 2);

        Bus::assertDispatched(SendOutboundEmailJob::class, 2);
    }

    public function test_send_job_marks_new_features_sent(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_NEW_FEATURES,
            'recipient_email' => $user->email,
            'subject' => 'New features',
            'status' => OutboundEmail::STATUS_QUEUED,
            'meta' => [
                'headline' => 'New on the app',
                'message' => 'Try these updates.',
                'links' => [['label' => 'Test', 'url' => 'https://example.com/test']],
            ],
            'queued_at' => now(),
        ]);

        (new SendOutboundEmailJob($outbound->id))->handle();

        $this->assertSame(OutboundEmail::STATUS_SENT, $outbound->fresh()->status);
        Mail::assertSent(\App\Mail\NewFeaturesAnnouncement::class);
    }

    public function test_send_job_marks_skipped_for_invalid_recipient_email(): void
    {
        $user = User::factory()->unverified()->create();
        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'recipient_email' => 'not-a-valid-email',
            'subject' => 'Verify',
            'status' => OutboundEmail::STATUS_QUEUED,
            'queued_at' => now(),
            'meta' => [],
        ]);

        (new SendOutboundEmailJob($outbound->id))->handle();

        $fresh = $outbound->fresh();
        $this->assertSame(OutboundEmail::STATUS_SKIPPED, $fresh->status);
        $this->assertStringContainsString('Invalid email', (string) $fresh->error_message);
    }

    public function test_send_job_marks_skipped_for_permanent_smtp_failure(): void
    {
        $user = User::factory()->unverified()->create();
        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'recipient_email' => $user->email,
            'subject' => 'Verify',
            'status' => OutboundEmail::STATUS_QUEUED,
            'queued_at' => now(),
            'meta' => [],
        ]);

        $pending = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException(
                'Expected response code "250" but got code "550", with message "550 5.1.1 User unknown".'
            ));

        Mail::shouldReceive('to')->once()->with($user->email)->andReturn($pending);

        (new SendOutboundEmailJob($outbound->id))->handle();

        $fresh = $outbound->fresh();
        $this->assertSame(OutboundEmail::STATUS_SKIPPED, $fresh->status);
        $this->assertStringContainsString('Undeliverable recipient', (string) $fresh->error_message);
    }

    public function test_send_job_sets_queued_for_retry_on_transient_failure_when_attempts_remain(): void
    {
        $user = User::factory()->unverified()->create();
        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'recipient_email' => $user->email,
            'subject' => 'Verify',
            'status' => OutboundEmail::STATUS_QUEUED,
            'queued_at' => now(),
            'meta' => [],
        ]);

        $pending = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Connection reset by peer'));

        Mail::shouldReceive('to')->once()->with($user->email)->andReturn($pending);

        $job = \Mockery::mock(SendOutboundEmailJob::class, [$outbound->id])->makePartial();
        $job->shouldReceive('attempts')->andReturn(1);

        try {
            $job->handle();
            $this->fail('Expected exception to bubble for queue retry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Connection reset by peer', $e->getMessage());
        }

        $fresh = $outbound->fresh();
        $this->assertSame(OutboundEmail::STATUS_QUEUED, $fresh->status);
        $this->assertStringContainsString('will retry', (string) $fresh->error_message);
        $this->assertIsArray($fresh->meta);
        $this->assertArrayHasKey('delivery', $fresh->meta);
    }

    public function test_send_job_marks_failed_on_final_transient_attempt(): void
    {
        $user = User::factory()->unverified()->create();
        $outbound = OutboundEmail::query()->create([
            'user_id' => $user->id,
            'triggered_by_user_id' => $this->admin->id,
            'type' => OutboundEmail::TYPE_VERIFICATION_REMINDER,
            'recipient_email' => $user->email,
            'subject' => 'Verify',
            'status' => OutboundEmail::STATUS_QUEUED,
            'queued_at' => now(),
            'meta' => [],
        ]);

        $pending = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Connection reset by peer'));

        Mail::shouldReceive('to')->once()->with($user->email)->andReturn($pending);

        $job = \Mockery::mock(SendOutboundEmailJob::class, [$outbound->id])->makePartial();
        $job->shouldReceive('attempts')->andReturn(3);

        try {
            $job->handle();
            $this->fail('Expected exception after marking failed.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(OutboundEmail::STATUS_FAILED, $outbound->fresh()->status);
    }
}
