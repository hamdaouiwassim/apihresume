<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiters', function (Blueprint $table) {
            $table->string('company_website')->nullable()->after('industry_focus');
        });
    }

    public function down(): void
    {
        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropColumn('company_website');
        });
    }
};
