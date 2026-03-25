<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cover_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('job_type');
            $table->string('language', 2)->default('en'); // 'en' or 'fr'
            $table->string('subject');
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cover_letter_templates');
    }
};
