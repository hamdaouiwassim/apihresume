<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Resume;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Get public statistics
     */
    public function index()
    {
        try {
            $stats = [
                'total_candidates' => User::where('is_admin', false)
                    ->where('is_recruiter', false)
                    ->count(),
                'total_users' => User::count(),
                'total_resumes' => Resume::count(),
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

