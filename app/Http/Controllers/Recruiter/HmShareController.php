<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\RecruiterHmShareLink;
use App\Models\Resume;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HmShareController extends Controller
{
    public function __construct(
        private readonly RecruiterResumeAccessService $access,
    ) {}

    public function store(Request $request, int $resumeId): JsonResponse
    {
        $resume = Resume::findOrFail($resumeId);
        if (! $this->access->visibleTo($request->user(), $resume)) {
            return response()->json(['status' => false, 'message' => 'Resume not accessible.'], 403);
        }

        $validated = Validator::make($request->all(), [
            'label' => 'nullable|string|max:120',
            'expires_in_days' => 'nullable|integer|min:1|max:90',
        ])->validate();

        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays((int) $validated['expires_in_days'])
            : now()->addDays(14);

        $link = RecruiterHmShareLink::create([
            'resume_id' => $resume->id,
            'created_by_user_id' => $request->user()->id,
            'token' => RecruiterHmShareLink::generateToken(),
            'label' => $validated['label'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $shareUrl = $frontendUrl.'/recruiter/hm-view/'.$link->token;

        return response()->json([
            'status' => true,
            'data' => [
                'link' => $link,
                'share_url' => $shareUrl,
            ],
        ], 201);
    }
}
