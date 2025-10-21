<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    //

    protected $fillable = [
        "company",
        "resume_id",
        "description",
        "startDate",
        "endDate",
        "position"

    ];

    /**
     * Get the resume that owns the Experience
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(related: Resume::class);
    }
}
