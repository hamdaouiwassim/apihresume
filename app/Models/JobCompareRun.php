<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCompareRun extends Model
{
    protected $fillable = [
        'job_id',
        'recruiter_user_id',
        'parent_run_id',
        'mode',
        'resume_ids',
        'results',
        'candidate_count',
    ];

    protected function casts(): array
    {
        return [
            'resume_ids' => 'array',
            'results' => 'array',
            'candidate_count' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RecruiterJob::class, 'job_id');
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_user_id');
    }

    public function parentRun(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_run_id');
    }
}
