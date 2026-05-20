<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterActivityLog extends Model
{
    protected $fillable = [
        'recruiter_user_id',
        'action',
        'resume_id',
        'meta',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_user_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
