<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('job_id')->nullable();
            $table->timestamps();

            $table->index('recruiter_user_id');
        });

        Schema::create('recruiter_shortlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlist_id')->constrained('recruiter_shortlists')->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('contact_revealed')->default(false);
            $table->timestamps();

            $table->unique(['shortlist_id', 'resume_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_shortlist_items');
        Schema::dropIfExists('recruiter_shortlists');
    }
};
