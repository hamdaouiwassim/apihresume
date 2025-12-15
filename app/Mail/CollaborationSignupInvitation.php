<?php

namespace App\Mail;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollaborationSignupInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly User $owner,
        public readonly Resume $resume,
        public readonly string $invitedEmail,
        public readonly string $acceptUrl,
        public readonly string $token
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->owner->name} invited you to collaborate on HResume - Join us to get started!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $registerUrl = $frontendUrl . '/register';
        $createResumeUrl = $frontendUrl . '/resume/create';

        return new Content(
            markdown: 'emails.resume.collaboration-signup-invitation',
            with: [
                'owner' => $this->owner,
                'resume' => $this->resume,
                'invitedEmail' => $this->invitedEmail,
                'acceptUrl' => $this->acceptUrl,
                'registerUrl' => $registerUrl,
                'createResumeUrl' => $createResumeUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
