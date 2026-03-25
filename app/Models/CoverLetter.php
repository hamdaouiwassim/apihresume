<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'recipient_name',
        'recipient_company',
        'recipient_address',
        'recipient_email',
        'city',
        'country',
        'date',
        'subject',
        'content',
        'style',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
