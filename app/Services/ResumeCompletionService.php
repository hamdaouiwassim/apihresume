<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\User;

class ResumeCompletionService
{
    /**
     * Resume is "incomplete" if missing key profile fields or has no experience entries.
     */
    public function isIncomplete(Resume $resume): bool
    {
        $resume->loadMissing(['basicInfo', 'experiences']);

        $basic = $resume->basicInfo;
        $hasName = $basic && filled($basic->full_name);
        $hasSummary = $basic && filled($basic->professional_summary ?? $basic->summary ?? null);
        $hasExperience = $resume->experiences->isNotEmpty();

        return ! $hasName || ! $hasSummary || ! $hasExperience;
    }

    /**
     * Latest resume for user that looks incomplete, or latest resume if none marked complete.
     */
    public function latestIncompleteResumeForUser(User $user): ?Resume
    {
        $resumes = $user->resumes()
            ->with(['basicInfo', 'experiences'])
            ->orderByDesc('updated_at')
            ->get();

        foreach ($resumes as $resume) {
            if ($this->isIncomplete($resume)) {
                return $resume;
            }
        }

        return $resumes->first();
    }
}
