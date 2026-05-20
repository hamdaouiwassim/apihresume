<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruiterOrganization extends Model
{
    protected $fillable = [
        'name',
        'owner_user_id',
        'brand_avatar',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(RecruiterOrganizationMember::class, 'organization_id');
    }
}
