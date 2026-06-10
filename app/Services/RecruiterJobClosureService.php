<?php

namespace App\Services;

use App\Models\RecruiterJob;
use Illuminate\Database\Eloquent\Builder;

class RecruiterJobClosureService
{
    public function closeExpiredOpenJobs(): int
    {
        return RecruiterJob::query()
            ->where('status', 'open')
            ->whereNotNull('application_closes_at')
            ->where('application_closes_at', '<=', now())
            ->update(['status' => 'closed']);
    }

    public function scopeAcceptingApplications(Builder $query): Builder
    {
        return $query
            ->where('status', 'open')
            ->where(function (Builder $q) {
                $q->whereNull('application_closes_at')
                    ->orWhere('application_closes_at', '>', now());
            });
    }

    public function resolveOpenJobBySlug(string $slug): ?RecruiterJob
    {
        $this->closeExpiredOpenJobs();

        $job = RecruiterJob::query()->where('slug', $slug)->first();

        if ($job && $job->status === 'open' && ! $job->isAcceptingApplications()) {
            $job->update(['status' => 'closed']);
            $job->refresh();
        }

        return $job;
    }
}
