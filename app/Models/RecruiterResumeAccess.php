<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterResumeAccess extends Model
{
    protected $table = 'recruiter_resume_access';

    protected $fillable = [
        'resume_id',
        'granted_to_user_id',
        'granted_by_user_id',
        'source',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function grantedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_to_user_id');
    }
}
