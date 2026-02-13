<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_fonts', function (Blueprint $table) {
            $table->id();
            $table->string('family_name')->unique();
            $table->string('regular_path');
            $table->string('bold_path')->nullable();
            $table->string('italic_path')->nullable();
            $table->string('bold_italic_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_fonts');
    }
};
