<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\RecruiterActivityLog;
use App\Models\RecruiterJob;
use App\Models\RecruiterShortlist;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RecruiterResumeAccessService $access): JsonResponse
    {
        $recruiter = $request->user();
        $recruiterId = $recruiter->id;

        $applicationsToday = JobApplication::query()
            ->whereHas('job', fn ($q) => $q->where('created_by_user_id', $recruiterId))
            ->whereDate('applied_at', today())
            ->count();

        return response()->json([
            'status' => true,
            'data' => [
                'talent_pool_count' => $access->poolCount(),
                'visible_resumes_count' => $access->visibleCountFor($recruiter),
                'applications_today' => $applicationsToday,
                'open_jobs_count' => RecruiterJob::query()
                    ->where('created_by_user_id', $recruiterId)
                    ->where('status', 'open')
                    ->count(),
                'shortlists_count' => RecruiterShortlist::query()
                    ->where('recruiter_user_id', $recruiterId)
                    ->count(),
                'recent_views' => RecruiterActivityLog::query()
                    ->where('recruiter_user_id', $recruiterId)
                    ->where('action', 'view_resume')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->with('resume:id,name')
                    ->get(['id', 'resume_id', 'created_at']),
            ],
        ]);
    }
}
