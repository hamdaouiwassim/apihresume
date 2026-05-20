<?php

namespace App\Services;

use App\Jobs\SendOutboundEmailJob;
use App\Models\OutboundEmail;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OutboundEmailService
{
    public function __construct(
        private readonly ResumeCompletionService $resumeCompletion,
    ) {}

    public function queueAdminCustom(User $admin, User $recipient, string $subject, string $body): OutboundEmail
    {
        return $this->queue(
            type: OutboundEmail::TYPE_ADMIN_CUSTOM,
            recipient: $recipient,
            subject: $subject,
            triggeredBy: $admin,
            resume: null,
            meta: ['body' => $body],
        );
    }

    public function queueResumeReminder(User $admin, User $recipient): OutboundEmail
    {
        $resume = $this->resumeCompletion->latestIncompleteResumeForUser($recipient);

        if (! $resume) {
            throw new \InvalidArgumentException('User has no resume to remind about.');
        }

        $subject = __('Finish your resume on :app', ['app' => config('app.name')]);

        return $this->queue(
            type: OutboundEmail::TYPE_RESUME_REMINDER,
            recipient: $recipient,
            subject: $subject,
            triggeredBy: $admin,
            resume: $resume,
            meta: ['resume_name' => $resume->name],
        );
    }

    public function queueVerificationReminder(User $admin, User $recipient): OutboundEmail
    {
        if ($recipient->hasVerifiedEmail()) {
            throw new \InvalidArgumentException('User email is already verified.');
        }

        $subject = __('Confirm your email on :app', ['app' => config('app.name')]);

        return $this->queue(
            type: OutboundEmail::TYPE_VERIFICATION_REMINDER,
            recipient: $recipient,
            subject: $subject,
            triggeredBy: $admin,
            resume: null,
            meta: [],
        );
    }

    /**
     * @param  list<array{label: string, url: string}>  $links
     */
    public function queueNewFeaturesAnnouncement(
        User $admin,
        User $recipient,
        string $subject,
        string $headline,
        string $message,
        array $links,
    ): OutboundEmail {
        if ($recipient->is_admin) {
            throw new \InvalidArgumentException('Cannot send feature announcements to admin accounts.');
        }

        return $this->queue(
            type: OutboundEmail::TYPE_NEW_FEATURES,
            recipient: $recipient,
            subject: $subject,
            triggeredBy: $admin,
            resume: null,
            meta: [
                'headline' => $headline,
                'message' => $message,
                'links' => $links,
            ],
        );
    }

    /**
     * @param  list<array{label: string, url: string}>  $links
     * @return array{queued: int, skipped: int, errors: list<string>}
     */
    public function queueNewFeaturesBulk(
        User $admin,
        string $filter,
        string $subject,
        string $headline,
        string $message,
        array $links,
    ): array {
        $users = $this->usersForBulkFilter($filter);

        $queued = 0;
        $skipped = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $this->queueNewFeaturesAnnouncement($admin, $user, $subject, $headline, $message, $links);
                $queued++;
            } catch (\InvalidArgumentException $e) {
                $skipped++;
                $errors[] = "User #{$user->id}: {$e->getMessage()}";
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "User #{$user->id}: {$e->getMessage()}";
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @return array{queued: int, skipped: int, errors: list<string>}
     */
    public function queueBulk(User $admin, string $type, string $filter): array
    {
        $users = $this->usersForBulkFilter($filter);

        $queued = 0;
        $skipped = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                match ($type) {
                    OutboundEmail::TYPE_RESUME_REMINDER => $this->queueResumeReminder($admin, $user),
                    OutboundEmail::TYPE_VERIFICATION_REMINDER => $this->queueVerificationReminder($admin, $user),
                    default => throw new \InvalidArgumentException('Unsupported bulk email type.'),
                };
                $queued++;
            } catch (\InvalidArgumentException $e) {
                $skipped++;
                $errors[] = "User #{$user->id}: {$e->getMessage()}";
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "User #{$user->id}: {$e->getMessage()}";
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function usersForBulkFilter(string $filter)
    {
        $query = User::query()->where('is_admin', false);

        return match ($filter) {
            'unverified' => $query->whereNull('email_verified_at')->get(),
            'incomplete_resume' => $query->whereHas('resumes')->get()->filter(function (User $user) {
                return $this->resumeCompletion->latestIncompleteResumeForUser($user) !== null;
            })->values(),
            'verified' => $query->whereNotNull('email_verified_at')->get(),
            'pro' => $query->where('is_pro', true)->whereNotNull('email_verified_at')->get(),
            'all_users' => $query->get(),
            default => throw new \InvalidArgumentException('Invalid bulk filter.'),
        };
    }

    private function queue(
        string $type,
        User $recipient,
        string $subject,
        User $triggeredBy,
        ?Resume $resume,
        array $meta,
    ): OutboundEmail {
        return DB::transaction(function () use ($type, $recipient, $subject, $triggeredBy, $resume, $meta) {
            $outbound = OutboundEmail::query()->create([
                'user_id' => $recipient->id,
                'triggered_by_user_id' => $triggeredBy->id,
                'resume_id' => $resume?->id,
                'type' => $type,
                'recipient_email' => $recipient->email,
                'subject' => $subject,
                'status' => OutboundEmail::STATUS_QUEUED,
                'meta' => $meta,
                'queued_at' => now(),
            ]);

            SendOutboundEmailJob::dispatch($outbound->id);

            return $outbound;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(): array
    {
        $since = now()->subDays(30);

        $base = OutboundEmail::query()->where('created_at', '>=', $since);

        $staleMinutes = (int) config('mail.outbound_stale_minutes', 30);
        $staleBefore = now()->subMinutes($staleMinutes);

        return [
            'period_days' => 30,
            'queued' => (clone $base)->where('status', OutboundEmail::STATUS_QUEUED)->count(),
            'processing' => (clone $base)->where('status', OutboundEmail::STATUS_PROCESSING)->count(),
            'sent' => (clone $base)->where('status', OutboundEmail::STATUS_SENT)->count(),
            'failed' => (clone $base)->where('status', OutboundEmail::STATUS_FAILED)->count(),
            'skipped' => (clone $base)->where('status', OutboundEmail::STATUS_SKIPPED)->count(),
            'stale_queued' => OutboundEmail::query()
                ->where('status', OutboundEmail::STATUS_QUEUED)
                ->where('queued_at', '<', $staleBefore)
                ->count(),
            'sent_24h' => OutboundEmail::query()
                ->where('status', OutboundEmail::STATUS_SENT)
                ->where('sent_at', '>=', now()->subDay())
                ->count(),
            'jobs_pending' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];
    }
}
