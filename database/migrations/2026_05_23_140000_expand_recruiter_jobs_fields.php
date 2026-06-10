<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_jobs', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('title');
            $table->string('company_logo')->nullable()->after('company_name');
            $table->string('company_industry')->nullable()->after('company_logo');
            $table->string('company_size')->nullable()->after('company_industry');
            $table->text('company_description')->nullable()->after('company_size');
            $table->string('company_website')->nullable()->after('company_description');

            $table->enum('location_type', ['remote', 'hybrid', 'onsite'])->nullable()->after('location');
            $table->string('location_city')->nullable()->after('location_type');
            $table->string('location_country')->nullable()->after('location_city');
            $table->text('office_details')->nullable()->after('location_country');

            $table->enum('employment_type', [
                'full_time',
                'part_time',
                'internship',
                'freelance',
                'contract',
                'temporary',
            ])->nullable()->after('office_details');

            $table->decimal('salary_min', 12, 2)->nullable()->after('employment_type');
            $table->decimal('salary_max', 12, 2)->nullable()->after('salary_min');
            $table->string('salary_currency', 3)->default('USD')->after('salary_max');

            $table->json('required_skills')->nullable()->after('description');
            $table->unsignedSmallInteger('experience_min_years')->nullable()->after('required_skills');
            $table->unsignedSmallInteger('experience_max_years')->nullable()->after('experience_min_years');
            $table->text('education_requirements')->nullable()->after('experience_max_years');
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_logo',
                'company_industry',
                'company_size',
                'company_description',
                'company_website',
                'location_type',
                'location_city',
                'location_country',
                'office_details',
                'employment_type',
                'salary_min',
                'salary_max',
                'salary_currency',
                'required_skills',
                'experience_min_years',
                'experience_max_years',
                'education_requirements',
            ]);
        });
    }
};
