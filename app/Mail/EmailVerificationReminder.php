<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $verificationUrl,
    ) {}

    public function build(): self
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
        $deletionDays = (int) config('auth.unverified_account_deletion_days', 30);
        $showDeletionNotice = ! $this->user->hasVerifiedEmail();
        $deletionDeadline = $showDeletionNotice && $this->user->created_at
            ? $this->user->created_at->copy()->addDays($deletionDays)
            : null;

        return $this->subject(__('Confirm your email on :app', ['app' => config('app.name')]))
            ->markdown('emails.admin.email-verification-reminder', [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'resendUrl' => $frontend.'/email-verification',
                'showDeletionNotice' => $showDeletionNotice,
                'deletionDays' => $deletionDays,
                'deletionDeadline' => $deletionDeadline,
            ]);
    }
}
