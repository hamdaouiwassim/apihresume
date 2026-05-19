<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\User;
use Carbon\Carbon;

class AiTokenLimitService
{
    /** Only platform admins get unlimited tokens by role. */
    public function isUnlimitedByRole(User $user): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    /**
     * Role-based default when ai_monthly_token_limit is null.
     */
    public function defaultMonthlyLimitForRole(User $user): int
    {
        if ($user->hasProAccess()) {
            return (int) config('monetization.pro_monthly_token_limit', 50000);
        }

        return (int) config('monetization.default_monthly_token_limit', 1000);
    }

    /**
     * Effective monthly token cap. null = unlimited (admins only, by default).
     */
    public function monthlyLimit(User $user): ?int
    {
        if (! array_key_exists('ai_monthly_token_limit', $user->getAttributes())) {
            return $this->isUnlimitedByRole($user) ? null : $this->defaultMonthlyLimitForRole($user);
        }

        if ($user->ai_monthly_token_limit !== null) {
            return (int) $user->ai_monthly_token_limit;
        }

        if ($this->isUnlimitedByRole($user)) {
            return null;
        }

        return $this->defaultMonthlyLimitForRole($user);
    }

    public function tokensUsedInMonth(User $user, ?Carbon $month = null): int
    {
        $start = ($month ?? now())->copy()->startOfMonth();
        $end = ($month ?? now())->copy()->endOfMonth();

        return (int) AiUsageLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_tokens');
    }

    public function hasTokenBudget(User $user): bool
    {
        $limit = $this->monthlyLimit($user);
        if ($limit === null) {
            return true;
        }
        if ($limit <= 0) {
            return false;
        }

        return $this->tokensUsedInMonth($user) < $limit;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        $limit = $this->monthlyLimit($user);
        $used = $this->tokensUsedInMonth($user);
        $unlimited = $limit === null;

        $remaining = $unlimited ? null : max(0, $limit - $used);

        $plan = $unlimited ? 'admin' : ($user->hasProAccess() ? 'pro' : 'free');

        return [
            'is_unlimited' => $unlimited,
            'plan' => $plan,
            'month' => now()->format('Y-m'),
            'tokens_used' => $used,
            'token_limit' => $limit,
            'tokens_remaining' => $remaining,
            'credits_used' => $used,
            'credits_total' => $limit,
            'credits_remaining' => $remaining,
            'percent_used' => $unlimited || $limit <= 0
                ? null
                : min(100, (int) round(($used / $limit) * 100)),
            'custom_limit' => $user->ai_monthly_token_limit,
            'default_limit' => $this->defaultMonthlyLimitForRole($user),
            'free_default_limit' => (int) config('monetization.default_monthly_token_limit', 1000),
            'pro_default_limit' => (int) config('monetization.pro_monthly_token_limit', 50000),
        ];
    }
}
