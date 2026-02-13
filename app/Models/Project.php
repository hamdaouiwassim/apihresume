<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        "name",
        "resume_id",
        "experience_id",
        "description",
        "technologies",
        "url",
        "startDate",
        "endDate"
    ];

    /**
     * Get the resume that owns the Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Get the experience this project is associated with (optional).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
}
