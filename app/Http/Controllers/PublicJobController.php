<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicJobController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = RecruiterJob::query()
            ->where('status', 'open')
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => [
                'data' => $jobs->items(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('slug', $slug)
            ->where('status', 'open')
            ->firstOrFail();

        return response()->json(['status' => true, 'data' => $job]);
    }

    public function apply(Request $request, string $slug): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('slug', $slug)
            ->where('status', 'open')
            ->firstOrFail();

        $validated = Validator::make($request->all(), [
            'resume_id' => 'required|integer|exists:resumes,id',
            'cover_note' => 'nullable|string|max:5000',
        ])->validate();

        $user = $request->user();
        $resume = Resume::findOrFail($validated['resume_id']);

        if ($resume->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Invalid resume.'], 403);
        }

        $application = JobApplication::updateOrCreate(
            ['job_id' => $job->id, 'resume_id' => $resume->id],
            [
                'user_id' => $user->id,
                'cover_note' => $validated['cover_note'] ?? null,
                'status' => 'new',
                'applied_at' => now(),
            ]
        );

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
}
