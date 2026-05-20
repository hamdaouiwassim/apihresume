<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterShortlistItem extends Model
{
    protected $fillable = [
        'shortlist_id',
        'resume_id',
        'added_by_user_id',
        'notes',
        'contact_revealed',
    ];

    protected function casts(): array
    {
        return [
            'contact_revealed' => 'boolean',
        ];
    }

    public function shortlist(): BelongsTo
    {
        return $this->belongsTo(RecruiterShortlist::class, 'shortlist_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
