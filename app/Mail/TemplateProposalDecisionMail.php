<?php

namespace App\Mail;

use App\Models\TemplateProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateProposalDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TemplateProposal $proposal,
        public string $decision,
        public ?string $adminNotes = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            'approved' => 'Your template proposal was approved',
            'rejected' => 'Your template proposal was not approved',
            default => 'Update on your template proposal',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template-proposal-decision',
            with: [
                'proposal' => $this->proposal,
                'decision' => $this->decision,
                'adminNotes' => $this->adminNotes,
            ],
        );
    }
}
