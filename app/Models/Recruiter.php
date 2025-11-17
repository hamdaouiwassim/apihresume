<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recruiter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'company_name',
        'company_size',
        'industry_focus',
        'hiring_focus',
        'recruiter_role',
        'recruiter_phone',
        'recruiter_linkedin',
        'compliance_accepted',
        'brand_avatar',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'compliance_accepted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
