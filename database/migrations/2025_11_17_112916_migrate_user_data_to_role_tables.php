<?php

use App\Models\User;
use App\Models\Recruiter;
use App\Models\Candidate;
use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate recruiter data
        $recruiters = User::where('is_recruiter', true)->get();
        foreach ($recruiters as $user) {
            Recruiter::create([
                'user_id' => $user->id,
                'status' => $user->recruiter_status ?? 'pending',
                'company_name' => $user->company_name ?? '',
                'company_size' => $user->company_size,
                'industry_focus' => $user->industry_focus ?? '',
                'hiring_focus' => $user->hiring_focus,
                'recruiter_role' => $user->recruiter_role,
                'recruiter_phone' => $user->recruiter_phone,
                'recruiter_linkedin' => $user->recruiter_linkedin,
                'compliance_accepted' => $user->compliance_accepted ?? false,
                'brand_avatar' => $user->brand_avatar,
                'admin_notes' => $user->recruiter_admin_notes,
            ]);
        }

        // Migrate admin data
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $user) {
            Admin::create([
                'user_id' => $user->id,
                'role' => 'admin',
                'notes' => null,
            ]);
        }

        // Migrate candidate data (all non-recruiter, non-admin users)
        $candidates = User::where('is_admin', false)
            ->where('is_recruiter', false)
            ->get();
        foreach ($candidates as $user) {
            Candidate::create([
                'user_id' => $user->id,
                'bio' => null,
                'linkedin_url' => null,
                'portfolio_url' => null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible
        // Data would need to be moved back manually if needed
        Recruiter::truncate();
        Admin::truncate();
        Candidate::truncate();
    }
};
