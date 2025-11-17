<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Education extends Model
{
    //
    protected $fillable = [
        "institution",
        "resume_id",
        "description",
        "start_date",
        "end_date",
        "degree"

    ];

    /**
     * Get the resume that owns the education
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(related: Resume::class);
    }
}
