<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionWelcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public readonly string $email)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to HResume - Start Building Your Professional Resume Today!',
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
            markdown: 'emails.subscription.welcome',
            with: [
                'email' => $this->email,
                'registerUrl' => $registerUrl,
                'createResumeUrl' => $createResumeUrl,
            ],
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

