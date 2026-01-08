<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resume extends Model
{
    //
    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'section_order'
    ];

    protected $casts = [
        'section_order' => 'array',
    ];

    /**
     * Get the template associated with the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the owner of the resume
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the basicInfo associated with the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function basicInfo(): HasOne
    {
        return $this->hasOne(BasicInfo::class );
    }

    /**
     * Get all of the experiences for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

     /**
     * Get all of the educations for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    /**
     * Get all of the skills for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * Get all of the hobbies for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hobbies(): HasMany
    {
        return $this->hasMany(Hobbie::class);
    }

    /**
     * Get all of the certificates for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get all of the languages for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function languages(): HasMany
    {
        return $this->hasMany(Language::class);
    }

    /**
     * Get all of the projects for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all collaborators for the Resume
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function collaborators(): HasMany
    {
        return $this->hasMany(ResumeCollaborator::class);
    }

    /**
     * Check if a user can edit this resume (owner or active collaborator)
     */
    public function canBeEditedBy(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        // Owner can always edit
        if ($this->user_id === $userId) {
            return true;
        }

        // Check if user is an active collaborator
        return $this->collaborators()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('accepted_at')
            ->exists();
    }

    /**
     * Check if a user can edit a specific section of this resume
     * 
     * @param int|null $userId
     * @param string $section Section name (e.g., 'basic_info', 'experiences', etc.)
     * @return bool
     */
    public function canEditSection(?int $userId, string $section): bool
    {
        if (!$userId) {
            return false;
        }

        // Owner can always edit all sections
        if ($this->user_id === $userId) {
            return true;
        }

        // Check if user is an active collaborator with permission for this section
        $collaborator = $this->collaborators()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('accepted_at')
            ->first();

        if (!$collaborator) {
            return false;
        }

        return $collaborator->canEditSection($section);
    }

    /**
     * Get the collaborator record for a user
     * 
     * @param int|null $userId
     * @return ResumeCollaborator|null
     */
    public function getCollaboratorForUser(?int $userId): ?ResumeCollaborator
    {
        if (!$userId || $this->user_id === $userId) {
            return null; // Owner doesn't need collaborator record
        }

        return $this->collaborators()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('accepted_at')
            ->first();
    }
}
