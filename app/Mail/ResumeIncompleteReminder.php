<?php

namespace App\Mail;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResumeIncompleteReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Resume $resume,
    ) {}

    public function build(): self
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
        $editUrl = $frontend.'/resume/edit/'.$this->resume->id;

        return $this->subject(__('Finish your resume on :app', ['app' => config('app.name')]))
            ->markdown('emails.admin.resume-incomplete-reminder', [
                'user' => $this->user,
                'resume' => $this->resume,
                'editUrl' => $editUrl,
            ]);
    }
}
