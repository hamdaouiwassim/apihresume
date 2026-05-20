<?php

namespace App\Models\Concerns;

use App\Models\BlogPost;
use App\Models\CoverLetter;
use App\Models\Resume;
use App\Models\Review;
use App\Models\WorkCertificate;

/**
 * When a user is soft-deleted, soft-delete owned content; restore together.
 */
trait CascadesSoftDeletesToOwnedContent
{
    protected static function bootCascadesSoftDeletesToOwnedContent(): void
    {
        static::deleting(function ($user) {
            if ($user->isForceDeleting()) {
                static::forceDeleteOwnedContent($user);

                return;
            }

            static::softDeleteOwnedContent($user);
        });

        static::restoring(function ($user) {
            static::restoreOwnedContent($user);
        });
    }

    protected static function softDeleteOwnedContent($user): void
    {
        $user->resumes()->get()->each->delete();
        $user->coverLetters()->get()->each->delete();
        $user->workCertificates()->get()->each->delete();
        if ($user->review) {
            $user->review->delete();
        }
        BlogPost::query()->where('user_id', $user->id)->get()->each->delete();
    }

    protected static function restoreOwnedContent($user): void
    {
        Resume::onlyTrashed()->where('user_id', $user->id)->restore();
        CoverLetter::onlyTrashed()->where('user_id', $user->id)->restore();
        WorkCertificate::onlyTrashed()->where('user_id', $user->id)->restore();
        Review::onlyTrashed()->where('user_id', $user->id)->restore();
        BlogPost::onlyTrashed()->where('user_id', $user->id)->restore();
    }

    protected static function forceDeleteOwnedContent($user): void
    {
        Resume::withTrashed()->where('user_id', $user->id)->get()->each->forceDelete();
        CoverLetter::withTrashed()->where('user_id', $user->id)->get()->each->forceDelete();
        WorkCertificate::withTrashed()->where('user_id', $user->id)->get()->each->forceDelete();
        Review::withTrashed()->where('user_id', $user->id)->get()->each->forceDelete();
        BlogPost::withTrashed()->where('user_id', $user->id)->get()->each->forceDelete();
    }
}
