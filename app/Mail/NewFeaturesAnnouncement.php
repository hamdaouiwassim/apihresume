<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewFeaturesAnnouncement extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{label: string, url: string}>  $links
     */
    public function __construct(
        public readonly User $user,
        public readonly string $subjectLine,
        public readonly string $headline,
        public readonly string $message,
        public readonly array $links,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->markdown('emails.admin.new-features-announcement', [
                'user' => $this->user,
                'headline' => $this->headline,
                'message' => $this->message,
                'links' => $this->links,
            ]);
    }
}
