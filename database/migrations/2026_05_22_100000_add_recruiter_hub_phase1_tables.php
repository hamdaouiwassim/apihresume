<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->boolean('open_to_recruiters')->default(false)->after('public_profile_meta_description');
            $table->timestamp('recruiter_visible_at')->nullable()->after('open_to_recruiters');
            $table->index('open_to_recruiters');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('default_open_to_recruiters')->default(false)->after('ai_monthly_token_limit');
        });

        Schema::create('recruiter_resume_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('share');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['resume_id', 'granted_to_user_id']);
            $table->index(['granted_to_user_id', 'expires_at']);
        });

        Schema::create('recruiter_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 64);
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['recruiter_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_activity_logs');
        Schema::dropIfExists('recruiter_resume_access');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_open_to_recruiters');
        });

        Schema::table('resumes', function (Blueprint $table) {
            $table->dropIndex(['open_to_recruiters']);
            $table->dropColumn(['open_to_recruiters', 'recruiter_visible_at']);
        });
    }
};
