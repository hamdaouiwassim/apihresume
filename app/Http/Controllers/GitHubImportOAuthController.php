<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitHubImportOAuthController extends Controller
{
    private const STATE_TTL_SECONDS = 600;

    /**
     * Allowed paths for post-OAuth redirect (open-redirect mitigation).
     */
    private function isAllowedReturnPath(string $path): bool
    {
        if (strlen($path) > 512 || str_contains($path, "\n") || str_contains($path, '//')) {
            return false;
        }

        $pathOnly = parse_url($path, PHP_URL_PATH);
        if (! is_string($pathOnly) || $pathOnly === '' || $pathOnly === '/') {
            return false;
        }
        if (! str_starts_with($pathOnly, '/')) {
            return false;
        }

        if (! (bool) preg_match('#^/(?:resumes|profile|work-certificates|work-certificate|resume)(?:/.*)?$#', $pathOnly)) {
            return false;
        }

        $query = parse_url($path, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            if (strlen($query) > 256 || ! preg_match('/^[a-zA-Z0-9_=&%.-]*$/', $query)) {
                return false;
            }
        }

        return true;
    }

    public function url(Request $request)
    {
        if (! $this->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'GitHub import (OAuth) is not configured on this server.',
            ], 503);
        }

        $returnTo = (string) $request->query('return_to', '/profile');
        if (! $this->isAllowedReturnPath($returnTo)) {
            $returnTo = '/profile';
        }

        $user = $request->user();
        $payload = [
            'uid' => $user->id,
            't' => time(),
            'return' => $returnTo,
        ];
        $state = Crypt::encryptString(json_encode($payload));

        $query = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => config('services.github.redirect_uri'),
            'scope' => 'repo',
            'state' => $state,
        ]);

        $authorizeUrl = 'https://github.com/login/oauth/authorize?'.$query;

        return response()->json([
            'status' => true,
            'url' => $authorizeUrl,
        ]);
    }

    public function callback(Request $request)
    {
        $defaultPath = '/profile';

        if (! $this->isConfigured()) {
            return $this->redirectFrontend($defaultPath, ['github_import' => 'error', 'message' => 'GitHub OAuth is not configured.']);
        }

        if ($request->filled('error')) {
            $msg = (string) $request->query('error_description', $request->query('error', 'Access denied'));

            return $this->redirectFrontend($defaultPath, ['github_import' => 'error', 'message' => Str::limit($msg, 200)]);
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $payload = json_decode(Crypt::decryptString($request->query('state')), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $this->redirectFrontend($defaultPath, ['github_import' => 'error', 'message' => 'Invalid or expired session. Try again.']);
        }

        if (! is_array($payload) || ! isset($payload['uid'], $payload['t'])) {
            return $this->redirectFrontend($defaultPath, ['github_import' => 'error', 'message' => 'Invalid state.']);
        }

        if ((time() - (int) $payload['t']) > self::STATE_TTL_SECONDS) {
            return $this->redirectFrontend($defaultPath, ['github_import' => 'error', 'message' => 'Authorization expired. Try again.']);
        }

        $user = User::find($payload['uid']);
        if (! $user) {
            return $this->redirectFrontend($defaultPath, ['github_import' => 'error', 'message' => 'User not found.']);
        }

        $returnTo = isset($payload['return']) && is_string($payload['return']) ? $payload['return'] : $defaultPath;
        if (! $this->isAllowedReturnPath($returnTo)) {
            $returnTo = $defaultPath;
        }

        try {
            $tokenResponse = Http::asForm()
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(20)
                ->post('https://github.com/login/oauth/access_token', [
                    'client_id' => config('services.github.client_id'),
                    'client_secret' => config('services.github.client_secret'),
                    'code' => $request->query('code'),
                    'redirect_uri' => config('services.github.redirect_uri'),
                ]);
        } catch (\Throwable $e) {
            Log::error('GitHub OAuth token exchange request failed', ['error' => $e->getMessage()]);

            return $this->redirectFrontend($returnTo, ['github_import' => 'error', 'message' => 'Could not reach GitHub.']);
        }

        if ($tokenResponse->failed()) {
            Log::warning('GitHub OAuth token exchange failed', ['body' => $tokenResponse->body()]);

            return $this->redirectFrontend($returnTo, ['github_import' => 'error', 'message' => 'GitHub did not return a token.']);
        }

        /** @var array<string, mixed> $tokenJson */
        $tokenJson = $tokenResponse->json();
        $accessToken = $tokenJson['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            return $this->redirectFrontend($returnTo, ['github_import' => 'error', 'message' => 'Missing access token from GitHub.']);
        }

        $login = null;
        try {
            $me = Http::withToken($accessToken)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                    'User-Agent' => 'HResume-App/1.0',
                ])
                ->timeout(15)
                ->get('https://api.github.com/user');
            if ($me->successful()) {
                $meJson = $me->json();
                $login = is_array($meJson) && isset($meJson['login']) ? (string) $meJson['login'] : null;
            }
        } catch (\Throwable) {
            // optional
        }

        $user->github_import_token = $accessToken;
        $user->github_import_login = $login;
        $user->github_import_connected_at = now();
        $user->save();

        return $this->redirectFrontend($returnTo, ['github_import' => 'success']);
    }

    public function disconnect(Request $request)
    {
        $user = $request->user();
        $user->github_import_token = null;
        $user->github_import_login = null;
        $user->github_import_connected_at = null;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'GitHub disconnected.',
        ]);
    }

    private function isConfigured(): bool
    {
        return filled(config('services.github.client_id'))
            && filled(config('services.github.client_secret'))
            && filled(config('services.github.redirect_uri'));
    }

    /**
     * @param  array<string, string>  $query
     */
    private function redirectFrontend(string $path, array $query = []): \Illuminate\Http\RedirectResponse
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = $path === '' ? '/profile' : $path;
        $qs = $query === [] ? '' : (str_contains($path, '?') ? '&' : '?').http_build_query($query);

        return redirect()->away($base.$path.$qs);
    }
}
