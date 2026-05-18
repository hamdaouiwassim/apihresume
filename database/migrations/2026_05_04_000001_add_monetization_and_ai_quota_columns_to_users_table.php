<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_pro')->default(false)->after('is_recruiter');
            $table->string('ai_usage_month', 7)->nullable()->after('is_pro');
            $table->unsignedSmallInteger('ai_enhance_used')->default(0)->after('ai_usage_month');
            $table->unsignedSmallInteger('ai_tailor_used')->default(0)->after('ai_enhance_used');
            $table->unsignedSmallInteger('ai_ats_used')->default(0)->after('ai_tailor_used');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_pro',
                'ai_usage_month',
                'ai_enhance_used',
                'ai_tailor_used',
                'ai_ats_used',
            ]);
        });
    }
};
