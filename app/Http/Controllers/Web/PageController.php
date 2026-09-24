<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\Review;
use App\Models\Template;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Public marketing / legal pages (server-rendered for SEO).
 */
class PageController extends Controller
{
    public function home(): View
    {
        $stats = Cache::remember('home.stats', 300, fn () => [
            'total_candidates' => User::where('is_admin', false)->where('is_recruiter', false)->count(),
            'total_resumes' => Resume::count(),
        ]);

        // Same data the SPA fetched from /api/templates and /api/reviews?public_only=1&per_page=10.
        $templates = Template::all();
        $reviews = Review::with('user:id,name,avatar')
            ->where('is_public', true)
            ->whereNotNull('rating')
            ->whereNotNull('comment')
            ->latest()
            ->limit(10)
            ->get()
            ->filter(fn ($review) => $review->user !== null)
            ->values();

        return view('pages.welcome', compact('stats', 'templates', 'reviews'));
    }

    public function pricing(): View
    {
        return view('pages.pricing', ['region' => pricing_region()]);
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function refund(): View
    {
        return view('pages.refund');
    }

    public function coverLetterBuilder(): View
    {
        return view('pages.product-landing', ['product' => 'coverLetter']);
    }

    public function workCertificate(): View
    {
        return view('pages.product-landing', ['product' => 'workCertificate']);
    }
}
