<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_compare_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('recruiter_jobs')->cascadeOnDelete();
            $table->foreignId('recruiter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_run_id')->nullable()->constrained('job_compare_runs')->nullOnDelete();
            $table->enum('mode', ['standard', 'deep']);
            $table->json('resume_ids');
            $table->json('results');
            $table->unsignedTinyInteger('candidate_count')->default(0);
            $table->timestamps();

            $table->index(['job_id', 'recruiter_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_compare_runs');
    }
};
