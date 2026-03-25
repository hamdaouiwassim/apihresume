<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoverLetterTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'job_type',
        'language',
        'subject',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
