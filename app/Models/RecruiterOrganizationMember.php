<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterOrganizationMember extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(RecruiterOrganization::class, 'organization_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
