<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Language toggle (former LanguageContext.toggleLanguage). Stores the choice in the session
 * and sends the visitor back to the page they were on.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', Translations::normalize($locale));

        $back = url()->previous();
        $host = parse_url($back, PHP_URL_HOST);

        if (! $host || $host !== $request->getHost() || str_contains($back, '/locale/')) {
            $back = url('/');
        }

        return redirect()->to($back);
    }
}
