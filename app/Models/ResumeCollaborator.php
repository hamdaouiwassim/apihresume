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
        'allowed_sections',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_active' => 'boolean',
        'allowed_sections' => 'array',
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

    /**
     * Check if collaborator can edit a specific section
     * 
     * @param string $section Section name (e.g., 'basic_info', 'experiences', 'educations', etc.)
     * @return bool
     */
    public function canEditSection(string $section): bool
    {
        // If no allowed_sections is set (null), allow all sections (backward compatibility)
        if ($this->allowed_sections === null) {
            return true;
        }

        // If allowed_sections is an empty array, deny all
        if (empty($this->allowed_sections)) {
            return false;
        }

        // Check if section is in allowed list
        return in_array($section, $this->allowed_sections);
    }

    /**
     * Get all allowed sections for this collaborator
     * 
     * @return array
     */
    public function getAllowedSections(): array
    {
        // If null, return all sections (backward compatibility)
        if ($this->allowed_sections === null) {
            return ['basic_info', 'experiences', 'educations', 'skills', 'hobbies', 'certificates', 'languages'];
        }

        return $this->allowed_sections ?? [];
    }
}
