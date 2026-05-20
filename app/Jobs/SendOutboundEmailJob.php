<?php

namespace App\Jobs;

use App\Mail\AdminUserMessage;
use App\Mail\EmailVerificationReminder;
use App\Mail\NewFeaturesAnnouncement;
use App\Mail\ResumeIncompleteReminder;
use App\Models\OutboundEmail;
use App\Models\Resume;
use App\Models\User;
use App\Services\OutboundEmailDeliveryClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendOutboundEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $outboundEmailId) {}

    public function handle(): void
    {
        $outbound = OutboundEmail::query()->find($this->outboundEmailId);

        if (! $outbound) {
            return;
        }

        if (in_array($outbound->status, [
            OutboundEmail::STATUS_SENT,
            OutboundEmail::STATUS_SKIPPED,
            OutboundEmail::STATUS_FAILED,
        ], true)) {
            return;
        }

        if (! filter_var($outbound->recipient_email, FILTER_VALIDATE_EMAIL)) {
            $outbound->markSkipped('Invalid email address format.');

            return;
        }

        $outbound->markProcessing();

        try {
            $mailable = $this->buildMailable($outbound);

            if ($mailable === null) {
                $outbound->markSkipped('Could not build email (invalid type or missing data).');

                return;
            }

            Mail::to($outbound->recipient_email)->send($mailable);
            $outbound->markSent();
        } catch (\Throwable $e) {
            if (OutboundEmailDeliveryClassifier::isPermanentRecipientFailure($e)) {
                $outbound->markSkipped('Undeliverable recipient: '.$e->getMessage());

                return;
            }

            if ($this->attempts() >= $this->tries) {
                $outbound->markFailed($e->getMessage());
                throw $e;
            }

            $meta = $outbound->meta ?? [];
            $delivery = $meta['delivery'] ?? [];
            $delivery['last_attempt_at'] = now()->toIso8601String();
            $delivery['attempt'] = $this->attempts();
            $delivery['max_attempts'] = $this->tries;
            $meta['delivery'] = $delivery;

            $outbound->update([
                'status' => OutboundEmail::STATUS_QUEUED,
                'meta' => $meta,
                'error_message' => mb_substr(
                    'Attempt '.$this->attempts().'/'.$this->tries.' (will retry): '.$e->getMessage(),
                    0,
                    2000
                ),
            ]);

            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $outbound = OutboundEmail::query()->find($this->outboundEmailId);
        if (! $outbound) {
            return;
        }

        if (in_array($outbound->status, [
            OutboundEmail::STATUS_SENT,
            OutboundEmail::STATUS_SKIPPED,
            OutboundEmail::STATUS_FAILED,
        ], true)) {
            return;
        }

        $outbound->markFailed($exception?->getMessage() ?? 'Job failed after retries.');
    }

    private function buildMailable(OutboundEmail $outbound): ?\Illuminate\Mail\Mailable
    {
        $user = $outbound->user_id
            ? User::query()->find($outbound->user_id)
            : null;

        return match ($outbound->type) {
            OutboundEmail::TYPE_ADMIN_CUSTOM => $this->buildAdminCustom($outbound, $user),
            OutboundEmail::TYPE_RESUME_REMINDER => $this->buildResumeReminder($outbound, $user),
            OutboundEmail::TYPE_VERIFICATION_REMINDER => $this->buildVerificationReminder($outbound, $user),
            OutboundEmail::TYPE_NEW_FEATURES => $this->buildNewFeaturesAnnouncement($outbound, $user),
            default => null,
        };
    }

    private function buildAdminCustom(OutboundEmail $outbound, ?User $user): ?AdminUserMessage
    {
        $meta = $outbound->meta ?? [];
        $adminId = $outbound->triggered_by_user_id;
        $admin = $adminId ? User::query()->find($adminId) : null;

        if (! $admin || ! $user || empty($meta['body'])) {
            return null;
        }

        return new AdminUserMessage(
            admin: $admin,
            user: $user,
            subjectLine: $outbound->subject,
            bodyMessage: (string) $meta['body'],
        );
    }

    private function buildResumeReminder(OutboundEmail $outbound, ?User $user): ?ResumeIncompleteReminder
    {
        if (! $user || ! $outbound->resume_id) {
            return null;
        }

        $resume = Resume::query()->find($outbound->resume_id);
        if (! $resume) {
            return null;
        }

        return new ResumeIncompleteReminder($user, $resume);
    }

    private function buildVerificationReminder(OutboundEmail $outbound, ?User $user): ?EmailVerificationReminder
    {
        if (! $user) {
            return null;
        }

        if ($user->hasVerifiedEmail()) {
            return null;
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        return new EmailVerificationReminder($user, $verificationUrl);
    }

    private function buildNewFeaturesAnnouncement(OutboundEmail $outbound, ?User $user): ?NewFeaturesAnnouncement
    {
        if (! $user) {
            return null;
        }

        $meta = $outbound->meta ?? [];
        $message = trim((string) ($meta['message'] ?? ''));
        $links = $meta['links'] ?? [];
        $headline = trim((string) ($meta['headline'] ?? $outbound->subject));

        if ($message === '' || ! is_array($links) || $links === []) {
            return null;
        }

        $normalizedLinks = [];
        foreach ($links as $link) {
            if (! is_array($link) || empty($link['label']) || empty($link['url'])) {
                continue;
            }
            $normalizedLinks[] = [
                'label' => (string) $link['label'],
                'url' => (string) $link['url'],
            ];
        }

        if ($normalizedLinks === []) {
            return null;
        }

        return new NewFeaturesAnnouncement($user, $outbound->subject, $headline, $message, $normalizedLinks);
    }
}
