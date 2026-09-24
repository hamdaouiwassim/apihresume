<?php

namespace App\Models;

use App\Support\LegacyUrls;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicInfo extends Model
{
    //
    protected $fillable = [
        'resume_id',
        'full_name',
        'email',
        'phone',
        'job_title',
        'address',
        'linkedin',
        'github',
        'website',
        'professional_summary',
        'location',
        'avatar',
    ];

    public function resume():BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /** Avatars uploaded under a former domain of this app are served from APP_URL now. */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn ($value) => LegacyUrls::rewrite($value));
    }
}
