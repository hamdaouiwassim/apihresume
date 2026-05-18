<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linkedin_id')->nullable()->unique()->after('google_refresh_token');
            $table->string('linkedin_avatar')->nullable()->after('linkedin_id');
            $table->text('linkedin_token')->nullable()->after('linkedin_avatar');
            $table->text('linkedin_refresh_token')->nullable()->after('linkedin_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'linkedin_id',
                'linkedin_avatar',
                'linkedin_token',
                'linkedin_refresh_token',
            ]);
        });
    }
};
