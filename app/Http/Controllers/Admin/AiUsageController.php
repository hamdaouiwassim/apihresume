<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AiTokenLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiUsageController extends Controller
{
    private const KINDS = ['enhance_text', 'tailor_resume', 'ats_score'];

    public function __construct(
        private readonly AiTokenLimitService $aiTokenLimit,
    ) {}

    /**
     * Paginated usage log (admin only).
     */
    public function logs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'kind' => ['nullable', 'string', 'in:'.implode(',', self::KINDS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = AiUsageLog::query()
            ->with(['user:id,name,email,is_pro,is_admin,ai_monthly_token_limit'])
            ->orderByDesc('created_at');

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }
        if (! empty($validated['kind'])) {
            $query->where('kind', $validated['kind']);
        }
        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        return response()->json([
            'status' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * Aggregated stats for dashboard and charts.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? \Carbon\Carbon::parse($validated['from'])->startOfDay()
            : now()->subDays(30)->startOfDay();
        $to = isset($validated['to'])
            ? \Carbon\Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        $base = AiUsageLog::query()->whereBetween('created_at', [$from, $to]);

        $totals = [
            'calls' => (clone $base)->count(),
            'prompt_tokens' => (int) (clone $base)->sum('prompt_tokens'),
            'completion_tokens' => (int) (clone $base)->sum('completion_tokens'),
            'total_tokens' => (int) (clone $base)->sum('total_tokens'),
        ];

        $byKind = (clone $base)
            ->select([
                'kind',
                DB::raw('COUNT(*) as calls'),
                DB::raw('COALESCE(SUM(prompt_tokens), 0) as prompt_tokens'),
                DB::raw('COALESCE(SUM(completion_tokens), 0) as completion_tokens'),
                DB::raw('COALESCE(SUM(total_tokens), 0) as total_tokens'),
            ])
            ->groupBy('kind')
            ->orderBy('kind')
            ->get();

        $byDay = (clone $base)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as calls'),
                DB::raw('COALESCE(SUM(total_tokens), 0) as total_tokens'),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $topUsers = DB::table('ai_usage_logs')
            ->whereBetween('created_at', [$from, $to])
            ->select('user_id', DB::raw('COUNT(*) as calls'), DB::raw('COALESCE(SUM(total_tokens), 0) as total_tokens'))
            ->groupBy('user_id')
            ->orderByDesc('total_tokens')
            ->limit(15)
            ->get();

        $userIds = $topUsers->pluck('user_id')->filter()->unique()->values()->all();
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $topUsersPayload = $topUsers->map(function ($row) use ($users) {
            $u = $users->get($row->user_id);

            return [
                'user_id' => (int) $row->user_id,
                'name' => $u?->name,
                'email' => $u?->email,
                'calls' => (int) $row->calls,
                'total_tokens' => (int) $row->total_tokens,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'period' => [
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                ],
                'totals' => $totals,
                'by_kind' => $byKind,
                'by_day' => $byDay,
                'top_users' => $topUsersPayload,
                'default_monthly_token_limit' => (int) config('monetization.default_monthly_token_limit', 1000),
                'pro_monthly_token_limit' => (int) config('monetization.pro_monthly_token_limit', 50000),
            ],
        ]);
    }

    /**
     * Users with monthly token usage vs limits (admin management).
     */
    public function userLimits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $search = $validated['search'] ?? null;

        $query = User::query()
            ->select(['id', 'name', 'email', 'is_pro', 'is_admin', 'ai_monthly_token_limit', 'email_verified_at'])
            ->orderByDesc('last_activity')
            ->orderByDesc('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (User $user) {
            $snap = $this->aiTokenLimit->snapshot($user);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_pro' => $user->hasProAccess(),
                'is_admin' => (bool) $user->is_admin,
                'ai_monthly_token_limit' => $user->ai_monthly_token_limit,
                'tokens_used' => $snap['tokens_used'],
                'token_limit' => $snap['token_limit'],
                'tokens_remaining' => $snap['tokens_remaining'],
                'percent_used' => $snap['percent_used'],
                'is_unlimited' => $snap['is_unlimited'],
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $paginator,
        ]);
    }

    /**
     * Set per-user monthly token cap (null = platform default / role rules).
     */
    public function updateUserTokenLimit(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'ai_monthly_token_limit' => ['present', 'nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        $user = User::findOrFail($userId);

        $user->ai_monthly_token_limit = $validated['ai_monthly_token_limit'];
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Token limit updated.',
            'data' => [
                'user_id' => $user->id,
                'ai_monthly_token_limit' => $user->ai_monthly_token_limit,
                'ai_tokens' => $this->aiTokenLimit->snapshot($user->fresh()),
            ],
        ]);
    }
}
