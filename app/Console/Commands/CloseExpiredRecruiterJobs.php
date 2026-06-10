<?php

namespace App\Console\Commands;

use App\Services\RecruiterJobClosureService;
use Illuminate\Console\Command;

class CloseExpiredRecruiterJobs extends Command
{
    protected $signature = 'jobs:close-expired';

    protected $description = 'Close open job postings that are past their application deadline';

    public function handle(RecruiterJobClosureService $closure): int
    {
        $closed = $closure->closeExpiredOpenJobs();

        $this->info("Closed {$closed} expired job posting(s).");

        return self::SUCCESS;
    }
}
