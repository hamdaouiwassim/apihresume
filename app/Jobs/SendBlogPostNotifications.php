<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Models\User;
use App\Mail\BlogPostPublished;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBlogPostNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public BlogPost $post)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get all verified users
            $users = User::whereNotNull('email_verified_at')
                ->where('email', '!=', '')
                ->get();

            if ($users->isEmpty()) {
                Log::info('No verified users found to send blog post notifications', [
                    'post_id' => $this->post->id
                ]);
                return;
            }

            // Send emails in batches to avoid memory issues
            $users->chunk(50)->each(function ($userChunk) {
                foreach ($userChunk as $user) {
                    try {
                        Mail::to($user->email)->send(new BlogPostPublished($this->post));
                    } catch (\Exception $e) {
                        // Log individual email failures but continue with others
                        Log::warning('Failed to send blog post notification to user', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'post_id' => $this->post->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            Log::info('Blog post notification emails queued successfully', [
                'post_id' => $this->post->id,
                'total_users' => $users->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send blog post notification emails', [
                'error' => $e->getMessage(),
                'post_id' => $this->post->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
}
