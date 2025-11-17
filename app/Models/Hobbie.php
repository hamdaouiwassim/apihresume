<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hobbie extends Model
{
    //
    protected $fillable = [
        "resume_id",
        "name"
    ];

    /**
     * Get the resume that owns the Hobbie
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
