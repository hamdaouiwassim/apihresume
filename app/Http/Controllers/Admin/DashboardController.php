<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\CoverLetter;
use App\Models\OutboundEmail;
use App\Models\Resume;
use App\Models\Template;
use App\Models\User;
use App\Models\WorkCertificate;
use App\Services\OutboundEmailService;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index()
    {
        try {
            $aiSince = now()->subDays(30)->startOfDay();
            $aiBase = AiUsageLog::query()->where('created_at', '>=', $aiSince);

            $stats = [
                'total_users' => User::count(),
                'total_admins' => User::where('is_admin', true)->count(),
                'total_templates' => Template::count(),
                'total_resumes' => Resume::count(),
                'total_cover_letters' => CoverLetter::count(),
                'total_work_certificates' => WorkCertificate::count(),
                'cover_letters_this_month' => CoverLetter::where('created_at', '>=', now()->startOfMonth())->count(),
                'work_certificates_this_month' => WorkCertificate::where('created_at', '>=', now()->startOfMonth())->count(),
                'total_recruiters' => User::where('is_recruiter', true)->count(),
                'active_users_24h' => User::where('last_activity', '>=', now()->subDay())->count(),
                'active_users_7d' => User::where('last_activity', '>=', now()->subDays(7))->count(),
                'ai_usage_30d' => [
                    'calls' => (clone $aiBase)->count(),
                    'total_tokens' => (int) (clone $aiBase)->sum('total_tokens'),
                ],
                'recent_users' => User::orderBy('created_at', 'desc')->limit(5)->get(['id', 'name', 'email', 'avatar', 'created_at']),
                'recent_templates' => Template::orderBy('created_at', 'desc')->limit(5)->get(['id', 'name', 'category', 'created_at']),
                'recent_cover_letters' => CoverLetter::with('user:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'user_id', 'title', 'style', 'created_at']),
                'recent_work_certificates' => WorkCertificate::with('user:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'user_id', 'title', 'company_name', 'employee_name', 'created_at']),
                'outbound_emails' => app(OutboundEmailService::class)->dashboardSummary(),
                'recent_outbound_emails' => OutboundEmail::query()
                    ->with(['user:id,name,email', 'triggeredBy:id,name,email'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Dashboard stats fetched successfully',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
