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
            'name' => 'Modern',
            'description' => 'Clean and contemporary design with a focus on visual hierarchy. Perfect for tech professionals and modern industries.',
            'category' => 'Corporate',
            'preview_image_url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&h=1000&fit=crop'
        ]);

        Template::create([
            'name' => 'Creative',
            'description' => 'Bold design for creative professionals and designers. Showcase your personality while maintaining professionalism.',
            'category' => 'Creative',
            'preview_image_url' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&h=1000&fit=crop'
        ]);

        Template::create([
            'name' => 'Minimal',
            'description' => 'Simple and elegant design that puts content first. Perfect for those who prefer a clean, uncluttered aesthetic.',
            'category' => 'Simple',
            'preview_image_url' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=800&h=1000&fit=crop'
        ]);

        Template::create([
            'name' => 'Professional',
            'description' => 'Executive-level template designed for senior professionals. Emphasizes experience and achievements.',
            'category' => 'Corporate',
            'preview_image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=1000&fit=crop'
        ]);

        Template::create([
            'name' => 'Bold',
            'description' => 'Eye-catching design for those who want to stand out. Great for artists, marketers, and creative fields.',
            'category' => 'Creative',
            'preview_image_url' => 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=800&h=1000&fit=crop'
        ]);
    }
}
