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
        Schema::create('recruiters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'revoked'])->default('pending');
            $table->string('company_name');
            $table->string('company_size')->nullable();
            $table->string('industry_focus');
            $table->string('hiring_focus')->nullable();
            $table->string('recruiter_role')->nullable();
            $table->string('recruiter_phone')->nullable();
            $table->string('recruiter_linkedin')->nullable();
            $table->boolean('compliance_accepted')->default(false);
            $table->string('brand_avatar')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruiters');
    }
};
