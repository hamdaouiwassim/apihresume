<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSkillCatalog extends Model
{
    protected $table = 'job_skill_catalog';

    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    public static function activeNames(): array
    {
        return static::query()->active()->ordered()->pluck('name')->all();
    }
}
