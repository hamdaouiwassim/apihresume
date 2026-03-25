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
        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_company')->nullable();
            $table->text('recipient_address')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('date')->nullable();
            $table->string('subject')->nullable();
            $table->longText('content')->nullable();
            $table->string('style')->default('classic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
    }
};
