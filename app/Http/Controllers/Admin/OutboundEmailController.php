<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewFeaturesEmailRequest;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\OutboundEmailService;
use App\Support\AdminPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutboundEmailController extends Controller
{
    public function __construct(
        private readonly OutboundEmailService $outboundEmailService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:queued,processing,sent,failed,skipped'],
            'type' => ['nullable', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.AdminPagination::MAX],
        ]);

        $perPage = AdminPagination::resolve($request);

        $query = OutboundEmail::query()
            ->with([
                'user:id,name,email',
                'triggeredBy:id,name,email',
                'resume:id,name',
            ])
            ->orderByDesc('created_at');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }
        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json([
            'status' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->outboundEmailService->dashboardSummary(),
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.OutboundEmail::TYPE_RESUME_REMINDER.','.OutboundEmail::TYPE_VERIFICATION_REMINDER],
            'filter' => ['required', 'string', 'in:unverified,incomplete_resume'],
        ]);

        $result = $this->outboundEmailService->queueBulk(
            $request->user(),
            $validated['type'],
            $validated['filter'],
        );

        return response()->json([
            'status' => true,
            'message' => "Queued {$result['queued']} email(s), skipped {$result['skipped']}.",
            'data' => $result,
        ]);
    }

    public function sendResumeReminder(Request $request, User $user): JsonResponse
    {
        try {
            $outbound = $this->outboundEmailService->queueResumeReminder($request->user(), $user);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Resume reminder queued.',
            'data' => $outbound->load(['user:id,name,email', 'triggeredBy:id,name,email']),
        ]);
    }

    public function sendVerificationReminder(Request $request, User $user): JsonResponse
    {
        try {
            $outbound = $this->outboundEmailService->queueVerificationReminder($request->user(), $user);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Verification reminder queued.',
            'data' => $outbound->load(['user:id,name,email', 'triggeredBy:id,name,email']),
        ]);
    }

    public function sendNewFeaturesToUser(NewFeaturesEmailRequest $request, User $user): JsonResponse
    {
        try {
            $outbound = $this->outboundEmailService->queueNewFeaturesAnnouncement(
                $request->user(),
                $user,
                $request->validated('subject'),
                $request->headline(),
                $request->validated('message'),
                $request->normalizedLinks(),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'New features announcement queued.',
            'data' => $outbound->load(['user:id,name,email', 'triggeredBy:id,name,email']),
        ]);
    }

    public function sendNewFeaturesBulk(NewFeaturesEmailRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['required', 'string', 'in:all_users,verified,pro,unverified,incomplete_resume'],
        ]);

        $result = $this->outboundEmailService->queueNewFeaturesBulk(
            $request->user(),
            $validated['filter'],
            $request->validated('subject'),
            $request->headline(),
            $request->validated('message'),
            $request->normalizedLinks(),
        );

        return response()->json([
            'status' => true,
            'message' => "Queued {$result['queued']} announcement(s), skipped {$result['skipped']}.",
            'data' => $result,
        ]);
    }
}
