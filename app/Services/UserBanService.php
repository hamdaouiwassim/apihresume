<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class UserBanService
{
    public const DURATION_3_DAYS = '3_days';

    public const DURATION_7_DAYS = '7_days';

    public const DURATION_15_DAYS = '15_days';

    public const DURATION_1_MONTH = '1_month';

    public const DURATION_PERMANENT = 'permanent';

    /** @var list<string> */
    public const DURATIONS = [
        self::DURATION_3_DAYS,
        self::DURATION_7_DAYS,
        self::DURATION_15_DAYS,
        self::DURATION_1_MONTH,
        self::DURATION_PERMANENT,
    ];

    public function isBanned(User $user): bool
    {
        $user = $this->clearExpiredBanIfNeeded($user);

        if ($user->banned_permanently) {
            return true;
        }

        return $user->banned_until !== null && $user->banned_until->isFuture();
    }

    public function clearExpiredBanIfNeeded(User $user): User
    {
        if ($user->banned_permanently || $user->banned_until === null) {
            return $user;
        }

        if ($user->banned_until->isPast()) {
            $this->liftBan($user);

            return $user->fresh();
        }

        return $user;
    }

    public function ban(User $target, User $admin, string $duration, ?string $reason = null): User
    {
        if ($target->is_admin) {
            throw new InvalidArgumentException('Admin accounts cannot be banned.');
        }

        if ($admin->id === $target->id) {
            throw new InvalidArgumentException('You cannot ban your own account.');
        }

        if (! in_array($duration, self::DURATIONS, true)) {
            throw new InvalidArgumentException('Invalid ban duration.');
        }

        $permanent = $duration === self::DURATION_PERMANENT;
        $until = $permanent ? null : $this->resolveUntil($duration);

        $target->forceFill([
            'banned_at' => now(),
            'banned_until' => $until,
            'banned_permanently' => $permanent,
            'ban_reason' => $reason ? trim($reason) : null,
            'banned_by_user_id' => $admin->id,
        ])->save();

        $target->tokens()->delete();

        return $target->fresh(['bannedBy:id,name,email']);
    }

    public function liftBan(User $user): User
    {
        $user->forceFill([
            'banned_at' => null,
            'banned_until' => null,
            'banned_permanently' => false,
            'ban_reason' => null,
            'banned_by_user_id' => null,
        ])->save();

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function banStatusPayload(User $user): array
    {
        $user = $this->clearExpiredBanIfNeeded($user);
        $banned = $this->isBanned($user);

        return [
            'is_banned' => $banned,
            'banned_permanently' => (bool) $user->banned_permanently,
            'banned_at' => $user->banned_at,
            'banned_until' => $user->banned_until,
            'ban_reason' => $user->ban_reason,
            'banned_by' => $user->relationLoaded('bannedBy')
                ? $user->bannedBy?->only(['id', 'name', 'email'])
                : null,
        ];
    }

    private function resolveUntil(string $duration): Carbon
    {
        return match ($duration) {
            self::DURATION_3_DAYS => now()->addDays(3),
            self::DURATION_7_DAYS => now()->addDays(7),
            self::DURATION_15_DAYS => now()->addDays(15),
            self::DURATION_1_MONTH => now()->addMonth(),
            default => throw new InvalidArgumentException('Invalid ban duration.'),
        };
    }
}
