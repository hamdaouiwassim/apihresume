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
        'phone',
        'job_title',
        'address',
        'linkedin',
        'github',
        'website',
        'professional_summary',
        'location'
    ];

    public function resume():BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
