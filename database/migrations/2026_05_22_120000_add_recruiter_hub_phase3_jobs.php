<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('location')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index(['created_by_user_id', 'status']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('recruiter_jobs')->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['new', 'reviewing', 'shortlisted', 'rejected', 'hired'])->default('new');
            $table->text('cover_note')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedTinyInteger('match_score')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['job_id', 'resume_id']);
            $table->index(['job_id', 'status']);
        });

        Schema::table('recruiter_shortlists', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('recruiter_jobs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_shortlists', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('recruiter_jobs');
    }
};
