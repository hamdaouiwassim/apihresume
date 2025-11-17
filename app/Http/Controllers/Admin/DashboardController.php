<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Template;
use App\Models\Resume;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'total_admins' => User::where('is_admin', true)->count(),
                'total_templates' => Template::count(),
                'total_resumes' => Resume::count(),
                'total_recruiters' => User::where('is_recruiter', true)->count(),
                'active_users_24h' => User::where('last_activity', '>=', now()->subDay())->count(),
                'active_users_7d' => User::where('last_activity', '>=', now()->subDays(7))->count(),
                'recent_users' => User::orderBy('created_at', 'desc')->limit(5)->get(['id', 'name', 'email', 'avatar', 'created_at']),
                'recent_templates' => Template::orderBy('created_at', 'desc')->limit(5)->get(['id', 'name', 'category', 'created_at']),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Dashboard stats fetched successfully',
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
