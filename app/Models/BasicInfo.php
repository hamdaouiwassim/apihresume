<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicInfo extends Model
{
    //
    protected $fillable = [
        'resume_id',
        'full_name',
        'email',
        'job_title',
        'address',
        'linkedin',
        'github',
        'professional_summary',
        'location'
    ];

    public function resume():BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
