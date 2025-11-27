<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminUserMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $admin,
        public readonly User $user,
        public readonly string $subjectLine,
        public readonly string $bodyMessage,
    ) {}

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->markdown('emails.admin.user-message', [
                'admin' => $this->admin,
                'user' => $this->user,
                'bodyMessage' => $this->bodyMessage,
            ]);
    }
}

