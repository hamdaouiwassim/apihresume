<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnverifiedAccountCleanupService
{
    public function gracePeriodDays(): int
    {
        return max(1, (int) config('auth.unverified_account_deletion_days', 30));
    }

    /**
     * Users eligible for removal: never verified, past grace period, not admin.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function eligibleUsersQuery()
    {
        $cutoff = now()->subDays($this->gracePeriodDays());

        return User::query()
            ->whereNull('email_verified_at')
            ->where('is_admin', false)
            ->where('created_at', '<=', $cutoff);
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleUsers(int $limit = 500): Collection
    {
        return $this->eligibleUsersQuery()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{deleted: int, dry_run: bool, eligible: int}
     */
    public function prune(bool $dryRun = false, int $batchSize = 100): array
    {
        $deleted = 0;
        $eligible = 0;

        $this->eligibleUsersQuery()
            ->orderBy('id')
            ->chunkById($batchSize, function ($users) use ($dryRun, &$deleted, &$eligible) {
                foreach ($users as $user) {
                    $eligible++;

                    if ($dryRun) {
                        continue;
                    }

                    DB::transaction(function () use ($user) {
                        $user->tokens()->delete();
                        $user->delete(); // soft delete — admin can restore
                    });

                    Log::info('Soft-deleted unverified account', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'created_at' => $user->created_at?->toIso8601String(),
                    ]);

                    $deleted++;
                }
            });

        return [
            'deleted' => $deleted,
            'dry_run' => $dryRun,
            'eligible' => $eligible,
            'grace_period_days' => $this->gracePeriodDays(),
        ];
    }
}
