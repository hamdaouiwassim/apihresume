<?php

namespace App\Http\Controllers;

use App\Models\Recruiter;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Models\User;
use App\Services\RecruiterJobClosureService;

class StatsController extends Controller
{
    public function __construct(
        private readonly RecruiterJobClosureService $jobClosure,
    ) {}

    /**
     * Get public statistics
     */
    public function index()
    {
        try {
            $this->jobClosure->closeExpiredOpenJobs();

            $stats = [
                'total_candidates' => User::where('is_admin', false)
                    ->where('is_recruiter', false)
                    ->count(),
                'total_users' => User::count(),
                'total_resumes' => Resume::count(),
                'recruiter_partners' => Recruiter::query()
                    ->where('status', 'approved')
                    ->count(),
                'open_jobs' => RecruiterJob::query()
                    ->where('status', 'open')
                    ->where(function ($q) {
                        $q->whereNull('application_closes_at')
                            ->orWhere('application_closes_at', '>', now());
                    })
                    ->count(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Statistics fetched successfully',
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

