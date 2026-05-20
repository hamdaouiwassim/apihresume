<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\RecruiterJob;
use App\Services\RecruiterResumeAccessService;
use App\Support\AdminPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    public function __construct(
        private readonly RecruiterResumeAccessService $access,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = AdminPagination::resolve($request);
        $query = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->withCount('applications')
            ->orderByDesc('updated_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:30',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,open,closed',
        ])->validate();

        $status = $validated['status'] ?? 'draft';
        if ($status === 'open' && ($limit = $this->access->openJobLimitExceeded($request->user()))) {
            return response()->json(['status' => false, 'message' => $limit], 429);
        }

        $job = RecruiterJob::create([
            'created_by_user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'location' => $validated['location'] ?? null,
            'status' => $status,
            'slug' => RecruiterJob::uniqueSlug($validated['title']),
        ]);

        return response()->json(['status' => true, 'data' => $job], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json(['status' => true, 'data' => $job]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:30',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|in:draft,open,closed',
        ])->validate();

        if (
            isset($validated['status'])
            && $validated['status'] === 'open'
            && $job->status !== 'open'
            && ($limit = $this->access->openJobLimitExceeded($request->user()))
        ) {
            return response()->json(['status' => false, 'message' => $limit], 429);
        }

        $job->update($validated);

        return response()->json(['status' => true, 'data' => $job->fresh()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($id);
        $job->delete();

        return response()->json(['status' => true, 'message' => 'Job deleted.']);
    }

    public function applications(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($id);

        $perPage = AdminPagination::resolve($request);
        $query = JobApplication::query()
            ->where('job_id', $job->id)
            ->with(['user:id,name,email,avatar', 'resume.template', 'resume.basicInfo'])
            ->orderByDesc('applied_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => [
                'job' => $job,
                'applications' => [
                    'data' => $paginator->items(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }
}
