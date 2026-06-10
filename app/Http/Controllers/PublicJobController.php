<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecruiterJobResource;
use App\Models\CoverLetter;
use App\Models\JobApplication;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Services\RecruiterJobClosureService;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicJobController extends Controller
{
    public function __construct(
        private readonly RecruiterJobClosureService $jobClosure,
    ) {}

    public function index(): JsonResponse
    {
        $this->jobClosure->closeExpiredOpenJobs();

        $jobs = $this->jobClosure->scopeAcceptingApplications(
            RecruiterJob::query()->with('creator:id,name')
        )
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => [
                'data' => RecruiterJobResource::collection($jobs->items())->resolve(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $job = $this->jobClosure->resolveOpenJobBySlug($slug);

        if (! $job || $job->status !== 'open' || ! $job->isAcceptingApplications()) {
            abort(404);
        }

        return response()->json([
            'status' => true,
            'data' => new RecruiterJobResource($job),
        ]);
    }

    public function myApplicationStatus(Request $request, string $slug): JsonResponse
    {
        $job = RecruiterJob::query()->where('slug', $slug)->first();

        if (! $job) {
            abort(404);
        }

        $application = JobApplication::query()
            ->where('job_id', $job->id)
            ->where('user_id', $request->user()->id)
            ->with('resume:id,name')
            ->first();

        return response()->json([
            'status' => true,
            'data' => [
                'has_applied' => $application !== null,
                'applied_at' => $application?->applied_at?->toIso8601String(),
                'resume_name' => $application?->resume?->name,
            ],
        ]);
    }

    public function apply(Request $request, string $slug): JsonResponse
    {
        $job = $this->jobClosure->resolveOpenJobBySlug($slug);

        if (! $job || $job->status !== 'open' || ! $job->isAcceptingApplications()) {
            return response()->json([
                'status' => false,
                'message' => 'This job is no longer accepting applications.',
            ], 422);
        }

        $user = $request->user();

        if (JobApplication::query()->where('job_id', $job->id)->where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You have already applied to this job.',
                'code' => 'already_applied',
            ], 422);
        }

        $validated = Validator::make($request->all(), [
            'resume_id' => 'required|integer|exists:resumes,id',
            'cover_letter_id' => 'nullable|integer|exists:cover_letters,id',
            'cover_note' => 'nullable|string|max:5000',
        ])->validate();

        $resume = Resume::findOrFail($validated['resume_id']);

        if ($resume->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Invalid resume.'], 403);
        }

        $coverNote = $validated['cover_note'] ?? null;
        $coverLetterId = $validated['cover_letter_id'] ?? null;

        if ($coverLetterId) {
            $coverLetter = CoverLetter::query()
                ->where('id', $coverLetterId)
                ->where('user_id', $user->id)
                ->first();

            if (! $coverLetter) {
                return response()->json(['status' => false, 'message' => 'Invalid cover letter.'], 403);
            }

            $coverNote = $this->formatCoverLetterForApplication($coverLetter);
        }

        $application = JobApplication::create([
            'job_id' => $job->id,
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'cover_note' => $coverNote,
            'cover_letter_id' => $coverLetterId,
            'status' => 'new',
            'applied_at' => now(),
        ]);

        app(RecruiterResumeAccessService::class)->grantAccess(
            $resume->id,
            $job->created_by_user_id,
            $user->id,
            'application',
        );

        return response()->json([
            'status' => true,
            'message' => 'Application submitted successfully.',
            'data' => $application,
        ], 201);
    }

    private function formatCoverLetterForApplication(CoverLetter $coverLetter): string
    {
        $parts = array_filter([
            $coverLetter->subject ? 'Subject: '.$coverLetter->subject : null,
            trim((string) $coverLetter->content),
        ]);

        return implode("\n\n", $parts);
    }
}
