<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Support\ApiBridge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sign-in pages. The forms post to the existing JSON endpoints (/api/login, /api/register) and the
 * social callback reuses AuthController's code exchange, so validation, ban checks and verification
 * emails stay in AuthController.
 */
class AuthPageController extends Controller
{
    public function login(): View
    {
        return view('pages.auth.login');
    }

    public function register(): View
    {
        return view('pages.auth.register');
    }

    /**
     * Google / LinkedIn land here with a one-time code. It is exchanged during this page load
     * (no XHR, so no CSRF round trip) and the user is logged into the web session.
     */
    public function socialCallback(Request $request): View|RedirectResponse
    {
        $provider = $request->query('provider') === 'linkedin' ? 'LinkedIn' : 'Google';
        $code = $request->query('code');
        $error = null;

        if ($request->query('status') !== 'success') {
            $error = $request->query('message') ?: "We could not verify your {$provider} session.";
        } elseif (is_string($code) && $code !== '') {
            $result = ApiBridge::call([AuthController::class, 'exchangeSocialAuthCode'], [], ['code' => $code]);
            $user = Auth::user();

            if ($result['status'] === 200 && $user) {
                return redirect()->to($user->is_admin ? route('admin.dashboard') : route('resumes.index'))
                    ->with($user->hasVerifiedEmail() ? 'success' : 'info', $user->hasVerifiedEmail()
                        ? "Signed in with {$provider}"
                        : 'Please verify your email to continue.');
            }

            $error = $result['data']['message']
                ?? 'Something went wrong while finalizing your login. Please try again.';
        }

        return view('pages.auth.social-callback', ['error' => $error]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
