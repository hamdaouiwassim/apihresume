<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfFont extends Model
{
    protected $fillable = [
        'family_name',
        'regular_path',
        'bold_path',
        'italic_path',
        'bold_italic_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get only active fonts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
