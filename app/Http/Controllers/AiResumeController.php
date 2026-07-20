<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\User;
use App\Services\AiQuotaService;
use App\Services\AiResumeTailorService;
use App\Services\AiTokenLimitService;
use App\Services\AiUsageLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AiResumeController extends Controller
{
    public function __construct(
        private readonly AiQuotaService $aiQuota,
        private readonly AiUsageLogger $aiUsageLogger,
        private readonly AiTokenLimitService $aiTokenLimit,
    ) {}

    public function tailor(Request $request, AiResumeTailorService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|integer|exists:resumes,id',
            'job_description' => 'required|string|min:30|max:6000',
            'target_role' => 'nullable|string|max:120',
            'seniority' => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $resume = Resume::with([
            'basicInfo',
            'experiences',
            'educations',
            'skills',
            'projects',
            'languages',
            'certificates',
            'hobbies',
        ])->findOrFail((int) $request->resume_id);

        if (! $resume->canBeEditedBy($user?->id)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (! $this->aiQuota->hasRemaining($user, 'tailor_resume')) {
            return $this->quotaExceededResponse($user);
        }

        if ($denied = $this->tokenLimitDeniedResponse($user)) {
            return $denied;
        }

        try {
            $tailored = $service->tailorResume([
                'job_description' => (string) $request->job_description,
                'target_role' => $request->target_role,
                'seniority' => $request->seniority,
                'resume' => [
                    'basic_info' => $resume->basicInfo,
                    'experiences' => $resume->experiences,
                    'educations' => $resume->educations,
                    'skills' => $resume->skills,
                    'projects' => $resume->projects,
                    'languages' => $resume->languages,
                    'certificates' => $resume->certificates,
                    'hobbies' => $resume->hobbies,
                ],
            ]);

            $this->aiQuota->increment($user, 'tailor_resume');
            $this->aiUsageLogger->log($user, 'tailor_resume', $resume->id, $tailored['usage'] ?? null);
            $user->refresh();

            return response()->json([
                'status' => true,
                'message' => 'AI suggestions generated successfully',
                'data' => $tailored['data'],
                'ai_quota' => $this->aiQuota->snapshot($user),
                'ai_tokens' => $this->aiTokenLimit->snapshot($user),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to generate AI suggestions right now.',
            ], 502);
        }
    }

    public function enhanceText(Request $request, AiResumeTailorService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:3|max:12000',
            'context' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! $this->aiQuota->hasRemaining($user, 'enhance_text')) {
            return $this->quotaExceededResponse($user);
        }

        if ($denied = $this->tokenLimitDeniedResponse($user)) {
            return $denied;
        }

        try {
            $enhanced = $service->enhanceText(
                (string) $request->text,
                $request->filled('context') ? (string) $request->context : null
            );

            $this->aiQuota->increment($user, 'enhance_text');
            $this->aiUsageLogger->log($user, 'enhance_text', null, $enhanced['usage'] ?? null);
            $user->refresh();

            return response()->json([
                'status' => true,
                'message' => 'Text enhanced successfully',
                'data' => [
                    'enhanced_text' => $enhanced['text'],
                ],
                'ai_quota' => $this->aiQuota->snapshot($user),
                'ai_tokens' => $this->aiTokenLimit->snapshot($user),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to enhance text right now.',
            ], 502);
        }
    }

    public function parseCvText(Request $request, AiResumeTailorService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:10|max:30000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        try {
            $parsed = $service->parseCvText((string) $request->text);

            $this->aiUsageLogger->log($user, 'parse_cv_text', null, $parsed['usage'] ?? null);
            $user->refresh();

            return response()->json([
                'status' => true,
                'message' => 'CV parsed successfully',
                'data' => $parsed['data'],
                'ai_quota' => $this->aiQuota->snapshot($user),
                'ai_tokens' => $this->aiTokenLimit->snapshot($user),
            ]);
        } catch (Throwable $e) {
            logger()->error('AI parse CV text failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to parse CV right now.',
            ], 502);
        }
    }

    public function atsScore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|integer|exists:resumes,id',
            'job_description' => 'nullable|string|max:6000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $resume = Resume::with([
            'basicInfo',
            'experiences',
            'educations',
            'skills',
            'projects',
            'languages',
            'certificates',
        ])->findOrFail((int) $request->resume_id);

        if (! $resume->canBeEditedBy($user?->id)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (! $this->aiQuota->hasRemaining($user, 'ats_score')) {
            return $this->quotaExceededResponse($user);
        }

        if ($denied = $this->tokenLimitDeniedResponse($user)) {
            return $denied;
        }

        $basicInfo = $resume->basicInfo;
        $experiences = $resume->experiences ?? collect();
        $educations = $resume->educations ?? collect();
        $skills = $resume->skills ?? collect();
        $projects = $resume->projects ?? collect();
        $jobDescription = (string) $request->input('job_description', '');

        $allText = trim(implode(' ', array_filter([
            (string) ($basicInfo?->professional_summary ?? ''),
            $experiences->pluck('description')->implode(' '),
            $projects->pluck('description')->implode(' '),
        ])));

        $contentScore = 0;
        $contentSignals = [];
        if (Str::length((string) ($basicInfo?->professional_summary ?? '')) >= 60) {
            $contentScore += 20;
            $contentSignals[] = 'Professional summary is detailed.';
        } else {
            $contentSignals[] = 'Add a stronger professional summary (at least 2-3 sentences).';
        }
        if ($experiences->count() >= 1) {
            $contentScore += 20;
            $contentSignals[] = 'Experience section is present.';
        } else {
            $contentSignals[] = 'Add at least one professional experience.';
        }
        if ($educations->count() >= 1) {
            $contentScore += 10;
            $contentSignals[] = 'Education section is present.';
        } else {
            $contentSignals[] = 'Add at least one education entry.';
        }
        if ($skills->count() >= 4) {
            $contentScore += 20;
            $contentSignals[] = 'Skills list is substantial.';
        } else {
            $contentSignals[] = 'Add more relevant skills (target 6+).';
        }
        if ($projects->count() >= 1) {
            $contentScore += 10;
            $contentSignals[] = 'Projects section strengthens technical profile.';
        }
        $contentScore = min(100, $contentScore);

        $formatScore = 0;
        $formatSignals = [];
        $hasContact = filled($basicInfo?->email) && filled($basicInfo?->phone);
        if ($hasContact) {
            $formatScore += 30;
            $formatSignals[] = 'Contact details are complete.';
        } else {
            $formatSignals[] = 'Add complete contact details (email + phone).';
        }
        $hasActionVerbs = preg_match('/\b(led|built|developed|implemented|designed|managed|improved|optimized|created)\b/i', $allText) === 1;
        if ($hasActionVerbs) {
            $formatScore += 35;
            $formatSignals[] = 'Strong action verbs detected.';
        } else {
            $formatSignals[] = 'Use stronger action verbs in experience bullets.';
        }
        $hasMetrics = preg_match('/\b\d+%|\b\d+\b/', $allText) === 1;
        if ($hasMetrics) {
            $formatScore += 35;
            $formatSignals[] = 'Quantified impact detected.';
        } else {
            $formatSignals[] = 'Add measurable outcomes (%, counts, time saved).';
        }
        $formatScore = min(100, $formatScore);

        $keywordScore = 0;
        $keywordSignals = [];
        $matchedKeywords = [];
        $missingKeywords = [];

        if ($jobDescription !== '') {
            preg_match_all('/[A-Za-z][A-Za-z0-9\+\#\.-]{2,}/', strtolower($jobDescription), $matches);
            $stopWords = ['the', 'and', 'for', 'with', 'you', 'your', 'are', 'this', 'that', 'will', 'from', 'our', 'job', 'role', 'have', 'has', 'not'];
            $keywords = collect($matches[0] ?? [])
                ->filter(fn ($w) => ! in_array($w, $stopWords, true))
                ->countBy()
                ->sortDesc()
                ->keys()
                ->take(20)
                ->values();

            $resumeTextLower = strtolower($allText.' '.$skills->pluck('name')->implode(' '));
            foreach ($keywords as $kw) {
                if (str_contains($resumeTextLower, $kw)) {
                    $matchedKeywords[] = $kw;
                } else {
                    $missingKeywords[] = $kw;
                }
            }

            $keywordScore = count($keywords) > 0
                ? (int) round((count($matchedKeywords) / count($keywords)) * 100)
                : 0;

            if ($keywordScore >= 70) {
                $keywordSignals[] = 'Strong keyword alignment with target role.';
            } elseif ($keywordScore >= 40) {
                $keywordSignals[] = 'Moderate keyword alignment; room to optimize.';
            } else {
                $keywordSignals[] = 'Low keyword alignment with job description.';
            }
        } else {
            $keywordScore = 50;
            $keywordSignals[] = 'Add a job description to run precise keyword matching.';
        }

        $overall = (int) round(($contentScore * 0.4) + ($formatScore * 0.3) + ($keywordScore * 0.3));

        $recommendations = collect([
            ...$contentSignals,
            ...$formatSignals,
            ...$keywordSignals,
        ])->filter(fn ($item) => str_starts_with($item, 'Add') || str_starts_with($item, 'Use') || str_starts_with($item, 'Low') || str_starts_with($item, 'Moderate'))
            ->take(6)
            ->values()
            ->all();

        $data = [
            'overall_score' => $overall,
            'breakdown' => [
                'content_strength' => $contentScore,
                'formatting_quality' => $formatScore,
                'keyword_match' => $keywordScore,
            ],
            'keyword_analysis' => [
                'matched' => array_values($matchedKeywords),
                'missing' => array_values(array_slice($missingKeywords, 0, 12)),
            ],
            'signals' => [
                'content' => $contentSignals,
                'format' => $formatSignals,
                'keywords' => $keywordSignals,
            ],
            'recommendations' => $recommendations,
        ];

        $unlimited = $this->aiQuota->isUnlimited($user);
        if (! $unlimited) {
            $data = $this->sanitizeAtsForFreeTier($data);
        }
        $data['insights_tier'] = $unlimited ? 'full' : 'lite';

        $this->aiQuota->increment($user, 'ats_score');
        $this->aiUsageLogger->log($user, 'ats_score', $resume->id, null);
        $user->refresh();

        return response()->json([
            'status' => true,
            'message' => 'ATS score generated successfully',
            'data' => $data,
            'ai_quota' => $this->aiQuota->snapshot($user),
            'ai_tokens' => $this->aiTokenLimit->snapshot($user),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeAtsForFreeTier(array $data): array
    {
        if (isset($data['keyword_analysis']['missing']) && is_array($data['keyword_analysis']['missing'])) {
            $data['keyword_analysis']['missing'] = array_slice($data['keyword_analysis']['missing'], 0, 5);
        }
        if (isset($data['signals']) && is_array($data['signals'])) {
            foreach ($data['signals'] as $key => $items) {
                if (is_array($items)) {
                    $data['signals'][$key] = array_slice($items, 0, 2);
                }
            }
        }
        if (isset($data['recommendations']) && is_array($data['recommendations'])) {
            $data['recommendations'] = array_slice($data['recommendations'], 0, 3);
        }

        return $data;
    }

    private function quotaExceededResponse(User $user): JsonResponse
    {
        return response()->json([
            'status' => false,
            'code' => 'AI_QUOTA_EXCEEDED',
            'message' => 'You have used all free AI credits for this feature this month. Upgrade to Pro for unlimited AI, full ATS keyword insights, and more.',
            'ai_quota' => $this->aiQuota->snapshot($user),
            'ai_tokens' => $this->aiTokenLimit->snapshot($user),
        ], 403);
    }

    private function tokenLimitDeniedResponse(User $user): ?JsonResponse
    {
        if ($this->aiTokenLimit->hasTokenBudget($user)) {
            return null;
        }

        $snap = $this->aiTokenLimit->snapshot($user);

        return response()->json([
            'status' => false,
            'code' => 'AI_TOKEN_LIMIT_EXCEEDED',
            'message' => 'You have reached your monthly AI token limit. Contact support or upgrade to Pro for more capacity.',
            'ai_tokens' => $snap,
            'ai_quota' => $this->aiQuota->snapshot($user),
        ], 403);
    }
}
