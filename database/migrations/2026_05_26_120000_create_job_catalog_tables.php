<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_skill_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('job_education_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (Schema::hasColumn('recruiter_jobs', 'education_requirements')) {
            Schema::table('recruiter_jobs', function (Blueprint $table) {
                $table->json('education_requirements_json')->nullable()->after('experience_max_years');
            });

            $jobs = DB::table('recruiter_jobs')
                ->whereNotNull('education_requirements')
                ->where('education_requirements', '!=', '')
                ->get(['id', 'education_requirements']);

            foreach ($jobs as $row) {
                DB::table('recruiter_jobs')
                    ->where('id', $row->id)
                    ->update([
                        'education_requirements_json' => json_encode([$row->education_requirements]),
                    ]);
            }

            Schema::table('recruiter_jobs', function (Blueprint $table) {
                $table->dropColumn('education_requirements');
            });

            Schema::table('recruiter_jobs', function (Blueprint $table) {
                $table->renameColumn('education_requirements_json', 'education_requirements');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recruiter_jobs', 'education_requirements')) {
            Schema::table('recruiter_jobs', function (Blueprint $table) {
                $table->text('education_requirements_text')->nullable()->after('experience_max_years');
            });

            $jobs = DB::table('recruiter_jobs')->whereNotNull('education_requirements')->get();

            foreach ($jobs as $row) {
                $decoded = json_decode($row->education_requirements, true);
                $text = is_array($decoded) ? implode("\n", $decoded) : (string) $row->education_requirements;
                DB::table('recruiter_jobs')
                    ->where('id', $row->id)
                    ->update(['education_requirements_text' => $text ?: null]);
            }

            Schema::table('recruiter_jobs', function (Blueprint $table) {
                $table->dropColumn('education_requirements');
            });

            Schema::table('recruiter_jobs', function (Blueprint $table) {
                $table->renameColumn('education_requirements_text', 'education_requirements');
            });
        }

        Schema::dropIfExists('job_education_catalog');
        Schema::dropIfExists('job_skill_catalog');
    }
};
