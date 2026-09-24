<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Controller;
use App\Support\ApiBridge;
use Illuminate\Contracts\View\View;

/**
 * Admin area pages (former AdminRoute). List pages load their data from the existing
 * admin JSON API through Alpine, exactly like the React admin did; the dashboard is server-rendered.
 */
class AdminPageController extends Controller
{
    public function dashboard(): View
    {
        $payload = ApiBridge::data([DashboardController::class, 'index']);

        return view('pages.admin.dashboard', ['stats' => $payload['data'] ?? []]);
    }

    public function users(): View
    {
        return view('pages.admin.users');
    }

    public function userDetails(int $id): View
    {
        return view('pages.admin.user-details', ['id' => $id]);
    }

    public function userCvs(int $id): View
    {
        return view('pages.admin.user-cvs', ['id' => $id]);
    }

    public function generatedCvs(): View
    {
        return $this->island('admin-generated-cvs', 'Generated CVs');
    }

    public function coverLetters(): View
    {
        return view('pages.admin.cover-letters');
    }

    public function workCertificates(): View
    {
        return view('pages.admin.work-certificates');
    }

    public function templates(): View
    {
        return view('pages.admin.templates');
    }

    public function coverLetterTemplates(): View
    {
        return view('pages.admin.cover-letter-templates');
    }

    public function aiUsage(): View
    {
        return $this->island('admin-ai-usage', 'AI usage');
    }

    public function emails(): View
    {
        return view('pages.admin.emails');
    }

    public function blog(): View
    {
        return view('pages.admin.blog');
    }

    public function blogEditor(): View
    {
        return $this->island('admin-blog-editor', 'Blog editor');
    }

    public function fonts(): View
    {
        return view('pages.admin.fonts');
    }

    public function reviews(): View
    {
        return view('pages.admin.reviews');
    }

    public function profile(): View
    {
        return view('pages.admin.profile');
    }

    private function island(string $island, string $title): View
    {
        return view('pages.island', [
            'layout' => 'admin',
            'island' => $island,
            'title' => $title.' | Admin | HResume',
        ]);
    }
}
