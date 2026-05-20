<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\RecruiterActivityLog;
use App\Models\RecruiterJob;
use App\Models\RecruiterResumeAccess;
use App\Models\RecruiterShortlistItem;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RecruiterResumeAccessService
{
    public function scopeVisibleTo(Builder $query, User $recruiter): Builder
    {
        $recruiterId = $recruiter->id;

        return $query->where(function (Builder $q) use ($recruiterId) {
            $q->where('open_to_recruiters', true)
                ->orWhereIn('id', $this->accessGrantResumeIdsSubquery($recruiterId))
                ->orWhereIn('id', $this->applicationResumeIdsSubquery($recruiterId));
        })->whereHas('user', function (Builder $uq) {
            $uq->whereNull('deleted_at')
                ->where(function (Builder $ban) {
                    $ban->where('banned_permanently', false)
                        ->where(function (Builder $until) {
                            $until->whereNull('banned_until')
                                ->orWhere('banned_until', '<=', now());
                        });
                });
        });
    }

    public function visibleTo(User $recruiter, Resume $resume): bool
    {
        if ($resume->trashed()) {
            return false;
        }

        $owner = $resume->user;
        if (! $owner || $owner->trashed()) {
            return false;
        }

        if ($this->userIsBanned($owner)) {
            return false;
        }

        if ($resume->open_to_recruiters) {
            return true;
        }

        if ($this->hasAccessGrant($recruiter->id, $resume->id)) {
            return true;
        }

        if ($this->hasApplicationAccess($recruiter->id, $resume->id)) {
            return true;
        }

        return false;
    }

    public function contactVisible(User $recruiter, Resume $resume): bool
    {
        if ($this->hasApplicationAccess($recruiter->id, $resume->id)) {
            return true;
        }

        return RecruiterShortlistItem::query()
            ->where('resume_id', $resume->id)
            ->where('contact_revealed', true)
            ->whereHas('shortlist', fn (Builder $q) => $q->where('recruiter_user_id', $recruiter->id))
            ->exists();
    }

    public function grantAccess(
        int $resumeId,
        int $grantedToUserId,
        ?int $grantedByUserId,
        string $source = 'share',
        ?\DateTimeInterface $expiresAt = null,
    ): RecruiterResumeAccess {
        return RecruiterResumeAccess::query()->updateOrCreate(
            [
                'resume_id' => $resumeId,
                'granted_to_user_id' => $grantedToUserId,
            ],
            [
                'granted_by_user_id' => $grantedByUserId,
                'source' => $source,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function logActivity(User $recruiter, string $action, ?int $resumeId = null, ?array $meta = null, ?Request $request = null): void
    {
        RecruiterActivityLog::create([
            'recruiter_user_id' => $recruiter->id,
            'action' => $action,
            'resume_id' => $resumeId,
            'meta' => $meta,
            'ip_address' => $request?->ip(),
        ]);
    }

    public function poolCount(): int
    {
        return Resume::query()
            ->where('open_to_recruiters', true)
            ->whereHas('user', fn (Builder $q) => $q->whereNull('deleted_at'))
            ->count();
    }

    public function visibleCountFor(User $recruiter): int
    {
        return $this->scopeVisibleTo(Resume::query(), $recruiter)->count();
    }

    public function resumeViewLimitExceeded(User $recruiter): ?string
    {
        if (! config('recruiter.limits.enabled')) {
            return null;
        }

        $limit = (int) config('recruiter.limits.monthly_resume_views', 500);
        if ($limit <= 0) {
            return null;
        }

        $count = RecruiterActivityLog::query()
            ->where('recruiter_user_id', $recruiter->id)
            ->whereIn('action', ['view_resume', 'export_pdf'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($count >= $limit) {
            return 'Monthly resume view limit reached. Contact support to upgrade your recruiter plan.';
        }

        return null;
    }

    public function openJobLimitExceeded(User $recruiter): ?string
    {
        if (! config('recruiter.limits.enabled')) {
            return null;
        }

        $limit = (int) config('recruiter.limits.max_open_jobs', 10);
        if ($limit <= 0) {
            return null;
        }

        $open = RecruiterJob::query()
            ->where('created_by_user_id', $recruiter->id)
            ->where('status', 'open')
            ->count();

        if ($open >= $limit) {
            return 'Open job limit reached for your recruiter plan.';
        }

        return null;
    }

    private function hasAccessGrant(int $recruiterId, int $resumeId): bool
    {
        return RecruiterResumeAccess::query()
            ->where('resume_id', $resumeId)
            ->where('granted_to_user_id', $recruiterId)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    private function hasApplicationAccess(int $recruiterId, int $resumeId): bool
    {
        return JobApplication::query()
            ->where('resume_id', $resumeId)
            ->whereHas('job', fn (Builder $q) => $q->where('created_by_user_id', $recruiterId))
            ->exists();
    }

    private function accessGrantResumeIdsSubquery(int $recruiterId): Builder
    {
        return RecruiterResumeAccess::query()
            ->select('resume_id')
            ->where('granted_to_user_id', $recruiterId)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    private function applicationResumeIdsSubquery(int $recruiterId): Builder
    {
        return JobApplication::query()
            ->select('resume_id')
            ->whereIn('job_id', RecruiterJob::query()->select('id')->where('created_by_user_id', $recruiterId));
    }

    private function userIsBanned(User $user): bool
    {
        if ($user->banned_permanently) {
            return true;
        }

        return $user->banned_until && $user->banned_until->isFuture();
    }
}
