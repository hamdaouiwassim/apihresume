<?php

namespace App\Services;

use App\Models\RecruiterJob;
use App\Models\Resume;
use Illuminate\Support\Str;

class RecruiterJobMatchService
{
    public const MAX_STANDARD_COMPARE = 25;

    public const MAX_DEEP_COMPARE = 4;

    /**
     * @param  array<int, int>  $resumeIds
     * @return array<int, array<string, mixed>>
     */
    public function scoreResumesForJob(RecruiterJob $job, array $resumeIds): array
    {
        $resumes = Resume::query()
            ->whereIn('id', $resumeIds)
            ->with(['basicInfo', 'experiences', 'educations', 'skills'])
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($resumeIds as $resumeId) {
            $resume = $resumes->get($resumeId);
            if (! $resume) {
                continue;
            }
            $results[] = $this->scoreResume($job, $resume);
        }

        usort($results, fn ($a, $b) => ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0));

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function scoreResume(RecruiterJob $job, Resume $resume): array
    {
        $basicInfo = $resume->basicInfo;
        $experiences = $resume->experiences ?? collect();
        $educations = $resume->educations ?? collect();
        $skills = $resume->skills ?? collect();

        $resumeSkillNames = $skills
            ->map(fn ($s) => $this->normalizeToken((string) ($s->name ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $requiredSkills = collect($job->required_skills ?? [])
            ->map(fn ($s) => $this->normalizeToken((string) $s))
            ->filter()
            ->unique()
            ->values();

        $matchedSkills = [];
        $missingSkills = [];
        foreach ($requiredSkills as $req) {
            $found = $this->skillMatches($req, $resumeSkillNames, $this->resumeTextBlob($resume));
            if ($found) {
                $matchedSkills[] = $req;
            } else {
                $missingSkills[] = $req;
            }
        }

        $skillsScore = $requiredSkills->isEmpty()
            ? 70
            : (int) round((count($matchedSkills) / max(1, $requiredSkills->count())) * 100);

        $experienceScore = $this->experienceScore(
            $job->experience_min_years,
            $job->experience_max_years,
            $experiences
        );

        $jobText = $this->jobTextBlob($job);
        $resumeText = $this->resumeTextBlob($resume);
        $keywordScore = $this->keywordScore($jobText, $resumeText);

        $educationScore = $this->educationScore(
            $job->educationRequirementsText(),
            $educations,
            $resumeText
        );

        $matchScore = (int) round(
            ($skillsScore * 0.35)
            + ($experienceScore * 0.25)
            + ($keywordScore * 0.30)
            + ($educationScore * 0.10)
        );

        $highlights = [];
        $gaps = [];

        if (count($matchedSkills) > 0) {
            $highlights[] = 'Matched skills: '.implode(', ', array_slice($matchedSkills, 0, 5));
        }
        if (count($missingSkills) > 0) {
            $gaps[] = 'Missing skills: '.implode(', ', array_slice($missingSkills, 0, 5));
        }
        if ($experienceScore >= 70) {
            $highlights[] = 'Experience level aligns with the role.';
        } elseif ($experienceScore < 50) {
            $gaps[] = 'Experience may be below role requirements.';
        }
        if ($keywordScore >= 65) {
            $highlights[] = 'Strong overlap with job description keywords.';
        } elseif ($keywordScore < 45) {
            $gaps[] = 'Limited keyword overlap with the job description.';
        }

        $candidateName = trim((string) ($basicInfo?->full_name ?? ''))
            ?: trim((string) (($basicInfo?->first_name ?? '').' '.($basicInfo?->last_name ?? '')));

        return [
            'resume_id' => $resume->id,
            'resume_name' => $resume->name,
            'candidate_name' => $candidateName !== '' ? $candidateName : null,
            'match_score' => min(100, max(0, $matchScore)),
            'breakdown' => [
                'skills' => [
                    'score' => $skillsScore,
                    'matched' => array_values($matchedSkills),
                    'missing' => array_values($missingSkills),
                ],
                'experience' => [
                    'score' => $experienceScore,
                    'estimated_years' => $this->estimateExperienceYears($experiences),
                    'required_label' => $job->experience_level_label,
                ],
                'keywords' => ['score' => $keywordScore],
                'education' => ['score' => $educationScore],
            ],
            'highlights' => array_slice($highlights, 0, 4),
            'gaps' => array_slice($gaps, 0, 4),
        ];
    }

    /**
     * Compact summary for Phase 2 prompts.
     *
     * @return array<string, mixed>
     */
    public function resumeSummaryForAi(Resume $resume): array
    {
        $basicInfo = $resume->basicInfo;
        $experiences = $resume->experiences ?? collect();

        $bullets = $experiences
            ->take(4)
            ->map(fn ($exp) => trim(
                ($exp->position ?? 'Role').' at '.($exp->company ?? 'Company')
                .': '.Str::limit((string) ($exp->description ?? ''), 200)
            ))
            ->values()
            ->all();

        return [
            'resume_id' => $resume->id,
            'candidate_name' => $basicInfo?->full_name ?? $resume->name,
            'summary' => Str::limit((string) ($basicInfo?->professional_summary ?? ''), 400),
            'skills' => ($resume->skills ?? collect())->pluck('name')->take(12)->values()->all(),
            'experience_bullets' => $bullets,
        ];
    }

    private function jobTextBlob(RecruiterJob $job): string
    {
        return strtolower(implode(' ', array_filter([
            $job->title,
            $job->description,
            $job->company_description,
            is_array($job->required_skills) ? implode(' ', $job->required_skills) : '',
        ])));
    }

    private function resumeTextBlob(Resume $resume): string
    {
        $basicInfo = $resume->basicInfo;
        $parts = [
            $basicInfo?->professional_summary,
            ($resume->experiences ?? collect())->pluck('description')->implode(' '),
            ($resume->experiences ?? collect())->pluck('position')->implode(' '),
            ($resume->skills ?? collect())->pluck('name')->implode(' '),
            ($resume->educations ?? collect())->pluck('degree')->implode(' '),
            ($resume->educations ?? collect())->pluck('institution')->implode(' '),
        ];

        return strtolower(implode(' ', array_filter($parts)));
    }

    private function normalizeToken(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * @param  array<int, string>  $resumeSkillNames
     */
    private function skillMatches(string $required, array $resumeSkillNames, string $resumeText): bool
    {
        foreach ($resumeSkillNames as $name) {
            if ($name === $required || str_contains($name, $required) || str_contains($required, $name)) {
                return true;
            }
        }

        return $required !== '' && str_contains($resumeText, $required);
    }

    private function experienceScore(?int $min, ?int $max, $experiences): int
    {
        if ($min === null && $max === null) {
            return 70;
        }

        $years = $this->estimateExperienceYears($experiences);
        if ($years === null) {
            return 50;
        }

        if ($min !== null && $max !== null) {
            if ($years >= $min && $years <= $max) {
                return 95;
            }
            if ($years >= $min - 1 && $years <= $max + 1) {
                return 75;
            }
            if ($years < $min) {
                return max(20, 60 - (($min - $years) * 15));
            }

            return max(40, 70 - (($years - $max) * 10));
        }

        if ($min !== null) {
            return $years >= $min ? 90 : max(25, 55 - (($min - $years) * 12));
        }

        return $years <= (int) $max ? 90 : max(35, 65 - (($years - $max) * 8));
    }

    private function estimateExperienceYears($experiences): ?float
    {
        if ($experiences->isEmpty()) {
            return null;
        }

        $months = 0;
        foreach ($experiences as $exp) {
            $start = $this->parseYearMonth((string) ($exp->startDate ?? ''));
            $end = ($exp->is_present ?? false)
                ? ['y' => (int) date('Y'), 'm' => (int) date('n')]
                : $this->parseYearMonth((string) ($exp->endDate ?? ''));
            if ($start && $end) {
                $months += max(1, (($end['y'] - $start['y']) * 12) + ($end['m'] - $start['m']));
            } else {
                $months += 12;
            }
        }

        return round($months / 12, 1);
    }

    /**
     * @return array{y: int, m: int}|null
     */
    private function parseYearMonth(string $value): ?array
    {
        if (preg_match('/(\d{4})/', $value, $m)) {
            $month = 1;
            if (preg_match('/(\d{1,2})[\/\-](\d{4})/', $value, $dm)) {
                $month = min(12, max(1, (int) $dm[1]));
            } elseif (preg_match('/([A-Za-z]{3,9})\s+(\d{4})/', $value, $mon)) {
                $parsed = date_parse($mon[1]);
                $month = $parsed['month'] ?: 1;
            }

            return ['y' => (int) $m[1], 'm' => $month];
        }

        return null;
    }

    private function keywordScore(string $jobText, string $resumeText): int
    {
        if ($jobText === '') {
            return 50;
        }

        preg_match_all('/[a-z][a-z0-9\+\#\.-]{2,}/', $jobText, $matches);
        $stopWords = ['the', 'and', 'for', 'with', 'you', 'your', 'are', 'this', 'that', 'will', 'from', 'our', 'job', 'role', 'have', 'has', 'not', 'who', 'can', 'all', 'any'];
        $keywords = collect($matches[0] ?? [])
            ->filter(fn ($w) => ! in_array($w, $stopWords, true))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(25)
            ->values();

        if ($keywords->isEmpty()) {
            return 50;
        }

        $matched = $keywords->filter(fn ($kw) => str_contains($resumeText, $kw))->count();

        return (int) round(($matched / $keywords->count()) * 100);
    }

    private function educationScore(string $requirements, $educations, string $resumeText): int
    {
        if ($requirements === '') {
            return 70;
        }

        $reqLower = strtolower($requirements);
        $eduText = strtolower($educations->map(fn ($e) => implode(' ', array_filter([
            $e->degree ?? '',
            $e->institution ?? '',
            $e->description ?? '',
        ])))->implode(' '));

        $haystack = $eduText.' '.$resumeText;
        preg_match_all('/[a-z]{4,}/', $reqLower, $m);
        $tokens = collect($m[0] ?? [])
            ->filter(fn ($t) => ! in_array($t, ['degree', 'bachelor', 'master', 'required', 'education'], true))
            ->take(8);

        if ($tokens->isEmpty()) {
            return str_contains($haystack, 'bachelor') || str_contains($haystack, 'master') || str_contains($haystack, 'phd')
                ? 85
                : 45;
        }

        $hits = $tokens->filter(fn ($t) => str_contains($haystack, $t))->count();

        return (int) round(($hits / max(1, $tokens->count())) * 100);
    }
}
