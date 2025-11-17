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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('is_recruiter');
            $table->string('company_size')->nullable()->after('company_name');
            $table->string('industry_focus')->nullable()->after('company_size');
            $table->string('hiring_focus')->nullable()->after('industry_focus');
            $table->string('recruiter_role')->nullable()->after('hiring_focus');
            $table->string('recruiter_phone')->nullable()->after('recruiter_role');
            $table->string('recruiter_linkedin')->nullable()->after('recruiter_phone');
            $table->boolean('compliance_accepted')->default(false)->after('recruiter_linkedin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_size',
                'industry_focus',
                'hiring_focus',
                'recruiter_role',
                'recruiter_phone',
                'recruiter_linkedin',
                'compliance_accepted',
            ]);
        });
    }
};
