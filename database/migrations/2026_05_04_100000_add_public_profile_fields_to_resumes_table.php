<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->boolean('public_profile_enabled')->default(false)->after('typography');
            $table->string('public_profile_slug', 80)->nullable()->after('public_profile_enabled');
            $table->string('public_profile_meta_title', 120)->nullable()->after('public_profile_slug');
            $table->string('public_profile_meta_description', 320)->nullable()->after('public_profile_meta_title');
        });

        Schema::table('resumes', function (Blueprint $table) {
            $table->unique('public_profile_slug');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropUnique(['public_profile_slug']);
            $table->dropColumn([
                'public_profile_enabled',
                'public_profile_slug',
                'public_profile_meta_title',
                'public_profile_meta_description',
            ]);
        });
    }
};
