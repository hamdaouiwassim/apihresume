<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sign-in pages. The forms post to the existing JSON endpoints (/api/login, /api/register,
 * /api/auth/social/exchange) so validation, ban checks and verification emails stay in AuthController.
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

    public function socialCallback(): View
    {
        return view('pages.auth.social-callback');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
