<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recruiter signup only collects basics; company/industry/compliance
     * are completed on the profile step afterward.
     */
    public function up(): void
    {
        Schema::table('recruiters', function (Blueprint $table) {
            $table->string('industry_focus')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('recruiters', function (Blueprint $table) {
            $table->string('industry_focus')->nullable(false)->change();
        });
    }
};
