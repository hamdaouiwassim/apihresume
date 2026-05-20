<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruiterResumeResource;
use App\Models\Resume;
use App\Services\RecruiterResumeAccessService;
use App\Support\AdminPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResumeController extends Controller
{
    public function __construct(
        private readonly RecruiterResumeAccessService $access,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $recruiter = $request->user();
            $perPage = AdminPagination::resolve($request);

            $query = $this->access->scopeVisibleTo(
                Resume::with(['user:id,name,email,avatar', 'template:id,name', 'basicInfo', 'skills']),
                $recruiter
            )->orderByDesc('updated_at');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('basicInfo', function ($sub) use ($search) {
                            $sub->where('full_name', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%");
                        });
                });
            }

            if ($templateId = $request->input('template_id')) {
                $query->where('template_id', $templateId);
            }

            if ($fromDate = $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate = $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            if ($skill = $request->input('skill')) {
                $query->whereHas('skills', function ($q) use ($skill) {
                    $q->where('name', 'like', '%'.$skill.'%');
                });
            }

            $paginator = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Resumes fetched successfully',
                'data' => [
                    'data' => RecruiterResumeResource::collection($paginator->items())->resolve(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch resumes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $recruiter = $request->user();
            $resume = Resume::with([
                'user:id,name,email,avatar',
                'template',
                'basicInfo',
                'experiences',
                'educations',
                'skills',
                'hobbies',
                'certificates',
                'languages',
                'projects',
            ])->findOrFail($id);

            if (! $this->access->visibleTo($recruiter, $resume)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this resume.',
                ], 403);
            }

            if ($limit = $this->access->resumeViewLimitExceeded($recruiter)) {
                return response()->json(['status' => false, 'message' => $limit], 429);
            }

            $this->access->logActivity($recruiter, 'view_resume', $resume->id, null, $request);

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => new RecruiterResumeResource($resume),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Resume not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function exportPdf(Request $request, int $id)
    {
        try {
            $recruiter = $request->user();
            $resume = Resume::findOrFail($id);

            if (! $this->access->visibleTo($recruiter, $resume)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this resume.',
                ], 403);
            }

            if ($limit = $this->access->resumeViewLimitExceeded($recruiter)) {
                return response()->json([
                    'status' => false,
                    'message' => $limit,
                ], 429);
            }

            $this->access->logActivity($recruiter, 'export_pdf', $resume->id, null, $request);

            $locale = $request->query('locale', 'en');
            if (! in_array($locale, ['en', 'fr'], true)) {
                $locale = 'en';
            }

            $pdf = app(\App\Http\Controllers\PDFController::class)->binaryFromResume($resume, $locale);
            $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $resume->name ?: 'cv') ?: 'cv';
            $filename = $base.'.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        } catch (\Exception $e) {
            Log::error('Recruiter PDF export failed', ['resume_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'PDF export failed',
            ], 500);
        }
    }
}
