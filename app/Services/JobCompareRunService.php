<?php

namespace App\Services;

use App\Models\JobCompareRun;
use App\Models\RecruiterJob;
use App\Models\User;

class JobCompareRunService
{
    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    public function sortByMatchScore(array $results): array
    {
        $sorted = $results;
        usort($sorted, fn ($a, $b) => ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0));

        return array_values($sorted);
    }

    /**
     * @param  array<int, array<string, mixed>>  $baseResults
     * @param  array<int, array<string, mixed>>  $scoredSubset
     * @param  array<int, array<string, mixed>>  $deepInsights  keyed by resume_id in each item
     * @return array<int, array<string, mixed>>
     */
    public function mergeDeepIntoResults(array $baseResults, array $scoredSubset, array $deepInsights): array
    {
        $scoreByResume = collect($scoredSubset)->keyBy('resume_id');
        $insightByResume = collect($deepInsights)->keyBy('resume_id');

        return collect($baseResults)->map(function (array $row) use ($scoreByResume, $insightByResume) {
            $id = $row['resume_id'];
            if ($fresh = $scoreByResume->get($id)) {
                $row = array_merge($row, $fresh);
            }
            if ($insight = $insightByResume->get($id)) {
                $row['deep_analysis'] = $insight;
            }

            return $row;
        })->all();
    }

    /**
     * @param  array<int, int>  $resumeIds
     * @param  array<int, array<string, mixed>>  $results
     */
    public function saveRun(
        RecruiterJob $job,
        User $recruiter,
        string $mode,
        array $resumeIds,
        array $results,
        ?int $parentRunId = null,
    ): JobCompareRun {
        $sorted = $this->sortByMatchScore($results);

        return JobCompareRun::create([
            'job_id' => $job->id,
            'recruiter_user_id' => $recruiter->id,
            'parent_run_id' => $parentRunId,
            'mode' => $mode,
            'resume_ids' => array_values($resumeIds),
            'results' => $sorted,
            'candidate_count' => count($sorted),
        ]);
    }

    public function latestStandardRun(RecruiterJob $job, User $recruiter): ?JobCompareRun
    {
        return JobCompareRun::query()
            ->where('job_id', $job->id)
            ->where('recruiter_user_id', $recruiter->id)
            ->where('mode', 'standard')
            ->orderByDesc('created_at')
            ->first();
    }

    public function ownedRun(RecruiterJob $job, User $recruiter, int $runId): JobCompareRun
    {
        return JobCompareRun::query()
            ->where('id', $runId)
            ->where('job_id', $job->id)
            ->where('recruiter_user_id', $recruiter->id)
            ->firstOrFail();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, JobCompareRun>
     */
    public function listForJob(RecruiterJob $job, User $recruiter, int $limit = 20)
    {
        return JobCompareRun::query()
            ->where('job_id', $job->id)
            ->where('recruiter_user_id', $recruiter->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'mode', 'parent_run_id', 'candidate_count', 'created_at']);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatRunSummary(JobCompareRun $run): array
    {
        return [
            'id' => $run->id,
            'mode' => $run->mode,
            'parent_run_id' => $run->parent_run_id,
            'candidate_count' => $run->candidate_count,
            'created_at' => $run->created_at?->toIso8601String(),
            'label' => $this->runLabel($run),
        ];
    }

    public function runLabel(JobCompareRun $run): string
    {
        $date = $run->created_at?->format('M j, Y g:i A') ?? '';
        $type = $run->mode === 'deep' ? 'Deep analysis' : 'Fit score';

        return "{$type} · {$run->candidate_count} candidates · {$date}";
    }
}
