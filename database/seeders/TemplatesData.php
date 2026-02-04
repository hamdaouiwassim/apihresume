<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplatesData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing templates (can't truncate due to foreign key constraints)
        Template::query()->delete();

        Template::create([
            'name' => 'Classic',
            'description' => 'Traditional layout perfect for corporate and business roles. Clean and professional design that emphasizes readability and structure.',
            'category' => 'Corporate',
            'preview_image_url' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=800&h=1000&fit=crop'
        ]);

        Template::create([
            'name' => 'Executive Split',
            'description' => 'Premium split-column layout with a dedicated sidebar for contact info, skills, and education. Inspired by modern sales executive resumes.',
            'category' => 'Corporate',
            'preview_image_url' => 'https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?w=800&h=1000&fit=crop'
        ]);

        Template::create([
            'name' => 'Modern Professional',
            'description' => 'Clean two-column layout with profile picture, perfect for freelancers and modern professionals. Features a dedicated sidebar for contact, skills, and achievements.',
            'category' => 'Professional',
            'preview_image_url' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=800&h=1000&fit=crop&q=80'
        ]);
    }
}
