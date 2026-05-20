<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('brand_avatar')->nullable();
            $table->timestamps();
        });

        Schema::create('recruiter_organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('recruiter_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'recruiter', 'viewer'])->default('recruiter');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained('recruiter_organizations')->nullOnDelete();
        });

        Schema::create('recruiter_hm_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('label')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_hm_share_links');

        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::dropIfExists('recruiter_organization_members');
        Schema::dropIfExists('recruiter_organizations');
    }
};
