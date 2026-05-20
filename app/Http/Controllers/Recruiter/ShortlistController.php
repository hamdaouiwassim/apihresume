<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruiterResumeResource;
use App\Models\RecruiterShortlist;
use App\Models\RecruiterShortlistItem;
use App\Models\Resume;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShortlistController extends Controller
{
    public function __construct(
        private readonly RecruiterResumeAccessService $access,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $lists = RecruiterShortlist::query()
            ->where('recruiter_user_id', $request->user()->id)
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['status' => true, 'data' => $lists]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'job_id' => 'nullable|integer|exists:recruiter_jobs,id',
        ])->validate();

        if (! empty($validated['job_id'])) {
            $ownsJob = \App\Models\RecruiterJob::query()
                ->where('id', $validated['job_id'])
                ->where('created_by_user_id', $request->user()->id)
                ->exists();
            if (! $ownsJob) {
                return response()->json(['status' => false, 'message' => 'Invalid job.'], 422);
            }
        }

        $list = RecruiterShortlist::create([
            'recruiter_user_id' => $request->user()->id,
            'name' => $validated['name'],
            'job_id' => $validated['job_id'] ?? null,
        ]);

        return response()->json(['status' => true, 'data' => $list], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $list = RecruiterShortlist::query()
            ->where('recruiter_user_id', $request->user()->id)
            ->with(['items.resume.user', 'items.resume.template', 'items.resume.basicInfo', 'items.resume.skills'])
            ->findOrFail($id);

        $items = $list->items->map(function (RecruiterShortlistItem $item) use ($request) {
            return [
                'id' => $item->id,
                'notes' => $item->notes,
                'contact_revealed' => $item->contact_revealed,
                'resume' => new RecruiterResumeResource($item->resume),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'shortlist' => $list->only(['id', 'name', 'job_id', 'created_at']),
                'items' => $items,
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $list = RecruiterShortlist::query()
            ->where('recruiter_user_id', $request->user()->id)
            ->findOrFail($id);
        $list->delete();

        return response()->json(['status' => true, 'message' => 'Shortlist deleted.']);
    }

    public function addItem(Request $request, int $id): JsonResponse
    {
        $list = RecruiterShortlist::query()
            ->where('recruiter_user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = Validator::make($request->all(), [
            'resume_id' => 'required|integer|exists:resumes,id',
            'notes' => 'nullable|string|max:5000',
            'reveal_contact' => 'sometimes|boolean',
        ])->validate();

        $resume = Resume::findOrFail($validated['resume_id']);
        if (! $this->access->visibleTo($request->user(), $resume)) {
            return response()->json(['status' => false, 'message' => 'Resume not accessible.'], 403);
        }

        $item = RecruiterShortlistItem::updateOrCreate(
            ['shortlist_id' => $list->id, 'resume_id' => $resume->id],
            [
                'added_by_user_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'contact_revealed' => (bool) ($validated['reveal_contact'] ?? false),
            ]
        );

        $this->access->logActivity($request->user(), 'shortlist_add', $resume->id, ['shortlist_id' => $list->id], $request);

        return response()->json(['status' => true, 'data' => $item], 201);
    }

    public function removeItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $list = RecruiterShortlist::query()
            ->where('recruiter_user_id', $request->user()->id)
            ->findOrFail($id);

        RecruiterShortlistItem::query()
            ->where('shortlist_id', $list->id)
            ->where('id', $itemId)
            ->delete();

        return response()->json(['status' => true, 'message' => 'Removed from shortlist.']);
    }
}
