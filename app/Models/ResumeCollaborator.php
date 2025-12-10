<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResumeCollaborator extends Model
{
    protected $fillable = [
        'resume_id',
        'user_id',
        'invitation_token',
        'invited_email',
        'invited_at',
        'accepted_at',
        'is_active',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the resume that this collaboration belongs to
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Get the user who is collaborating
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique invitation token
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('invitation_token', $token)->exists());

        return $token;
    }

    /**
     * Check if the invitation is still valid (not expired and not accepted)
     */
    public function isInvitationValid(): bool
    {
        return $this->invitation_token !== null 
            && $this->accepted_at === null 
            && $this->is_active;
    }

    /**
     * Accept the invitation
     */
    public function accept(): void
    {
        $this->update([
            'accepted_at' => now(),
            'invitation_token' => null,
        ]);
    }
}
