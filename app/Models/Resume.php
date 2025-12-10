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
        'name'
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
}
