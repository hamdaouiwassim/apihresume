<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ShareableLinkController;
use App\Support\ApiBridge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Public resume pages: shared link (React island), personal website (server-rendered for SEO),
 * collaboration invitation acceptance.
 */
class PublicResumeController extends Controller
{
    public function share(string $token): View
    {
        return view('pages.island', [
            'layout' => 'base',
            'island' => 'shared-resume',
            'title' => 'Shared resume | HResume',
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function website(string $slugOrToken)
    {
        $result = ApiBridge::call([ShareableLinkController::class, 'website'], ['slugOrToken' => $slugOrToken]);
        $payload = $result['data'];
        $ok = $result['status'] === 200 && ($payload['status'] ?? false);

        return response()->view('pages.website', [
            'resume' => $ok ? ($payload['data'] ?? null) : null,
            'meta' => $payload['meta'] ?? [],
            'error' => $ok ? null : ($payload['message'] ?? 'This profile is unavailable.'),
            // PDF download is only offered on the stable /u/{slug} route (same rule as the SPA).
            'routeSlug' => request()->routeIs('website.slug') ? $slugOrToken : null,
        ], $ok ? Response::HTTP_OK : ($result['status'] === 410 ? Response::HTTP_GONE : Response::HTTP_NOT_FOUND));
    }

    public function acceptCollaboration(string $token): View
    {
        return view('pages.app.accept-collaboration', ['token' => $token]);
    }
}
