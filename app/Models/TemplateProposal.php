<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'preview_image_url',
        'status',
        'admin_notes',
    ];

    /**
     * Recruiter who submitted the proposal
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

