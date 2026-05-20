<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserBanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserBanController extends Controller
{
    public function __construct(
        private readonly UserBanService $userBanService,
    ) {}

    public function ban(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'duration' => ['required', 'string', Rule::in(UserBanService::DURATIONS)],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $updated = $this->userBanService->ban(
                $user,
                $request->user(),
                $validated['duration'],
                $validated['reason'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => $this->banSuccessMessage($validated['duration']),
            'data' => array_merge(
                $updated->toArray(),
                ['ban' => $this->userBanService->banStatusPayload($updated)]
            ),
        ]);
    }

    public function unban(Request $request, User $user): JsonResponse
    {
        if (! $user->banned_at && ! $user->banned_permanently && ! $user->banned_until) {
            return response()->json([
                'status' => false,
                'message' => 'User is not banned.',
            ], 422);
        }

        $updated = $this->userBanService->liftBan($user);

        return response()->json([
            'status' => true,
            'message' => 'Ban lifted. User can sign in again.',
            'data' => array_merge(
                $updated->toArray(),
                ['ban' => $this->userBanService->banStatusPayload($updated)]
            ),
        ]);
    }

    private function banSuccessMessage(string $duration): string
    {
        return match ($duration) {
            UserBanService::DURATION_PERMANENT => 'User permanently banned.',
            UserBanService::DURATION_3_DAYS => 'User banned for 3 days.',
            UserBanService::DURATION_7_DAYS => 'User banned for 7 days.',
            UserBanService::DURATION_15_DAYS => 'User banned for 15 days.',
            UserBanService::DURATION_1_MONTH => 'User banned for 1 month.',
            default => 'User banned.',
        };
    }
}
