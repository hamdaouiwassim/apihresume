<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    //
    protected $fillable = [
        'name',
        'description',
        'category',
        'preview_image_url'
    ];

    /**
     * Get all resumes using this template
     */
    public function resumes()
    {
        return $this->hasMany(\App\Models\Resume::class);
    }
}
