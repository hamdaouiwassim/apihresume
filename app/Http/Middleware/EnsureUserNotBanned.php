<?php

namespace App\Http\Middleware;

use App\Services\UserBanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    public function __construct(
        private readonly UserBanService $userBanService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user = $this->userBanService->clearExpiredBanIfNeeded($user);

        if ($this->userBanService->isBanned($user)) {
            $user->tokens()->delete();

            return response()->json([
                'status' => false,
                'message' => $this->banMessage($user),
                'code' => 'account_banned',
                'ban' => $this->userBanService->banStatusPayload($user),
            ], 403);
        }

        return $next($request);
    }

    private function banMessage($user): string
    {
        if ($user->banned_permanently) {
            return __('Your account has been permanently suspended. Contact support if you believe this is a mistake.');
        }

        if ($user->banned_until) {
            return __('Your account is suspended until :date.', [
                'date' => $user->banned_until->timezone(config('app.timezone'))->format('F j, Y g:i A'),
            ]);
        }

        return __('Your account is currently suspended.');
    }
}
