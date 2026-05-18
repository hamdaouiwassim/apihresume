<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCertificate extends Model
{
    protected $fillable = [
        'title',
        'employee_name',
        'employee_job_title',
        'company_name',
        'company_address',
        'employment_start',
        'employment_end',
        'is_current_employment',
        'duties_summary',
        'letter_place',
        'letter_date',
        'signer_name_title',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'employment_start' => 'date',
            'employment_end' => 'date',
            'letter_date' => 'date',
            'is_current_employment' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
