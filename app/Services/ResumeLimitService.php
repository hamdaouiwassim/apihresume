<?php

namespace App\Services;

use App\Models\User;

class ResumeLimitService
{
    public function freeOwnedResumeLimit(): int
    {
        return (int) config('resume.free_owned_resume_limit', 1);
    }

    public function hasUnlimitedOwnedResumes(User $user): bool
    {
        return (bool) ($user->is_admin ?? false) || $user->hasProAccess();
    }

    public function ownedResumeCount(User $user): int
    {
        return $user->resumes()->count();
    }

    public function canCreateOwnedResume(User $user): bool
    {
        if ($this->hasUnlimitedOwnedResumes($user)) {
            return true;
        }

        return $this->ownedResumeCount($user) < $this->freeOwnedResumeLimit();
    }

    /**
     * @return array{owned_count: int, owned_limit: int|null, can_create: bool}
     */
    public function limitsFor(User $user): array
    {
        $ownedCount = $this->ownedResumeCount($user);
        $unlimited = $this->hasUnlimitedOwnedResumes($user);

        return [
            'owned_count' => $ownedCount,
            'owned_limit' => $unlimited ? null : $this->freeOwnedResumeLimit(),
            'can_create' => $unlimited || $ownedCount < $this->freeOwnedResumeLimit(),
        ];
    }
}
