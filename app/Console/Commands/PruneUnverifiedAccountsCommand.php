<?php

namespace App\Console\Commands;

use App\Services\UnverifiedAccountCleanupService;
use Illuminate\Console\Command;

class PruneUnverifiedAccountsCommand extends Command
{
    protected $signature = 'accounts:prune-unverified
                            {--dry-run : List how many accounts would be deleted without removing them}';

    protected $description = 'Soft-delete accounts that remain unverified past the configured grace period (restorable by admin)';

    public function handle(UnverifiedAccountCleanupService $cleanup): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = $cleanup->gracePeriodDays();

        $this->info(sprintf(
            '%s unverified accounts older than %d day(s)...',
            $dryRun ? 'Checking' : 'Pruning',
            $days
        ));

        $result = $cleanup->prune($dryRun);

        if ($dryRun) {
            $this->warn("Dry run: {$result['eligible']} account(s) would be soft-deleted.");

            return self::SUCCESS;
        }

        $this->info("Soft-deleted {$result['deleted']} unverified account(s).");

        return self::SUCCESS;
    }
}
