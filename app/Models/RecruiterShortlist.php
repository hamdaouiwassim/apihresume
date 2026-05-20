<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruiterShortlist extends Model
{
    protected $fillable = [
        'recruiter_user_id',
        'name',
        'job_id',
    ];

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_user_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RecruiterJob::class, 'job_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecruiterShortlistItem::class, 'shortlist_id');
    }
}
