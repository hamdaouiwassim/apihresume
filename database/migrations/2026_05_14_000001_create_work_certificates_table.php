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
        Schema::create('work_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Certificate of Employment');
            $table->string('employee_name');
            $table->string('employee_job_title')->nullable();
            $table->string('company_name');
            $table->text('company_address')->nullable();
            $table->date('employment_start');
            $table->date('employment_end')->nullable();
            $table->boolean('is_current_employment')->default(false);
            $table->text('duties_summary')->nullable();
            $table->string('letter_place')->nullable();
            $table->date('letter_date')->nullable();
            $table->string('signer_name_title')->nullable();
            $table->string('locale', 8)->default('en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_certificates');
    }
};
