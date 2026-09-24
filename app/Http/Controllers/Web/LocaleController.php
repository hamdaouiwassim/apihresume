<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\LocalizedUrls;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Language toggle (former LanguageContext.toggleLanguage). Stores the choice in the session
 * and sends the visitor back to the page they were on, in the chosen language's URL when the page has one.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $locale = Translations::normalize($locale);
        $request->session()->put('locale', $locale);

        $back = url()->previous();
        $host = parse_url($back, PHP_URL_HOST);

        if (! $host || $host !== $request->getHost() || str_contains($back, '/locale/')) {
            $back = url('/');
        }

        // Pages with both language versions: go to the URL of the chosen language ("/pricing" <-> "/fr/pricing").
        $path = parse_url($back, PHP_URL_PATH) ?: '/';
        if (LocalizedUrls::isLocalizedPath($path)) {
            $query = parse_url($back, PHP_URL_QUERY);
            $back = LocalizedUrls::pathFor($path, $locale).($query ? '?'.$query : '');
        }

        return redirect()->to($back);
    }
}
