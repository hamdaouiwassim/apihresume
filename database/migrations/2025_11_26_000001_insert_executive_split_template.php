<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('templates')
            ->where('name', 'Executive Split')
            ->exists();

        if (!$exists) {
            DB::table('templates')->insert([
                'name' => 'Executive Split',
                'description' => 'Premium split-column layout with sidebar focus on contact, education, and skills.',
                'category' => 'Corporate',
                'preview_image_url' => 'https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?w=800&h=1000&fit=crop',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('templates')
            ->where('name', 'Executive Split')
            ->delete();
    }
};

