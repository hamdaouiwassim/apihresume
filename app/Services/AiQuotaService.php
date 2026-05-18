<?php

namespace App\Services;

use App\Models\User;

class AiQuotaService
{
    private const KIND_TO_COLUMN = [
        'enhance_text' => 'ai_enhance_used',
        'tailor_resume' => 'ai_tailor_used',
        'ats_score' => 'ai_ats_used',
    ];

    public function isUnlimited(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) ($user->is_admin ?? false) || (bool) ($user->is_pro ?? false);
    }

    public function hasQuotaColumns(User $user): bool
    {
        return array_key_exists('ai_usage_month', $user->getAttributes());
    }

    public function resetCycleIfNeeded(User $user): void
    {
        if (! $this->hasQuotaColumns($user)) {
            return;
        }

        $ym = now()->format('Y-m');
        if (($user->ai_usage_month ?? '') !== $ym) {
            $user->forceFill([
                'ai_usage_month' => $ym,
                'ai_enhance_used' => 0,
                'ai_tailor_used' => 0,
                'ai_ats_used' => 0,
            ])->save();
        }
    }

    public function limitFor(string $kind): int
    {
        $limits = config('monetization.free_monthly', []);

        return (int) ($limits[$kind] ?? 0);
    }

    public function hasRemaining(User $user, string $kind): bool
    {
        if ($this->isUnlimited($user)) {
            return true;
        }

        if (! $this->hasQuotaColumns($user)) {
            return false;
        }

        $column = self::KIND_TO_COLUMN[$kind] ?? null;
        if (! $column) {
            return false;
        }

        $this->resetCycleIfNeeded($user);
        $user->refresh();

        $used = (int) ($user->{$column} ?? 0);

        return $used < $this->limitFor($kind);
    }

    public function increment(User $user, string $kind): void
    {
        if ($this->isUnlimited($user)) {
            return;
        }

        if (! $this->hasQuotaColumns($user)) {
            return;
        }

        $column = self::KIND_TO_COLUMN[$kind] ?? null;
        if (! $column) {
            return;
        }

        $this->resetCycleIfNeeded($user);
        $user->refresh();
        $user->increment($column);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        if (! array_key_exists('ai_usage_month', $user->getAttributes())) {
            return [
                'is_unlimited' => $this->isUnlimited($user),
                'legacy' => true,
            ];
        }

        $this->resetCycleIfNeeded($user);
        $user->refresh();

        $unlimited = $this->isUnlimited($user);
        $limits = config('monetization.free_monthly', []);

        $row = function (string $kind, string $column) use ($user, $limits, $unlimited): array {
            $limit = (int) ($limits[$kind] ?? 0);
            $used = (int) ($user->{$column} ?? 0);
            $remaining = $unlimited ? null : max(0, $limit - $used);

            return [
                'used' => $used,
                'limit' => $limit,
                'remaining' => $remaining,
            ];
        };

        return [
            'is_unlimited' => $unlimited,
            'cycle' => $user->ai_usage_month,
            'enhance' => $row('enhance_text', 'ai_enhance_used'),
            'tailor' => $row('tailor_resume', 'ai_tailor_used'),
            'ats' => $row('ats_score', 'ai_ats_used'),
        ];
    }
}
