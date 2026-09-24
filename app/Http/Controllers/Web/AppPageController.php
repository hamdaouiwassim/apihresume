<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CoverLetterController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WorkCertificateController;
use App\Models\Template;
use App\Services\ResumeLimitService;
use App\Support\ApiBridge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Signed-in user area (former PrivateRoute pages). Data comes from the same API controller
 * actions the React SPA called, via ApiBridge.
 */
class AppPageController extends Controller
{
    public function resumes(): View
    {
        $payload = ApiBridge::data([ResumeController::class, 'index']);

        return view('pages.app.resumes', [
            'owned' => $payload['data']['owned'] ?? [],
            'shared' => $payload['data']['shared'] ?? [],
            'limits' => $payload['limits'] ?? null,
        ]);
    }

    public function sharedWithMe(): View
    {
        $payload = ApiBridge::data([ResumeController::class, 'index']);

        return view('pages.app.shared-with-me', ['shared' => $payload['data']['shared'] ?? []]);
    }

    /** /resume/start (guest draft) and /resume/create (signed in). */
    public function createResume(Request $request): View|RedirectResponse
    {
        $allowGuest = $request->routeIs('resume.start');
        if ($allowGuest && $request->user()) {
            return redirect()->route('resume.create', $request->query());
        }

        $templates = Template::all();
        $canCreate = true;
        if (! $allowGuest) {
            $limits = app(ResumeLimitService::class)->limitsFor($request->user());
            $canCreate = ! (is_array($limits) && ($limits['can_create'] ?? true) === false);
        }

        $requested = $request->integer('template');
        $initialTemplate = $requested && $templates->contains('id', $requested) ? $requested : $templates->first()?->id;

        return view('pages.app.create-resume', compact('templates', 'allowGuest', 'canCreate', 'initialTemplate'));
    }

    public function claimDraft(): View
    {
        return view('pages.app.claim-draft');
    }

    public function editResume(string $id): View
    {
        return view('pages.island', [
            'layout' => 'app',
            'island' => 'resume-editor',
            'title' => 'Edit resume | HResume',
        ]);
    }

    public function profile(): View
    {
        return view('pages.app.profile');
    }

    public function review(): View
    {
        $result = ApiBridge::call([ReviewController::class, 'myReview']);

        return view('pages.app.review', [
            'existing' => $result['status'] === 200 ? ($result['data']['data'] ?? null) : null,
        ]);
    }

    public function pricingSuccess(): View
    {
        return view('pages.app.pricing-success');
    }

    public function coverLetters(): View
    {
        return view('pages.app.cover-letters', [
            'letters' => ApiBridge::data([CoverLetterController::class, 'index'])['data'] ?? [],
        ]);
    }

    public function editCoverLetter(): View
    {
        return view('pages.island', [
            'layout' => 'app',
            'island' => 'cover-letter-editor',
            'title' => t('coverLetter.title', [], 'Cover Letters').' | HResume',
        ]);
    }

    public function workCertificates(): View
    {
        return view('pages.app.work-certificates', [
            'items' => ApiBridge::data([WorkCertificateController::class, 'index'])['data'] ?? [],
        ]);
    }

    public function editWorkCertificate(): View
    {
        return view('pages.island', [
            'layout' => 'app',
            'island' => 'work-certificate-editor',
            'title' => t('workCertificate.title', [], 'Work certificates').' | HResume',
        ]);
    }
}
