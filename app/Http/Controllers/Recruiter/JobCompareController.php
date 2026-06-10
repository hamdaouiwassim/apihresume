<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruiterJobResource;
use App\Models\JobApplication;
use App\Models\JobCompareRun;
use App\Models\RecruiterJob;
use App\Models\Resume;
use App\Services\AiTokenLimitService;
use App\Services\AiUsageLogger;
use App\Services\JobCompareRunService;
use App\Services\RecruiterJobCompareAiService;
use App\Services\RecruiterJobMatchService;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class JobCompareController extends Controller
{
    public function __construct(
        private readonly RecruiterJobMatchService $matchService,
        private readonly RecruiterJobCompareAiService $compareAi,
        private readonly RecruiterResumeAccessService $access,
        private readonly AiTokenLimitService $aiTokenLimit,
        private readonly AiUsageLogger $aiUsageLogger,
        private readonly JobCompareRunService $compareRuns,
    ) {}

    public function indexRuns(Request $request, int $jobId): JsonResponse
    {
        $job = $this->ownedJob($request, $jobId);
        $runs = $this->compareRuns->listForJob($job, $request->user());

        return response()->json([
            'status' => true,
            'data' => $runs->map(fn (JobCompareRun $run) => $this->compareRuns->formatRunSummary($run))->values(),
        ]);
    }

    public function showRun(Request $request, int $jobId, int $runId): JsonResponse
    {
        $job = $this->ownedJob($request, $jobId);
        $run = $this->compareRuns->ownedRun($job, $request->user(), $runId);

        return response()->json([
            'status' => true,
            'data' => [
                'run' => array_merge($this->compareRuns->formatRunSummary($run), [
                    'resume_ids' => $run->resume_ids,
                ]),
                'job' => new RecruiterJobResource($job),
                'mode' => $run->mode,
                'results' => $run->results,
            ],
        ]);
    }

    public function compare(Request $request, int $jobId): JsonResponse
    {
        $job = $this->ownedJob($request, $jobId);
        $recruiter = $request->user();

        $validated = Validator::make($request->all(), [
            'resume_ids' => 'required|array|min:1|max:'.RecruiterJobMatchService::MAX_STANDARD_COMPARE,
            'resume_ids.*' => 'integer|distinct',
            'persist_scores' => 'sometimes|boolean',
        ])->validate();

        $resumeIds = array_values(array_unique(array_map('intval', $validated['resume_ids'])));
        $this->assertResumesVisible($recruiter, $resumeIds);

        $results = $this->compareRuns->sortByMatchScore(
            $this->matchService->scoreResumesForJob($job, $resumeIds)
        );

        if ($validated['persist_scores'] ?? true) {
            $this->persistMatchScores($job, $results);
        }

        $run = $this->compareRuns->saveRun($job, $recruiter, 'standard', $resumeIds, $results);

        return response()->json([
            'status' => true,
            'data' => [
                'job' => new RecruiterJobResource($job),
                'mode' => 'standard',
                'run_id' => $run->id,
                'results' => $run->results,
            ],
        ]);
    }

    public function compareDeep(Request $request, int $jobId): JsonResponse
    {
        $job = $this->ownedJob($request, $jobId);
        $recruiter = $request->user();

        $validated = Validator::make($request->all(), [
            'resume_ids' => 'required|array|min:1|max:'.RecruiterJobMatchService::MAX_DEEP_COMPARE,
            'resume_ids.*' => 'integer|distinct',
            'persist_scores' => 'sometimes|boolean',
            'base_run_id' => 'nullable|integer|exists:job_compare_runs,id',
        ])->validate();

        $resumeIds = array_values(array_unique(array_map('intval', $validated['resume_ids'])));
        $this->assertResumesVisible($recruiter, $resumeIds);

        if (! $this->aiTokenLimit->hasTokenBudget($recruiter)) {
            return response()->json([
                'status' => false,
                'message' => 'Monthly AI token limit reached. Contact support or upgrade your plan.',
                'code' => 'ai_token_limit_exceeded',
            ], 429);
        }

        $parentRun = null;
        if (! empty($validated['base_run_id'])) {
            $parentRun = $this->compareRuns->ownedRun($job, $recruiter, (int) $validated['base_run_id']);
            if ($parentRun->mode !== 'standard') {
                return response()->json([
                    'status' => false,
                    'message' => 'Base comparison must be a fit score run.',
                ], 422);
            }
        } else {
            $parentRun = $this->compareRuns->latestStandardRun($job, $recruiter);
        }

        $phaseOneSubset = $this->compareRuns->sortByMatchScore(
            $this->matchService->scoreResumesForJob($job, $resumeIds)
        );

        $baseResults = $parentRun
            ? $parentRun->results
            : $phaseOneSubset;

        $resumes = Resume::query()
            ->whereIn('id', $resumeIds)
            ->with(['basicInfo', 'experiences', 'educations', 'skills'])
            ->get();

        $summaries = $resumes
            ->map(fn (Resume $r) => $this->matchService->resumeSummaryForAi($r))
            ->values()
            ->all();

        try {
            $ai = $this->compareAi->deepInsights($job, $phaseOneSubset, $summaries);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Deep analysis failed: '.$e->getMessage(),
            ], 502);
        }

        $insights = collect($ai['insights'])->map(function (array $item) {
            return [
                'resume_id' => $item['resume_id'],
                'summary' => $item['summary'] ?? '',
                'strengths' => $item['strengths'] ?? [],
                'risks' => $item['risks'] ?? [],
                'interview_questions' => $item['interview_questions'] ?? [],
            ];
        })->all();

        $merged = $this->compareRuns->sortByMatchScore(
            $this->compareRuns->mergeDeepIntoResults($baseResults, $phaseOneSubset, $insights)
        );

        if ($validated['persist_scores'] ?? true) {
            $this->persistMatchScores($job, $merged);
        }

        $run = $this->compareRuns->saveRun(
            $job,
            $recruiter,
            'deep',
            $parentRun ? $parentRun->resume_ids : $resumeIds,
            $merged,
            $parentRun?->id,
        );

        $this->aiUsageLogger->log(
            $recruiter,
            'recruiter_compare_deep',
            null,
            $ai['usage'] ?? null,
            [
                'job_id' => $job->id,
                'job_title' => $job->title,
                'resume_ids' => $resumeIds,
                'compare_run_id' => $run->id,
            ],
            ['candidates' => $ai['insights']],
        );

        return response()->json([
            'status' => true,
            'data' => [
                'job' => new RecruiterJobResource($job),
                'mode' => 'deep',
                'run_id' => $run->id,
                'parent_run_id' => $parentRun?->id,
                'results' => $run->results,
            ],
        ]);
    }

    private function ownedJob(Request $request, int $jobId): RecruiterJob
    {
        return RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($jobId);
    }

    /**
     * @param  array<int, int>  $resumeIds
     */
    private function assertResumesVisible($recruiter, array $resumeIds): void
    {
        $resumes = Resume::query()->whereIn('id', $resumeIds)->get();
        if ($resumes->count() !== count($resumeIds)) {
            abort(422, 'One or more resumes were not found.');
        }

        foreach ($resumes as $resume) {
            if (! $this->access->visibleTo($recruiter, $resume)) {
                abort(403, 'You do not have access to one or more selected resumes.');
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function persistMatchScores(RecruiterJob $job, array $results): void
    {
        foreach ($results as $row) {
            JobApplication::query()
                ->where('job_id', $job->id)
                ->where('resume_id', $row['resume_id'])
                ->update(['match_score' => $row['match_score'] ?? null]);
        }
    }
}
